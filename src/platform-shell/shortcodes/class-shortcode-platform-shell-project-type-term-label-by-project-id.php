<?php
/**
 * Platform_Shell\Shortcodes\Shortcode_Platform_Shell_Project_Type_Term_Label_By_Project_Id
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Shortcodes;

use \Platform_Shell\CPT\Project\Project_Taxonomy_Category;

/**
 * Classe Shortcode pour récupérer le label d'un terme type de projet par id de projet.
 *
 * @class    Shortcode_Platform_Shell_Project_Type_Term_Label_By_Project_Id
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Shortcode_Platform_Shell_Project_Type_Term_Label_By_Project_Id {

	/**
	 * Instance de Project_Taxonomy_Category (DI).
	 *
	 * @var Project_Taxonomy_Category
	 */
	private $project_taxonomy_category;

	/**
	 * Constructeur.
	 *
	 * @param Project_Taxonomy_Category $project_taxonomy_category    Instance de Project_Taxonomy_Category (DI).
	 */
	public function __construct( Project_Taxonomy_Category $project_taxonomy_category ) {
		$this->project_taxonomy_category = $project_taxonomy_category;
	}

	/**
	 * Méthode run
	 *
	 * @param  array $atts    Attributs du shortcode.
	 * @return string         Données résultante du shortcode.
	 * @throws \Exception     Exception lorsque qu'il y a un problème d'exécution du shortcode.
	 */
	public function run( $atts ) {
		if ( isset( $atts['id'] ) ) {
			$project_id = $atts['id'];

			/* todo_refactoring_get_cat_term?: getter du term dans project. */
			$terms = wp_get_post_terms( $project_id, 'platform_shell_tax_proj_cat' );
			/* Il devrait toujours y en avoir un seul. */
			foreach ( $terms as $term ) {
				$term_name = $term->name;
			}
			if ( isset( $term_name ) ) {
				return $this->project_taxonomy_category->get_term_label( $term_name );
			} else {
				return '';
			}
		} else {
			throw new \Exception( 'Missing id for Shortcode_Platform_Shell_Project_Type_Label_By_Project_Id.' );
		}
	}

}
