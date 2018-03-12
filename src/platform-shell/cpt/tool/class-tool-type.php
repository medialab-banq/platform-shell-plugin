<?php
/**
 * Platform_Shell\CPT\Tool\Tool_Type
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Tool;

use Platform_Shell\Roles_Configs;
use Platform_Shell\CPT\CPT_Helper;
use Platform_Shell\CPT\CPT_Type;

/**
 * Platform_Shell Tools_Type
 *
 * @class    Tools_Type
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Tool_Type extends CPT_Type {

	/**
	 * Constructor
	 *
	 * @param Tool_Configs  $configs         Une instance des paramètres de configuration du post type.
	 * @param Roles_Configs $roles_config    Une instance des paramètre des différents roles assignée au post type.
	 * @param CPT_Helper    $cpt_helper      Instance de la classe helper pour les différents types de contenus.
	 */
	public function __construct( Tool_Configs $configs, Roles_Configs $roles_config, CPT_Helper $cpt_helper ) { // phpcs:ignore Generic --PHPCS ne prends pas compte l'injection de paramètres.
		parent::__construct( $configs, $roles_config, $cpt_helper );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Platform_Shell\CPT\CPT_Type::init()
	 */
	public function init() {
		add_action( 'init', [ &$this, 'register_post_type' ] );
	}

	/**
	 * Méthode register_post_type
	 * Enregister le posttype.
	 *
	 * @throws \Exception    Lorsque l'on redéfini un post type existant.
	 */
	public function register_post_type() {

		$post_type_name        = $this->configs->post_type_name;
		$post_type_name_plural = $this->configs->post_type_name_plural;

		// Boiler plate code..
		if ( post_type_exists( $post_type_name ) ) {
			throw new \Exception( 'Redéfinition d’un CPT existant.' );
		}

		$args = [
			'labels'             => $this->configs->labels,
			'description'        => _x( 'Description.', 'cpt-tool-description', 'platform-shell-plugin' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => [ 'slug' => _x( 'outils', 'cpt-tool-slug', 'platform-shell-plugin' ) ],
			'capability_type'    => [ $post_type_name, $post_type_name_plural ],
			'map_meta_cap'       => true,
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'supports'           => [ 'title', 'editor', 'author', 'thumbnail', 'revisions', 'comments' ],
			'query_var'          => true,
			'can_export'         => true,
			'taxonomies'         => [ 'category' ],
		];

		register_post_type( $post_type_name, $args );
	}
}
