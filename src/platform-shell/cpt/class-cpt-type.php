<?php
/**
 * Platform_Shell\CPT\CPT_Type
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT;

use Platform_Shell\Roles_Configs;
use WP_Post;

/**
 * CPT_Type
 *
 * @class        CPT_Type
 * @description  Classe de base pour les définitions de post types personalisées.
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
abstract class CPT_Type {

	/**
	 * Configuration des labels de cpt.
	 *
	 * @var array
	 */
	public $labels;

	/**
	 * Une instance des paramètres de configuration du post type.
	 *
	 * @var CPT_Configs
	 */
	public $configs;

	/**
	 * Une instance des paramètres de configuration des metaboxes.
	 *
	 * @var null|CPT_Metaboxes
	 */
	protected $metaboxes;

	/**
	 * Détermine si les commentaires sont activés sur le post type.
	 *
	 * @var boolean
	 */
	protected $comments_option_checked_on_add;

	/**
	 * Une instance des paramètre des différents roles assignée au post type.
	 *
	 * @var Roles_Configs
	 */
	protected $roles_configs;

	/**
	 * Instance de la classe helper pour les différents types de contenus.
	 *
	 * @var CPT_Helper
	 */
	protected $cpt_helper;

	/**
	 * Identifiant du nonce à être utilisé dans le post type.
	 *
	 * @var string
	 */
	private $nonce_id = 'cpt_nonce';

	/**
	 * Action à appeler lors de la sauvegarde des metaboxes.
	 *
	 * @var string
	 */
	private $nonce_save_metabox_action = 'save_meta_box';

	/**
	 * Constructeur
	 *
	 * @param CPT_Configs   $configs                           Une instance des paramètres de configuration du post type.
	 * @param Roles_Configs $roles_config                      Une instance des paramètre des différents roles assignée au post type.
	 * @param CPT_Helper    $cpt_helper                        Instance de la classe helper pour les différents types de contenus.
	 * @param CPT_Metaboxes $metaboxes                         Une instance des paramètres de configuration des metaboxes.
	 * @param boolean       $comments_option_checked_on_add    Détermine si les commentaires sont activés sur le post type.
	 */
	public function __construct( CPT_Configs $configs, Roles_Configs $roles_config, CPT_Helper $cpt_helper, CPT_Metaboxes $metaboxes = null, $comments_option_checked_on_add = true ) {

		$this->configs                        = $configs;
		$this->metaboxes                      = $metaboxes;
		$this->comments_option_checked_on_add = $comments_option_checked_on_add;
		$this->roles_configs                  = $roles_config;
		$this->cpt_helper                     = $cpt_helper;

		$this->cpt_helper->init_config( $configs );

		add_filter( 'wp_insert_post_data', array( &$this, 'on_insert_post_data' ) );
	}

	/**
	 * Méthode Init
	 *
	 * Cette méthode initialize les paramètres par défaut pour les métaboxes.
	 */
	protected function init() {

		// S'assurer que le callback soit appelé pour le post type visé seulement.
		if ( isset( $this->metaboxes ) ) {

			$cpt_add_meta_boxes_name = 'add_meta_boxes_' . $this->configs->post_type_name;

			add_action( 'submitpost_box', array( &$this, 'add_nonce_save_metabox' ) );

			add_action( $cpt_add_meta_boxes_name, array( &$this, 'add_meta_boxes_callback' ) );
		}

		// Nous ajoutons l'appel à la fonction "add_author_filters" après que les dépendances soient chargées.
		add_action( 'current_screen', [ &$this, 'add_author_filters' ] );
	}

	/**
	 * Méthode add_author_filters
	 *
	 * Cette méthode ajoute les filtres nécessaires pour le métabox d'édition du post type
	 */
	public function add_author_filters() {

		$screen = get_current_screen();

		if ( ! ( empty( $screen->post_type ) || $this->configs->post_type_name !== $screen->post_type ) ) {

			add_filter( 'wp_dropdown_users_args', [ &$this, 'modify_author_dropdown' ], 10, 2 );
		}

	}

	/**
	 * Méthode modify_author_dropdown
	 *
	 * Cette méthode modifie la liste des auteurs à afficher, en s'assurant que les admins, les gestionnaires et les
	 * utilisateurs soient inclus dans la liste.
	 *
	 * @param array $args      : Les arguments passés à la méthode.
	 * @param array $reference : Une référence au dropdown à modifier.
	 *
	 * @see https://themehybrid.com/weblog/correcting-the-author-meta-box-drop-down
	 * @see https://developer.wordpress.org/reference/functions/add_filter/#more-information
	 */
	public function modify_author_dropdown( array $args, array $reference ) {

		global $wp_roles, $post;

		if ( 'post_author_override' === $reference['name'] && $this->configs->post_type_name === $post->post_type ) {

			$args['who']      = '';
			$args['role__in'] = $this->roles_configs->get_roles();
		}

	}

	/**
	 * Méthode add_nonce_save_metabox
	 *
	 * @param WP_Post $post    Le post auquel les métaboxes sont associées.
	 */
	public function add_nonce_save_metabox( WP_Post $post ) {

		if ( $post->post_type === $this->configs->post_type_name ) {

			wp_nonce_field( $this->nonce_save_metabox_action, $this->nonce_id );
		}

	}

	/**
	 * Méthode add_meta_boxes_callback
	 *
	 * @param WP_Post $post    Le post auquel les métaboxes sont associées.
	 */
	public function add_meta_boxes_callback( WP_Post $post ) {

		$this->metaboxes->add_cpt_meta_boxes( $post );
	}

	/**
	 * Méthode on_insert_post_data.
	 *
	 * @param array $data    An array of slashed post data (https://codex.wordpress.org/Plugin_API/Filter_Reference/wp_insert_post_data).
	 * @return array
	 */
	public function on_insert_post_data( array $data ) {

		$data = $this->set_permalink( $data );
		$data = $this->set_autorized_comment( $data );

		return $data;
	}

	/**
	 * Méthode set_permalink
	 *
	 * @param array $data    An array of slashed post data (https://codex.wordpress.org/Plugin_API/Filter_Reference/wp_insert_post_data).
	 * @return array
	 */
	private function set_permalink( array $data ) {

		// https://wordpress.stackexchange.com/questions/52896/force-post-slug-to-be-auto-generated-from-title-on-save
		// Si utilisation de la gestion de révision, on veut regénérer le permalien (tout les cpt types ne permettent pas l'édition de permaliens).
		if ( $data['post_type'] === $this->configs->post_type_name ) {
			if ( ! in_array( $data['post_status'], array( 'draft', 'pending', 'auto-draft' ) ) ) {
				$data['post_name'] = sanitize_title( $data['post_title'] );
			}
		}

		return $data;
	}

	/**
	 * Méthode set_autorized_comment
	 *
	 * @param array $data    An array of slashed post data (https://codex.wordpress.org/Plugin_API/Filter_Reference/wp_insert_post_data).
	 * @return array
	 */
	private function set_autorized_comment( array $data ) {

		// Le callback va être appelé une fois pour chaque post type défini mais c'est acceptable puisque ce filtre.
		// Ne va être exécuté qu'à la création. Il y pourrait y avoir une autre approche mais c'est la solution la plus simple.
		// Il ne semble pas possible d'ajouter l'option dans les configurations admin existantes (recherche supplémentaire requise).
		if ( $data['post_type'] === $this->configs->post_type_name && 'auto-draft' === $data['post_status'] ) {

			if ( $this->comments_option_checked_on_add ) {

				$data['comment_status'] = 'open';
			} else {

				$data['comment_status'] = 'closed';
			}
		}

		return $data;
	}

	/**
	 * Méthode register_taxonomies
	 */
	public function register_taxonomies() {

		/* nothing. */
	}

	/**
	 * Méthode install_taxonomies
	 */
	public function install_taxonomies() {

		/* nothing. */
	}

	/**
	 * Méthode uninstall_taxonomies
	 */
	public function uninstall_taxonomies() {

		/* nothing. */
	}

	/**
	 * Méthode register_for_metabox_save
	 */
	public function register_for_metabox_save() {

		// Enregistrement metabox sur post save.
		add_action( 'save_post', array( &$this, 'save_post_for_metabox_save_callback' ), 10, 3 );
	}

	/**
	 * Méthode unregister_for_metabox_save
	 */
	public function unregister_for_metabox_save() {

		remove_action( 'save_post', array( &$this, 'save_post_for_metabox_save_callback' ), 10, 3 );
	}

	/**
	 * Méthode save_post_for_metabox_save_callback
	 *
	 * @param integer $post_id    Identifiant du post.
	 * @param WP_Post $post       Instance du post.
	 * @param boolean $update     Determination si le post est existant.
	 * @return integer
	 */
	public function save_post_for_metabox_save_callback( $post_id, $post, $update ) {

		/* COMMMON SAVE / VALIDATIONS : */

		// Sortir si le post sauvegardé ne correspond pas au post type courant.
		if ( $post->post_type !== $this->configs->post_type_name ) {

			return $post_id;
		}

		$post_status = get_post_status( $post_id );
		// Ignorer état auto-draft (création initiale) et trash.
		if ( 'auto-draft' === $post_status || 'trash' === $post_status ) {

			return $post_id;
		}

		// check autosave. Doit être absolument être appelé avec le check_admin_referer!
		// Sur autosave, les données de nonce et fields de metabox ne sont pas envoyés, il faut donc sortir du traitement.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {

			return $post_id;
		}

		// Check nonce.
		if ( ! check_admin_referer( $this->nonce_save_metabox_action, $this->nonce_id ) ) {

			return $post_id;
		}

		// SPECIFIC SAVE.
		if ( method_exists( $this, 'save_meta_box' ) ) {

			$this->unregister_for_metabox_save(); /* Éviter appel récursif lors du cancel publish. */
			$this->save_meta_box( $post_id, $post, $update );
			$this->register_for_metabox_save(); /* Éviter appel récursif lors du cancel publish. */
		}

	}

	/**
	 * Méthode get_capabilities
	 *
	 * @return string[]
	 */
	public function get_capabilities() {

		$plural_name = $this->configs->post_type_name_plural;

		$vars = array(
			'{$plural_name}' => $plural_name,
		);

		$capabilities = [
			'delete_others_{$plural_name}',
			'delete_private_{$plural_name}',
			'delete_{$plural_name}',
			'delete_published_{$plural_name}',
			'edit_others_{$plural_name}',
			'edit_private_{$plural_name}',
			'edit_{$plural_name}',
			'edit_published_{$plural_name}',
			'publish_{$plural_name}',
			'read_private_{$plural_name}',
		];

		foreach ( $capabilities as &$str ) {

			$str = strtr( $str, $vars );
		}

		return $capabilities;
	}
}
