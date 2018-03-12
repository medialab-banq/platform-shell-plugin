<?php
/**
 * Platform_Shell\Settings\Settings_Menu
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Settings;

use \Platform_Shell\Settings\Pages\Settings_Page_Main;
use \Platform_Shell\Settings\Pages\Settings_Page_Site_Sections;
use \Platform_Shell\Settings\Pages\Settings_Page_Social_Sharing;
use \Platform_Shell\Settings\Pages\Settings_Page_Seo_And_Stats;

/**
 * Classe de gestion des menus des pages de configurations.
 *
 * @class        Settings_Page
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class Settings_Menu {

	/**
	 * Liste des pages de configuration.
	 *
	 * @var array
	 */
	private $settings_pages = array();

	/**
	 * Slug racine du menu.
	 *
	 * @var string
	 */
	private $root_menu_slug;

	/**
	 * Constructeur.
	 *
	 * @param Settings_Page_Main           $settings_page_main                        Instance de Settings_Page_Main (DI).
	 * @param Settings_Page_Site_Sections  $settings_page_sections           Instance de Settings_Page_Site_Sections (DI).
	 * @param Settings_Page_Social_Sharing $settings_page_social_sharing    Instance de Settings_Page_Social_Sharing (DI).
	 * @param Settings_Page_Seo_And_Stats  $settings_page_seo_and_stats      Instance de Settings_Page_Seo_And_Stats (DI).
	 */
	public function __construct( Settings_Page_Main $settings_page_main,
		Settings_Page_Site_Sections $settings_page_sections,
		Settings_Page_Social_Sharing $settings_page_social_sharing,
		Settings_Page_Seo_And_Stats $settings_page_seo_and_stats
	) {
		/* Défini ordre d'affichage dans menu. */
		array_push( $this->settings_pages, $settings_page_main );
		array_push( $this->settings_pages, $settings_page_sections );
		array_push( $this->settings_pages, $settings_page_social_sharing );
		array_push( $this->settings_pages, $settings_page_seo_and_stats );
	}

	/**
	 * Méthode init.
	 */
	public function init() {
		$this->root_menu_slug = _x( 'plateforme-reglages', 'settings-slug', 'platform-shell-plugin' );

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		/* Sous-menus et définition des pages de settings. */
		foreach ( $this->settings_pages as $settings_page ) {
			$settings_page->set_root_menu_slug( $this->root_menu_slug );
			$settings_page->init();
		}
	}

	/**
	 * Méthode d'affichage du menu (callback).
	 */
	public function admin_menu() {
		/* Ajout du menu root. */
		add_menu_page(
			_x( 'Réglages de la plateforme', 'settings-page-title', 'platform-shell-plugin' ),
			_x( 'Réglages de la plateforme', 'settings-menu-title', 'platform-shell-plugin' ),
			'platform_shell_cap_manage_basic_options',
			$this->root_menu_slug,
			null
		);
	}
}
