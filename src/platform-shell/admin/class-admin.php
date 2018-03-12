<?php
/**
 * Platform_Shell\Admin\Admin
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Admin;

use Platform_Shell\Settings\Map_Background_Image;
use Platform_Shell\Settings\Settings_Menu;

/**
 * Admin class.
 */
class Admin {

	/**
	 * URL du plugin
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Path du plugin.
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * Instance de Settings_Menu
	 *
	 * @var object
	 */
	private $settings_menu;

	/**
	 * Instance de Map_Background_Image
	 *
	 * @var object
	 */
	private $map_background_image;

	/**
	 * Constructeur
	 *
	 * @param string               $plugin_url              URL du plugin (DI).
	 * @param string               $plugin_path             Path du plugin (DI).
	 * @param Settings_Menu        $settings_menu           Instance de Settings_Menu (DI).
	 * @param Map_Background_Image $map_background_image    Instance de Map_Background_Image (DI).
	 */
	public function __construct( $plugin_url, $plugin_path, Settings_Menu $settings_menu, Map_Background_Image $map_background_image ) {

		$this->plugin_url           = $plugin_url;
		$this->plugin_path          = $plugin_path;
		$this->settings_menu        = $settings_menu;
		$this->map_background_image = $map_background_image;
	}

	/**
	 * Méthode init.
	 */
	public function init() {

		$this->settings_menu->init();
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );

