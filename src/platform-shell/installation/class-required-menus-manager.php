<?php
/**
 * Platform_Shell\installation\Required_Menus_Manager
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\installation;

use Platform_Shell\CPT\CPT_Configs;
use Platform_Shell\CPT\Activity\Activity_Configs;
use Platform_Shell\CPT\Contest\Contest_Configs;
use Platform_Shell\CPT\Equipment\Equipment_Configs;
use Platform_Shell\CPT\Project\Project_Configs;
use Platform_Shell\CPT\Tool\Tool_Configs;

/**
 * Gestionnaire des menus requis par la plateforme.
 *
 * @class    Required_Menus_Manager
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Required_Menus_Manager {

	/**
	 * Indentifiant de callback.
	 *
	 * @var type string
	 */
	private $callback_function_suffix = '_add_content_callback';

	/**
	 * Options de menu installés.
	 *
	 * @var type array
	 */
	private $installed_menus_option = null;

	/**
	 * Identifiant d'option de menu installés.
	 *
	 * @var type string
	 */
	private $installed_menus_option_name = 'platform_shell_installed_menus';

	/**
	 * Instance des configurations de pages requises.
	 *
	 * @var type Required_Pages_Configs
	 */
	private $required_pages_configs;

	/**
	 * Instance du gestionnaire de pages requises
	 *
	 * @var type Required_Pages_Manager
	 */
	private $required_page_manager;

	/**
	 * Instance des configurations de cpt 'contest'.
	 *
	 * @var type Contest_Configs
	 */
	private $contest_configs;

	/**
	 * Instance des configurations de cpt 'project'.
	 *
	 * @var type Project_Configs
	 */
	private $project_configs;

	/**
	 * Instance des configurations de cpt 'tool'.
	 *
	 * @var type Tool_Configs
	 */
	private $tool_configs;

	/**
	 * Instance des configurations de cpt 'equipment'.
	 *
	 * @var type Equipment_Configs
	 */
	private $equipment_configs;

	/**
	 * Instance des configurations de cpt 'activity'.
	 *
	 * @var type Activity_Configs
	 */
	private $activity_configs;

	/**
	 * Données des menus requis pour l'installation.
	 *
	 * @var type array
	 */
	private $required_menus_data = array();

	/**
	 * Constructeur
	 *
	 * @param \Platform_Shell\installation\Required_Pages_Configs $required_pages_configs    Instance des configurations de pages requises.
	 * @param \Platform_Shell\installation\Required_Pages_Manager $required_page_manager     Instance du gestionnaire de pages requises.
	 * @param Contest_Configs                                     $contest_configs           Instance des configurations de cpt 'contest'.
	 * @param Project_Configs                                     $project_configs           Instance des configurations de cpt 'project'.
	 * @param Tool_Configs                                        $tool_configs              Instance des configurations de cpt 'tool'.
	 * @param Equipment_Configs                                   $equipment_configs         Instance des configurations de cpt 'equipment'.
	 * @param Activity_Configs                                    $activity_configs          Instance des configurations de cpt 'activity'.
	 */
	public function __construct( Required_Pages_Configs $required_pages_configs,
							Required_Pages_Manager $required_page_manager, Contest_Configs $contest_configs,
							Project_Configs $project_configs, Tool_Configs $tool_configs, Equipment_Configs $equipment_configs,
							Activity_Configs $activity_configs
	) {
		$this->required_pages_configs = $required_pages_configs;
		$this->required_page_manager  = $required_page_manager;
		$this->contest_configs        = $contest_configs;
		$this->project_configs        = $project_configs;
		$this->tool_configs           = $tool_configs;
		$this->equipment_configs      = $equipment_configs;
		$this->activity_configs       = $activity_configs;

		add_filter( 'hidden_columns', array( $this, 'filter_hidden_columns' ), 10, 3 );
	}

	/**
	 * Méthode permettant de modifier de WordPress afin d'afficher en tout temps toutes les options des menus.
	 *
	 * @param array   $hidden          Voir documentation WordPress https://developer.wordpress.org/reference/hooks/hidden_columns/.
	 * @param screen  $screen          Voir documentation WordPress https://developer.wordpress.org/reference/hooks/hidden_columns/.
	 * @param boolean $use_defaults    Voir documentation WordPress https://developer.wordpress.org/reference/hooks/hidden_columns/.
	 * @return array
	 */
	public function filter_hidden_columns( $hidden, $screen, $use_defaults ) {

		// Cette fonctionnalité n'est pas directement liée au processus d'installation.
		// mais touche la fonctionnalité. des menus sur mesures (required menus).
		// Rendre visible par défaut les options des menus.
		// La configuration de l'affichage des options est enregistrée par utilisateur.
		// On veut plutôt que ça soit actif en tout temps pour tous les admins et gestionnaires.
		if ( 'nav-menus' == $screen->id ) {
			// Afficher toutes les options en tout temps.
			return [];
		} else {
			// No changes.
			return $hidden;
		}
	}

	/**
	 * Méthode définissant les menus requis.
	 */
	private function set_required_menus() {
		$this->add_required_menu( 'platform_shell_menu_main', _x( 'Principal', 'required-menus-name', 'platform-shell-plugin' ) );
		$this->add_required_menu( 'platform_shell_menu_primary_footer', _x( 'Pied de page primaire', 'required-menus-name', 'platform-shell-plugin' ) );
		$this->add_required_menu( 'platform_shell_menu_secondary_footer', _x( 'Pied de page secondaire', 'required-menus-name', 'platform-shell-plugin' ) );
		$this->add_required_menu( 'platform_shell_menu_social_links', _x( 'Liens des médias sociaux', 'required-menus-name', 'platform-shell-plugin' ) );
		$this->add_required_menu( 'platform_shell_menu_user_links', _x( 'Liens de l’utilisateur', 'required-menus-name', 'platform-shell-plugin' ) );
		$this->add_required_menu( 'platform_shell_menu_partners_links', _x( 'Liens des partenaires', 'required-menus-name', 'platform-shell-plugin' ) );
	}

	/**
	 * Méthode pour ajouter une définition de menu requis.
	 *
	 * @param string $id      Id du menu.
	 * @param string $name    Nom du menu.
	 * @throws \Exception     Exception si doublon.
	 */
	private function add_required_menu( $id, $name ) {
		if ( ! isset( $this->required_menus_data[ $id ] ) ) {
			$this->required_menus_data[ $id ] = [
				'id'   => $id,
				'name' => $name,
			];
		} else {
			throw new \Exception( 'Doublon dans add_required_menu_data.' );
		}
	}

	/**
	 * Méthode pour compléter l'installation des menus (appelé par le processus d'installation du plugin).
	 */
	public function install() {
		// todo : Récupérer data existant pour avoir un hint de rename de menu?
		$this->reset_installed_menus_option();
		$this->set_required_menus();

		foreach ( $this->required_menus_data as $key => $menu_data ) {
			$this->create_menu_if_not_exist( $menu_data['id'], $menu_data['name'] );
		}

		$this->set_nav_menu_locations();
		$this->save_installed_menu_options();
	}

	/**
	 * Méthode pour récupérer l'id WordPress du menu selon son id de définition.
	 *
	 * @param type $menu_option_id    Id menu de la plateforme.
	 * @return string
	 */
	public function get_wordpress_menu_id_by_platform_shell_option_menu_id( $menu_option_id ) {
		// Lazy.
		if ( ! isset( $this->installed_menus_option ) ) {
			$this->get_installed_menus_option();
		}
		if ( isset( $this->installed_menus_option[ $menu_option_id ] ) ) {
			return $this->installed_menus_option[ $menu_option_id ];
		} else {
			return null;
		}
	}

	/**
	 * Méthode pour définir les sections de menus connus au niveau du thème.
	 */
	private function set_nav_menu_locations() {
		/*
		 *  Association permettant l'affichage des menus via thème. Voir http://wp-snippets.com/pre-fill-a-custom-menu/
		 */

		$nav_menu_location = array();

		// Attention : identifiants provenant du thème.
		$nav_menu_location['main_nav']     = $this->installed_menus_option['platform_shell_menu_main'];
		$nav_menu_location['footer_links'] = $this->installed_menus_option['platform_shell_menu_primary_footer'];

		set_theme_mod( 'nav_menu_locations', $nav_menu_location );

	}

	/**
	 * Méthode pour remettre à zéro les options d'installation des menus.
	 */
	private function reset_installed_menus_option() {
		$this->installed_menus_option = array();
		$this->save_installed_menu_options();
	}

	/**
	 * Méthode pour récupérer les options de menus installés.
	 */
	private function get_installed_menus_option() {
		$installed_menus              = get_option( $this->installed_menus_option_name, null );
		$this->installed_menus_option = isset( $installed_menus ) ? $installed_menus : array();
	}

	/**
	 * Méthode pour enregistrer les options de menus installés.
	 */
	private function save_installed_menu_options() {
		update_option( $this->installed_menus_option_name, $this->installed_menus_option, false );
	}

	/**
	 * Méthode pour créer un menu seulement s'il n'existe pas déjà.
	 *
	 * @param string $menu_option_id     Id de l'option à utiliser pour le suivi d'installation du menu.
	 * @param string $menu_name          Nom du menu.
	 */
	private function create_menu_if_not_exist( $menu_option_id, $menu_name ) {
		$menu = wp_get_nav_menu_object( $menu_name );

		if ( ! $menu ) {
			$menu_id = wp_create_nav_menu( $menu_name );

			// Voir si un callback est défini.
			$add_content_callback_function_name = $menu_option_id . $this->callback_function_suffix; /* ex. platform_shell_menu_main_add_content_callback */
			if ( method_exists( $this, $add_content_callback_function_name ) ) {
				call_user_func_array( array( $this, $add_content_callback_function_name ), array( $menu_id ) );
			}
		} else {
			$menu_id = $menu->term_id;
		}
		if ( isset( $menu_id ) && isset( $nav_menu_location ) && isset( $location ) ) {
			$nav_menu_location[ $location ] = $menu_id;
		}
		$this->installed_menus_option[ $menu_option_id ] = $menu_id;
	}

	/**
	 * Méthode pour définir le contenu du menu principal (via callback).
	 *
	 * @param string $menu_id   Id WordPress du menu.
	 */
	private function platform_shell_menu_main_add_content_callback( $menu_id ) {
		$this->add_all_post_types_links( $menu_id );
		$this->add_required_page_link( 'platform-shell-page-whats-new', $menu_id );
	}

	/**
	 * Méthode pour définir le contenu du menu de pied page primaire (via callback).
	 *
	 * @param string $menu_id    Id WordPress du menu.
	 */
	private function platform_shell_menu_primary_footer_add_content_callback( $menu_id ) {
		wp_update_nav_menu_item(
			$menu_id, 0, array(
				'menu-item-title'   => _x( 'Accueil', 'required-menus-home-title', 'platform-shell-plugin' ),
				'menu-item-classes' => 'home',
				'menu-item-url'     => home_url( '/' ),
				'menu-item-status'  => 'publish',
			)
		);
		$this->add_all_post_types_links( $menu_id );
	}

	/**
	 * Méthode pour définir le contenu du menu de pied de page secondaire (via callback).
	 *
	 * @param type $menu_id    Id WordPress du menu.
	 */
	private function platform_shell_menu_secondary_footer_add_content_callback( $menu_id ) {
		$this->add_required_page_link( 'platform-shell-page-about', $menu_id );
		$this->add_required_page_link( 'platform-shell-page-whats-new', $menu_id );
		$this->add_required_page_link( 'platform-shell-page-site-plan', $menu_id );
		$this->add_required_page_link( 'platform-shell-page-general-rules', $menu_id );
		$this->add_required_page_link( 'platform-shell-page-code-of-conduct', $menu_id );
		$this->add_required_page_link( 'platform-shell-page-accessibility', $menu_id );
		$this->add_required_page_link( 'platform-shell-page-contact', $menu_id );
	}

	/**
	 * Méthode pour définir le contenu du menu de partenaires (via callback).
	 *
	 * @param type $menu_id    Id WordPress du menu.
	 */
	private function platform_shell_menu_partners_links_add_content_callback( $menu_id ) {
		// Ajouter logo partenaires du projet comme démo de la section en remerciement..
		// Les utilisateurs de la plateforme pourront modifier pour leur propres partenaires / logos.
		$this->add_partner_demo_logo( $menu_id, 'logo_qc.png', 'https://www.gouv.qc.ca/' );
		$this->add_partner_demo_logo( $menu_id, 'logo_banc.png', 'https://www.bnc.ca/' );
		$this->add_partner_demo_logo( $menu_id, 'logo_fondation_banq.png', 'https://fondation.banq.qc.ca/' );
	}

	/**
	 * Méthode pour ajouter la définition d'un lien logo de partenaire.
	 *
	 * @param string $menu_id     Id WordPress du menu.
	 * @param string $filename    Nom du fichier.
	 * @param string $url         Url destination.
	 */
	private function add_partner_demo_logo( $menu_id, $filename, $url ) {
		$demo_content_folder = get_bloginfo( 'stylesheet_directory' ) . '/images/interface/samples/';

		// Lors de l'installation du plugin on ne sait pas si le site va rouler en  http ou https.
		// Si l'installation se fait en http et le site roule en https, cela génère des erreurs de "mixed contennt".
		// L'utilisation de cette méthode n'est pas idéale mais acceptable dans le contexte (voir https://jeremywagner.me/blog/stop-using-the-protocol-relative-url/).
		// Puisque la valeur n'est pas calculée dynamiquement.
		$demo_content_folder = str_replace( 'http://', '//', $demo_content_folder );

		wp_update_nav_menu_item(
			$menu_id, 0, array(
				'menu-item-title'       => _x( 'Exemple de logo de partenaire', 'demo-menus-social-link-title', 'platform-shell-plugin' ),
				'menu-item-classes'     => '',
				'menu-item-url'         => $url,
				'menu-item-description' => $demo_content_folder . $filename, /* Utilise description pour enregistrer / récupérer url de logo. */
				'menu-item-target'      => '_blank',
				'menu-item-status'      => 'publish',
			)
		);
	}

	/**
	 * Méthode pour définir le contenu du menu de liens sociaux (via callback).
	 *
	 * @param string $menu_id    Id WordPress du menu.
	 */
	private function platform_shell_menu_social_links_add_content_callback( $menu_id ) {
		// Ajouter contenu démo. Les utilisateurs de la coquille pourront préciser le lien ou les enlever.
		wp_update_nav_menu_item(
			$menu_id, 0, array(
				'menu-item-title'   => _x( 'Facebook', 'demo-menus-social-link-title', 'platform-shell-plugin' ),
				'menu-item-classes' => 'fa fa-facebook-official',
				'menu-item-url'     => 'https://www.facebook.com/',
				'menu-item-target'  => '_blank',
				'menu-item-status'  => 'publish',
			)
		);

		wp_update_nav_menu_item(
			$menu_id, 0, array(
				'menu-item-title'   => _x( 'Instagram', 'demo-menus-social-link-title', 'platform-shell-plugin' ),
				'menu-item-classes' => 'fa fa-instagram',
				'menu-item-url'     => 'https://www.instagram.com/',
				'menu-item-target'  => '_blank',
				'menu-item-status'  => 'publish',
			)
		);

		wp_update_nav_menu_item(
			$menu_id, 0, array(
				'menu-item-title'   => _x( 'YouTube', 'demo-menus-social-link-title', 'platform-shell-plugin' ),
				'menu-item-classes' => 'fa fa-youtube',
				'menu-item-url'     => 'https://www.youtube.com/',
				'menu-item-target'  => '_blank',
				'menu-item-status'  => 'publish',
			)
		);
	}

	/**
	 * Méthode pour ajouter les liens de post type dans un menu.
	 *
	 * @param type $menu_id    Id WordPress du menu.
	 */
	private function add_all_post_types_links( $menu_id ) {
		$post_type_configs = $this->get_post_types_configs_in_order();
		foreach ( $post_type_configs as $post_type_config ) {
			$this->add_post_type_menu_link( $post_type_config, $menu_id );
		}
	}

	/**
	 * Méthode pour définir des données de liens post type (sélection ordonnée).
	 *
	 * @return array
	 */
	private function get_post_types_configs_in_order() {
		$ordered_cpt_configs = array();

		// Ordre éditorial.
		array_push( $ordered_cpt_configs, $this->contest_configs );
		array_push( $ordered_cpt_configs, $this->project_configs );
		array_push( $ordered_cpt_configs, $this->equipment_configs );
		array_push( $ordered_cpt_configs, $this->tool_configs );
		array_push( $ordered_cpt_configs, $this->activity_configs );

		return $ordered_cpt_configs;
	}

	/**
	 * Méthode pour définir un lien vers archive post type (ex. page de projets).
	 *
	 * @param CPT_Configs $post_type_configs    Configuration du post_type.
	 * @param string      $menu_id              Id WordPress du menu.
	 */
	private function add_post_type_menu_link( CPT_Configs $post_type_configs, $menu_id ) {
		$post_type_archive_link = get_post_type_archive_link( $post_type_configs->post_type_name );
		wp_update_nav_menu_item(
			$menu_id, 0, array(
				'menu-item-title'  => $post_type_configs->labels['menu_name'], /* Valeur au pluriel. */
				'menu-item-url'    => $post_type_archive_link,
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			)
		);
	}

	/**
	 * Méthode pour ajouter un lien de page requise.
	 *
	 * @param string $required_page_config_id    Id de la page.
	 * @param string $menu_id                    Id WordPress du menu.
	 */
	private function add_required_page_link( $required_page_config_id, $menu_id ) {
		$required_page_configs = $this->required_pages_configs->get_page_configs_by_id( $required_page_config_id );
		wp_update_nav_menu_item(
			$menu_id, 0, array(
				'menu-item-title'     => $required_page_configs['title'],
				'menu-item-object-id' => $this->required_page_manager->get_installed_page_id_by_required_page_config_id( $required_page_config_id ),
				'menu-item-object'    => 'page',
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'post_type',
			)
		);
	}
}
