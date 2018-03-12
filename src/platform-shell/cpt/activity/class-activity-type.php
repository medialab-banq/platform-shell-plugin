<?php
/**
 * Platform_Shell\CPT\Activity\Activity_Type
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Activity;

use Platform_Shell\Roles_Configs;
use Platform_Shell\CPT\CPT_Helper;
use Platform_Shell\CPT\CPT_Type;
use Exception;

/**
 * Activity_Type
 *
 * @class    Activity_Type
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Activity_Type extends CPT_Type {

	/**
	 * Liste des champs de métadonnées
	 *
	 * @var array
	 */
	private $activity_metadata_fields = [];

	/**
	 * Constructeur.
	 *
	 * @param Activity_Configs   $configs      Configuration du post type des activités.
	 * @param Roles_Configs      $roles_config Configuration des rôles.
	 * @param CPT_Helper         $cpt_helper   Classe helper pour les post types personalisés.
	 * @param Activity_Metaboxes $metaboxes    Métaboxes.
	 */
	public function __construct( Activity_Configs $configs, Roles_Configs $roles_config, CPT_Helper $cpt_helper, Activity_Metaboxes $metaboxes ) { // phpcs:ignore Generic --PHPCS ne prends pas compte l'injection de paramètres.
		parent::__construct( $configs, $roles_config, $cpt_helper, $metaboxes );
	}

	/**
	 * Méthode get_admissibility_list
	 *
	 * @return array
	 */
	private function get_admissibility_list() {
		return $this->cpt_helper->get_simple_select_list_from_option( 'platform_shell_option_contests_admissibility_list', 'platform-shell-settings-page-site-sections-general', '' );
	}

	/**
	 * Méthode set_activity_meta
	 */
	public function set_activity_meta() {
		$this->activity_metadata_fields = [
			[
				'label'   => _x( 'Admissibilité', 'cpt-activity-metaboxes', 'platform-shell-plugin' ),
				'desc'    => 'Les valeurs permises peuvent être modifiées dans les écrans de configurations de la plateforme.',
				'id'      => $this->configs->metadata_prefix . 'admissibility',
				'type'    => 'select',
				'require' => 'true',
				'options' => $this->get_admissibility_list(),
			],
			[
				'label'   => _x( 'Date', 'cpt-activity-metaboxes', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'date',
				'type'    => 'date',
				'require' => 'true',
				'filter'  => [ '\Platform_Shell\PlatformShellDateTime', 'date_filter' ],
			],
			[
				'label'   => _x( 'Heure de l’activité', 'cpt-activity-metaboxes', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'hour',
				'type'    => 'text',
				'require' => 'true',
				'filter'  => false,
			],
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Platform_Shell\CPT\CPT_Type::init()
	 */
	public function init() {
		add_action( 'init', array( &$this, 'register_post_type' ) );

		$this->register_for_metabox_save();

		parent::init();
	}

	/**
	 * Enregister le posttype.
	 *
	 * @throws Exception Lors de la redéfinition d'un post type existant.
	 */
	public function register_post_type() {

		$post_type_name        = $this->configs->post_type_name;
		$post_type_name_plural = $this->configs->post_type_name_plural;

		if ( post_type_exists( $post_type_name ) ) {
			throw new Exception( 'Redéfinition d’un CPT existant.' );
		}

		$args = array(
			'labels'             => $this->configs->labels,
			'description'        => _x( 'Activités.', 'cpt-activity-description', 'platform-shell-plugin' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => _x( 'activites', 'cpt-activity-slug', 'platform-shell-plugin' ) ),
			'capability_type'    => array( $post_type_name, $post_type_name_plural ),
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'comments', 'revisions', 'excerpt' ),
			'query_var'          => true,
			'can_export'         => true,
			'taxonomies'         => array( 'category' ),
		);

		register_post_type( $post_type_name, $args );

		$this->set_activity_meta();

		$this->metaboxes->set_temporary_fix_activity_metadata( $this->activity_metadata_fields );
	}

	/**
	 *  Note : solution de transition. Code de save devrait être au même endroit pour tous les types.
	 *
	 * @param integer  $post_id ID de l'activité.
	 * @param \WP_Post $post    L'instance de l'activité.
	 * @param bool     $update  Si nous mettons à jour une activité existante.
	 */
	public function save_meta_box( $post_id, $post, $update ) {
		$this->metaboxes->save( $post_id, $post, $update );
	}
}
