<?php
/**
 * Platform_Shell\Settings\Settings_Page
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Settings;

/**
 * Classe de gestion des configurations.
 *
 * @class        Settings_Page
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class Settings_Page {

	/**
	 * Instance de settings api.
	 *
	 * @var Settings_API
	 */
	protected $settings_api;

	/**
	 * Slug du menu
	 *
	 * @var string
	 */
	protected $root_menu_slug;

	/**
	 * Constructeur
	 */
	public function __construct() {
	}

	/**
	 * Méthode init.
	 */
	public function init() {
		$this->settings_api = new \Settings_API();

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Méthode pour déterminer la racine du slug.
	 *
	 * @param string $root_menu_slug    slug racine.
	 */
	public function set_root_menu_slug( $root_menu_slug ) {
		$this->root_menu_slug = $root_menu_slug;
	}

	/**
	 * Méthode admin_init (callback)
	 */
	public function admin_init() {

		$sections = $this->get_settings_sections();
		// set the settings.
		$this->settings_api->set_sections( $sections );
		$this->settings_api->set_fields( $this->get_settings_fields() );

		// initialize settings.
		$this->settings_api->admin_init();

		$this->register_page_capability_filters();
		$this->register_option_capability_pre_update_option_filter();

	}

	/**
	 * Méthode pour enregistrer filtre page_capability.
	 */
	private function register_page_capability_filters() {
		$sections = $this->settings_api->get_sections();
		foreach ( $sections as $section_id => $section ) {
			$filter_page_identifier = 'option_page_capability_' . $section['id']; /* Voir filtre dans options.php */
			add_filter( $filter_page_identifier, array( $this, 'default_page_capability_handler' ), 10, 3 );
		}
	}

	/**
	 * Méthode pour enregistrer filtre option_capability_pre_update_option.
	 */
	private function register_option_capability_pre_update_option_filter() {
		/*
		 * La granularité de gestion de Worpdress ne permet pas facilement de mélanger dans un même écran
		 * des options qui auraient des capability différente (capability au niveau de la page).
		 * Le problème n'est pas visible mais il est techniquement
		 * possible de modifier une option "admin" en manipulant simplement les id dans le formulaire front-end.
		 * Il y aurait deux options possibles : structurer les écrans de manière à ne pas mélanger les options admin et gestionnaire
		 * mais ça rend plus complexe l'organisation logique des options dans les écrans ou empêcher la modification
		 * de la valeur si cela n'est pas permis (la solution ici).
		 */

		add_filter( 'pre_update_option', array( $this, 'update_option_capability_restriction_handler' ), 10, 3 );
	}

	/**
	 * Méthode pour retourner map section / options.
	 *
	 * @param array $section_options    Données des options des sections.
	 * @return array
	 */
	private function get_section_options_map( $section_options ) {

		$fields = $this->settings_api->get_fields();

		$field_map = array();

		foreach ( $section_options as $sub_option ) {
			$field_map[ $sub_option['name'] ] = $sub_option;
		}

		return $field_map;
	}

	/**
	 * Méthode pour la gestion des restriction d'accès aux configurations selon les capabilities.
	 *
	 * @param any    $value        Nouvelle valeur.
	 * @param string $option       Nom de l'option.
	 * @param any    $old_value    Ancienne valeur.
	 * @return any
	 */
	public function update_option_capability_restriction_handler( $value, $option, $old_value ) {
		/*
		 *	Vérifier si option correspond à section connue.
		 *  Vérifier capability sur champs.
		 */

		// Sortie rapide. Pas un array, ce n'est pas probablement pas un update provenant d'un écran de settings.
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$fields = $this->settings_api->get_fields();

		$section_options = isset( $fields[ $option ] ) ? $fields[ $option ] : null;

		if ( null != $section_options ) {
			$section_options_map = $this->get_section_options_map( $section_options );

			// Valider que l'utilisateur courant a le droit de modifier les propriété.
			// Sinon ça pourrait être une erreur dans la code ou une possiblement une tentative
			// de manipulation de données.
			foreach ( $value as $sub_option_name => $sub_option_value /* No used. Ok. */ ) {
				$sub_option = isset( $section_options_map[ $sub_option_name ] ) ? $section_options_map[ $sub_option_name ] : null;

				/*
				 * WordPress ne valide pas si l'option a été enregistrée?? Il est possible de créer une nouvelle
				 * valeur dans la BD en inventant un nom dans le formulaire. Bug mineur mais on valide si la valeur est bien connue.
				 */
				if ( isset( $sub_option ) /* Null si l'option n'est pas connue. */ ) {
					$required_capability = isset( $sub_option['required_capability'] ) ? $sub_option['required_capability'] : null;

					if ( null != $required_capability && ! current_user_can( $required_capability ) ) {
						$this->update_option_error_abort( $sub_option_name );
					}
				} else {
					/* L'option n'est pas connue. Pourrait être une erreur de programmation mais pourrait être une manipulation volontaire du formulaire. */
					$this->update_option_error_abort( $sub_option_name );
				}
			}
		}

		return $value;
	}

	/**
	 * Méthode pour traiter erreur manipulation incorrecte des données.
	 *
	 * @param type $option_name    Nom de l'option.
	 */
	private function update_option_error_abort( $option_name ) {
		// translators: %1$s nom de l'option.
		$message = sprintf( _x( 'Vous n’êtes pas authorisé à modifier l’option [%1$s]. Veuillez communiquer avec un administrateur.', 'settings', 'platform-shell-plugin' ), $option_name );

		wp_die(
			'<h1>' . _x( 'Problème technique détecté', 'settings', 'platform-shell-plugin' ) . '</h1>' .
			'<p>' . esc_html( $message ) . '</p>',
			403
		);
	}

	/**
	 * Méthode pour retourner le capability par défaut.
	 *
	 * @param string $capability    Nom de la capability.
	 * @return string
	 */
	public function default_page_capability_handler( $capability ) {
		/*
		 * Par défaut $capability est manage_option, on veut retourner un capability plus précis.
		 * Puisqu'on ne veut pas donner manage_option aux gestionnaires.
		 * On pourrait avoir une granularité par page mais pour simplifier toutes les pages dans leur ensemble ne sont pas restreintes.
		 * Les options pour lesquelles on veut limiter l'accès à admin seront filtrés à l'affichage.
		 */
		return 'platform_shell_cap_manage_basic_options';
	}

	/**
	 * Méthode de rendu par défaut (callback).
	 */
	public function default_page_renderer_callback() {
		echo '<div class="wrap">';
		$this->settings_api->show_navigation();
		$this->settings_api->show_forms();
		echo '</div>';
	}

	/**
	 * Méthode pour récupérer toutes les pages de configurations.
	 *
	 * @return array page names with key value pairs
	 */
	public function get_pages() {
		$pages         = get_pages();
		$pages_options = array();
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$pages_options[ $page->ID ] = $page->post_title;
			}
		}

		return $pages_options;
	}
}
