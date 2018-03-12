<?php
/**
 * Platform_Shell\Users
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell;

use Platform_Shell\Admin\Admin_Notices;
use Platform_Shell\Settings\Plugin_Settings;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Platform_Shell Users
 *
 * @class    Users
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Users {

	/**
	 * Plugin Settings
	 *
	 * @var Plugin_Settings
	 */
	private $plugin_settings;

	/**
	 * Roles Settings
	 *
	 * @var Roles_Configs
	 */
	private $roles_configs;

	/**
	 * Constructeur
	 *
	 * @param Plugin_Settings $plugin_settings    Plugin Settings.
	 * @param Roles_Configs   $roles_configs      Roles_Configs.
	 */
	public function __construct( Plugin_Settings $plugin_settings, Roles_Configs $roles_configs ) {
		$this->plugin_settings = $plugin_settings;
		$this->roles_configs   = $roles_configs;
	}

	/**
	 * Méthode init
	 */
	public function init() {
		// Sur création d'un compte.
		add_action( 'user_register', array( $this, 'on_action_user_register' ) );
		$this->activate_filter_profile_update();

		// Synchronisation du nickname avec Shibboleth.
		add_filter( 'shibboleth_user_nickname', array( $this, 'on_filter_shibboleth_user_nickname' ) );

		add_filter( 'shibboleth_override_email', array( $this, 'on_filter_shibboleth_override_email' ) );

		// Changement du profil.
		add_action( 'user_profile_update_errors', array( $this, 'validate_nickname_field' ) );

		add_action( 'wp_ajax_platform_shell_action_search_users', [ &$this, 'search_users' ] );
		add_action( 'wp_ajax_nopriv_platform_shell_action_search_users', [ &$this, 'search_users' ] );
	}

	/**
	 * Méthode search_users
	 *
	 * Cette méthode est utilisé lors de la recherche de co-créateurs.
	 */
	public function search_users() {

		$current_user = wp_get_current_user();

		$nonce     = $_REQUEST['nonce'];
		$nonce_key = 'platform_shell_action_search_users';

		// Par défaut, rechercher uniquement les usagers réguliers.
		$roles = [
			$this->roles_configs->user_role,
		];

		// Si l'utilisateur est un administrateur ou un gestionnaire.
		if (
			in_array( $this->roles_configs->admin_role, $current_user->roles ) ||
			in_array( $this->roles_configs->manager_role, $current_user->roles )
		) {
			// Autoriser l'utilisateur à ajouter des gestionnaires.
			$roles[] = $this->roles_configs->manager_role;
		}

		if ( false !== wp_verify_nonce( $nonce, $nonce_key ) ) {

			$query      = $_REQUEST['query'];
			$author     = $_REQUEST['author'];
			$excluded   = ( isset( $_REQUEST['selected'] ) ) ? $_REQUEST['selected'] : [];
			$excluded[] = $author;

			array_walk(
				$excluded, function ( &$item ) {
					$item = intval( $item );
				}
			);

			sort( $excluded );

			$user_query = new WP_User_Query(
				[
					'role__in'       => $roles,
					'exclude'        => $excluded,
					'search'         => $query . '*',
					'search_columns' => [
						'display_name',
					],
					'orderby'        => 'display_name',
					'fields'         => [
						'ID',
						'display_name',
					],
				]
			);

			$users = $user_query->get_results();

			array_walk(
				$users, function ( &$user ) {
					$user = [
						'id'   => $user->ID,
						'text' => $user->display_name,
					];
				}
			);

			$response = [
				'results' => $users,
			];
		} else {
			$this->errors['unexpected_errors'] = [
				_x( 'Vous n’êtes pas autorisé à rechercher les utilisateurs.', 'unauthorized_user_search', 'platform-shell-plugin' ),
			];
		}

		if ( ! empty( $this->errors ) ) {
			$response = [ 'errors' => $this->errors ];
		}

		platform_shell_display_json_response( $response );
	}

	/**
	 * Méthode activate_filter_profile_update
	 */
	public function activate_filter_profile_update() {
		add_action( 'profile_update', array( $this, 'on_action_profile_update' ) );
	}

	/**
	 * Méthoe deactivate_filter_profile_update
	 */
	public function deactivate_filter_profile_update() {
		remove_action( 'profile_update', array( $this, 'on_action_profile_update' ) );
	}

	/**
	 * Méthode validate_nickname_field
	 *
	 * Valider que le nickname est unique sur la mise à jour du profil.
	 * Inspiré de https://wordpress.org/support/topic/how-to-make-displayname-nicknames-unique/.
	 *
	 * $update et $user sont des champs obligatoires pour l'action, donc est requis dans la signature de la méthode,
	 * même si ces paramètres ne sont pas utilisés.
	 *
	 * @param array    $errors    Errors object to add any custom errors to.
	 * @param boolean  $update    true if updating an existing user, false if saving a new user.
	 * @param \WP_User $user      User object for user being edited.
	 * @see   https://codex.wordpress.org/Plugin_API/Action_Reference/user_profile_update_errors
	 */
	public function validate_nickname_field( &$errors, $update = null, &$user = null ) {

		$user_id  = isset( $_POST['user_id'] ) ? sanitize_text_field( $_POST['user_id'] ) : null;
		$nickname = isset( $_POST['nickname'] ) ? sanitize_text_field( $_POST['nickname'] ) : null;

		if ( isset( $user_id ) && isset( $nickname ) ) { // Conflit plugin creation utilisation Shibboleth (pas de post). A corriger.
			// Note: L'admin a aussi cette containte (à revalider).
			// ATTENTION. NE PAS UTILISER L'OBJET USER (Le id n'est pas bon et la vérification la requête de validation ne filtre pas le user courant et identifie un faux doublon (le user lui-même).
			if ( ! self::validate_nickname_unique( $user_id, $nickname ) ) {
				/* translators: %1$s: pseudonyme */
				$error_message = sprintf( esc_html_x( 'Le pseudonyme %1$s est déjà utilisé. SVP choisis-en un autre.', 'users', 'platform-shell-plugin' ), $nickname );
				/* translators: %1$s: message d'erreur */
				$errors->add( 'nickname_already_exist', '<strong>' . sprintf( _x( 'Erreur : %1$s', 'users', 'platform-shell-plugin' ), $error_message ) . '</strong>' );
			}
		}
	}

	/**
	 * Méthode validate_nickname_accepted_characters
	 *
	 * @param string $nickname    Chaîne de charactère à vérifier.
	 * @return boolean            true si la chaîne est valide, false si elle est invalide.
	 */
	public static function validate_nickname_accepted_characters( $nickname ) {
		$sanitized_nickname = sanitize_text_field( $nickname );
		if ( $nickname != $sanitized_nickname ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Méthode validate_nickname_unique
	 *
	 * Check in database using current post nickname if the nickname is unique.
	 * Vérifier dans la base de donnée si le nickname est unique (qu'il soit utilisé ou non par utilisateur $user_id).
	 * Autrement dit, si l'utilisateur a déjà le nickname donné en paramètre le nickname est considéré unique.
	 * Inspiré de : https://wordpress.org/support/topic/how-to-make-displayname-nicknames-unique/
	 *
	 * @param integer $user_id     ID de l'utilisateur.
	 * @param string  $nickname    Nom d'affichage de l'utilisateur.
	 * @see https://wordpress.org/support/topic/how-to-make-displayname-nicknames-unique/
	 */
	public static function validate_nickname_unique( $user_id, $nickname ) {
		global $wpdb;
		// Getting user data and user meta data.
		$err['nickname'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users as users, $wpdb->usermeta as meta WHERE users.ID = meta.user_id AND meta.meta_key = 'nickname' AND meta.meta_value = %s AND users.ID <> %d", $nickname, $user_id ) );
		foreach ( $err as $key => $e ) {
			// If nickname already exists.
			if ( $e >= 1 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Méthode on_action_user_register
	 *
	 * Action sur création d'un nouvel utilisateur.
	 *
	 * @param integer $user_id     ID de l'utilisateur.
	 */
	public function on_action_user_register( $user_id ) {
		$user = get_userdata( $user_id );

		// Création manuelle d'un compte. Ne pas considérer admin.
		if ( ! in_array( 'administrator', (array) $user->roles ) ) {
			$nickname = $this->get_unique_nickname();
			update_user_meta( $user_id, 'nickname', $nickname );

			// TRÈS IMPORTANT : Patch pour contourner bug appel récursif.
			$this->deactivate_filter_profile_update();
			wp_update_user(
				[
					'ID'           => $user->ID,
					'display_name' => $nickname,
				]
			);
			$this->activate_filter_profile_update();
		}
	}

	/**
	 * Méthode on_action_profile_update
	 *
	 * @param integer $user_id     ID de l'utilisateur.
	 */
	public function on_action_profile_update( $user_id ) {
		$user = get_userdata( $user_id );

		if ( ! in_array( 'administrator', (array) $user->roles ) ) {
			$nickname = get_user_meta( $user_id, 'nickname', true );

			if ( $user->display_name != $nickname ) {
				// TRÈS IMPORTANT : Patch pour contourner bug appel récursif.
				$this->deactivate_filter_profile_update();

				$this->admin_notices = new Admin_Notices( 'PROFILE', -1 );
				$this->admin_notices->add_message( _x( '<stong>Attention : </stong> Le nom à afficher publiquement ne peut pas être modifié. Veuillez plutôt modifier le pseudonyme.', 'users', 'platform-shell-plugin' ), 'error', Admin_Notices::MESSAGE_LIFETIME_USE_ONCE );
				$this->admin_notices = null;

				wp_update_user(
					[
						'ID'           => $user->ID,
						'display_name' => $nickname,
					]
				);
				$this->activate_filter_profile_update();
			}
		}
	}

	/**
	 * Méthode on_filter_shibboleth_override_email
	 *
	 * @param array $user_data    Données sur l'utilisateur provenant de Shibboleth.
	 * @return string             Addresse email à afficher.
	 */
	public function on_filter_shibboleth_override_email( array $user_data ) {

		$missing_email_behavior = $this->plugin_settings->get_option(
			'platform_shell_option_shibboleth_missing_email_behavior',
			'platform_shell_settings_main_accounts',
			'ASSIGN_NEVER'
		);

		$email = isset( $user_data['user_email'] ) ? $user_data['user_email'] : '';

		if ( ( 'ASSIGN_ALL' == $missing_email_behavior ) || ( 'ASSIGN_MISSING' == $missing_email_behavior && empty( $email ) ) ) {
			$urlparts = parse_url( site_url() );
			$domain   = $urlparts['host'];
			// Définir une adresse fictive selon le domaine courant et user_login (numérique).
			$email = $user_data['user_login'] . '@' . $domain;
		}
		return $email;
	}

	/**
	 * Méthode on_filter_shibboleth_user_nickname.
	 *
	 * @param string $nickname    Surnom de l'utilisateur.
	 * @return string
	 */
	public function on_filter_shibboleth_user_nickname( $nickname ) {
		// Assigner un nickname temporaire unique.
		return $this->get_unique_nickname();
	}

	/**
	 * Méthode get_unique_nickname
	 *
	 * @return string    Identifiant unique.
	 */
	public function get_unique_nickname() {
		return uniqid(); /* Génère un identifiant unique. Voir doc php. */
	}
}
