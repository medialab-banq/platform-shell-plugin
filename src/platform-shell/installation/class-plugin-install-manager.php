<?php
/**
 * Platform_Shell\installation\Plugin_Install_Manager
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\installation;

use \Platform_Shell\installation\Plugin_Install_Instructions;
use \Platform_Shell\Roles_And_Capabilities;
use \Platform_Shell\installation\Required_Pages_Manager;
use \Platform_Shell\installation\Required_Menus_Manager;
use \Platform_Shell\CPT\CPT_Manager;
use \Platform_Shell\Settings\Plugin_Settings;

/**
 * Gestionnaire d'installation du plugin.
 *
 * @class    Plugin_Install_Manager
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Plugin_Install_Manager {

	/**
	 * Identifiant de l'option de version de plugin installé.
	 *
	 * @var string
	 */
	private $installed_plugin_version_option_name = 'platform_shell_option_installed_plugin_version';

	/**
	 * Version du plugin.
	 *
	 * @var String
	 */
	private $plugin_version;


	/**
	 * Instruction d'installation courante.
	 *
	 * @var int
	 */
	private $plugin_install_instructions;

	/**
	 * Instance de Roles_And_Capabilities.
	 *
	 * @var Roles_And_Capabilities
	 */
	private $roles_and_capabilities;

	/**
	 * Instance de Required_Pages_Manager.
	 *
	 * @var Required_Pages_Manager
	 */
	private $required_pages_manager;


	/**
	 * Instance de Required_Menus_Manager.
	 *
	 * @var Required_Menus_Manager
	 */
	private $required_menus_manager;

	/**
	 * Instance de CPT_Manager.
	 *
	 * @var CPT_Manager
	 */
	private $cpt_manager = null;

	/**
	 * Instance de Plugin_Settings
	 *
	 * @var Plugin_Settings
	 */
	private $plugin_settings;

	/**
	 * Constructeur.
	 *
	 * @param Plugin_Install_Instructions $plugin_install_instructions    Auto DI.
	 * @param Roles_And_Capabilities      $roles_and_capabilities         Auto DI.
	 * @param Required_Pages_Manager      $required_pages_manager         Auto DI.
	 * @param Required_Menus_Manager      $required_menus_manager         Auto DI.
	 * @param CPT_Manager                 $cpt_manager                    Auto DI.
	 * @param Plugin_Settings             $plugin_settings                Auto DI.
	 * @param string                      $plugin_version                 Auto DI.
	 */
	public function __construct(  Plugin_Install_Instructions $plugin_install_instructions,
		Roles_And_Capabilities $roles_and_capabilities,
		Required_Pages_Manager $required_pages_manager,
		Required_Menus_Manager $required_menus_manager,
		CPT_Manager $cpt_manager,
		Plugin_Settings $plugin_settings,
		$plugin_version
	) {
		$this->plugin_install_instructions = $plugin_install_instructions;
		$this->roles_and_capabilities      = $roles_and_capabilities;
		$this->required_pages_manager      = $required_pages_manager;
		$this->required_menus_manager      = $required_menus_manager;

		$this->cpt_manager     = $cpt_manager;
		$this->plugin_settings = $plugin_settings;
		$this->plugin_version  = $plugin_version;
	}

	/**
	 * Méthode d'initialisation.
	 */
	public function init() {
		// Rien pour maintenant.
	}

	/**
	 * Méthode pour gérer l'exécution d'une instruction de gestion de cycle de vie du plugin (point d'entrée).
	 *
	 * @param int $instruction    Instruction à exécuter.
	 */
	public function execute_instruction( $instruction ) {
		switch ( $instruction ) {
			case ( $this->plugin_install_instructions->install ):
				$this->install_plugin();
				break;
			case ( $this->plugin_install_instructions->post_install ):
				$this->post_install_plugin();
				break;
			case ( $this->plugin_install_instructions->uninstall ):
				$this->uninstall_plugin();
				break;
			case ( $this->plugin_install_instructions->repair ):
				$this->repair_plugin();
				break;
		}
	}

	/**
	 * Méthode pour gérer l'istallation/activation du plugin.
	 * cette fonction est appelé après l'Activiation du module dans WordPress
	 */
	private function install_plugin() {
		$this->install_database();
		$this->required_pages_manager->install();
		$this->roles_and_capabilities->install();

		// Conserver une trace de la version installée.
		update_option( $this->installed_plugin_version_option_name, $this->plugin_version );

		// Lever le flag avant de faire l'appel "force_post_install_check".
		add_option( 'do_post_install', 1, '', true );

		// Si installation wp-cli (ou tgmpa?) on ne peut pas prendre pour acquis.
		// Qu'il y aura une visite dans le tableau de bord.
		$this->force_post_install_check();
	}

	/**
	 * Méthode pour gérer la post installation du plugin.
	 * Certains traitements ne fonctionnent pas bien si fait à l'intérieur du cycle d'activation.
	 * Le post install est donc un traitement d'installation différé. Voir aussi documentation WordPress : https://codex.wordpress.org/Function_Reference/register_activation_hook.
	 *
	 * @global type $wp_rewrite
	 */
	private function post_install_plugin() {
		global $wp_rewrite;

		$this->cpt_manager->install_taxonomies();
		$this->required_menus_manager->install(); /* Requiert post types. */
		$this->plugin_settings->install();

		// Force reload.
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		update_option( 'rewrite_rules', false );
		flush_rewrite_rules( true );
	}

	/**
	 * Méthode pour tentative de correction d'une problématique d'installation (settings non à jour).
	 * Les menus ne s'affichent pas correctement mais sont bien enregistrés.
	 * La séquence origine du problème n'a pas être identifié exactement.
	 */
	private function force_post_install_check() {
		// On simule un accès à l'écran admin.
		$bug_fix_force_refresh_menu_options_url = get_option( 'siteurl' ) . '/wp-admin/';
		// Fire and forget (réponse n'est pas utilisée).
		// L'accès à la page va déclencher un accès normal.
		// post_install_check dans class-main va fonctioner tel qu'attendu.
		// Note : L'API Heartbeat pourrait théoriquement déclencher un appel suffisant pour provoquer un appel équivalent.
		// Mais ce n'est pas un mécanisme fiable et le traitement post_install ne sera fait qu'une seule fois de toute manière.
		$response = wp_remote_get( $bug_fix_force_refresh_menu_options_url );
	}

	/**
	 * Méthode pour gérer la desactivation du module.
	 * cette fonction est appelé après Désactivation du module dans WordPress
	 */
	private function uninstall_plugin() {
		$this->roles_and_capabilities->uninstall();
		delete_option( $this->installed_plugin_version_option_name );
	}

	/**
	 * Méthode pour gérer la réparation du plugin.
	 */
	private function repair_plugin() {
		/* Rien pour maintenant. */
	}

	/**
	 * Méthode de création des tables supplémentaires pour les besoins de la plateforme dans la DB WordPress.
	 */
	private function install_database() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( ! empty( $wpdb->charset ) ) {
				$collate .= " DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}platform_shell_contest_entry (
				  `project_id` bigint(20) NOT NULL,
				  `contest_id` bigint(20) NOT NULL,
				  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				  `p_winner` tinyint(1) NOT NULL DEFAULT '0',
				  PRIMARY KEY (`project_id`,`contest_id`)
				) $collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
