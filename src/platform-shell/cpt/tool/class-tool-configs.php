<?php
/**
 * Platform_Shell\CPT\Tool\Tool_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Tool;

use Platform_Shell\CPT\CPT_Configs;

/**
 * Tool_Configs class.
 */
class Tool_Configs extends CPT_Configs {

	/**
	 * Constructeur.
	 */
	public function __construct() {

		parent::__construct();

		$this->metadata_prefix       = $this->base_metadata_prefix . 'tools_';
		$this->post_type_name        = 'tool';
		$this->post_type_name_plural = 'tools';

		$this->labels = [
			'name'               => _x( 'Outils numériques', 'cpt-tools-labels-plural', 'platform-shell-plugin' ),
			'singular_name'      => _x( 'Outil numérique', 'cpt-tools-labels-singular', 'platform-shell-plugin' ),
			'menu_name'          => _x( 'Outils numériques', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'name_admin_bar'     => _x( 'Outil numérique', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'add_new'            => _x( 'Ajouter', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'add_new_item'       => _x( 'Ajouter un outil numérique', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'new_item'           => _x( 'Nouvel outil numérique', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'edit_item'          => _x( 'Éditer une fiche outil numérique', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'view_item'          => _x( 'Voir les outils numériques', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'all_items'          => _x( 'Tous les outils numériques', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'search_items'       => _x( 'Chercher un outil numérique', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'parent_item'        => _x( 'Outil numérique parent', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'parent_item_colon'  => _x( 'Outil numérique parent :', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'not_found'          => _x( 'Aucun outils numériques.', 'cpt-tools-labels', 'platform-shell-plugin' ),
			'not_found_in_trash' => _x( 'Il n’y a aucun outils numériques dans la corbeille.', 'cpt-tools-labels', 'platform-shell-plugin' ),
		];

	}
}
