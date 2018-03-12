<?php
/**
 * Platform_Shell\Profile
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell;

use Platform_Shell\Admin\Admin_Notices;
use Platform_Shell\Templates\Template_Helper;
use Platform_Shell\Settings\Plugin_Settings;

/**
 * Platform_Shell Profile
 *
 * @class    Profile
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Profile {

	/**
	 * Préfixe des champs du profil
	 *
	 * @var string
	 */
	private static $prefix = 'platform_shell_profile_';

	/**
	 * Nom de la route de base du profil
	 *
	 * @var string
	 */
	private static $profile_route_base_name = null;

	/**
	 * Notices à afficher à l'utilisateur
	 *
	 * @var Admin_Notices
	 */
	private $admin_notices = null;

	/**
	 * Classe helper pour afficher le contenu du profil
	 *
	 * @var Template_Helper
	 */
	private $template_helper;

	/**
	 * Plugin Settings
	 *
	 * @var Plugin_Settings
	 */
	private $plugin_settings;

	/**
	 * Constructeur.
	 *
	 * @param Template_Helper $template_helper    Instance de Template_Helper (DI).
	 * @param Plugin_Settings $plugin_settings    Instance de Plugin_Settings (DI).
	 */
	public function __construct( Template_Helper $template_helper, Plugin_Settings $plugin_settings ) {
		$this->template_helper = $template_helper;
		$this->plugin_settings = $plugin_settings;

		self::$profile_route_base_name = _x( 'profil', 'profile', 'platform-shell-plugin' );
	}

	/**
	 * Destructeur
	 */
	public function __destruct() {
		$this->admin_notices = null;
	}

	/**
	 * Méthode init_admin_notices
	 */
	private function init_admin_notices() {
		global $post;
		if ( ! isset( $this->admin_notices ) ) {
			// Admin.
			$this->admin_notices = new Admin_Notices( 'PROFILE', -1 );
		}
	}

	/**
	 * Méthode admin_notice
	 */
	public function admin_notice() {
		$screen = get_current_screen();
		$this->init_admin_notices();
		// Limiter le contexte d'affichage (à revalider contest vs edit-contest?).
		if ( is_admin() && ( 'profile' == $screen->id || 'user-edit' == $screen->id ) ) {

			$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : null;

			if ( ! is_null( $user_id ) && get_user_meta( $user_id, 'shibboleth_account' ) ) {
				$this->admin_notices->add_message(
					_x(
						'<stong>Attention : </stong> Ce compte a été créé avec l’extension’ Shibboleth.<br/>Certains champs sont synchronisés automatiquement lorsque l’utilisateur se connecte, d’autres ne sont pas utilisés dans le profil spécial de la plateforme.<br/>Le profil du tableau de bord de WordPress n’est jamais présenté aux utilisateurs.<br/>Le pseudonyme pourrait être modifié par un administrateur mais l’utilisateur sera toujours en mesure de le modifier par la suite.',
						'profile-admin-notice-warning',
						'platform-shell-plugin'
					),
					'warning',
					Admin_Notices::MESSAGE_LIFETIME_USE_ONCE
				);
			}
			$this->admin_notices->show_admin_notices();
		}
	}

	/**
	 * Méthode init
	 */
	public function init() {
		$this->init_actions();
		$this->init_filters();
	}

	/**
	 * Méthode init_actions
	 */
	private function init_actions() {
		add_action( 'wp_ajax_platform_shell_edit_profile_handler', array( &$this, 'platform_shell_edit_profile_handler' ) ); /* register ajax handler */
		add_action( 'wp_ajax_platform_shell_validate_nickname_handler', array( &$this, 'platform_shell_validate_nickname_handler' ) ); /* register ajax handler */
		add_action( 'init', array( &$this, 'wordpress_init' ) );
		add_action( 'template_redirect', array( &$this, 'profile_route_rewrite_catch' ) ); /*template_include  */ /* template_redirect */
		add_action( 'admin_notices', array( &$this, 'admin_notice' ) );

		/*
		 * Solution pour tags sur profil provenant de : (http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress)
		 * Voir plus bas pour la suite.
		 */
		add_action( 'admin_menu', array( &$this, 'add_profiles_tags_admin_page' ) );
		add_filter( 'manage_edit-profiles_tags_columns', array( &$this, 'manage_profiles_tags_user_column' ) );
		add_action( 'manage_profiles_tags_custom_column', array( &$this, 'manage_profiles_tags_column' ), 10, 3 );
	}

	/**
	 * Les termes /taxonomie sont utilisés avec les post en premier lieu.
	 * WordPress semble permettre l'utilisation de taxonomie sur profil mais il n'y a pas d'implémentation par défault.
	 * Cela implique aussi qu'il n'est pas possible de partager une banque de terme commune entre les projets et les profils.
	 * Je vais implémenter une première version selon cette approche :
	 * http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress
	 */

	/**
	 * L'utilisation d'un post (custom post type caché (?) dans la mesure du possible pourrait permettre de gérer une taxonomie
	 * commune + le post permettrais de faire une indirection pour gérer le conflit de clé id user et id post en utilisation post vide.
	 * Un meta dans le user profil permettrais de retrouver la clé du post id avec lequel aller chercher les termes.
	 * http://wordpress.stackexchange.com/questions/10566/is-it-possible-to-add-taxonomies-to-user-profiles
	 */

	/**
	 * Méthode register_profile_tags_taxonomy
	 */
	public function register_profile_tags_taxonomy() {
		register_taxonomy(
			'profiles_tags', 'user', array(
				'public'                => true,
				'labels'                => [
					'name'                       => _x( 'Mots-clés', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'singular_name'              => _x( 'Mot-clé', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'menu_name'                  => _x( 'Mots-clés', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'search_items'               => _x( 'Chercher parmi les mots-clés', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'popular_items'              => _x( 'Mots-clés populaires', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'all_items'                  => _x( 'Tous les mots-clés', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'edit_item'                  => _x( 'Modifier les mots-clés', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'update_item'                => _x( 'Mettre à jour les mots-clés', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'add_new_item'               => _x( 'Ajouter un mot-clé', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'new_item_name'              => _x( 'Nouveau nom de mot-clé', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'separate_items_with_commas' => _x( 'Séparer les mots-clés avec des virgules', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'add_or_remove_items'        => _x( 'Ajouter ou enlever des mots-clés', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
					'choose_from_most_used'      => _x( 'Choisir parmi les mots-clés populaires', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' ),
				],
				'rewrite'               => [
					'with_front' => true,
					'slug'       => 'author', // Conflit possible si utilise "author", voir article.
				],
				'capabilities'          => [
					'manage_terms' => 'edit_users', // Using 'edit_users' cap to keep this simple.
					'edit_terms'   => 'edit_users',
					'delete_terms' => 'edit_users',
					'assign_terms' => 'read',
				],
				'update_count_callback' => [
					&$this,
					'update_profiles_tags_count',
				], // Use a custom function to update the count.
			)
		);
	}

	/**
	 * Méthode add_profiles_tags_admin_page
	 *
	 * @see http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress
	 */
	public function add_profiles_tags_admin_page() {
		$tax = get_taxonomy( 'profiles_tags' );
		add_users_page(
			esc_attr( $tax->labels->menu_name ), esc_attr( $tax->labels->menu_name ), $tax->cap->manage_terms, 'edit-tags.php?taxonomy=' . $tax->name
		);
	}

	/**
	 * Méthode update_profiles_tags_count
	 *
	 * @param array        $terms       List of Term taxonomy IDs.
	 * @param \WP_Taxonomy $taxonomy    Current taxonomy object of terms.
	 * @see http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress
	 */
	public function update_profiles_tags_count( $terms, $taxonomy ) {
		global $wpdb;
		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );
			do_action( 'edit_term_taxonomy', $term, $taxonomy );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
			do_action( 'edited_term_taxonomy', $term, $taxonomy );
		}
	}

	/**
	 * Méthode manage_profiles_tags_user_column
	 *
	 * @param array $columns    Un tableau contenant les colonnes associées aux tags de l'utilisateur.
	 * @return array            Le tableau filtré contenant les colonnes associées aux tags de l'utilisateur.
	 * @see http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress
	 */
	public function manage_profiles_tags_user_column( $columns ) {
		unset( $columns['posts'] );
		$columns['users'] = _x( 'Utilisateurs', 'profile-tags-taxonomy-admin', 'platform-shell-plugin' );
		return $columns;
	}

	/**
	 * Méthode manage_profiles_tags_column
	 *
	 * @param string  $display    WP just passes an empty string here.
	 * @param string  $column     The name of the custom column.
	 * @param integer $term_id    The ID of the term being displayed in the table.
	 * @see http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress
	 */
	public function manage_profiles_tags_column( $display, $column, $term_id ) {
		if ( 'users' === $column ) {
			$term = get_term( $term_id, 'profiles_tags' );
			echo $term->count;
		}
	}

	/**
	 * Méthode save_profile_tags
	 *
	 * @param integer $user_id         L'identifiant de l'utilisateur.
	 * @param string  $profile_tags    Une liste des tags séparé par des virgules.
	 * @return boolean                 Retourne false si l'utilisateur ne peux sauvegarder les tags.
	 * @see http://justintadlock.com/archives/2011/10/20/custom-user-taxonomies-in-wordpress
	 */
	private function save_profile_tags( $user_id, $profile_tags /* string with comma. */ ) {
		$tax = get_taxonomy( 'profiles_tags' );
		/* Make sure the current user can edit the user and assign terms before proceeding. */
		if ( ! current_user_can( 'edit_user', $user_id ) && current_user_can( $tax->cap->assign_terms ) ) {
			return false;
		}

		$tags_terms = platform_shell_tags_to_terms( $profile_tags );

		/* Sets the terms (we're just using a single term) for the user. */
		wp_set_object_terms( $user_id, $tags_terms, 'profiles_tags', false );
		clean_object_term_cache( $user_id, 'profiles_tags' );
	}

	/**
	 * Méthode wordpress_init
	 */
	public function wordpress_init() {
		$this->init_profile_route();
		$this->register_profile_tags_taxonomy();
	}

	/**
	 * Méthode init_profile_route
	 *
	 * @see https://www.pmg.com/blog/a-mostly-complete-guide-to-the-wordpress-rewrite-api/
	 */
	public function init_profile_route() {

		add_rewrite_rule(
			'^' . self::$profile_route_base_name . '/?$', 'index.php?pagename=' . self::$profile_route_base_name . '&user_id=$matches[1]', 'top'
		);
	}

	/**
	 * Méthode init_filters
	 */
	private function init_filters() {
		add_filter( 'query_vars', array( &$this, 'profile_route_rewrite_add_var' ) );
		add_filter( 'get_avatar', array( &$this, 'get_medialab_avatar' ), 10, 5 );
	}


	/**
	 * Méthode pour modifier le comportement de get_avatar et pouvoir contrôler l'utilisation de gravatar.
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/get_avatar
	 * @param string $avatar         HTML rendu de l'avatar.
	 * @param string $id_or_email    Id ou email (Gravatar).
	 * @param string $size           Taille.
	 * @param string $default        Default.
	 * @param string $alt            Alt.
	 * @return string
	 */
	public function get_medialab_avatar( $avatar, $id_or_email, $size, $default, $alt ) {

		$use_gravatar_option = $this->plugin_settings->get_option(
			'platform_shell_option_profile_use_gravatar',
			'platform_shell_settings_main_accounts',
			'off'
		);

		if ( 'off' === $use_gravatar_option ) {
			// Afficher un icône par défaut.
			$avatar_url = get_avatar_url( $id_or_email, [ 'force_default' => true ] );
			// Reproduire effet arrondis. Réinjecter seulement si présent dans le rendu de $avatar.
			$extra_class = ( false !== strpos( $avatar, 'img-circle' ) ) ? 'img-circle' : '';
			$avatar      = "<img alt='{$alt}' src='{$avatar_url}' srcset='{$avatar_url} 2x' class='avatar avatar-{$size} photo {$extra_class}' height='{$size}' width='{$size}' />";
		}

		return $avatar;
	}

	/**
	 * Méthode profile_route_rewrite_add_var
	 *
	 * @param array $vars   Tableau contenant les variables de la route pour la redirection.
	 * @return array        Tableau contenant les variables de la route pour la redirection.
	 */
	public function profile_route_rewrite_add_var( $vars ) {
		$vars[] = 'user_id';
		$vars[] = 'edit';
		return $vars;
	}

	/**
	 * Méthode get_current_url
	 *
	 * @return string    L'URL courant.
	 * @see https://roots.io/routing-wp-requests/
	 */
	public function get_current_url() {

		/**
		 *  Retourne partie droite de l'url en tenant compte de la possible installation avec sous-dossiers ./
		 * "http://domain/profil/?id=123123&edit=1" va retourner "/profil/?id=123123&edit=1"
		 * En anglais "http://domain/profile/?id=123123&edit=1" va retourner "/profile/?id=123123&edit=1"
		 */

		// Get current URL path, stripping out slashes on boundaries.
		$current_url = trim( esc_url_raw( add_query_arg( [] ) ), '/' );

		// Get the path of the home URL, stripping out slashes on boundaries.
		$home_path = trim( parse_url( site_url(), PHP_URL_PATH ), '/' );

		// If a URL part exists, and the current URL part starts with it...
		if ( $home_path && strpos( $current_url, $home_path ) === 0 ) {

			// ... just remove the home URL path form the current URL path.
			$current_url = trim( substr( $current_url, strlen( $home_path ) ), '/' );
		}
		return $current_url;
	}

	/**
	 * Méthode profile_route_rewrite_catch
	 *
	 * Note : Solution adhoc qui pourrait ne pas bien traiter certains cas.
	 * Idéalement on utiliserait un routeur (pas offert par WordPress) commun en amont qui aurait été mis à l'épreuve.
	 * En gros:
	 * - On veut pouvoir traiter le cas d'installation dans sous dossier ex. http://domain/sousdossier/sousdossier/profil/
	 * - Le regex doit pouvoir gérer la présence ou non du dernier backslash.
	 * - Le regex ne devrait pas accepter un match sur "exempleprofile2" (ne pas faire un contains).
	 * - Tous les urls passent par ce traitement, il faudrait donc s'assurer que le traitement soit rapide.
	 *   et discrimine au plus tôt les cas à ignorer (ex. url à l'intérieur de wp-content).
	 *
	 * Exemples d'url acceptés:
	 * Params par défauts : edit = 0 (consultation) et user_id = utilisateur connecté courant (s'il existe).
	 * - /{self::$profile_route_base_name} (mode vue, va prendre utilisateur connecté)
	 * - /{self::$profile_route_base_name}/ (mode vue, va prendre utilisateur connecté)
	 * - /{self::$profile_route_base_name}?edit=1 (mode édition, va prendre utilisateur connecté)
	 * - /{self::$profile_route_base_name}?user_id=2 (mode vue, utilisateur spécifié)
	 * - /{self::$profile_route_base_name}?user_id=2&edit=1 (mode édition, utilisateur spécifié)
	 * - /{self::$profile_route_base_name}?edit=true (va être redirigé vers profil?edit=1)
	 * - /sousdossier/.../{self::$profile_route_base_name}
	 * - ... (les autres cas normaux de la partie droite avec params)
	 *
	 * @param string $template    Contenu du template.
	 */
	public function profile_route_rewrite_catch( $template ) {

		$current_url      = $this->get_current_url();
		$parsed           = parse_url( $current_url );
		$is_profile_route = 0;

		if ( isset( $parsed['path'] ) ) {
			$path  = $parsed['path'];
			$path .= ( substr( $path, -1 ) == '/' ? '' : '/' ); // pour garder un regex simple, uniformiser les slash (doit être présent).
			// Vérifier si on est vraiment sur la route de profil (à défaut d'avoir un routeur complet).
			$is_profile_route = preg_match( '/^' . self::$profile_route_base_name . '\//', $path ); // On veut surtour valider la partie gauche de la route.
		}

		if ( 0 == $is_profile_route ) {
			// Laisser WordPress faire son  traitement normal.
			return;
		} else {
			self::profile_route_handler(); // Prendre en charge le traitement et l'affichage.
		}
	}

	/**
	 * Méthode profile_route_handler
	 *
	 * Exemples d'accès:
	 * http://domain/{self::$profile_route_base_name}/
	 *  Afficher profil usager courant + requiert usager connecté pour déterminer id.
	 *  Si pas connecté, redirection au login affiche message invitant connexion.
	 * http://domain/{self::$profile_route_base_name}/?id=123123
	 * Afficher profil usager id=x
	 * http://domain/{self::$profile_route_base_name}/?id=123123&edit=1
	 * Editer profile usager id=x avec protection x doit être égal à id usager connecté + requiert usager connecté.
	 * Si usager différent, redirection au login.
	 * http://domain/{self::$profile_route_base_name}/?edit=1
	 * C'est un peu un sous-cas des autres cas existants.
	 * - Si id n'existe pas, requiert usager connecté pour déterminer id usager courant.
	 * Editer profile usager courant (avec protection x doit être égal à id usager connecté + requiert usager connecté).
	 * Note supplémentaire params inconnus:
	 * http://domain/{self::$profile_route_base_name}/?autreparaminconnu=34132
	 * Les params inconnus sont ignorés et les paramètres reconnus vont être traités selon règles indiquées plus haut.
	 * Tests :
	 * À partir des urls suivants comme exemple de base :
	 *
	 * A) http://domain/{self::$profile_route_base_name}/
	 *
	 * B) http://domain/{self::$profile_route_base_name}/?user_id=2
	 *
	 * C) http://domain/{self::$profile_route_base_name}/?user_id=2&edit=1
	 *
	 * Si l'usager n'est pas connecté:
	 *
	 * A = login + affiche profil utilisateur connecté (usager ou admin)
	 *
	 * B = affiche profil public sauf si admin ou id inconnu = retour accueil.
	 *
	 * C = login + affiche profil en mode edit si c'est le profil de l'usager connecté sinon retour accueil.
	 *
	 * Si l'usager est connecté :
	 *
	 * A = affiche profil utilisateur connecté (usager ou admin)
	 *
	 * B = affiche profil public sauf si admin ou id inconnu = retour accueil.
	 *
	 * C = affiche profil en mode edit si correspond à l'utilisateur connecté. Sinon, si id invalide retour à home, si id valide et usager courant est admin affiche le profil dans le tableau de bord.
	 */
	private function profile_route_handler() {
		$profile_user_id_param = get_query_var( 'user_id', null ); // Param WordPress = user_id=5.
		$profile_edit_param    = get_query_var( 'edit', null );
		$profile_user          = null;
		$profile_edit          = false || ( true == $profile_edit_param );
		$edit_user_by_id       = ( isset( $profile_user_id_param ) && true == $profile_edit_param );
		$profile_user_id       = 0;

		// La chaîne de traitement est conçue de manière à rejeter au plus tôt les requêtes dont le contexte (état login, params) est incorrect.
		// Lorsque les params manquent et peuvent être assignés, on fait une redirection pour rendre visible l'effet du traitement (rendre visible les params dans l'url).
		$unknown_profile_id = ( ( null === $profile_user_id_param ) || empty( $profile_user_id_param ) );
		$require_login      = ( ( true === $profile_edit ) || $unknown_profile_id );

		// 1) Validation login requis.
		if ( $require_login && ! is_user_logged_in() ) {
			$login_and_return_to_profile_url = wp_login_url( self::get_profile_url( $profile_user_id_param, $profile_edit, false ) );
			$this->access_restriction( $login_and_return_to_profile_url );
		}

		// 2) Traiter cas accès sans param ex. /profil/
		if ( $unknown_profile_id ) {
			// Renvoyer au profil de l'utilisateur.
			$current_user_id = get_current_user_id();
			$profile_url     = self::get_profile_url( $current_user_id, $profile_edit, false /* relative url */ );
			wp_redirect( $profile_url );
			exit();
		}

		// 3) Cas problématique utilisation de http_build_query et évaluation de $same_referer à la sauvegarde des changements.
		if ( 'true' == $profile_edit_param ) {
			// Les flag bool ne sont pas toujours encodé de la même manière (0 / false)  et cela rend les comparaison instables.
			// Exemple :
			// - http_build_query utilisé dans get_profile_url encode sous forme de 1.
			// - Elle semble transformée en bool (true) dans _wp_http_referer
			// Solution temporaire : Forcer utilisation du bon paramètre avec redirect.
			wp_redirect( self::get_profile_url( $profile_user_id_param, true /* edit */, false /* relative url */ ) );
			exit();
		}

		// Le données du compte auquel on essaie d'accéder (donc peut être différent du compte de l'utilisateur courant).
		$profile_user = get_userdata( $profile_user_id_param );

		// 4) Valider que le profil demandé existe vraiment.
		if ( false == $profile_user ) {
			$this->access_restriction(); // Rejeter, tentative de modification d'un profil inexistant.
			exit();
		} else {
			/**
			 * Traitement particulier, $profile_user->filter = 'edit'.
			 * La gestion de profile de WordPress utilise get_user_to_edit plutôt que get_userdata.
			 * La fonction get_user_to_edit n'est pas visible par le plugin sans inclure plus de scripts du niveau admin.
			 * Pour ne pas introduire trop d'inconnues, je vais seulelement ajouter le traitement manquant (ajouter le filtre).
			 * Ce filtre semble forcer un "sanitize" des données lors des get, un comportement qu'on voudrait conserver.
			 *
			 * @see https://developer.wordpress.org/reference/functions/get_user_to_edit/
			 */
			$profile_user->filter = 'edit';
		}

		// 5) RESTRICTIONS D'ACCÈS : Les fiches admin ne sont pas consultables ni modifiables.
		$current_user = wp_get_current_user();
		if ( in_array( 'administrator', (array) $profile_user->roles ) || in_array( 'administrator', (array) $current_user->roles ) ) {

			// Si l'utisateur courant est un admin, le rediriger sur le profil WordPress.
			if ( in_array( 'administrator', (array) $current_user->roles ) ) {

				// Un admin essaie d'accéder à son profil ou celui d'un autre usager.
				// Note : On aurait pu permettre au gestionnaire de modifier un profil avec le formulaire front-end.
				$admin_user_profile_url = get_edit_user_link( $profile_user_id_param );

				// Pourrait afficher un message "ONCE".
				$this->access_restriction( $admin_user_profile_url );
			} else {
				$this->access_restriction();
			}
		} else {
			// RESTRICTION D'ACCÈS : Un utilisateur ne peut pas modifier le profil d'un autre utilisateur.
			if ( $edit_user_by_id && ( intval( $profile_user_id_param ) != get_current_user_id() ) ) {
				// La modification de profil est normalement limité dans WordPress par rôle.
				// Mais la modification de profil front-end est limité plus strictement à l'usager courant.
				// 2 options possibles : redirect au profil de l'usager ou retour au home.
				// Je pense que le retour au home est préférable.
				$this->access_restriction(); // On pourrait renvoery à la modification de profil de l'usager?
			}
		}

		$callback = null;
		if ( false == $profile_edit ) {
			// Afficher la page de profil en mode lecture.
			$template_path = 'profile/view-profile.php'; /* DONT_LOCALISE_PROFILE */
			if ( intval( $profile_user_id_param ) != get_current_user_id() ) {
				$callback = function () use ( $profile_user ) {
					return sprintf(
						/* translators: %1$s: Pseudonyme de l'utilisateur */
						_x(
							'Profil de %1$s',
							'profile-title',
							'platform-shell-plugin'
						),
						$profile_user->display_name
					);
				};
			} else {
				$callback = function () {
					return _x(
						'Mon profil',
						'profile-title',
						'platform-shell-plugin'
					);
				};
			}
		} else {
			// Affiche la page de profil en mode édition.
			$template_path = 'profile/form-edit-profile.php'; /* DONT_LOCALISE_PROFILE */
			$callback      = function () {
				return _x(
					'Modification du profil',
					'profile-title',
					'platform-shell-plugin'
				);
			};
		}
		add_filter(
			'wp_title',
			$callback,
			10,
			2
		);
		$template = $this->template_helper->get_template( $template_path, array( 'profile_user' => $profile_user ) );
		echo $template;
		exit(); /* Empêcher le traitement de template par défaut. */
	}

	/**
	 * Méthode access_restriction
	 *
	 * @param string $redirect_target    Cible de la redirection.
	 */
	private function access_restriction( $redirect_target = null ) {
		if ( isset( $redirect_target ) ) {
			wp_redirect( $redirect_target );
		} else {
			wp_redirect( site_url() ); // Retour à l'accueil, comportement par défaut.
		}
		exit();
	}

	/**
	 * Méthode platform_shell_validate_nickname_handler
	 */
	public function platform_shell_validate_nickname_handler() {
		// Note : pourrait faire check supplémentaire sur current_user_id mais
		// le résultat de retour va être attrapé par jquery validate.
		// Si l'usager manipule l'id ce n'est pas très grave, on va le revalider à la sauvegarde.
		$new_nickname = sanitize_text_field( $_POST['platform_shell_profile_nickname'] );
		if ( empty( $new_nickname ) ) {
			platform_shell_display_json_response( _x( 'Il faut inscrire un pseudonyme.', 'profile-nickname-change-validation', 'platform-shell-plugin' ) );
		} else {
			$is_new_nickname_unique = Users::validate_nickname_unique( sanitize_text_field( get_current_user_id() ), $new_nickname );
			if ( ! $is_new_nickname_unique ) {
				/* translators: %1$s: Pseudonyme */
				platform_shell_display_json_response( sprintf( _x( 'Le pseudonyme %1$s est déjà utilisé. SVP choisis-en un autre.', 'profile-nickname-change-validation', 'platform-shell-plugin' ), esc_html( $new_nickname ) ) );
			} else {
				$is_valid_pseudo = Users::validate_nickname_accepted_characters( $new_nickname );
				if ( ! $is_valid_pseudo ) {
					platform_shell_display_json_response( _x( 'Le pseudonyme contient des caractères interdits.', 'profile-nickname-change-validation', 'platform-shell-plugin' ) );
				} else {
					platform_shell_display_json_response( true );
				}
			}
		}
	}

	/**
	 * Méthode platform_shell_edit_profile_handler
	 */
	public function platform_shell_edit_profile_handler() {

		$current_user_id = get_current_user_id();
		$profile_user_id = sanitize_text_field( $_POST['user_id'] );

		$nonce_key = 'save_profile_' . $profile_user_id;
		$nonce     = $_REQUEST['save_profile'];

		$valid_nonce  = ( false !== wp_verify_nonce( $nonce, $nonce_key ) );
		$same_user_id = ( $profile_user_id == $current_user_id );

		if ( ! $valid_nonce || ! $same_user_id ) {
			$error_message = _x( 'Paramètres invalides dans platform_shell_edit_profile_handler', 'profile-save-developper-error', 'platform-shell-plugin' );
			$response      = [
				'success'     => false,
				'errors'      => array( $error_message ),
				'redirect_to' => site_url(),
			];
			platform_shell_display_json_response( $response );
			exit();
		} else {
			$this->save_profile_changes_from_post( $profile_user_id );
		}
	}

	/**
	 * Méthode save_profile_changes_from_post
	 *
	 * @param integer $profile_user_id    Identifiant de l'utilisateur associé au profil.
	 */
	private function save_profile_changes_from_post( $profile_user_id ) {
		$errors            = [];
		$validation_errors = [];
		// Important:
		// La modification de profil de WordPress passe par edit_user( $user_id = 0 ) qui utilise $_POST.
		// Le code actuel du profil Platform_Shell pose plusieurs problème au niveau de l'intégration.
		// incluant le partage de fonctionnalités commune (validation du pseudo et synchro).
		// Pour simplifier, le traitement sera fait explicitement ici, le plus important étant la validation de doublon de pseudo.
		// Conséquence principale : les hooks WordPress ne s'appliquent pas.
		// Enregistrement des champs de l'objet user.
		//
		// Il y a déjà une validation front-end. On doit refaire les validations backend
		// On prends pour acquis que les données sont pré-validés, donc on annule le traitement s'il y a une erreur (soit manipulation, soit un bug).
		// // Nickname :
		// - Pas de doublon.
		// - Longueur limitée à 50 char.
		//
		$profile_fields                   = $this->get_edit_profile_fields( get_userdata( $profile_user_id ) );
		$general_backend_validation_error = false;

		// Traiter les erreurs en cascade (ne pas afficher toutes les erreurs en même temps).
		$new_nickname           = sanitize_text_field( $_POST['platform_shell_profile_nickname'] ); // Doit être fait en premier.
		$is_new_nickname_unique = Users::validate_nickname_unique( $profile_user_id, $new_nickname );

		if ( ! $is_new_nickname_unique ) {
			// Erreur doublon.
			$error_message = sprintf(
				/* translators: %1$s: pseudonyme */
				_x( 'Le pseudonyme %1$s est déjà utilisé. SVP choisis-en un autre.', 'profile-nickname-change-validation', 'platform-shell-plugin' ),
				$new_nickname
			);
			$validation_errors['platform_shell_profile_nickname'] = [ $error_message ];
			$general_backend_validation_error                     = true;
		} else {
			if ( isset( $profile_fields['pseudonym']['max_length'] ) ) {
				if ( strlen( $new_nickname ) > intval( $profile_fields['pseudonym']['max_length'] ) ) {
					// Erreur longueur permise.
					$error_message = sprintf(
						/* translators: %1$s: Nombre de caractères pour le pseudonyme. */
						_x( 'Le pseudonyme ne peut contenir plus de %1$s caractères.', 'profile-nickname-change-validation', 'platform-shell-plugin' ),
						$profile_fields['pseudonym']['max_length']
					);
					$validation_errors['platform_shell_profile_nickname'] = [ $error_message ];
					$general_backend_validation_error                     = true;
				} else {
					if ( strlen( $new_nickname ) == 0 ) {
						$error_message                                        = _x(
							'Le champs Pseudonyme ne peut pas être vide.',
							'profile-nickname-change-validation',
							'platform-shell-plugin'
						);
						$validation_errors['platform_shell_profile_nickname'] = [ $error_message ];
						$general_backend_validation_error                     = true;
					} else {
						if ( isset( $profile_fields['pseudonym']['min_length'] ) ) {
							if ( strlen( $new_nickname ) < intval( $profile_fields['pseudonym']['min_length'] ) ) {
								$error_message = sprintf(
									/* translators: %1$s: Nombre de caractères pour le pseudonyme. */
									_x(
										'Le champs Pseudonyme doit contenir au moins %1$s caractères.',
										'profile-nickname-change-validation',
										'platform-shell-plugin'
									), $profile_fields['pseudonym']['min_length']
								);
								$validation_errors['platform_shell_profile_nickname'] = [ $error_message ];
								$general_backend_validation_error                     = true;
							}
						}
					}
				}
			}
			// Erreur / Avertissement. Sanitize.
		}
		if ( empty( $validation_errors ) && empty( $errors ) && ! $general_backend_validation_error ) {
			$user_data = wp_update_user(
				array(
					'ID'       => $profile_user_id,
					'nickname' => sanitize_text_field( $_POST['platform_shell_profile_nickname'] ), /* Note : un autre mécanisme va s'occuper de synchroniser le display name. */
				)
			);
			// Mots-clés.
			$this->save_profile_tags( $profile_user_id, sanitize_text_field( $_POST['platform_shell_profile_tags'] ) /* string with comma. */ );
		}
		if ( isset( $user_data ) && is_wp_error( $user_data ) ) {
			$error_message = _x( 'Erreur indéterminée dans wp_update_user.', 'profile-validate-developper-error', 'platform-shell-plugin' );
			array_push( $errors, $error_message );
		}
		$admin_notices = new Admin_Notices( 'PROFILE', $profile_user_id );

		if ( empty( $validation_errors ) && empty( $errors ) && ! $general_backend_validation_error ) {

			$message = _x( '<strong>Modification du profil : </strong> Ton profil a été modifié.', 'profile-save', 'platform-shell-plugin' );
			$admin_notices->add_message( $message, 'success', Admin_Notices::MESSAGE_LIFETIME_USE_ONCE );

			if ( strlen( $new_nickname ) != strlen( $_POST['platform_shell_profile_nickname'] ) /* Vérification sur longueur unsanitized. Ok. */ ) {

				$message = _x( '<strong> - </strong> Certains caractères ont été enlevés de ton pseudonyme.', 'profile-save', 'platform-shell-plugin' );
				$admin_notices->add_message( $message, 'warning', Admin_Notices::MESSAGE_LIFETIME_USE_ONCE );
			}

			$response = [
				'success'     => true,
				'redirect_to' => $this->get_profile_url( $profile_user_id, false, false ),
			];

		} else {

			$message = _x( '<strong>Modification du profil : </strong> Les changements n’ont pas été sauvegardés. Un problème technique est survenu.', 'profile-save', 'platform-shell-plugin' );
			$admin_notices->add_message( $message, 'error', Admin_Notices::MESSAGE_LIFETIME_USE_ONCE );
			$response = [
				'success'     => false,
				'redirect_to' => $this->get_profile_url( $profile_user_id, false, false ),
			];
		}
		$admin_notices = null;
		platform_shell_display_json_response( $response );

		die();
	}

	/**
	 * Méthode get_edit_profile_fields
	 *
	 * @param \WP_User $profile_user    Utilisateur associé au profil.
	 * @return array                   Les champs du formulaire d'édition du profil.
	 */
	public static function get_edit_profile_fields( $profile_user ) {

		$fields_prefix = self::$prefix;
		$tags          = '';

		if ( ! empty( $tags ) && is_array( $tags ) ) {
			$tags = join( ',', $tags ); // Convertir en liste texte.
		}

		$edit_profile_fields = [
			'pseudonym'  => [
				'label'      => _x( 'Pseudonyme', 'profile-edit-form-fields', 'platform-shell-plugin' ),
				'id'         => $fields_prefix . 'nickname',
				'desc'       => '',
				'type'       => 'text',
				'require'    => 'true',
				'value'      => $profile_user->nickname,
				'max_length' => '50',
				'min_length' => '3',
			],
			'name'       => [
				'label'    => _x( 'Prénom', 'profile-edit-form-fields', 'platform-shell-plugin' ),
				'id'       => $fields_prefix . 'first_name',
				'desc'     => '',
				'type'     => 'text',
				'disabled' => 'true',
				'value'    => $profile_user->first_name,
			],
			'surname'    => [
				'label'    => _x( 'Nom', 'profile-edit-form-fields', 'platform-shell-plugin' ),
				'id'       => $fields_prefix . 'last_name',
				'desc'     => '',
				'type'     => 'text',
				'disabled' => 'true',
				'value'    => $profile_user->last_name,
			],
			'email'      => [
				'label'    => _x( 'Courriel', 'profile-edit-form-fields', 'platform-shell-plugin' ),
				'id'       => $fields_prefix . 'user_email',
				'desc'     => '',
				'type'     => 'text',
				'disabled' => 'true',
				'value'    => platform_shell_get_profile_email_text( $profile_user->ID ),
			],
			'tags_input' => [
				'label' => _x( 'Mots-clés', 'profile-edit-form-fields', 'platform-shell-plugin' ),
				'id'    => $fields_prefix . 'tags',
				'type'  => 'text',
				'value' => $tags,
				'desc'  => _x( 'Entre plusieurs mots-clés séparés par des virgules.', 'profile-edit-form-fields', 'platform-shell-plugin' ),
				'value' => platform_shell_get_tags_list( $profile_user->ID, 'profiles_tags' ),
			],
		];

		return $edit_profile_fields;
	}

	/**
	 * Méthode get_profile_url
	 *
	 * @param integer $user_id     Identifiant de l'utilisateur.
	 * @param boolean $edit        Si nous sommes en train d'éditer le profil.
	 * @param boolean $relative    Si nous devons retourner une URL relative.
	 * @return string
	 */
	public static function get_profile_url( $user_id = null, $edit = false, $relative = false ) {

		$url_params = [];

		if ( null != $user_id ) {
			$url_params['user_id'] = $user_id;
		}

		if ( false != $edit ) {
			$url_params['edit'] = 1; /* attention. http_build_query encode en numérique. */
		}

		// Racine optionnelle.
		if ( $relative ) {
			$url = '';
		} else {
			$url = get_option( 'siteurl' ); /* racine du site. */
		}

		// Route commune.
		$url = $url . '/' . self::$profile_route_base_name . '/';

		// Params optionnels.
		if ( ! empty( $url_params ) ) {
			$url = $url . '?' . http_build_query( $url_params );
		}

		return $url;
	}
}