		$this->map_background_image->init();
	}

	/**
	 * [admin_enqueue_scripts description]
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {

		$css_base_url  = $this->plugin_url . 'css/';
		$css_base_path = $this->plugin_path . '/css/';
		$js_base_url   = $this->plugin_url . 'js/';
		$js_base_path  = $this->plugin_path . '/js/';

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-dialog' );

		// Il n'y a pas de styles par défaut pour le date picker dans WordPress (voir par ex. http://wordpress.stackexchange.com/questions/83322/inbuilt-style-for-jquery-ui-datepicker)
		// Utilise styles du thème smooth récupérés du site de jquery ui.
		$jquery_ui_css_url  = $css_base_url . 'jquery-ui.min.css';
		$jquery_ui_css_path = $css_base_path . 'jquery-ui.min.css';
		wp_enqueue_style( 'jquery-ui-css', $jquery_ui_css_url, false, platform_shell_get_file_version( $jquery_ui_css_path ) );

		$jquery_ui_structure_css_url  = $css_base_url . 'jquery-ui.structure.min.css';
		$jquery_ui_structure_css_path = $css_base_path . 'jquery-ui.structure.min.css';
		wp_enqueue_style( 'jquery-ui-structure-css', $jquery_ui_structure_css_url, $this->plugin_path, platform_shell_get_file_version( $jquery_ui_structure_css_path ) );

		$jquery_ui_theme_css_url  = $css_base_url . 'jquery-ui.theme.min.css';
		$jquery_ui_theme_css_path = $css_base_path . 'jquery-ui.theme.min.css';
		wp_enqueue_style( 'jquery-ui-theme-css', $jquery_ui_theme_css_url, $this->plugin_path, platform_shell_get_file_version( $jquery_ui_theme_css_path ) );

		$common_backend_css_url  = $css_base_url . 'common-backend.css';
		$common_backend_css_path = $css_base_path . 'common-backend.css';
		wp_enqueue_style( 'common-backend-css', $common_backend_css_url, $this->plugin_path, platform_shell_get_file_version( $common_backend_css_path ) );

		$common_backend_js_url  = $js_base_url . 'common-backend.js';
		$common_backend_js_path = $js_base_path . 'common-backend.js';
		wp_enqueue_script( 'common-backend', $common_backend_js_url, $this->plugin_path, platform_shell_get_file_version( $common_backend_js_path ) );

		$footer_image_generator_backend_js      = 'footer-image-generator-backend.js';
		$footer_image_generator_backend_js_url  = $js_base_url . $footer_image_generator_backend_js;
		$footer_image_generator_backend_js_path = $js_base_path . $footer_image_generator_backend_js;
		wp_enqueue_script(
			'footer-image-generator-backend', // Handle.
			$footer_image_generator_backend_js_url, // SRC.
			$this->plugin_path, // DEPS.
			platform_shell_get_file_version( $footer_image_generator_backend_js_path ), // Version.
			true // In footer.
		);

		wp_localize_script(
			'footer-image-generator-backend',
			'footer_image_generator',
			[
				'title'              => esc_html_x( 'Générer une image de fond', 'option-generate-background', 'platform-shell-plugin' ),
				'noncesave'          => wp_create_nonce( 'platform_shell_footer_location_background_url_generate_save' ),
				'noncecreate'        => wp_create_nonce( 'platform_shell_footer_location_background_url_generate_image' ),
				'pinlocation'        => plugins_url( 'images/pin.png', dirname( dirname( dirname( __FILE__ ) ) ) ),
				'contributors_label' => _x( 'et ses contributeurs', 'leaflet-openstreetmap-attribution', 'platform-shell-plugin' ),
			]
		);

		$leaflet_css_file = 'lib/leaflet/leaflet.css';
		$leaflet_css_url  = $js_base_url . $leaflet_css_file;
		$leaflet_css_path = $js_base_path . $leaflet_css_file;
		wp_enqueue_style( 'leaflet-css', $leaflet_css_url, $this->plugin_path, platform_shell_get_file_version( $leaflet_css_path ) );

		$leaflet_js_file = 'lib/leaflet/leaflet.js';
		$leaflet_js_url  = $js_base_url . $leaflet_js_file;
		$leaflet_js_path = $js_base_path . $leaflet_js_file;
		wp_enqueue_script( 'leaflet-js', $leaflet_js_url, $this->plugin_path, platform_shell_get_file_version( $leaflet_js_path ) );

		$tilelayer_grayscale_js_file = 'lib/TileLayer.Grayscale.js';
		$tilelayer_grayscale_js_url  = $js_base_url . $tilelayer_grayscale_js_file;
		$tilelayer_grayscale_js_path = $js_base_path . $tilelayer_grayscale_js_file;
		wp_enqueue_script( 'tilelayer-grayscale-js', $tilelayer_grayscale_js_url, $this->plugin_path, platform_shell_get_file_version( $tilelayer_grayscale_js_path ) );

		$html2canvas_js_file = 'lib/html2canvas.min.js';
		$html2canvas_js_url  = $js_base_url . $html2canvas_js_file;
		$html2canvas_js_path = $js_base_path . $html2canvas_js_file;
		wp_enqueue_script( 'html2canvas-js', $html2canvas_js_url, $this->plugin_path, platform_shell_get_file_version( $html2canvas_js_path ) );

		$canvas2image_js_file = 'lib/canvas2image.js';
		$canvas2image_js_url  = $js_base_url . $canvas2image_js_file;
		$canvas2image_js_path = $js_base_path . $canvas2image_js_file;
		wp_enqueue_script( 'canvas2image-js', $canvas2image_js_url, $this->plugin_path, platform_shell_get_file_version( $canvas2image_js_path ) );

		// todo_refactoring : problème dépendance script avec script non admin.
		// Si enlève ce code l'affichage ne se fait pas correctement dans le panneau de settings.
		// L'import de script admin dans l'admin est risqué? (sauf pour besoins de metabox?).
		if ( function_exists( 'platform_shell_theme_register_theme_scripts' ) ) {

			platform_shell_theme_register_theme_scripts(); /* Charge les définitions de scripts connus par le thème. */
			platform_shell_theme_enqueue_theme_scripts();  /* Enqueue du sous-ensemble requis pour l'admin (tableau de bord / theme admin. Ex. validation jQuery. */
		}

	}
}
