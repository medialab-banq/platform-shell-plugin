<?php
/**
 * Platform_Shell\Shortcodes\Shortcode_Platform_Shell_Permalink_By_Page_Id
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Shortcodes;

use \Platform_Shell\installation\Required_Pages_Manager;

/**
 * Classe Shortcode pour récupérer le permalink d'une page son id de plateforme (required page settings).
 *
 * @class    Shortcode_Platform_Shell_Permalink_By_Page_Id
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Shortcode_Platform_Shell_Permalink_By_Page_Id {

	/**
	 * Instance de Required_Pages_Manager (DI).
	 *
	 * @var Project_Taxonomy_Category
	 */
	private $required_page_manager;

	/**
	 * Constructeur.
	 *
	 * @param Required_Pages_Manager $required_page_manager    Instance de Required_Pages_Manager (DI).
	 */
	public function __construct( Required_Pages_Manager $required_page_manager ) {
		$this->required_page_manager = $required_page_manager;
	}

	/**
	 * Méthode run
	 *
	 * @param  array $atts   Attributs du shortcode.
	 * @return string        Données résultante du shortcode.
	 * @throws \Exception    Exception lorsque qu'il y a un problème d'exécution du shortcode.
	 */
	public function run( $atts ) {
		if ( isset( $atts['id'] ) ) {
			$required_page_config_id = $atts['id'];
			$post_id                 = $this->required_page_manager->get_installed_page_id_by_required_page_config_id( $required_page_config_id );
			$permalink               = get_permalink( $post_id );

			return $permalink;
		} else {
			throw new \Exception( 'Missing id for Shortcode_Platform_Shell_Permalink_By_Page_Id.' );
		}
	}

}
