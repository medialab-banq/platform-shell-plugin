<?php
/**
 * Platform_Shell\CPT\Project\Project_Type
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Project;

use Platform_Shell\Roles_Configs;
use Platform_Shell\UploadHelper;
use Platform_Shell\Admin\Admin_Notices;
use Platform_Shell\CPT\CPT_Helper;
use Platform_Shell\CPT\CPT_Type;
use Platform_Shell\installation\Required_Pages_Manager;
use Exception;
use Throwable;
use WP_Post;
use WP_User_Query;

/**
 * Project_Type
 *
 * @class    Project_Type
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Project_Type extends CPT_Type {

	/**
	 * Tableau contenant les définitions de chaques champs pour un projet.
	 *
	 * @var array
	 */
	public static $project_form_fields;

	/**
	 * Instance de la classe helper pour l'upload
	 *
	 * @var UploadHelper
	 */
	private $upload_helper;

	/**
	 * Instance de la définition de la taxonomie de catégories pour le projet.
	 *
	 * @var Project_Taxonomy_Category
	 */
	private $project_taxonomy_category;

	/**
	 * Indicateur de création de projet.
	 *
	 * @var boolean
	 */
	private $is_creating_project = false;

	/**
	 * Liste des erreurs détectées dans le formulaire
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 * ID du projet courant.
	 *
	 * @var integer
	 */
	private $current_project_id = null;

	/**
	 * Gestionaire des pages requises
	 *
	 * @var Required_Pages_Manager
	 */
	private $required_page_manager;

	/**
	 * Constructeur
	 *
	 * @param Project_Configs           $configs                      Une instance des paramètres de configuration du post type.
	 * @param Roles_Configs             $roles_config                 Une instance des paramètre des différents roles assignée au post type.
	 * @param CPT_Helper                $cpt_helper                   Instance de la classe helper pour les différents types de contenus.
	 * @param Project_Metaboxes         $metaboxes                    Une instance des paramètres de configuration des metaboxes.
	 * @param UploadHelper              $upload_helper                Instance de la classe helper pour l'upload.
	 * @param Project_Taxonomy_Category $project_taxonomy_category    Instance de la définition de la taxonomie de catégories pour le projet.
	 * @param Required_Pages_Manager    $required_page_manager        Instance de la classe des pages requises.
	 */
	public function __construct(
		Project_Configs $configs,
		Roles_Configs $roles_config,
		CPT_Helper $cpt_helper,
		Project_Metaboxes $metaboxes,
		UploadHelper $upload_helper,
		Project_Taxonomy_Category $project_taxonomy_category,
		Required_Pages_Manager $required_page_manager
	) {
		parent::__construct( $configs, $roles_config, $cpt_helper, $metaboxes );

		$this->upload_helper             = $upload_helper;
		$this->project_taxonomy_category = $project_taxonomy_category;
		$this->required_page_manager     = $required_page_manager;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Platform_Shell\CPT\CPT_Type::init()
	 */
	public function init() {

		add_action( 'init', [ &$this, 'init_from_hook' ] );

		add_action( 'wp_ajax_platform_shell_action_add_project', [ &$this, 'save_project_handler' ] );
		add_action( 'wp_ajax_nopriv_platform_shell_action_add_project', [ &$this, 'save_project_handler' ] );
		add_action( 'wp_ajax_platform_shell_action_subscribe_project', [ &$this, 'subscribe_project_handler' ] );
		add_action( 'wp_ajax_nopriv_platform_shell_action_subscribe_project', [ &$this, 'subscribe_project_handler' ] );

		add_action( 'session_handler', [ &$this, 'killsession' ] );

		$this->add_meta_filters();

		add_filter( 'term_link', [ &$this->project_taxonomy_category, 'project_taxonomy_term_link' ], 1, 3 );

		// Un handler pour les erreurs fatales n'est nécessaire que pour PHP < 7.
		if ( version_compare( PHP_VERSION, '7.0.0' ) < 0 ) {
			add_action( 'shutdown', [ &$this, 'fatal_error_handler' ] );
		}

		parent::init();
	}

	/**
	 * Méthode sanitize_cocreators
	 *
	 * Cette méthode enlève les utilisateurs qui ne sont pas permis par l'utilisateur
	 *
	 * @param array $data_post     Un tableau contenant l'information du projet.
	 * @param array $cocreators    Une liste des ids de co-créateurs.
	 * @return string
	 */
	private function sanitize_cocreators( $data_post, $cocreators ) {

		ini_set( 'html_errors', false );

		if ( is_array( $cocreators ) && ! empty( $cocreators ) ) {

			// Nous enlevons tous les doublons possibles.
			$cocreators = array_unique( $cocreators );

			// Nous cherchons une référence à l'ID de l'autheur du projet.
			$author_index = array_search( $data_post['post_author'], $cocreators );

			// Si nous avons trouvé une référence à l'auteur.
			if ( false !== $author_index ) {
				// Nous la supprimons.
				unset( $cocreators[ $author_index ] );
			}

			$current_value = get_post_meta( $data_post['ID'], 'platform_shell_meta_project_cocreators', true );
			$current_value = explode( ',', $current_value );

			// Nous vérifions les valeurs qui n'ont pas été modifiées pour pouvoir garder les gestionnaires,
			// lorsqu'un utilisateur fait des modifications au projet.
			$keepers = array_intersect( $current_value, $cocreators );

			$current_user = wp_get_current_user();

			// Par défaut, rechercher uniquement les usagers réguliers.
			$roles = [
				$this->roles_configs->user_role,
			];

			// Si l'utilisateur est un administrateur ou un gestionnaire.
			if (
				in_array( $this->roles_configs->admin_role, $current_user->roles ) ||
				in_array( $this->roles_configs->manager_role, $current_user->roles )
			) {
				// Autoriser l'utilisateur à ajouter des gestionnaires.
				$roles[] = $this->roles_configs->manager_role;
			}

			$user_query = new WP_User_Query(
				[
					'role__in' => $roles,
					'include'  => $cocreators,
					'orderby'  => 'display_name',
					'fields'   => [
						'ID',
					],
				]
			);

			$users = $user_query->get_results();

			array_walk(
				$users, function ( &$user ) {
					$user = $user->ID;
				}
			);

			$cocreators = array_unique( array_merge( $keepers, $users ) );

			sort( $cocreators );

			implode( ',', $cocreators );
		} else {
			$cocreators = '';
		}

		return $cocreators;
	}

	/**
	 * Méthode add_meta_filters
	 */
	private function add_meta_filters() {
		// Filters on meta.
		add_filter( 'sanitize_post_meta_platform_shell_meta_project_type', [ &$this, 'sanitize_project_type' ] );
		add_filter( 'sanitize_post_meta_platform_shell_meta_project_level', [ &$this, 'sanitize_project_level' ] );
		add_filter( 'sanitize_post_meta_platform_shell_meta_project_creation_type', [ &$this, 'sanitize_project_creation_type' ] );
		add_filter( 'sanitize_post_meta_platform_shell_meta_project_status', [ &$this, 'sanitize_project_status' ] );
		add_filter( 'bulk_actions-edit-project', [ &$this, 'remove_bulk_edit' ], 10, 2 );
		add_filter( 'post_row_actions', [ &$this, 'remove_quick_edit' ], 10, 2 );
	}

	/**
	 * Méthode remove_bulk_edit
	 *
	 * @param array $actions    Liste des actions disponible pour le post type.
	 * @return array
	 * @todo refactoring : Mettre en fonction partagée.
	 */
	public function remove_bulk_edit( array $actions ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Méthode remove_quick_edit
	 *
	 * @param array   $actions    Liste des actions disponible pour le post type.
	 * @param WP_Post $post       Liste des actions disponible pour le post type.
	 * @return array
	 * @todo refactoring : Mettre en fonction partagée.
	 */
	public function remove_quick_edit( array $actions = [], WP_Post $post = null ) {
		if ( ! is_null( $post ) && isset( $post->post_type ) && $this->configs->post_type_name === $post->post_type ) {
			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
		}
		return $actions;
	}

	/**
	 * Méthode init_from_hook
	 */
	public function init_from_hook() {

		$this->register_post_type();

		add_rewrite_tag( '%action%', '([^&]+)' );
		add_rewrite_tag( '%project_code%', '([^&]+)' );

		$edit_page_id = $this->required_page_manager->get_installed_page_id_by_required_page_config_id( 'platform-shell-page-project-edit-page' );

		/**
		 * Cette règle de réécriture utilise le code regex suivante : ^projets/([^/]*)/modifier$
		 *
		 * Nous utilisons les valeurs localisées pour "projet" et "modifier".
		 * Nous nous servons de ce regex pour extraire le shortcode pour le projet.
		 */
		add_rewrite_rule(
			'^' . _x(
				'projets',
				'cpt-project-slug',
				'platform-shell-plugin'
			) . '/([^/]*)/' . _x(
				'modifier',
				'cpt-project-rewrite',
				'platform-shell-plugin'
			) . '$',
			'index.php?page_id=' . $edit_page_id . '&project_code=$matches[1]&action=edit',
			'top'
		);

		add_action( 'platform_shell_project_edit', [ &$this, 'set_project_fields' ] );
	}

	/**
	 * Méthode register_post_type
	 *
	 * @throws Exception    Lorsque l'on redéfini un post type existant.
	 */
	public function register_post_type() {

		$post_type_name        = $this->configs->post_type_name;
		$post_type_name_plural = $this->configs->post_type_name_plural;

		if ( post_type_exists( $post_type_name ) ) {
			throw new Exception( 'Redéfinition d’un CPT existant.' );
		}

		/* Voir https://codex.wordpress.org/Function_Reference/register_post_type */
		$args = [
			'labels'              => $this->configs->labels,
			'description'         => '', /* eg. inutilisé. Les normes WordPress demandent à avoir du contenu à localiser pour utiliser les méthodes de localisation */
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'query_var'           => true,
			'rewrite'             => [
				'slug' => _x( 'projets', 'cpt-project-slug', 'platform-shell-plugin' ),
			],
			'capability_type'     => [ $post_type_name, $post_type_name_plural ],
			'map_meta_cap'        => true,
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => 25,
			'supports'            => [ 'author', 'revisions', 'comments', 'thumbnail' ],
			'query_var'           => true,
			'can_export'          => true,
			'taxonomies'          => [], /* Ne pas utiliser pour taxonomie déclaré par CPT. Utilisation register_taxonomy suffisante. */
		];

		register_post_type( $post_type_name, $args );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Platform_Shell\CPT\CPT_Type::register_taxonomies()
	 */
	public function register_taxonomies() {

		$post_type_name         = $this->configs->post_type_name;
		$tags_taxonomy_name     = $this->configs->tags_taxonomy_name;
		$category_taxonomy_name = $this->configs->category_taxonomy_name;

		register_taxonomy(
			$tags_taxonomy_name,
			$post_type_name,
			[
				'label'        => _x( 'Mots-clés', 'cpt-project-taxonomy-label', 'platform-shell-plugin' ),
				'rewrite'      => [
					'slug' => _x( 'mots-cles-projet', 'cpt-project-tags-taxonomy-slug', 'platform-shell-plugin' ),
				],
				'hierarchical' => false,
				'show_ui'      => false,
				'show_in_menu' => false,
				'query_var'    => true,
			]
		);

		register_taxonomy(
			$category_taxonomy_name, $post_type_name, [
				'label'        => _x( 'Type de projet', 'cpt-project-taxonomy-label', 'platform-shell-plugin' ),
				'rewrite'      => [
					'slug' => _x( 'type-projet', 'cpt-project-taxonomy-slug', 'platform-shell-plugin' ),
				],
				'hierarchical' => false,
				'show_ui'      => false,
				'show_in_menu' => false,
				'query_var'    => true,
			]
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Platform_Shell\CPT\CPT_Type::install_taxonomies()
	 */
	public function install_taxonomies() {
		$this->insert_or_update_terms_for_projects_cat();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Platform_Shell\CPT\CPT_Type::uninstall_taxonomies()
	 */
	public function uninstall_taxonomies() {
		$category_taxonomy = $this->configs->category_taxonomy_name;

		$terms = get_terms(
			$category_taxonomy,
			[
				'fields'     => 'ids',
				'hide_empty' => false,
			]
		);

		foreach ( $terms as $value ) {
			wp_delete_term( $value, $category_taxonomy );
		}
	}

	/**
	 * Méthode insert_or_update_terms_for_projects_cat
	 */
	private function insert_or_update_terms_for_projects_cat() {

		/**
		 * Lors d'une activation / déactivation on veut tenir compte de la langue
		 * courante pour mettre à jout le slug et la description,
		 * Le terme devrait toujours rester un identifiant de programmation indépendant de la langue.
		 */

		$category_taxonomy = $this->configs->category_taxonomy_name;
		$terms_configs     = $this->project_taxonomy_category->get_terms_configs();

		foreach ( $terms_configs as $key_name => $term_config ) {

			$existing_term = term_exists( $key_name, $category_taxonomy ); // array is returned if taxonomy is given.
			if ( isset( $existing_term ) ) {
				$existing_term_id = $existing_term['term_id'];
				wp_update_term(
					$existing_term,
					$category_taxonomy,
					[
						'description' => $term_config['description'],
						'slug'        => $term_config['slug'],
					]
				);
			} else {
				wp_insert_term(
					$key_name,
					$category_taxonomy,
					[
						'description' => $term_config['description'],
						'slug'        => $term_config['slug'],
					]
				);
			}
		}
	}

	/**
	 * Méthode set_project_fields
	 */
	public function set_project_fields() {

		$terms = get_terms(
			[
				'taxonomy'   => $this->configs->category_taxonomy_name,
				'hide_empty' => false,
			]
		);

		$project_type_select_list = [];

		// Ajouter les valeurs possibles.
		foreach ( $terms as $term ) {
			$project_type_select_list[ $term->name ] = $this->project_taxonomy_category->get_term_label( $term->name );
		}

		// Trier alphabétiquement.
		asort( $project_type_select_list );

		// Ajouter la valeur générique "Choisir un type" en début de liste (utilise union d'array associatif).
		$project_type_select_list = [ '' => _x( 'Choisir un type', 'cpt-project', 'platform-shell-plugin' ) ] + $project_type_select_list;

		$level = [
			''             => _x( 'Choisir une option', 'cpt-project-field', 'platform-shell-plugin' ),
			'beginner'     => _x( 'Débutant', 'cpt-project-field', 'platform-shell-plugin' ),
			'intermediate' => _x( 'Intermédiaire', 'cpt-project-field', 'platform-shell-plugin' ),
			'advanced'     => _x( 'Avancé', 'cpt-project-field', 'platform-shell-plugin' ),
		];

		$creation_type = [
			''                    => _x( 'Choisir une option', 'cpt-project-field', 'platform-shell-plugin' ),
			'individual-creation' => _x( 'Individuelle', 'cpt-project-field', 'platform-shell-plugin' ),
			'group-creation'      => _x( 'En groupe', 'cpt-project-field', 'platform-shell-plugin' ),
		];

		$current_user          = wp_get_current_user();
		$available_status_list = $this->get_user_available_status_action( $current_user );

		$projects_fields = [
			'post_title'       => [
				'label'      => _x( 'Titre du projet', 'cpt-project-field', 'platform-shell-plugin' ),
				'id'         => $this->configs->metadata_prefix . 'title',
				'desc'       => '',
				'meta'       => false,
				'key'        => 'post_title',
				'max_length' => '100',
				'type'       => 'text',
				'require'    => 'true',
			],
			'type'             => [
				'label'   => _x( 'Type de projet', 'cpt-project-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'type',
				'meta'    => true,
				'require' => 'true',
				'type'    => 'select',
				'options' => $project_type_select_list,
			],
			'level'            => [
				'label'   => _x( 'Niveau', 'cpt-project-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'level',
				'require' => 'true',
				'type'    => 'select',
				'options' => $level,
			],
			'creation_type'    => [
				'label'   => _x( 'Type de création', 'cpt-project-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'creation_type',
				'require' => 'true',
				'type'    => 'select',
				'options' => $creation_type,
			],
			'cocreators'       => [
				'label'  => _x( 'Autres créateurs', 'cpt-project-field', 'platform-shell-plugin' ),
				'desc'   => '',
				'class'  => 'cocreators hidden',
				'id'     => $this->configs->metadata_prefix . 'cocreators',
				'type'   => 'multi-users',
				'author' => $current_user->ID,
			],
			'featured_image'   => [
				'label'   => _x( 'Image principale', 'cpt-project-field', 'platform-shell-plugin' ),
				'desc'    => sprintf(
					/* translators: %1$s: Taille maximale pour Upload */
					_x( 'Formats d’images acceptés : JPEG, PNG et GIF de moins de %1$s.', 'cpt-project-field', 'platform-shell-plugin' ),
					UploadHelper::get_max_upload_filesize_localized()
				),
				'id'      => $this->configs->base_metadata_prefix . 'featured_image',
				'key'     => 'post_thumbnail',
				'type'    => 'upload',
				'require' => 'true',
			],
			'gallery'          => [
				'label' => _x( 'Galerie d’images', 'cpt-project-field', 'platform-shell-plugin' ),
				'desc'  => sprintf(
					/* translators: %1$s: Taille maximale pour Upload */
					_x( 'Formats d’images acceptés : JPEG, PNG et GIF de moins de %1$s.', 'cpt-project-field', 'platform-shell-plugin' ),
					UploadHelper::get_max_upload_filesize_localized()
				),
				'id'    => $this->configs->base_metadata_prefix . 'gallery_1',
				'key'   => $this->configs->base_metadata_prefix . 'gallery',
				'type'  => 'repeatable',
			],
			'videos'           => [
				'label' => _x( 'Vidéos', 'cpt-project-field', 'platform-shell-plugin' ),
				'id'    => $this->configs->base_metadata_prefix . 'video',
				'desc'  => _x( 'Tu peux ajouter une ou plusieurs vidéos à partir de YouTube ou de Vimeo. Entre les liens (URL) des vidéos séparés par des virgules.', 'cpt-project-field', 'platform-shell-plugin' ),
				'type'  => 'text',
			],
			'post_content'     => [
				'label'   => _x( 'Description du projet', 'cpt-project-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'description',
				'meta'    => false,
				'key'     => 'post_content',
				'type'    => 'wysiwyg',
				'options' => [
					'wpautop'       => true,
					'media_buttons' => false,
					'textarea_name' => $this->configs->metadata_prefix . 'description',
					'textarea_rows' => get_option( 'default_post_edit_rows', 10 ),
					'tabindex'      => '',
					'editor_css'    => '',
					'editor_class'  => '',
					'teeny'         => false,
					'dfw'           => false,
					'tinymce'       => true,
					'quicktags'     => true,
				],
			],
			'creative_process' => [
				'label'   => _x( 'Processus de création', 'pcpt-project-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'creative_process',
				'type'    => 'wysiwyg',
				'options' => [
					'wpautop'       => true,
					'media_buttons' => false,
					'textarea_name' => $this->configs->metadata_prefix . 'creative_process',
					'textarea_rows' => get_option( 'default_post_edit_rows', 10 ),
					'tabindex'      => '',
					'editor_css'    => '',
					'editor_class'  => '',
					'teeny'         => false,
					'dfw'           => false,
					'tinymce'       => true,
					'quicktags'     => true,
				],
			],
			'tags_input'       => [
				'label' => _x( 'Mots-clés', 'cpt-project-field', 'platform-shell-plugin' ),
				'id'    => $this->configs->metadata_prefix . 'tags',
				'meta'  => false,
				'key'   => 'project_tags',
				'type'  => 'text',
				'desc'  => _x(
					'Entre plusieurs mots-clés séparés par des virgules.',
					'cpt-project-field',
					'platform-shell-plugin'
				),
			],
			'project_status'   => [
				'label'   => _x( 'Publication', 'cpt-project-field', 'platform-shell-plugin' ),
				'desc'    => _x(
					'Publier : Ton projet sera publié et visible par tous les utilisateurs.<br/>
					Mettre en attente : Ton projet ne sera visible que par toi.<br/>
					<br/>
					<span class="important" ><strong>Important</strong> : Tu peux choisir l’option « En attente » à tout moment,
					mais si ton projet est inscrit à un ou des concours courants, il sera désinscrit automatiquement.</span>',
					'cpt-project-field',
					'platform-shell-plugin'
				),
				'id'      => $this->configs->metadata_prefix . 'status',
				'require' => 'true',
				'type'    => 'select',
				'options' => $available_status_list,
				'meta'    => false,
				'key'     => 'post_status',
			],
		];

		self::$project_form_fields = $projects_fields;
	}

	/**
	 * Méthode pour déterminer si un utilisateur a accès au statut de modération de projet.
	 *
	 * @param object $user    Utilisateur WordPress.
	 * @return boolean        true/false l'utilisateur peut modérer le projet.
	 */
	private function user_can_moderate_project( $user ) {
		$user_can_hide = array_reduce(
			$user->roles,
			function ( $carry, $item ) {
				// Pourrait utiliser une capabibilities nommé.
				if ( in_array( $item, $this->roles_configs->get_elevated_roles(), true ) ) {
					$carry = true;
				}

				return $carry;
			},
			false
		);

		return $user_can_hide;
	}

	/**
	 * Méthode permettant de récupérer la liste des status pouvant être assigné selon les droits de l'utilisateur.
	 *
	 * @param object $user    Utilisateur WordPress.
	 * @return array          Liste des status action disponibles à l'utilisateur.
	 */
	private function get_user_available_status_action( $user ) {

		$available_status_action = $this->configs->status_action;

		$user_can_moderate = $this->user_can_moderate_project( $user );

		if ( ! $user_can_moderate ) {
			unset( $available_status_action['private'] );
		}

		return $available_status_action;
	}

	/**
	 * Méthopde get_project_fields
	 *
	 * @return array
	 */
	public static function get_project_fields() {
		return self::$project_form_fields;
	}

	/**
	 * AJAX methode pour la création d'un projet
	 *
	 * $_POST[platform_shell_meta_project_title]            => titre du projet
	 * $_POST[platform_shell_meta_project_type]             => Type de projet (categories de projets )
	 * $_POST[platform_shell_meta_project_level]            => Niveau (débutant,intermédiaire,avancé)
	 * $_POST[platform_shell_meta_video]                    => videéos de YouTube (séparés par des virgules)
	 * $_POST[platform_shell_meta_project_creation_type]    => type de création (individuelle ou en groupe)
	 * $_POST[platform_shell_meta_project_description]      => null
	 * $_POST[platform_shell_meta_project_creative_process] => null
	 * $_POST[platform_shell_meta_project_tags]             => mot-clés
	 * $_POST[post_content]                                 => description du projet
	 * $_POST[creation_creative_process]                    => processus de création
	 * $_FILES[platform_shell_meta_featured_image    ]      => featured images
	 * $_FILES[ platform_shell_meta_gallery_$i ]            => array d'images
	 *
	 * @throws Project_Exception Lorsqu'il a une erreur de validation sur le projet.
	 */
	public function save_project_handler() {

		global $current_user;

		// Obtenir le post id.
		$this->current_project_id = isset( $_POST['project_id'] ) ? sanitize_text_field( $_POST['project_id'] ) : null;
		$project                  = is_null( $this->current_project_id ) ? null : get_post( $this->current_project_id );

		// Premièrement, nous vérifions que le nonce est valide.
		$nonce_key = 'save_project_details_' . ( is_null( $this->current_project_id ) ? 'new' : $this->current_project_id );

		// phpcs:ignore WordPress --Nous utilisons cette valeur pour valider le nonce
		$nonce = $_REQUEST['save_project_details'];

		$valid_nonce = ( false !== wp_verify_nonce( $nonce, $nonce_key ) );

		$is_author              = is_null( $project ) ? true : ( (int) $current_user->ID === (int) $project->post_author );
		$is_locked              = is_null( $project ) ? false : ( 'private' === $project->post_status );
		$can_edit_project       = current_user_can( 'edit_projects', $this->current_project_id );
		$can_edit_other_project = current_user_can( 'edit_others_projects', $this->current_project_id );

		if ( $valid_nonce && ( ( $is_author && $can_edit_project && ! $is_locked ) || $can_edit_other_project ) ) {

			$response        = [];
			$success_message = '';

			$this->is_creating_project = false;

			// Obtenir les tags et les catégories.
			$tags = array_filter( explode( ',', sanitize_text_field( $_POST['platform_shell_meta_project_tags'] ) ) );
			$cats = array_filter( explode( ',', sanitize_text_field( $_POST['platform_shell_meta_project_type'] ) ) );

			try {

				$post_status = sanitize_meta( 'platform_shell_meta_project_status', sanitize_text_field( $_POST['platform_shell_meta_project_status'] ), 'post' );

				// Données de base du projet.
				$data_post = [
					// La valeur pour "post_author" doit être assignée selon le mode de sauvegarde (création vs mise à jour).
					'post_content'  => stripslashes( $this->cpt_helper->clean_html( $_POST['post_content'] ) ), // La sauvegarde du "post_content" ajoute automatiquement les antislashs.
					'post_title'    => sanitize_text_field( stripslashes( $_POST['platform_shell_meta_project_title'] ) ),
					'post_name'     => sanitize_title( $_POST['platform_shell_meta_project_title'] ),
					'post_category' => null,
					'post_status'   => $post_status,
					'post_type'     => 'project',
					'tags_input'    => $tags,
				];

				// Obtenir le l'ID du projet.
				if ( ! is_null( $this->current_project_id ) ) { // Mode mise à jour. Sanitize non requis.

					$this->is_creating_project = false;

					// L'on assigne l'ID de l'auteur courant à la valeur 'post_author'.
					$data_post['post_author'] = $project->post_author;

					$success_message = _x( 'Ton projet a été modifié.', 'project-type', 'platform-shell-plugin' );

				} else { // Mode création.

					$this->current_project_id  = wp_insert_post( $data_post );
					$this->is_creating_project = true;

					// L'on assigne l'ID de l'utilisateur courant à la valeur 'post_author'.
					$data_post['post_author'] = $current_user->ID;

					$success_message = _x( 'Ton projet a été créé.', 'project-type', 'platform-shell-plugin' );
				}

				$data_post['ID'] = $this->current_project_id;

				// Traiter l'upload des images.
				// Traiter la vignette du projet.
				$attach_id = $this->upload_helper->upload_thumbnail(
					$_FILES['platform_shell_meta_featured_image'], // Comme le nom de fichier d'une image est renommée, il est inutile de néttoyer le nom de fichier.
					$this->errors,
					$this->current_project_id
				);

				$existing_attachments = isset( $_POST['platform_shell_meta_gallery'] ) ? sanitize_text_field( $_POST['platform_shell_meta_gallery'] ) : '';

				// Traiter les images de la galerie.
				$this->upload_helper->upload_gallery(
					$_FILES['platform_shell_meta_gallery'], // Comme le nom de fichier d'une image est renommée, il est inutile de néttoyer le nom de fichier.
					$this->errors,
					$this->current_project_id,
					$existing_attachments
				);

				// Mettre à jour le projet.
				wp_update_post( wp_slash( $data_post ) );

				// Mettre à jour les tags.
				wp_set_object_terms( $this->current_project_id, null, 'platform_shell_tax_proj_tags' ); // Il faut vider les tags avant de mettre à jour.
				wp_set_object_terms( $this->current_project_id, $tags, 'platform_shell_tax_proj_tags', true );

				$cocreators = '';

				if (
					'group-creation' === $_POST['platform_shell_meta_project_creation_type'] &&
					isset( $_POST['platform_shell_meta_project_cocreators'] )
				) {

					$cocreators = $this->sanitize_cocreators( $data_post, $_POST['platform_shell_meta_project_cocreators'] );
					$cocreators = implode( ',', $cocreators );
				}

				// Mettre à jour les métadonnées.
				$metas = [
					'platform_shell_meta_project_cocreators' => [
						'update_on_empty' => true,
						'value'           => sanitize_meta(
							'platform_shell_meta_project_cocreators',
							sanitize_text_field( $cocreators ),
							'post'
						),
					],
					'platform_shell_meta_project_type'  => [
						'update_on_empty' => true,
						'value'           => sanitize_meta(
							'platform_shell_meta_project_type',
							sanitize_text_field( $_POST['platform_shell_meta_project_type'] ),
							'post'
						),
					],
					'platform_shell_meta_project_type'  => [
						'update_on_empty' => true,
						'value'           => sanitize_meta(
							'platform_shell_meta_project_type',
							sanitize_text_field( $_POST['platform_shell_meta_project_type'] ),
							'post'
						),
					],
					'platform_shell_meta_project_level' => [
						'update_on_empty' => true,
						'value'           => sanitize_meta(
							'platform_shell_meta_project_level',
							sanitize_text_field( $_POST['platform_shell_meta_project_level'] ),
							'post'
						),
					],
					'platform_shell_meta_video'         => [
						'update_on_empty' => true,
						'value'           => sanitize_meta(
							'platform_shell_meta_video',
							sanitize_text_field( $_POST['platform_shell_meta_video'] ),
							'post'
						),
					],
					'platform_shell_meta_project_creation_type' => [
						'update_on_empty' => true,
						'value'           => sanitize_meta(
							'platform_shell_meta_project_creation_type',
							sanitize_text_field( $_POST['platform_shell_meta_project_creation_type'] ),
							'post'
						),
					],
					'platform_shell_meta_project_creative_process' => [
						'update_on_empty' => true,
						'value'           => $this->cpt_helper->clean_html( $_POST['creative_process'] ),
					],
				];

				foreach ( $metas as $key => $meta ) {
					$this->process_meta( $key, $meta );
				}

				// Vérifier qu'aucune erreur n'est subvenue.
				if ( ! empty( $this->errors ) ) {
					throw new Project_Exception( _x( 'Une erreur a été détectée lors du traitement du projet.', 'project-type', 'platform-shell-plugin' ) );
				} else {
					// Fix bug séquence. Il faut sauvegarder la donnée seulement une fois le sanitize complété.
					// Mettre à jour les catégories.
					$saved_cat = get_post_meta( $this->current_project_id, 'platform_shell_meta_project_type', true );
					wp_set_object_terms( $this->current_project_id, null, 'platform_shell_tax_proj_cat' ); // Il faut vider les tags avant de mettre à jour.
					wp_set_object_terms( $this->current_project_id, $saved_cat, 'platform_shell_tax_proj_cat', true );
				}

				// Ajouter un message de succès.
				$response = [
					'success' => [
						'message' => $success_message,
						'id'      => $this->current_project_id,
						'href'    => get_permalink( $this->current_project_id ),
					],
				];

				// Envoyer une notice aux administrateurs.
				$admin_notices = new Admin_Notices( 'POST', $this->current_project_id );
				$admin_notices->add_message( $success_message, 'success', Admin_Notices::MESSAGE_LIFETIME_USE_ONCE );

			} catch ( Project_Exception $e ) {
				$this->check_delete_project_on_error( $e );
			} catch ( Throwable $t ) { // Avec PHP > 7, les erreurs et les exceptions sont du type parent Throwable.
				$this->check_delete_project_on_error( $t );
			} catch ( Exception $e ) { // Par contre, le type Throwable n'existe pas sous PHP < 7.
				$this->check_delete_project_on_error( $e );
			}
		} else {
			$this->errors['unexpected_errors'] = [
				_x( 'Vous n’êtes pas autorisé à modifier ce projet.', 'project-type', 'platform-shell-plugin' ),
			];
		}

		if ( ! empty( $this->errors ) ) {
			$response = [ 'errors' => $this->errors ];
		}

		platform_shell_display_json_response( $response );
	}

	/**
	 * Méthode check_delete_project_on_error
	 *
	 * @param Exception $e    The error.
	 */
	private function check_delete_project_on_error( $e ) {
		if ( $this->is_creating_project ) {
			// Supprimer le projet s'il y a une exception lors de la création afin de ne pas laisser le projet dans un état invalide.
			$this->delete_project();
		}

		if ( empty( $this->errors ) ) {
			$this->errors['unexpected_errors'] = [
				$this->get_unexpected_error_message(),
			];
		}

		// phpcs:ignore WordPress --Nous voulons logguer les erreurs innatendues pour debugger des problèmes potentiels en production.
		error_log( $e->getMessage() );
	}

	/**
	 * Méthode process_meta
	 *
	 * @param string $key     The meta key.
	 * @param array  $meta    The meta values.
	 */
	private function process_meta( $key, $meta ) {

		if ( $meta['update_on_empty'] || ! empty( $meta['value'] ) ) {

			$old = get_post_meta( $this->current_project_id, $key, false );

			if ( empty( $old ) ) {

				add_post_meta( $this->current_project_id, $key, $meta['value'], true );

			} else {

				$old = array_shift( $old );
				if ( $old !== $meta['value'] ) {
					update_post_meta( $this->current_project_id, $key, $meta['value'] );
				}
			}
		}
	}

	/**
	 * Méthode trim_gallery
	 *
	 * @param string $str   La liste d'éléments dans la galerie en format CSV.
	 * @return array        La liste d'éléments dans la galerie.
	 */
	private function trim_gallery( $str ) {
		$returned_str = str_replace( ',,', ',', $str );
		$returned_str = ltrim( $returned_str, ',' );
		$returned_str = rtrim( $returned_str, ',' );
		$result       = explode( ',', $returned_str );

		return $result;
	}

	/**
	 * Méthode error_handler
	 *
	 * Méthode qui gère les erreurs régulières sous PHP
	 */
	private function error_handler() {
		if ( $this->is_creating_project && ! is_null( $this->current_project_id ) ) {
			$this->delete_project();
			$this->errors['unexpected_errors'] = [
				$this->get_unexpected_error_message(),
			];
		}
	}

	/**
	 * Méthode delete_project
	 *
	 * Cette méthode supprime le projet qui est présentement créé lorsqu'une erreur est détectée
	 *
	 * @param int $project_id    ID du projet à supprimer.
	 */
	private function delete_project( $project_id = null ) {
		if ( ! is_null( $this->current_project_id ) && is_null( $project_id ) ) {
			$project_id = $this->current_project_id;
		}

		if ( ! is_null( $project_id ) ) {
			wp_delete_post( $project_id, true );
		}
	}

	/**
	 * Méthode get_unexpected_error_message
	 *
	 * @return string    Le message d'erreur localisé.
	 */
	private function get_unexpected_error_message() {
		return _x( 'Une erreur est survenue. Veuillez réessayer plus tard.', 'project-type', 'platform-shell-plugin' );
	}

	/**
	 * Méthode fatal_error_handler
	 *
	 * Methode qui gère les erreurs fatales sous PHP 5.*
	 * Les erreurs fatales sous PHP 5.* ne sont pas attrapés par un handler défini par set_error_handler
	 */
	public function fatal_error_handler() {
		if ( version_compare( PHP_VERSION, '7.0.0' ) < 0 ) {
			if ( $this->is_creating_project && ! is_null( $this->current_project_id ) ) {
				$error = error_get_last();
				if (
					! is_null( $error ) &&
					in_array(
						$error['type'],
						[
							E_ERROR,         // Fatal run-time errors.
							E_CORE_ERROR,    // Fatal errors that occur during PHP's initial startup.
							E_COMPILE_ERROR, // Fatal compile-time errors.
						],
						true
					)
				) {
					// Une erreur est arrivé en créant le projet.
					$this->delete_project();
					header( 'Content-Type: application/json' );
					platform_shell_display_json_response(
						[
							'errors' => [
								'unexpected_errors' => [
									$this->get_unexpected_error_message(),
								],
							],
						]
					);
				}
			}
		}
	}

	/**
	 * Méthode sanitize_project_level
	 *
	 * @param string $level   La valeur actuelle du niveau.
	 * @return string         La valeur néttoyée du niveau.
	 */
	public function sanitize_project_level( $level ) {

		$level_arr = [ 'beginner', 'intermediate', 'advanced' ];

		if ( ! ( in_array( $level, $level_arr, true ) ) ) {

			$level = $level_arr[0];
		}

		return $level;
	}

	/**
	 * Méthode sanitize_project_type
	 *
	 * @param string $project_type    La valeur actuelle du type de projet à néttoyer.
	 * @return void|string            La valeur néttoyée du type de projet.
	 */
	public function sanitize_project_type( $project_type ) {
		$term = term_exists( $project_type, 'platform_shell_tax_proj_cat' );

		if ( 0 !== $term && null !== $term ) {
			return $project_type;
		} else {
			// Protection valeur invalide. Devrait plutôt lever une exception.
			$this->errors['unexpected_errors'] = [
				$this->get_unexpected_error_message(),
			];
		}
	}

	/**
	 * Méthode sanitize_project_creation_type
	 *
	 * @param string $creation_type    La valeur actuelle du type de création à néttoyer.
	 * @return string                  La valeur néttoyée du type de création
	 */
	public function sanitize_project_creation_type( $creation_type ) {

		$known_creation_types = [ 'individual-creation', 'group-creation' ];

		if ( ! ( in_array( $creation_type, $known_creation_types, true ) ) ) {

			$creation_type = $known_creation_types[0];
		}

		return $creation_type;
	}

	/**
	 * Méthode sanitize_project_status
	 *
	 * @param string $status    La valeur actuelle du statut du projet à néttoyer.
	 * @return string           La valeur néttoyée du statut du projet
	 * @throws Project_Exception        Lorsque la valeur a été manipulée / pas dans le liste des valeurs permises.
	 */
	public function sanitize_project_status( $status ) {
		$current_user          = wp_get_current_user();
		$available_status_list = $this->get_user_available_status_action( $current_user );

		// Manipulation de données détectées. On pourrait aussi réassigner une valeur minimale valide.
		if ( ! isset( $available_status_list[ $status ] ) ) {
			throw new Project_Exception( _x( 'Une erreur a été détecté lors du traitement du projet.', 'project-type', 'platform-shell-plugin' ) );
		}

		return $status;
	}

	/**
	 * Méthode get_user_projects
	 *
	 * @param integer $user_id    L'ID de l'usager pour lequel nous voulons récupérer les projets.
	 * @return WP_Post            Les projets de l'usagé recherché.
	 */
	public static function get_user_projects( $user_id ) {

		$args = [
			'author'         => $user_id,
			'orderby'        => 'post_date',
			'order'          => 'ASC',
			'posts_per_page' => -1,
			'post_type'      => 'project',
			'post_status'    => 'publish',
		];

		$current_user_posts = get_posts( $args );
		$total              = count( $current_user_posts );
		return $current_user_posts;
	}

	/**
	 * Méthode subscribe_project_handler
	 */
	public function subscribe_project_handler() {

		global $wpdb;

		// phpcs:ignore WordPress --La validation des nonces de fait après la réception de ces données.
		$data   = $_REQUEST['form_data'];
		$params = [];

		foreach ( explode( '&', $data ) as $chunk ) {

			if ( 1 !== substr_count( $chunk, '=' ) ) { // Chaîne de charactère invalide.
				continue; // L'on passe à la prochaine itération.
			}

			list( $key, $value ) = explode( '=', $chunk );

			$key   = sanitize_text_field( $key );
			$value = sanitize_text_field( $value );

			$params[ $key ] = $value;
		}

		$project_id = $params['project_id'];
		$contest_id = $params['contest_id'];

		$nonce_name  = 'subscribe_project_contest';
		$nonce_key   = $nonce_name . '_' . $contest_id;
		$nonce_value = $params[ $nonce_name ];

		$response = '';

		// La validation des nonces se fait ici.
		if ( false !== wp_verify_nonce( $nonce_value, $nonce_key ) ) {

			$table = $wpdb->prefix . 'platform_shell_contest_entry';

			$verify_subscription = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE project_id = %d AND contest_id = %d", // phpcs:ignore WordPress --Choix dynamique de la table
					[
						$project_id,
						$contest_id,
					]
				)
			);

			if ( isset( $verify_subscription ) ) {

				$response = [
					'result'  => 'error',
					'message' => _x( 'Ton projet est déjà inscrit à ce concours.', 'project-type', 'platform-shell-plugin' ),
					'href'    => esc_url( $_SERVER['HTTP_REFERER'] ),
				];
			} else {

				$result_row = $wpdb->insert(
					$table,
					[
						'project_id' => $project_id,
						'contest_id' => $contest_id,
					],
					[
						'%d',
						'%d',
					]
				);

				if ( $result_row > 0 ) {
					$response = [
						'result'  => 'success',
						'message' => _x( 'Ton projet est maintenant inscrit au concours.', 'project-type', 'platform-shell-plugin' ),
						'href'    => esc_url( $_SERVER['HTTP_REFERER'] ),
					];
				} else {

					$response = [
						'result'  => 'error',
						'message' => _x( 'Une erreur est survenue.', 'project-type', 'platform-shell-plugin' ),
						'href'    => esc_url( $_SERVER['HTTP_REFERER'] ),
					];
				}
			}
		} else {

			$response = [
				'result'  => 'error',
				'message' => _x( 'Erreur innatendue. Veuillez recharger la page et réessayer.', 'project-type', 'platform-shell-plugin' ),
				'href'    => esc_url( $_SERVER['HTTP_REFERER'] ),
			];
		}

		platform_shell_display_json_response( $response );
	}
}
