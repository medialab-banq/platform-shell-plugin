<?php
/**
 * Platform_Shell\CPT\CPT_Manager
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT;

use Platform_Shell\CPT\Activity\Activity_Type;
use Platform_Shell\CPT\Banner\Banner_Type;
use Platform_Shell\CPT\Contest\Contest_Type;
use Platform_Shell\CPT\Equipment\Equipment_Type;
use Platform_Shell\CPT\Project\Project_Type;
use Platform_Shell\CPT\Tool\Tool_Type;

/**
 * CPT_Manager class
 */
class CPT_Manager {

	/**
	 * Liste des post types connus.
	 *
	 * @var array
	 */
	private $known_custom_post_types = [];

	/**
	 * Constructeur.
	 *
	 * @param Project_Type   $project_type      Post type pour les projets.
	 * @param Contest_Type   $contest_type      Post type pour les concours.
	 * @param Activity_Type  $activity_type     Post type pour les activités.
	 * @param Tool_Type      $tool_type         Post type pour les outils.
	 * @param Equipment_Type $equipment_type    Post type pour les équipements.
	 * @param Banner_Type    $banner_type       Post type pour les Bannières.
	 */
	public function __construct(
		Project_Type $project_type,
		Contest_Type $contest_type,
		Activity_Type $activity_type,
		Tool_Type $tool_type,
		Equipment_Type $equipment_type,
		Banner_Type $banner_type
	) {

		$this->known_custom_post_types['project']        = $project_type;
		$this->known_custom_post_types['contest']        = $contest_type;
		$this->known_custom_post_types['activity_type']  = $activity_type;
		$this->known_custom_post_types['tool_type']      = $tool_type;
		$this->known_custom_post_types['equipment_type'] = $equipment_type;
		$this->known_custom_post_types['banner_type']    = $banner_type;
	}

	/**
	 * Méthode init
	 */
	public function init() {
		$this->init_all_custom_post_types();
		$this->register_all_custom_post_types_taxonomies();
	}

	/**
	 * Méthode init_all_custom_post_types
	 */
	private function init_all_custom_post_types() {
		foreach ( $this->known_custom_post_types as $post_type_name => &$post_type_object ) {
			call_user_func( array( $post_type_object, 'init' ) );
		}
	}

	/**
	 * Méthode register_all_custom_post_types_taxonomies
	 */
	private function register_all_custom_post_types_taxonomies() {
		// todo_refactoring : interface + loop.. enlever dépendance id.
		// Doit être fait une seule fois, lors du "post install".
		$this->known_custom_post_types['project']->register_taxonomies();
		$this->known_custom_post_types['contest']->register_taxonomies();
	}

	/**
	 * Méthode install_taxonomies
	 */
	public function install_taxonomies() {
		$this->known_custom_post_types['project']->install_taxonomies();
	}

	/**
	 * Méthode uninstall_taxonomies
	 */
	public function uninstall_taxonomies() {
		$this->known_custom_post_types['project']->uninstall_taxonomies();
	}
}
