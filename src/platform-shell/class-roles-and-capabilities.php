<?php
/**
 * Platform_Shell\Roles_And_Capabilities
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell;

use Platform_Shell\CPT\Activity\Activity_Type;
use Platform_Shell\CPT\Banner\Banner_Type;
use Platform_Shell\CPT\Contest\Contest_Type;
use Platform_Shell\CPT\Equipment\Equipment_Type;
use Platform_Shell\CPT\Project\Project_Type;
use Platform_Shell\CPT\Tool\Tool_Type;
use WP_Roles;
/**
 * Roles_And_Capabilities
 *
 * @class Roles_And_Capabilities
 * @author Bibliothèque et Archives nationales du Québec (BAnQ)
 */
/**
 * Roles_And_Capabilities class.
 */
class Roles_And_Capabilities {

	/**
	 * Activity Type
	 *
	 * @var Activity_Type
	 */
	private $activity_type;

	/**
	 * Contest Type
	 *
	 * @var Contest_Type
	 */
	private $contest_type;

	/**
	 * Equipment Type
	 *
	 * @var Equipment_Type
	 */
	private $equipment_type;

	/**
	 * Project Type
	 *
	 * @var Project_Type
	 */
	private $project_type;

	/**
	 * Tool Type
	 *
	 * @var Tool_Type
	 */
	private $tool_type;

	/**
	 * Banner Type
	 *
	 * @var Banner_Type
	 */
	private $banner_type;

	/**
	 * Roles Configs
	 *
	 * @var Roles_Configs
	 */
	private $role_configs;

	/**
	 * Constructeur
	 *
	 * @param Activity_Type  $activity_type     Activity Type.
	 * @param Contest_Type   $contest_type      Contest Type.
	 * @param Equipment_Type $equipment_type    Equipment Type.
	 * @param Project_Type   $project_type      Project Type.
	 * @param Tool_Type      $tool_type         Tool Type.
	 * @param Banner_Type    $banner_type       Banner Type.
	 * @param Roles_Configs  $role_configs      Roles Configs.
	 */
	public function __construct(
		Activity_Type $activity_type,
		Contest_Type $contest_type,
		Equipment_Type $equipment_type,
		Project_Type $project_type,
		Tool_Type $tool_type,
		Banner_Type $banner_type,
		Roles_Configs $role_configs
	) {
		$this->activity_type  = $activity_type;
		$this->contest_type   = $contest_type;
		$this->equipment_type = $equipment_type;
		$this->project_type   = $project_type;
		$this->tool_type      = $tool_type;
		$this->banner_type    = $banner_type;
		$this->role_configs   = $role_configs;
	}

	/**
	 * Méthode init
	 */
	public function init() {
		add_action( 'editable_roles', array( $this, 'on_editable_roles_exclude_role' ) );
		add_filter( 'pre_option_default_role', array( $this, 'set_create_account_default_role' ) );
	}

	/**
	 * Méthode on_editable_roles_exclude_role
	 *
	 * @param array $roles    Liste des roles.
	 * @return array
	 */
	public function on_editable_roles_exclude_role( $roles ) {
		$known_roles = $this->role_configs->get_roles();
		// Enlever les rôles qui ne sont pas requis pour le Platform_Shell.
		foreach ( $roles as $key => &$value ) {
			if ( ! in_array( $key, $known_roles ) ) {
				unset( $roles[ $key ] );
			}
		}
		return $roles;
	}
	/**
	 * Creating Additional Roles.
	 */
	public function install() {
		global $wp_roles;
		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		$this->assign_admin_capabilities( $wp_roles );
		$this->assign_user_capabilities( $wp_roles );
		$this->assign_manager_capabilities( $wp_roles );
	}

	/**
	 * Méthode set_create_account_default_role
	 *
	 * @param string $default_role    Rôle par défaut.
	 * @return string
	 */
	public function set_create_account_default_role( $default_role ) {
		// Modifie le role par défaut de WordPress.
		return $this->role_configs->user_role;
	}

