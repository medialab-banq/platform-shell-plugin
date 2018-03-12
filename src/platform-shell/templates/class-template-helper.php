<?php
/**
 * Platform_Shell\Templates\Template_Helper
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Templates;

/**
 * Classe pour rassembler les méthodes utilitaires de "template" (implémentaiton adhoc minimale).
 *
 * @class    Template_Helper
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Template_Helper {

	/**
	 * Valeur du path du plugin.
	 *
	 * @var string    Valeur de path du plugin (DI).
	 */
	private $plugin_path;

	/**
	 * Constructeur.
	 *
	 * @param type $plugin_path    Path du plugin (DI).
	 */
	public function __construct( $plugin_path ) {
		$this->plugin_path = $plugin_path;
	}

	/**
	 * Méthode pour récupérer un template rendu dans le contexte désiré.
	 *
	 * @param string     $template_name    Nom du template.
	 * @param array|null $args         Arguments pour créer contexte.
	 * @return string
	 */
	public function get_template( $template_name, $args = array() ) {

		if ( ! empty( $args ) && is_array( $args ) ) {
			// phpcs:ignore -- Il y a un risque mais les utilisations actuelles sont correctes. Il faudrait choisir une solution de templating pour corriger le problème.
			extract( $args );
		}

		$located = self::locate_template( $template_name );

		ob_start(); // turn on output buffering.
		include $located;
		$res = ob_get_contents(); // get the contents of the output buffer.
		ob_end_clean();
		return $res;
	}

	/**
	 * Méthode pour récupérer le path d'un template.
	 *
	 * @param string $template_name     Nom du template.
	 * @return string
	 */
	public function locate_template( $template_name ) {
		$templates_path = $this->plugin_path . '/src/platform-shell/templates/';

		// Modification: Get the template from this plugin, if it exists.
		if ( file_exists( $templates_path . $template_name ) ) {
			$template = $templates_path . $template_name;
		} else {
			// Look within passed path within the theme - this is priority.
			$template = locate_template( array( $templates_path . $template_name, $template_name ) );
		}

		return $template;
	}
}
