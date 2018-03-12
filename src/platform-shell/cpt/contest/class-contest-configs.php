<?php
/**
 * Platform_Shell\CPT\Contest\Contest_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Contest;

use Platform_Shell\CPT\CPT_Configs;

/**
 * Contest_Configs class.
 */
class Contest_Configs extends CPT_Configs {

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

		$this->metadata_prefix       = $this->base_metadata_prefix . 'contest_';
		$this->post_type_name        = 'contest';
		$this->post_type_name_plural = 'contests';

		$this->labels = [
			'name'               => _x( 'Concours', 'cpt-contests-labels-plural', 'platform-shell-plugin' ),
			'singular_name'      => _x( 'Concours', 'cpt-contests-labels-singular', 'platform-shell-plugin' ),
			'menu_name'          => _x( 'Concours', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'name_admin_bar'     => _x( 'Concours', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'add_new'            => _x( 'Ajouter', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'add_new_item'       => _x( 'Ajouter un concours', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'new_item'           => _x( 'Nouveau concours', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'edit_item'          => _x( 'Éditer une fiche concours', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'view_item'          => _x( 'Voir les concours', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'all_items'          => _x( 'Tous les concours', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'search_items'       => _x( 'Chercher un concours', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'parent_item'        => _x( 'Concours parent', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'parent_item_colon'  => _x( 'Concours parent :', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'not_found'          => _x( 'Aucun concours.', 'cpt-contests-labels', 'platform-shell-plugin' ),
			'not_found_in_trash' => _x( 'Il n’y a aucun concours dans la corbeille.', 'cpt-contests-labels', 'platform-shell-plugin' ),
		];

		$this->tags_taxonomy_name = 'platform_shell_tax_contest_tags'; /* < 32 char limit. */
	}
}
