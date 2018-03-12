<?php
/**
 * Platform_Shell\Shortcodes\Shortcode_Platform_Shell_Contest_Rules_Page_Content
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Shortcodes;

use \Platform_Shell\installation\Required_Pages_Manager;

/**
 * Classe Shortcode pour récupérer le texte des réglements généraux.
 *
 * @class    Shortcode_Platform_Shell_Contest_Rules_Page_Content
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Shortcode_Platform_Shell_Contest_Rules_Page_Content {

	/**
	 * Instance de Required_Pages_Manager (DI).
	 *
	 * @var Project_Taxonomy_Category
	 */
	private $required_page_manager;

	/**
	 * Const RULE_REQUIRED_PAGE_CONFIG_ID
	 */
	const RULE_REQUIRED_PAGE_CONFIG_ID = 'platform-shell-page-general-rules';

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
	 * @return string        Données résultante du shortcode
	 */
	public function run( $atts ) {
		// Indirection spéciale permet être moins fragile au renommage de page.
		$post_id = $this->required_page_manager->get_installed_page_id_by_required_page_config_id( self::RULE_REQUIRED_PAGE_CONFIG_ID );
		$content = get_post_field( 'post_content', $post_id );

		return $content;
	}

}
