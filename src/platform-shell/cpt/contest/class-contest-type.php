<?php
/**
 * Platform_Shell\CPT\Contest\Contest_Type
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Contest;

use Platform_Shell\PlatformShellDateTime;
use Platform_Shell\Roles_Configs;
use Platform_Shell\Admin\Admin_Notices;
use Platform_Shell\CPT\CPT_Helper;
use Platform_Shell\CPT\CPT_Type;
use Platform_Shell\Settings\Plugin_Settings;
use Exception;

/**
 * Platform_Shell Contest_Type
 *
 * @class    Contest_Type
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Contest_Type extends CPT_Type {

	/**
	 * Liste des champs de métadonnées
	 *
	 * @var array
	 */
	public $contest_metadata_fields = [];

	/**
	 * Messages à afficher à l'utilisateur.
	 *
	 * @var Admin_Notices
	 */
	private $admin_notices = null;

	/**
	 * Instance des paramètres du plugin.
	 *
	 * @var Plugin_Settings
	 */
	private $plugin_settings;

	/**
	 * Constructeur
	 *
	 * @param Contest_Configs   $configs            Une instance des paramètres de configuration du post type.
	 * @param Roles_Configs     $roles_config       Une instance des paramètre des différents roles assignée au post type.
	 * @param CPT_Helper        $cpt_helper         Instance de la classe helper pour les différents types de contenus.
	 * @param Contest_Metaboxes $metaboxes          Une instance des paramètres de configuration des metaboxes.
	 * @param Plugin_Settings   $plugin_settings    Instance des paramètres du plugin.
	 */
	public function __construct( Contest_Configs $configs, Roles_Configs $roles_config, CPT_Helper $cpt_helper, Contest_Metaboxes $metaboxes, Plugin_Settings $plugin_settings ) {

		$this->plugin_settings = $plugin_settings;

		parent::__construct( $configs, $roles_config, $cpt_helper, $metaboxes );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Platform_Shell\CPT\CPT_Type::init()
	 */
	public function init() {

		add_action( 'init', array( &$this, 'register_post_type' ) );

		$this->register_for_metabox_save();

		add_action( 'admin_notices', array( &$this, 'admin_notice' ) );

		parent::init();
	}

	/**
	 * Destructeur
	 */
	public function __destruct() {

		$this->admin_notices = null;
	}

	/**
	 * Méthode init_admin_notices
	 */
	private function init_admin_notices() {

		$post = get_post();

		if ( ! isset( $this->admin_notices ) ) {

			if ( isset( $post->ID ) ) {

				$post_id             = $post->ID;
				$this->admin_notices = new Admin_Notices( 'POST', $post_id );
			}
		}
	}

	/**
	 * Méthode admin_notice
	 */
	public function admin_notice() {
		$screen = get_current_screen();
		// Limiter le contexte d'affichage ( à revalider contest vs edit-contest? ).
		if ( is_admin() && ( $screen->id === $this->configs->post_type_name ) ) {
			$this->init_admin_notices();

			// Date avec heure ( important pour valider configuration du "timezone" ).
			$current_date_time = date( PlatformShellDateTime::get_save_format(), current_time( 'timestamp', 0 ) );

			// Message informatif pour voir un indication minimale que les dates du système sont bien configurées.
			// translators: %s: Date courante.
			$message = sprintf( _x( 'Veuillez vérifier que l’heure actuelle correspond à celle configurée sur le serveur afin que la détermination du moment du début et de la fin du concours soit correcte. Heure configurée sur le serveur : %1$s.', 'profile-admin-notice-warning', 'platform-shell-plugin' ), $current_date_time );
			$this->admin_notices->add_message( $message, 'info', Admin_Notices::MESSAGE_LIFETIME_USE_ONCE );
			$this->admin_notices->show_admin_notices();
		}
	}

	/**
	 * Enregister le posttype.
	 *
	 * @throws Exception    Erreur lorsque l'on essaie de redéfinir un post type.
	 */
	public function register_post_type() {

		$post_type_name        = $this->configs->post_type_name;
		$post_type_name_plural = $this->configs->post_type_name_plural;

		if ( post_type_exists( $post_type_name ) ) {
			throw new Exception( 'Redéfinition d’un CPT existant.' );
		}

		$args = [
			'labels'             => $this->configs->labels,
			'description'        => _x( 'Description.', 'cp-contest-description', 'platform-shell-plugin' ),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => _x( 'concours', 'cpt-contests-slug', 'platform-shell-plugin' ) ),
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 25,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'comments', 'revisions' ),
			'query_var'          => true,
			'can_export'         => true,
			'capability_type'    => array( $post_type_name, $post_type_name_plural ),
			'map_meta_cap'       => true,
		];

		register_post_type( $post_type_name, $args );

		$this->set_contest_metadata();

		// Vestige de la première implémentation avec mauvaise séparation des responsabilités.
		// Le code des metabox dépend de données qui sont définis dans type. Il faudrait revoir tout ça.
		// L'implémentation dans Project diffère sensiblement alors c'est possible qu'il n'y ait pas de solution commmune.
		$this->metaboxes->set_temporary_fix_contest_metadata( $this->contest_metadata_fields );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Platform_Shell\CPT\CPT_Type::register_taxonomies()
	 */
	public function register_taxonomies() {

		register_taxonomy(
			'platform_shell_tax_contest_tags',
			$this->configs->post_type_name, [
				'label'        => _x( 'Mots-clés', 'cpt-contest-taxonomy', 'platform-shell-plugin' ),
				'rewrite'      => [
					'slug' => _x( 'mots-cles-concours', 'cpt-project-tags-taxonomy-slug', 'platform-shell-plugin' ),
				],
				'hierarchical' => false,
				'label'        => _x( 'Mots-clés', 'cpt-contest-taxonomy', 'platform-shell-plugin' ),
				'show_ui'      => true,
				'query_var'    => true,
			]
		);
	}

	/**
	 * Méthode get_articles_list
	 *
	 * @return array
	 */
	private function get_articles_list() {

		$posts_list = [];

		// get_posts pour construire le select du lien de l'article des lauréats.
		$args = [
			'orderby'     => 'date',
			'order'       => 'DESC',
			'post_type'   => 'post',
			'post_status' => 'publish',
		];

		$posts_array    = get_posts( $args );
		$posts_list[''] = _x( 'Choisir un article', 'cpt-contest-metadata', 'platform-shell-plugin' );

		foreach ( $posts_array as $article ) {
			$posts_list[ $article->ID ] = $article->post_title;
		}

		wp_reset_postdata();

		return $posts_list;
	}

	/**
	 * Méthode set_contest_metadata
	 */
	public function set_contest_metadata() {
		$prize_meta_fields = [
			[
				'label'   => _x( 'Prix et images des prix.', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => _x( '<br/>Informations supplémentaires : <br/> La largeur maximale de l’image du prix à l’affichage du concours est de 120 pixels.<br/><br/>', 'cpt-contest-field', 'platform-shell-plugin' ),
				'id'      => $this->configs->metadata_prefix . 'prize',
				'type'    => 'wysiwyg',
				'require' => 'true',
				'options' => [
					'wpautop'       => true,
					'media_buttons' => true,
					'textarea_name' => $this->configs->metadata_prefix . 'prize',
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
			[
				'label'   => _x( 'Jury et images du jury', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'judges',
				'type'    => 'wysiwyg',
				'require' => 'true',
				'options' => [
					'wpautop'       => true,
					'media_buttons' => true,
					'textarea_name' => $this->configs->metadata_prefix . 'judges',
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
		];

		$terms_meta_fields = [
			[
				'label' => _x( 'Image du commanditaire', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'  => '',
				'id'    => $this->configs->metadata_prefix . 'sponsor_image',
				'type'  => 'metadata',
			],
			[
				'label'   => _x( 'Admissibilité', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => 'Les valeurs permises peuvent être modifiées dans les écrans de configurations de la plateforme.',
				'id'      => $this->configs->metadata_prefix . 'admissibility',
				'type'    => 'select',
				'require' => 'true',
				'options' => $this->get_admissibility_list(),
			],
			[
				'label'   => _x( 'Organisateur', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => 'Les valeurs permises peuvent être modifiées dans les écrans de configurations de la plateforme.',
				'id'      => $this->configs->metadata_prefix . 'organizer',
				'type'    => 'select',
				'require' => 'true',
				'options' => $this->get_organizer_list(),
			],
			[
				'label'   => _x( 'Type de concours', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => 'Les valeurs permises peuvent être modifiées dans les écrans de configurations de la plateforme.',
				'id'      => $this->configs->metadata_prefix . 'type',
				'type'    => 'select',
				'require' => 'true',
				'options' => $this->get_contest_type_list_for_select(),
			],
			[
				'label'   => _x( 'Modalités de participation', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'terms',
				'type'    => 'wysiwyg',
				'require' => 'true',
				'options' => [
					'wpautop'       => true,
					'media_buttons' => false,
					'textarea_name' => $this->configs->metadata_prefix . 'terms',
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
			[
				'label'   => _x( 'Critères d’évaluation', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'evaluation_criteria',
				'type'    => 'wysiwyg',
				'require' => 'true',
				'options' => [
					'wpautop'       => true,
					'media_buttons' => false,
					'textarea_name' => $this->configs->metadata_prefix . 'evaluation_criteria',
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
			[
				'type'    => 'metadata', /* cas particulier pour validation. Il y a pas d'élément html à créer */
				'label'   => _x( 'Prix en vedette', 'cpt-contest-field', 'platform-shell-plugin' ),
				'id'      => $this->configs->metadata_prefix . 'main_prize',
				'require' => 'true',
			],
			[
				'type'    => 'metadata', /* cas particulier pour validation. Il y a pas d'élément html à créer */
				'label'   => _x( 'Image du prix en vedette', 'cpt-contest-field', 'platform-shell-plugin' ),
				'id'      => $this->configs->metadata_prefix . 'main_prize_image',
				'require' => 'true',
			],
		];

		$dates_meta_fields = [
			[
				'label'   => _x( 'Date d’ouverture', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'date_open',
				'type'    => 'date',
				'require' => 'true',
				'filter'  => [ '\Platform_Shell\PlatformShellDateTime', 'date_filter' ],
			],
			[
				'label'   => _x( 'Date de fin', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'date_end',
				'type'    => 'date',
				'require' => 'true',
				'filter'  => [ '\Platform_Shell\PlatformShellDateTime', 'date_filter' ],
			],
			[
				'label'   => _x( 'Date d’annonce des gagnants', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'date_winners_announcement',
				'type'    => 'date',
				'require' => 'true',
				'filter'  => [ '\Platform_Shell\PlatformShellDateTime', 'date_filter' ],
			],
		];

		$videos_meta_fields = [
			[
				'label' => _x( 'Vidéos', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'  => _x(
					'Vous pouvez ajouter plusieurs vidéos à partir de YouTube ou Vimeo. Entrez les liens (URL) des vidéos séparés par des virgules.',
					'cpt-contest-field',
					'platform-shell-plugin'
				),
				'id'    => $this->configs->base_metadata_prefix . 'video',
				'type'  => 'text',
			],
		];

		$winners_meta_fields = [
			[
				'label'   => _x( 'Lien vers l’article sur les lauréats', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'    => '',
				'id'      => $this->configs->metadata_prefix . 'winners_announcement_article',
				'type'    => 'select',
				'options' => $this->get_articles_list(),
			],
			[
				'label' => _x( 'Résumé', 'cpt-contest-field', 'platform-shell-plugin' ),
				'desc'  => '',
				'id'    => $this->configs->metadata_prefix . 'resume',
				'type'  => 'textarea',
			],
		];

		$this->contest_metadata_fields['prize']   = $prize_meta_fields;
		$this->contest_metadata_fields['terms']   = $terms_meta_fields;
		$this->contest_metadata_fields['dates']   = $dates_meta_fields;
		$this->contest_metadata_fields['videos']  = $videos_meta_fields;
		$this->contest_metadata_fields['winners'] = $winners_meta_fields;

		array_walk(
			$this->contest_metadata_fields,
			function ( &$fields ) {
				foreach ( $fields as $field ) {
					if ( isset( $field['filter'] ) && false !== $field['filter'] ) {
						add_filter( 'sanitize_post_meta_' . $field['id'], $field['filter'] );
					}
				}
			}
		);
	}

	/**
	 * Méthode get_organizer_list
	 *
	 * @return array
	 */
	private function get_organizer_list() {
		return $this->cpt_helper->get_simple_select_list_from_option( 'platform_shell_option_contests_organizers_list', 'platform-shell-settings-page-site-sections-contests', '' );
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
	 * Méthode get_contest_type_list_option_map
	 *
	 * @return array
	 */
	private function get_contest_type_list_option_map() {

		/* Return clean parsed list. */
		$contest_type_list               = [];
		$contest_type_option_json_config = $this->plugin_settings->get_option(
			'platform_shell_option_contests_type_list',
			'platform-shell-settings-page-site-sections-contests',
			''
		);

		if ( '' !== $contest_type_option_json_config ) {

			$parse_associative = true;
			$parsed_option     = json_decode( $contest_type_option_json_config, $parse_associative );

			if ( isset( $parsed_option ) ) {
				foreach ( $parsed_option as $key => $option ) {
					$type_key = $option['type'] ? $option['type'] : null;
					if ( null !== $type_key && '' !== $type_key ) {
						$contest_type_list[ $type_key ] = $option;
					}
				}
			}
		}
		return $contest_type_list;
	}

	/**
	 * Méthode get_contest_type_list_for_select
	 *
	 * @return array
	 */
	private function get_contest_type_list_for_select() {

		$contest_type_list    = [];
		$type_list_option_map = $this->get_contest_type_list_option_map();

		if ( count( $type_list_option_map ) !== 0 ) {
			foreach ( $type_list_option_map as $key => $option ) {
				$contest_type_list[ $key ] = $key;
			}
			// Ajouter 'choisir une option', préserver l'ordre ( https://stackoverflow.com/questions/1371016/php-prepend-associative-array-with-literal-keys ).
			// La clé vide détermine la valeur non assignée ( pour validation du required ).
			$contest_type_list = array( '' => _x( 'Choisir une option', 'cpt-contest-field', 'platform-shell-plugin' ) ) + $contest_type_list;
		}
		return $contest_type_list;
	}

	/**
	 * Méthode get_contest_metadata
	 *
	 * @return array
	 */
	private function get_contest_metadata() {
		return $this->contest_metadata_fields;
	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param int      $post_id   The post ID.
	 * @param \WP_Post $post      The post object.
	 * @param bool     $update    Whether this is an existing post being updated or not.
	 */
	public function save_meta_box( $post_id, $post, $update ) {

		if ( false !== wp_verify_nonce( $_REQUEST[ $this->configs->post_type_name ], $this->configs->post_type_name . '_' . $post_id ) ) {

			// Remettre à zéro les messages à afficher sur l'état de publication du concours ( information supplémentaire par rapport aux fonctionnalités de WordPress.
			$this->init_admin_notices();
			$this->admin_notices->clear_notices();

			$required_missing    = false; /* Validation des champs requis. Si un champs requis est manquant, lever le flag. */
			$contest_meta_fields = $this->get_contest_metadata();
			$field_keys          = array( 'prize', 'terms', 'dates', 'videos', 'winners' );

			foreach ( $field_keys as $field_key ) {
				foreach ( $contest_meta_fields[ $field_key ] as $field ) {
					$this->update_change( $post_id, $field );
				}
			}

			// Update le champ texte du prix vedette.
			$main_prize = isset( $_POST['platform_shell_meta_contest_main_prize'] ) ? sanitize_text_field( $_POST['platform_shell_meta_contest_main_prize'] ) : '';
			update_post_meta( $post_id, 'platform_shell_meta_contest_main_prize', $main_prize );

			// Update l'image du prix vedette.
			$main_image = isset( $_POST['platform_shell_meta_contest_main_prize_image'] ) ? sanitize_text_field( $_POST['platform_shell_meta_contest_main_prize_image'] ) : '';
			update_post_meta( $post_id, 'platform_shell_meta_contest_main_prize_image', $main_image );

			// Update contest gallery image.
			$attachment_ids = isset( $_POST['platform_shell_meta_gallery'] ) ? array_filter( explode( ',', sanitize_text_field( $_POST['platform_shell_meta_gallery'] ) ) ) : [];
			update_post_meta( $post_id, 'platform_shell_meta_gallery', implode( ',', $attachment_ids ) );

			$this->validate_for_publishing( $post_id );
		}
	}

	/**
	 * Méthode update_change
	 *
	 * @param int   $post_id    The post ID.
	 * @param array $field      Les donnée du champ de métadonnées.
	 */
	private function update_change( $post_id, &$field ) {

		if ( false !== wp_verify_nonce( $_REQUEST[ $this->configs->post_type_name ], $this->configs->post_type_name . '_' . $post_id ) ) {

			$old = get_post_meta( $post_id, $field['id'], true );

			if ( isset( $field['filter'] ) && false !== $field['filter'] ) {
				add_filter( 'sanitize_post_meta_' . $field['id'], $field['filter'] );
			}

			$new = ( isset( $_POST[ $field['id'] ] ) ) ? sanitize_meta( $field['id'], wp_kses_post( $_POST[ $field['id'] ] ), 'post' ) : $old;

			if ( $new && $new !== $old ) {
				update_post_meta( $post_id, $field['id'], $new );
			} elseif ( '' === $new && $old ) {
				delete_post_meta( $post_id, $field['id'], $old );
			}
		}
	}

	/**
	 * Méthode validate_for_publishing
	 *
	 * @param int $post_id    The post ID.
	 */
	private function validate_for_publishing( $post_id ) {

		if ( false !== wp_verify_nonce( $_REQUEST[ $this->configs->post_type_name ], $this->configs->post_type_name . '_' . $post_id ) ) {
			/*
			 * Traitement particulier.
			 *
			 * La validation des champs requis et le "flow" d'affichage d'erreur est difficile à gérer
			 * dans le contexte admin. Cette solution avec admin_notices va permettre de :
			 * - Valider si les champs requis sont biens remplis.
			 *   - ( optionnnel ) Valider si les champs sont dans le formats demandés.
			 * - Annuler la publication et remettre à l'état brouillon si les requis ne sont pas comblés.
			 * - Afficher un message informatif au gestionnnaire dans le haut de la page.
			 * - Laisser une certaine flexibilité de saisie incomplète / invalide dans l'état brouillon.
			 */
			$validation_messages = $this->validate_required( $post_id );

			// Pourrait faire distinction entre erreur erreurs et warning. Bloquer sur erreur seulement?
			if ( ! empty( $validation_messages ) ) {
				$this->admin_notices->add_message( _x( '<stong>Attention : </stong>  Le processus de validation a  détecté des erreurs dans le concours lors de la dernière opération de publication, de mise à jour ou d’enregistrement du brouillon.', 'contest-validation', 'platform-shell-plugin' ), 'error' );
				foreach ( $validation_messages as $validation_message ) {
					$padding = ' - ';
					$this->admin_notices->add_message( ( $padding . $validation_message['message'] ), $validation_message['type'] );
				}

				if ( 'publish' === sanitize_text_field( $_POST['post_status'] ) ) {
					$this->cancel_publish( $post_id );
				}

				// Afficher les erreurs de validation détectées ( avant ou après )?
				$this->admin_notices->add_message( _x( '- <stong>Attention : </stong> Le concours est en mode brouillon ou attente de relecture et ne peut pas être vu par les utilisateurs du médialab', 'contest-validation', 'platform-shell-plugin' ), 'warning' );
			} else {

				$this->admin_notices->add_message( _x( '- Information : Le processus de validation n’a pas détecté d’erreurs dans le concours lors de la dernière opération de publication, de mise à jour ou d’enregistrement du brouillon.', 'contest-validation', 'platform-shell-plugin' ), 'success', Admin_Notices::MESSAGE_LIFETIME_USE_ONCE );

				if ( 'publish' === sanitize_text_field( $_POST['post_status'] ) ) {
					$this->admin_notices->add_message( _x( '- Information : Le concours est publié et peut être vu par les utilisateurs du médialab.', 'contest-validation', 'platform-shell-plugin' ), 'success' );
				} else {
					$this->admin_notices->add_message( _x( '- <stong>Attention : </stong> Le concours est en mode brouillon ou attente de relecture et ne peut pas être vu par les utilisateurs du médialab.', 'contest-validation', 'platform-shell-plugin' ), 'warning' );
				}
			}
		}
	}

	/**
	 * Méthode validate_field_required
	 *
	 * @param int      $post_id                The post ID.
	 * @param array    $field                  Les donnée du champ de métadonnées.
	 * @param string[] $validation_messages    Les messages de validations à afficher à l'usager.
	 */
	private function validate_field_required( $post_id, $field, &$validation_messages ) {
		if ( isset( $field['require'] ) ) {
			$data = get_post_meta( $post_id, $field['id'], true );
			if ( ! isset( $data ) || empty( $data ) || '' === $data ) {

				/* translators: %s: Label du champ requis */
				$message = sprintf( _x( 'Erreur : Il faut compléter la saisie de : "%1$s".', 'contest-validation', 'platform-shell-plugin' ), $field['label'] );

				array_push(
					$validation_messages,
					[
						'message' => $message,
						'type'    => 'error',
					]
				);
			}
		}
	}

	/**
	 * Méthode validate_required
	 *
	 * @param int $post_id    The post ID.
	 * @return array
	 */
	private function validate_required( $post_id ) {

		$validation_messages = [];
		$contest_post        = get_post( $post_id );

		/*
		 * Validation brute à partir de l'objet WP_POST
		 */

		// Titre.
		if ( empty( $contest_post->post_title ) ) {
			array_push(
				$validation_messages,
				[
					'message' => _x( 'Erreur : Il faut compléter la saisie du titre.', 'contest-validation', 'platform-shell-plugin' ),
					'type'    => 'error',
				]
			);
		}

		// Texte descriptif ( body du post ).
		if ( empty( $contest_post->post_content ) ) {
			array_push(
				$validation_messages,
				[
					'message' => _x( 'Erreur : Il faut compléter la saisie du texte descriptif.', 'contest-validation', 'platform-shell-plugin' ),
					'type'    => 'error',
				]
			);
		}

		// Image principale.
		if ( ! has_post_thumbnail( $post_id ) ) {
			array_push(
				$validation_messages,
				[
					'message' => _x( 'Erreur : Il faut entrer une image principale (image mise en avant).', 'contest-validation', 'platform-shell-plugin' ),
					'type'    => 'error',
				]
			);
		}

		// Métadonnées:
		// On a des structures de données qui définissent le "required" mais c'est incomplet?
		$contest_meta_fields = $this->get_contest_metadata();

		// Listes ( ex. videos, .. ).
		foreach ( $contest_meta_fields as $contest_meta_field ) {
			// Devrait faire function récursive. Un seul niveau par contre.
			if ( is_array( $contest_meta_field ) ) {
				foreach ( $contest_meta_field as $array_contest_meta_field ) {
					$this->validate_field_required( $post_id, $array_contest_meta_field, $validation_messages );
				}
			} else {
				$this->validate_field_required( $post_id, $contest_meta_field, $validation_messages );
			}
		}

		return $validation_messages;
	}

	/**
	 * Méthode cancel_publish
	 *
	 * @param int $post_id    The post ID.
	 */
	private function cancel_publish( $post_id ) {
		remove_action(
			'save_post',
			[
				&$this,
				'save_meta_box',
			],
			10,
			3
		); // prévenir appel récursif.

		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'draft',
			]
		);

		$this->admin_notices->add_message(
			_x(
				'- <stong>Attention : </stong> Retour automatique à l’état brouillon : Les problèmes identifiés devront être corrigés avant que vous puissiez compléter la publication du concours.',
				'contest-cancel-publish',
				'platform-shell-plugin'
			),
			'warning',
			Admin_Notices::MESSAGE_LIFETIME_USE_ONCE
		);

		add_action( 'save_post', array( &$this, 'save_meta_box' ), 10, 3 );
	}
}
