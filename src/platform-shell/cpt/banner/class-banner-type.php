<?php
/**
 * Platform_Shell\CPT\Banner\Banner_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2017 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Banner;

use Platform_Shell\Roles_Configs;
use Platform_Shell\CPT\CPT_Helper;
use Platform_Shell\CPT\CPT_Type;
use Exception;

/**
 * Platform_Shell Banner_Type
 *
 * @class    Banner_Type
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Banner_Type extends CPT_Type {

	/**
	 * Constructeur.
	 *
	 * @param Banner_Configs $configs         Une instance des paramètres de configuration du post type.
	 * @param Roles_Configs  $roles_config    Une instance des paramètre des différents roles assignée au post type.
	 * @param CPT_Helper     $cpt_helper      Instance de la classe helper pour les différents types de contenus.
	 */
	public function __construct( Banner_Configs $configs, Roles_Configs $roles_config, CPT_Helper $cpt_helper ) { // phpcs:ignore Generic --PHPCS ne prends pas compte l'injection de paramètres.
		parent::__construct( $configs, $roles_config, $cpt_helper );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Platform_Shell\CPT\CPT_Type::init()
	 */
	public function init() {
		add_action( 'init', [ &$this, 'register_post_type' ] );
		add_action( 'admin_notices', [ &$this, 'display_pseudo_inline_help' ] );
	}

	/**
	 * Méthode display_pseudo_inline_help
	 */
	public function display_pseudo_inline_help() {
		$screen = get_current_screen();

		// If not on the screen with ID 'edit-post' abort.
		if ( 'edit-banner' === $screen->id ) {
			?>
			<div class="notice notice-info">
				<p><?php _ex( '<strong>Configurations des bannières de la plateforme</strong>.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?></p>
				<p><?php _ex( '1) Ajouter une bannière en utilisant le bouton dans le haut de l’écran ou modifier les bannières existantes.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?></p>
				<p><?php _ex( '2) Assigner le titre de la bannière en respectant la nomenclature (voir plus bas).', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?></p>
				<p><?php _ex( '3) Assigner une « <strong>Image mise en avant</strong> ». La taille  recommandée pour les images est de 1400 x 340 pixels pour la bannière « banner_front_page » et de 1400 x 240 pixels pour les autres.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?></p>
				<p><?php _ex( '4) Publier ou mettre à jour les changements et valider les changements en consultant le site.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?></p>
				<p><?php _ex( 'Notes supplémentaires :', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?></p>
				<p><?php _ex( '- Vous pouvez supprimer les bannières inutilisées, mais il est fortement recommandé de conserver au minimum les bannières « <strong>banner_front_page</strong> » et « <strong>banner_default</strong> ».', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '- La détection de l’association de bannière se fait dans cet ordre : banner_post_id_{id numérique}, banner_{type} (post, page, project, etc.), banner_default.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '- Une bannière vide est affichée lorsque la plateforme ne trouve pas d’association valide.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/></p>

				<p><?php _ex( 'Nomenclature des titres de bannière à utiliser pour l’association :', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?></p>
				<p><?php _ex( '<strong>banner_default</strong> : La bannière affichée par défaut lorsqu’il n’y a pas d’autres associations plus spécifiques.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '<strong>banner_front_page</strong> : La bannière utilisée spécifiquement pour la page d’accueil (taille plus grande).', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '<strong>banner_post</strong> : La bannière utilisée pour les articles.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '<strong>banner_page</strong> : La bannière utilisée pour les pages.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '<strong>banner_project</strong> : La bannière utilisée pour les projets.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '<strong>banner_contest</strong> : La bannière utilisée pour les concours. ', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '<strong>banner_activity</strong> : La bannière utilisée pour les activités.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '<strong>banner_tool</strong> : La bannière utilisée pour les outils numériques.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php _ex( '<strong>banner_equipment</strong> : La bannière utilisée pour les équipements.', 'cpt-banner-inline-doc', 'platform-shell-plugin' ); ?><br/>
				<?php
				_ex(
					// phpcs:ignore Generic.Files.LineLength.TooLong
					'<strong>banner_post_id_{id numérique}</strong> : La bannière utilisée pour n’importe lequel type de « post », en utilisant son id.<br/> - Pour récupérer l’id numérique d’un « post », ouvrir le « post » dans l’interface d’administration et repérer son celui-ci dans l’url, par exemple « .../wp-admin/post.php?post=<strong>93</strong>&action=edit ». Le titre de la bannière devrait alors être « <strong>banner_post_id_93</strong> » pour compléter l’association au « post ».',
					'cpt-banner-inline-doc',
					'platform-shell-plugin'
				);
				?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Méthode register_post_type
	 *
	 * Enregistre le post type
	 *
	 * @throws Exception    Lorsque l'on redéfini un post type existant.
	 */
	public function register_post_type() {
		$post_type_name        = $this->configs->post_type_name;
		$post_type_name_plural = $this->configs->post_type_name_plural;

		// Boiler plate code..
		if ( post_type_exists( $post_type_name ) ) {
			throw new Exception( 'Redéfinition d’un CPT existant.' );
		}

		$args = [
			'labels'              => $this->configs->labels,
			'description'         => _x( 'Description.', 'cpt-banner-description', 'platform-shell-plugin' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => [
				'slug' => _x( 'bannieres', 'cpt-banner-slug', 'platform-shell-plugin' ),
			],
			'capability_type'     => [
				$post_type_name,
				$post_type_name_plural,
			],
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_position'       => 35,
			'supports'            => [ 'title', 'editor', 'thumbnail', 'comments' ],
			'query_var'           => true,
			'can_export'          => true,
			'taxonomies'          => [],
			'exclude_from_search' => true,
		];

		register_post_type( $post_type_name, $args );
	}
}
