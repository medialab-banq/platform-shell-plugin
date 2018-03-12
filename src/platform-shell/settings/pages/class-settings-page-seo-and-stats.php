<?php
/**
 * Platform_Shell\Settings\Settings_Page_Seo_And_Stats
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Settings\Pages;

use \Platform_Shell\Settings\Settings_Page;

/**
 * Classe de gestion de l'écran de settings google et json-LD.
 *
 * @class        Settings_Page_Seo_And_Stats
 * @description  À compléter.
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class Settings_Page_Seo_And_Stats extends Settings_Page {

	/**
	 * Constructeur.
	 */
	public function __construct() {

	}

	/**
	 * Méthode de définition du menu (callback).
	 */
	public function admin_menu() {
		/* Voir aussi : https://wordpress.stackexchange.com/questions/66498/add-menu-page-with-different-name-for-first-submenu-item. */
		add_submenu_page(
			$this->root_menu_slug,
			_x( 'Réglages Puce Google et données JSON-LD', 'settings-page-title', 'platform-shell-plugin' ),
			_x( 'Puce Google et données JSON-LD', 'settings-page-title', 'platform-shell-plugin' ),
			'platform_shell_cap_manage_basic_options',
			_x( 'reglages-puce-google-et-donnees-json-ld', 'settings-page-slug', 'platform-shell-plugin' ),
			array( $this, 'default_page_renderer_callback' )
		);
	}

	/**
	 * Méthode de configuration des configurations des sections (callback).
	 *
	 * @return array
	 */
	public function get_settings_sections() {
		$sections = array(
			array(
				'id'    => 'platform-shell-settings-google-tag-and-json-ld',
				'title' => _x( 'Puce Google et données JSON-LD', 'settings', 'platform-shell-plugin' ),
			),
		);
		return $sections;
	}

	/**
	 * Méthode de configuration des configurations des champs de settings (callback).
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		$settings_fields = array(
			'platform-shell-settings-google-tag-and-json-ld'      => array(
				array(
					'name'                => 'platform_shell_option_json_ld',
					'label'               => _x( 'JSON-LD', 'settings', 'platform-shell-plugin' ),
					'desc'                => _x( 'Bloc de données JSON-LD<br /> - Voir la <a href="https://json-ld.org">documentation</a> du format json-ld.<br /> - Les données JSON-LD seront insérées automatiquement dans la page HTML.<br /> - Les données devraient être validées avec un validateur de JSON.<br />- Les données peuvent être en format minifié ou non.<br />- Attention : Ne pas insérer des retours de ligne manuellement.', 'settings', 'platform-shell-plugin' ),
					'placeholder'         => _x( 'Entrer la donnée en format JSON-LD : { ... }', 'settings', 'platform-shell-plugin' ),
					'type'                => 'textarea',
					'default'             => '',
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				),
				array(
					'name'                => 'platform_shell_option_google_tag_manager_tracking_code',
					'label'               => _x( 'Google Tag manager - tracking code (Puce)', 'settings', 'platform-shell-plugin' ),
					'desc'                => _x( 'Google Tag manager (puce).<br /> - Voir la <a href="https://developers.google.com/tag-manager/devguide">documentation</a> de Google Tag manager.<br /> - Attention : La validité de la donnée n’est pas vérifiée par le système.', 'settings', 'platform-shell-plugin' ),
					'placeholder'         => _x( 'Ex. : <!-- Google Tag Manager --> [...] <!-- End Google Tag Manager -->', 'settings', 'platform-shell-plugin' ),
					'type'                => 'textarea',
					'default'             => '',
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				),
				array(
					'name'                => 'platform_shell_option_google_tag_manager_no_script',
					'label'               => _x( 'Google Tag manager - noscript', 'settings', 'platform-shell-plugin' ),
					'desc'                => _x( 'Google Tag manager à utiliser pour noscript. <br /> - Voir la <a href="https://developers.google.com/tag-manager/devguide">documentation</a> de Google Tag manager.<br /> - Attention : La validité de la donnée n’est pas vérifiée par le système.', 'settings', 'platform-shell-plugin' ),
					'placeholder'         => _x( 'Ex. : <!-- Google Tag Manager (noscript) --> [...] <!-- End Google Tag Manager (noscript) -->', 'settings', 'platform-shell-plugin' ),
					'type'                => 'textarea',
					'default'             => '',
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				),
			),

		);

		return $settings_fields;
	}
}
