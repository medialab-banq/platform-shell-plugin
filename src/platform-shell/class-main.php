<?php
/**
 * Platform_Shell\Main
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell;

use DI\FactoryInterface;
use Platform_Shell\Admin\Admin;
use Platform_Shell\CPT\CPT_Manager;
use Platform_Shell\Fields\Fields_Helper;
use Platform_Shell\Installation\Required_Widget_Manager;
use Platform_Shell\Restriction\Restrictions;
use Platform_Shell\Shortcodes\Shortcodes_Manager;
use Platform_Shell\installation\Plugin_Install_Instructions;
use Platform_Shell\installation\Required_Pages_Manager;

/**
 * Main
 *
 * @class    Main
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Main {
	/**
	 * Plugin url path.
	 *
	 * @var string
	 */
	public $plugin_url = null;

	/**
	 * Plugin absolute path.
	 *
	 * @var string
	 */
	public $plugin_path = null;

	/**
	 * Slug du plugin
	 *
	 * @var string
	 */
	public $plugin_slug = null;

	/**
	 * Factory Interface
	 *
	 * @var FactoryInterface
	 */
	private $di_container = null;

	/**
	 * Restrictions
	 *
	 * @var Restrictions
	 */
	private $restrictions = null;

	/**
	 * Login
	 *
	 * @var Login
	 */
	private $login = null;

	/**
	 * Users
	 *
	 * @var Users
	 */
	private $users = null;

	/**
	 * Roles And Capabilities
	 *
	 * @var Roles_And_Capabilities
	 */
	private $roles_and_capabilities = null;

	/**
	 * Profile
	 *
	 * @var Profile
	 */
	private $profile = null;

	/**
	 * Content Type Manager
	 *
	 * @var CPT_Manager
	 */
	private $cpt_manager = null;

	/**
	 * Plugin Install Manager
	 *
	 * @var \Platform_Shell\installation\Plugin_Install_Manager
	 */
	private $plugin_install_manager = null;

	/**
	 * Plugin Install Instructions
	 *
	 * @var Plugin_Install_Instructions
	 */
	private $plugin_install_instruction = null;

	/**
	 * Required Widget Manager
	 *
	 * @var Required_Widget_Manager
	 */
	private $required_widget_manager = null;

	/**
	 * Shortcodes Manager
	 *
	 * @var Shortcodes_Manager
	 */
	private $shortcodes_manager = null;

	/**
	 * Gestionaire des pages requises
	 *
	 * @var Required_Pages_Manager
	 */
	private $required_page_manager;

	/**
	 * Admin
	 *
	 * @var Admin
	 */
	private $admin = null;

	/**
	 * Contructeur
	 *
	 * @param string                                              $plugin_url                     URL du plugin.
	 * @param string                                              $plugin_path                    Path du plugin.
	 * @param string                                              $plugin_slug                    Slug du plugin.
	 * @param CPT_Manager                                         $cpt_manager                    Custom Post Type Manager.
	 * @param FactoryInterface                                    $di_container                   Factory Interface.
	 * @param \Platform_Shell\installation\Plugin_Install_Manager $lazy_plugin_install_manager    Plugin_Install_Manager.
	 * @param Plugin_Install_Instructions                         $plugin_install_instruction     Plugin Install Instructions.
	 * @param Required_Widget_Manager                             $required_widget_manager        Required Widget Manager.
	 * @param Shortcodes_Manager                                  $shortcodes_manager             Shortcodes Manager.
	 * @param Admin                                               $admin                          Admin.
	 * @param Restrictions                                        $restrictions                   Restrictions.
	 * @param Profile                                             $profile                        Profile.
	 * @param Fields_Helper                                       $fields_helper                  Fields Helper.
	 * @param Login                                               $login                          Login.
	 * @param Users                                               $users                          Users.
	 * @param Roles_And_Capabilities                              $roles_and_capabilities         Roles And Capabilities.
	 * @param Required_Pages_Manager                              $required_page_manager         Required Pages.
	 */
	public function __construct(
		$plugin_url,
		$plugin_path,
		$plugin_slug,
		CPT_Manager $cpt_manager,
		FactoryInterface $di_container,
		$lazy_plugin_install_manager,
		Plugin_Install_Instructions $plugin_install_instruction,
		Required_Widget_Manager $required_widget_manager,
		Shortcodes_Manager $shortcodes_manager,
		Admin $admin, Restrictions $restrictions,
		Profile $profile,
		Fields_Helper $fields_helper,
		Login $login,
		Users $users,
		Roles_And_Capabilities $roles_and_capabilities,
		Required_Pages_Manager $required_page_manager
	) {
		$this->plugin_url                 = $plugin_url;
		$this->plugin_path                = $plugin_path;
		$this->plugin_slug                = $plugin_slug;
		$this->di_container               = $di_container;
		$this->cpt_manager                = $cpt_manager;
		$this->plugin_install_manager     = $lazy_plugin_install_manager;
		$this->plugin_install_instruction = $plugin_install_instruction;
		$this->required_widget_manager    = $required_widget_manager;
		$this->shortcodes_manager         = $shortcodes_manager;
		$this->admin                      = $admin;
		$this->restrictions               = $restrictions;
		$this->profile                    = $profile;
		$this->fields_helper              = $fields_helper;
		$this->login                      = $login;
		$this->users                      = $users;
		$this->roles_and_capabilities     = $roles_and_capabilities;
		$this->required_page_manager      = $required_page_manager;

		// enqueue les javascript pour le module.
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_assets' ) );
		add_action( 'init', array( &$this, 'init' ), 1 );

		if ( get_option( 'do_post_install' ) == 1 ) {
			/* Important : utilisation du init. Pas de garantie que le post_install soit fait en mode admin. */
			add_action( 'init', array( &$this, 'post_install_execute' ), 9999 /* Important le plus tard possible / après enregistrement des cpt. */ );
		}

		add_action( 'widgets_init', array( &$this->required_widget_manager, 'init' ), 99 );
	}

	/**
	 * Méthode post_install_check
	 * Lorsqu'on utilise tgmpa, nous n'avons pas la garantie d'un rechargement de page.
	 * et la empêche d'utiliser le post_intall_check de manière prévisible avec is_admin() puisque le premier accès
	 * suite à l'installation du plugin pourrait être une page du site.
	 */
	public function post_install_execute() {
		$this->plugin_execute_installer_instruction( $this->plugin_install_instruction->post_install );
		delete_option( 'do_post_install' );
	}

	/**
	 * Méthode plugin_install
	 */
	public function plugin_install() {
		// Dépendances (le init principal n'est pas exécuté lors de l'activation.
		$this->load_plugin_textdomain(); /* Important: Pour installation des pages dans la bonne langue. */
		$this->plugin_execute_installer_instruction( $this->plugin_install_instruction->install );
	}

	/**
	 * Méthode plugin_uninstall
	 */
	public function plugin_uninstall() {
		$this->plugin_execute_installer_instruction( $this->plugin_install_instruction->uninstall );
	}

	/**
	 * Méthode plugin_execute_installer_instruction
	 *
	 * @param int $instruction    Type d'instruction.
	 */
	public function plugin_execute_installer_instruction( $instruction ) {
		$this->plugin_install_manager->execute_instruction( $instruction );
	}

	/**
	 * Méthode init
	 */
	public function init() {
		$this->load_plugin_textdomain();
		$this->run_required_init();
	}

	/**
	 * Méthode load_plugin_textdomain
	 */
	public function load_plugin_textdomain() {
		$plugin_base_folder            = basename( $this->plugin_path );
		$relative_languages_files_path = $plugin_base_folder . '/languages/';
		load_plugin_textdomain( 'platform-shell-plugin', false, $relative_languages_files_path );
	}

	/**
	 * Méthode run_required_init
	 */
	public function run_required_init() {

		if ( is_admin() ) {
			$this->admin->init();
			$this->plugin_install_manager->init();
		}

		$this->fields_helper->init();
		$this->restrictions->init();
		$this->login->init();
		$this->users->init();
		$this->profile->init();
		$this->roles_and_capabilities->init();
		$this->cpt_manager->init();
		$this->reporting = $this->di_container->get( 'Platform_Shell\Reporting\Reporting' );
		$this->reporting->init();
		$this->shortcodes_manager->init();
	}

	/**
	 * Enqueue les javascripts en frontend.
	 */
	public function enqueue_assets() {
		$css_base_url  = $this->plugin_url . 'css/';
		$js_base_url   = $this->plugin_url . 'js/';
		$css_base_path = $this->plugin_path . '/css/';
		$js_base_path  = $this->plugin_path . '/js/';

		$edit_page_id    = $this->required_page_manager->get_installed_page_id_by_required_page_config_id( 'platform-shell-page-project-edit-page' );
		$create_page_id  = $this->required_page_manager->get_installed_page_id_by_required_page_config_id( 'platform-shell-page-project-create-page' );
		$profile_page_id = $this->required_page_manager->get_installed_page_id_by_required_page_config_id( 'platform-shell-page-profile' );

		// CSS.
		/* Couplage fort avec scripts définis dans le thème. */
		if ( function_exists( 'platform_shell_theme_register_theme_scripts' ) ) {
			platform_shell_theme_register_theme_scripts(); /* Charge les déinitions de scripts connus par le thème. */
			platform_shell_theme_enqueue_theme_scripts();  /* Enqueue du sous-ensemble requis pour l'admin (tableau de bord / theme admin. Ex. validation jQuery. */
		}

		// JS.
		$common_frontend_url  = $js_base_url . 'common-frontend.js';
		$common_frontend_path = $js_base_path . 'common-frontend.js';

		wp_register_script( 'common-frontend', $common_frontend_url, array( 'jquery' ), platform_shell_get_file_version( $common_frontend_path ), true );
		wp_localize_script( 'common-frontend', 'WPURLS', array( 'siteurl' => get_option( 'siteurl' ) ) );

		$filesize_allowed = UploadHelper::get_max_upload_filesize_bytes();

		$ajax_url = admin_url( 'admin-ajax.php' );

		wp_localize_script(
			'common-frontend', 'WP_platform_shell_utils', array(
				'ajax_url'      => $ajax_url,
				'max_file_size' => $filesize_allowed,
			)
		);
		wp_localize_script( 'common-frontend', 'WP_common_frontend_script_string', $this->get_common_frontend_script_frontend_language_strings() );

		wp_enqueue_script( 'common-frontend' );

		// JS pour Projets.
		if ( is_page( $edit_page_id ) || is_page( $create_page_id ) ) {

			// REGISTER.
			$select2_url    = $js_base_url . 'lib/select2/js/select2.full.min.js';
			$select2_path   = $js_base_path . 'lib/select2/js/select2.full.min.js';
			$select2_locale = strtolower( mb_substr( get_locale(), 0, 2 ) );

			wp_register_script( 'select2', $select2_url, array( 'jquery' ), platform_shell_get_file_version( $select2_path ), true );
			wp_localize_script(
				'select2', 'select2_strings', [
					'locale'                => $select2_locale,
					'coauthor_default_text' => _x( 'Saisissez les premières lettres d’un pseudonyme', 'coauthor_default_text', 'platform-shell-plugin' ),
				]
			);

			$select2_locale_file = $select2_locale . '.js';
			$select2_locale_name = 'select2-' . $select2_locale;

			$select2_fr_url  = $js_base_url . 'lib/select2/js/i18n/' . $select2_locale_file;
			$select2_fr_path = $js_base_path . 'lib/select2/js/i18n/' . $select2_locale_file;

			wp_register_script( $select2_locale_name, $select2_fr_url, array( 'jquery' ), platform_shell_get_file_version( $select2_fr_path ), true );

			$project_url  = $js_base_url . 'project.js';
			$project_path = $js_base_path . 'project.js';

			wp_register_script( 'project', $project_url, array( 'jquery' ), platform_shell_get_file_version( $project_path ), true );
			wp_localize_script( 'project', 'WP_project_script_string', $this->get_project_script_frontend_language_strings() );
			wp_localize_script(
				'project', 'project_strings', [
					'user_search_nonce' => wp_create_nonce( 'platform_shell_action_search_users' ),
				]
			);

			// ENQUEUE.
			wp_enqueue_script( 'select2' );
			wp_enqueue_script( $select2_locale_name );
			wp_enqueue_script( 'project' );

			// CSS.
			$select2_css_url  = $js_base_url . 'lib/select2/css/select2.min.css';
			$select2_css_path = $js_base_path . 'lib/select2/css/select2.min.css';
			wp_enqueue_style( 'select2', $select2_css_url, [], platform_shell_get_file_version( $select2_css_path ) );
		}

		// JS pour profile.
		if ( is_page( $profile_page_id ) ) {

			$profile_url  = $js_base_url . 'profile.js';
			$profile_path = $js_base_path . 'profile.js';

			wp_register_script( 'profile', $profile_url, array( 'jquery' ), platform_shell_get_file_version( $profile_path ), true );

			wp_localize_script(
				'profile', 'WP_profile_configs', [
					'min_pseudo_length' => 3,
					'max_pseudo_length' => 50,
				]
			);
			wp_localize_script( 'profile', 'WP_profile_script_string', $this->get_profile_script_front_end_language_strings() );

			wp_enqueue_script( 'profile' );
		}

		if ( is_singular( 'contest' ) ) {
			$contest_url  = $js_base_url . 'contest.js';
			$contest_path = $js_base_path . 'contest.js';

			wp_register_script( 'contest', $contest_url, array( 'jquery' ), platform_shell_get_file_version( $contest_path ), true );
			wp_localize_script( 'contest', 'WP_contest_script_string', $this->get_contest_script_front_end_language_strings() );

			wp_enqueue_script( 'contest' );
		}

		$common_frontend_css_url  = $css_base_url . 'common-frontend.css';
		$common_frontend_css_path = $css_base_path . 'common-frontend.css';
		wp_enqueue_style( 'common-frontend-css', $common_frontend_css_url, [], platform_shell_get_file_version( $common_frontend_css_path ) );
	}

	/**
	 * Méthode get_common_frontend_script_frontend_language_strings
	 *
	 * @return string[]
	 */
	private function get_common_frontend_script_frontend_language_strings() {
		$strings = array(
			'choose_option' => _x(
				'Tu dois choisir une option.',
				'common-frontend_js',
				'platform-shell-plugin'
			),
		);

		return $strings;
	}

	/**
	 * Méthode get_project_script_frontend_language_strings
	 *
	 * @return string[]
	 */
	private function get_project_script_frontend_language_strings() {

		$strings = array(
			'validation_error_featured_missing' => _x( 'Tu dois entrer un format d’image valide.', 'form-project-frontend-string', 'platform-shell-plugin' ),
			'validation_error_accept_missing'   => _x( 'Pour créer ou modifier ton projet, tu dois accepter les règlements en cochant la case ci-dessus.', 'form-project-frontend-string', 'platform-shell-plugin' ),
			'unexpected_error'                  => _x( 'Une erreur indéterminée est survenue; recommence svp. <br/>Si le problème persiste, n’hésite pas à nous contacter.', 'form-project-frontend-string', 'platform-shell-plugin' ),
		);

		return $strings;
	}

	/**
	 * Méthode get_profile_script_front_end_language_strings
	 *
	 * @return string[]
	 */
	private function get_profile_script_front_end_language_strings() {

		$strings = array(
			'unknown_save_error'              => _x( 'Erreur indéterminée lors de la sauvegarde du profil.', 'form-profile-frontend-string', 'platform-shell-plugin' ),
			'validation_error_accept_missing' => _x( 'Pour modifier ton profil, tu dois accepter les règlements en cochant la case ci-dessus.', 'form-profile-frontend-string', 'platform-shell-plugin' ),
		);

		return $strings;
	}

	/**
	 * Méthode get_contest_script_front_end_language_strings
	 *
	 * @return string[]
	 */
	private function get_contest_script_front_end_language_strings() {

		$strings = array(
			'validation_error_project_required' => _x( 'Tu dois choisir un de tes projets.', 'form-contest-frontend-string', 'platform-shell-plugin' ),
			'validation_error_accept_missing'   => _x( 'Pour inscrire ton projet au concours, tu dois accepter les règlements en cochant la case ci-dessus.', 'form-contest-frontend-string', 'platform-shell-plugin' ),
		);

		return $strings;
	}

	/**
	 * Return le URL du script Ajax .
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}
}
