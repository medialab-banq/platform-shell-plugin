<?php
/**
 * Platform_Shell\Widgets\Project_Category_Filter
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Widgets;

use \Platform_Shell\CPT\Project\Project_Taxonomy_Category;
use \Platform_Shell\CPT\Project\Project_Configs;

/**
 * Classe pour gérer l'affichage du Widget de type de projet et permettant un pseudo-filtre par type de projet.
 *
 * @class    Project_Category_Filter
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Project_Category_Filter extends \WP_Widget {

	/**
	 * Instance de Project_Taxonomy_Category (DI).
	 *
	 * @var Project_Taxonomy_Category
	 */
	private $project_taxonomy_category;

	/**
	 * Instance de Project_Configs (DI).
	 *
	 * @var Project_Configs
	 */
	private $project_configs;

	/**
	 * Constructeur.
	 *
	 * @param Project_Taxonomy_Category $project_taxonomy_category    Instance de Project_Taxonomy_Category (DI).
	 * @param Project_Configs           $project_configs                        Instance de Project_Configs (DI).
	 */
	public function __construct( Project_Taxonomy_Category $project_taxonomy_category, Project_Configs $project_configs ) {

		$this->project_taxonomy_category = $project_taxonomy_category;
		$this->project_configs           = $project_configs;

		parent::__construct( 'platform_shell_option_project_category_filter', _x( 'Filtre des projets par catégories', 'widget-project-category-filter-description', 'platform-shell-plugin' ), array( 'description' => _x( 'Widget pour filtrer les projets par catégories', 'widget-project-category-filter-description', 'platform-shell-plugin' ) ) );
	}

	/**
	 * Préparation du Widget.
	 *
	 * @param type $args         Paramètre d'isntanciation du widget.
	 * @param type $instance     Instance de widget.
	 */
	public function widget( $args, $instance ) {
		$this->render_widget_body();
	}

	/**
	 * Méthode de rendu du Widget.
	 */
	private function render_widget_body() {

		$projects_cat = $this->get_project_categories();

		echo ' <div><h2>' . esc_html( _x( 'Types', 'widget-project-category-filter-description', 'platform-shell-plugin' ) ) . '</h2><ul>';

		$project_post_type_name = $this->project_configs->post_type_name;
		$post_type_data         = get_post_type_object( $project_post_type_name );
		$post_type_slug         = $post_type_data->rewrite['slug'];

		$projects_cat_link_label = [];
		foreach ( $projects_cat as $cat ) {
			$label                                    = $this->project_taxonomy_category->get_term_label( $cat->name );
			$projects_cat_link_label[ $cat->term_id ] = $label;
		}

		// Trier alphabétiquement.
		asort( $projects_cat_link_label );

		echo '<li><a href="' . esc_attr( site_url( '/' . $post_type_slug . '/' ) ) . '">' . esc_html( _x( 'Tous', 'widget-project-category-filter-description', 'platform-shell-plugin' ) ) . '</a></li>';

		// Change : add to array, sort and output.
		foreach ( $projects_cat_link_label as $term_id => $label ) {
			$link = get_term_link( $term_id );
			echo '<li><a href="' . esc_attr( $link ) . '">' . esc_html( $label ) . '</a></li>';
		}

		echo '</ul></div>';
	}

	/**
	 * Méthode pour récupérer les catégories connues.
	 *
	 * @return type
	 */
	private function get_project_categories() {

		$terms = get_terms(
			array(
				/* Attention. Ne pas faire tri alpha ici. Name = clé identifiante. */
				'taxonomy'   => 'platform_shell_tax_proj_cat',
				'hide_empty' => false,
			)
		);

		return $terms;
	}
}
