<?php
/**
 * Platform_Shell\CPT\Equipment\Equipment_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Equipment;

use Platform_Shell\CPT\CPT_Configs;

/**
 * Equipment_Configs class.
 */
class Equipment_Configs extends CPT_Configs {

	/**
	 * Constructeur.
	 */
	public function __construct() {

		parent::__construct();

		$this->metadata_prefix       = $this->base_metadata_prefix . 'equipment_';
		$this->post_type_name        = 'equipment';
		$this->post_type_name_plural = 'equipments';

		$this->labels = [
			'name'               => _x( 'Équipement', 'cpt-equipment-labels-plural', 'platform-shell-plugin' ),
			'singular_name'      => _x( 'Équipement', 'cpt-equipment-labels-singular', 'platform-shell-plugin' ),
			'menu_name'          => _x( 'Équipements', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'name_admin_bar'     => _x( 'Équipement', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'add_new'            => _x( 'Ajouter', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'add_new_item'       => _x( 'Ajouter un équipement', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'new_item'           => _x( 'Nouvel équipement', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'edit_item'          => _x( 'Editer une fiche équipement', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'view_item'          => _x( 'Voir les équipements', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'all_items'          => _x( 'Tous les équipements', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'search_items'       => _x( 'Chercher un équipement', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'parent_item'        => _x( 'Outil parent', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'parent_item_colon'  => _x( 'Outil parent :', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'not_found'          => _x( 'Aucun équipement.', 'cpt-equipment-labels', 'platform-shell-plugin' ),
			'not_found_in_trash' => _x( 'Il n’y a aucun équipement dans la corbeille.', 'cpt-equipment-labels', 'platform-shell-plugin' ),
		];
	}
}