	/**
	 * Méthode assign_admin_capabilities
	 *
	 * @param WP_Roles $wp_roles    Roles.
	 */
	private function assign_admin_capabilities( $wp_roles ) {
		// Pour les rendre visibles.
		$wp_roles->add_cap( 'administrator', 'platform_shell_cap_manage_basic_options' );
		$wp_roles->add_cap( 'administrator', 'platform_shell_cap_manage_advanced_options' );
		$this->assign_all_cpt_capabilities( $wp_roles, $this->role_configs->admin_role );
	}

	/**
	 * Méthode assign_user_capabilities
	 *
	 * @param WP_Roles $wp_roles    Roles.
	 */
	private function assign_user_capabilities( $wp_roles ) {
		// Rôle utilisateur.
		add_role(
			$this->role_configs->user_role, _x( 'Utilisateur', 'roles', 'platform-shell-plugin' ), [
				'create_projects' => true, // Allows user to create new posts.
				'edit_projects'   => true,
			]
		);
	}

	/**
	 * Méthode assign_manager_capabilities
	 *
	 * @param WP_Roles $wp_roles    Roles.
	 */
	private function assign_manager_capabilities( $wp_roles ) {
		// Capabilities de base (articles, pages, etc.
		add_role(
			$this->role_configs->manager_role, _x( 'Gestionnaire', 'roles', 'platform-shell-plugin' ), array(
				'read'                    => true,
				'create_posts'            => true, // Allows user to create new posts.
				'edit_posts'              => true, // Allows user to edit their own posts.
				'edit_others_posts'       => true,
				'edit_private_posts'      => true,
				'edit_published_posts'    => true,
				'delete_posts'            => true,
				'publish_posts'           => true,
				'read_post'               => true,
				'read_pages'              => true,
				'edit_pages'              => true,
				'edit_published_pages'    => true,
				'edit_private_pages'      => true,
				'edit_others_pages'       => true,
				'publish_pages'           => true,
				'list_users'              => true,
				'edit_users'              => false,
				'create_projects'         => true,
				'edit_projects'           => true,
				'edit_others_projects'    => true,
				'edit_private_projects'   => true,
				'edit_published_projects' => true,
				'delete_projects'         => true,
				'publish_projects'        => true,
				'read_project'            => true,
			)
		);
		// Capabilities associés aux entités (cpt).
		$this->assign_all_cpt_capabilities( $wp_roles, $this->role_configs->manager_role );
		// Autres capabilities gestionnaire.
		$wp_roles->add_cap( $this->role_configs->manager_role, 'upload_files' );
		$wp_roles->add_cap( $this->role_configs->manager_role, 'edit_theme_options' );
		// Settings.
		$wp_roles->add_cap( $this->role_configs->manager_role, 'platform_shell_cap_manage_basic_options' );
	}

	/**
	 * Méthode assign_all_cpt_capabilities
	 *
	 * @param WP_Roles $wp_roles    Rôles.
	 * @param string   $role        Rôle.
	 */
	private function assign_all_cpt_capabilities( $wp_roles, $role ) {
		// Donner donner toutes les capabilities des CPT aux gestionnaires.
		$this->assign_capabilities( $wp_roles, $role, $this->activity_type->get_capabilities() );
		$this->assign_capabilities( $wp_roles, $role, $this->contest_type->get_capabilities() );
		$this->assign_capabilities( $wp_roles, $role, $this->equipment_type->get_capabilities() );
		$this->assign_capabilities( $wp_roles, $role, $this->project_type->get_capabilities() );
		$this->assign_capabilities( $wp_roles, $role, $this->tool_type->get_capabilities() );
		$this->assign_capabilities( $wp_roles, $role, $this->banner_type->get_capabilities() );
	}

	/**
	 * Méthode assign_all_cpt_capabilities
	 *
	 * @param WP_Roles $wp_roles    Rôles.
	 * @param string   $role        Rôle.
	 * @param string[] $caps        Liste des assign_capabilities.
	 */
	private function assign_capabilities( $wp_roles, $role, $caps ) {
		foreach ( $caps as $cap ) {
			$wp_roles->add_cap( $role, $cap );
		}
	}

	/**
	 * Méthode uninstall
	 */
	public function uninstall() {
		if ( get_role( $this->role_configs->user_role ) ) {
			remove_role( $this->role_configs->user_role );
		}
		if ( get_role( $this->role_configs->manager_role ) ) {
			remove_role( $this->role_configs->manager_role );
		}
	}
}
