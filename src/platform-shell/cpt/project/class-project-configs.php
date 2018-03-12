<?php
/**
 * Platform_Shell\CPT\Project\Project_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Project;

use Platform_Shell\CPT\CPT_Configs;

/**
 * Platform_Shell Project_Configs
 *
 * @class    Project_Configs
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Project_Configs extends CPT_Configs {

	/**
	 * Nom de la taxonomie pour la catégorie
	 *
	 * @var string
	 */
	public $category_taxonomy_name;

	/**
	 * Nom de la taxonomie pour les tags
	 *
	 * @var string
	 */
	public $tags_taxonomy_name;

	/**
	 * Constructeur.
	 */
	public function __construct() {

		parent::__construct();

		$this->metadata_prefix       = $this->base_metadata_prefix . 'project_';
		$this->post_type_name        = 'project';
		$this->post_type_name_plural = 'projects';

		$this->labels = [
			'name'               => _x( 'Projets', 'cpt-project-labels-plural', 'platform-shell-plugin' ),
			'singular_name'      => _x( 'Projet', 'cpt-project-labels-singular', 'platform-shell-plugin' ),
			'menu_name'          => _x( 'Projets', 'cpt-project-labels', 'platform-shell-plugin' ),
			'name_admin_bar'     => _x( 'Projet', 'cpt-project-labels', 'platform-shell-plugin' ),
			'add_new'            => _x( 'Ajouter', 'cpt-project-labels', 'platform-shell-plugin' ),
			'add_new_item'       => _x( 'Ajouter un projet', 'cpt-project-labels', 'platform-shell-plugin' ),
			'new_item'           => _x( 'Nouveau projet', 'cpt-project-labels', 'platform-shell-plugin' ),
			'edit_item'          => _x( 'Éditer une fiche projet', 'cpt-project-labels', 'platform-shell-plugin' ),
			'view_item'          => _x( 'Voir les projets', 'cpt-project-labels', 'platform-shell-plugin' ),
			'all_items'          => _x( 'Tous les projets', 'cpt-project-labels', 'platform-shell-plugin' ),
			'search_items'       => _x( 'Chercher un projet', 'cpt-project-labels', 'platform-shell-plugin' ),
			'parent_item'        => _x( 'Projet parent', 'cpt-project-labels', 'platform-shell-plugin' ),
			'parent_item_colon'  => _x( 'Projet parent :', 'cpt-project-labels', 'platform-shell-plugin' ),
			'not_found'          => _x( 'Aucun projet trouvé.', 'cpt-project-labels', 'platform-shell-plugin' ),
			'not_found_in_trash' => _x(
				'Il n’y a aucun projet dans la corbeille.',
				'cpt-project-labels',
				'platform-shell-plugin'
			),
		];

		/*
		 * Attention : nom = 32 char limit.
		 */
		$this->category_taxonomy_name = 'platform_shell_tax_proj_cat'; /* < 32 char limit. */
		$this->tags_taxonomy_name     = 'platform_shell_tax_proj_tags'; /* < 32 char limit. */
	}
}
