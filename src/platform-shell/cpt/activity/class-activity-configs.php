<?php
/**
 * Platform_Shell\CPT\Activity\Activity_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Activity;

use Platform_Shell\CPT\CPT_Configs;

/**
 * Activity_Configs class.
 */
class Activity_Configs extends CPT_Configs {

	/**
	 * Constructeur.
	 */
	public function __construct() {

		parent::__construct();

		$this->metadata_prefix       = $this->base_metadata_prefix . 'activity_';
		$this->post_type_name        = 'activity';
		$this->post_type_name_plural = 'activities';

		$this->labels = array(
			'name'               => _x( 'Activités', 'cpt-activity-labels-plural', 'platform-shell-plugin' ),
			'singular_name'      => _x( 'Activité', 'cpt-activity-labels-singular', 'platform-shell-plugin' ),
			'menu_name'          => _x( 'Activités', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'name_admin_bar'     => _x( 'Activité', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'add_new'            => _x( 'Ajouter', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'add_new_item'       => _x( 'Ajouter une activité', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'new_item'           => _x( 'Nouvelle activité', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'edit_item'          => _x( 'Éditer une fiche activité', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'view_item'          => _x( 'Voir les activités', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'all_items'          => _x( 'Toutes les activités', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'search_items'       => _x( 'Rechercher une activité', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'parent_item'        => _x( 'Activité parent', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'parent_item_colon'  => _x( 'Activité parent :', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'not_found'          => _x( 'Aucune activité.', 'cpt-activity-labels', 'platform-shell-plugin' ),
			'not_found_in_trash' => _x( 'Il n’y a aucune activité dans la corbeille.', 'cpt-activity-labels', 'platform-shell-plugin' ),
		);
	}
}
