<?php
/**
 * Platform_Shell\CPT\Activity\Activity_Metaboxes
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Activity;

use Platform_Shell\CPT\CPT_Metaboxes;
use Platform_Shell\Fields\Fields_Helper;
use WP_Post;

/**
 * Activity_Metaboxes class.
 */
class Activity_Metaboxes extends CPT_Metaboxes {

	const METABOX_NAME = 'platform-shell-activity-datetime';

	/**
	 * Liste des champs de métadonnées pour le Post Type pour les activités
	 *
	 * @var array
	 */
	private $activity_metadata_fields = [];

	/**
	 * Constructeur.
	 *
	 * @param Activity_Configs $configs         Instance de la configuration du Post Type pour les activités.
	 * @param Fields_Helper    $field_helper    Instance de la classe helper pour générer les champs divers.
	 */
	public function __construct( Activity_Configs $configs, Fields_Helper $field_helper ) {

		parent::__construct( $configs, $field_helper );
		$this->set_activity_meta_fields();
	}

	/**
	 * Add Meta Box to posts type.
	 */
	public function add_cpt_meta_boxes() {

		add_meta_box(
			self::METABOX_NAME,
			_x(
				'Information sur l’activité',
				'cpt-activity-metaboxes',
				'platform-shell-plugin'
			),
			[
				$this,
				'render_metadata_tab',
			],
			$this->configs->post_type_name,
			'normal',
			'high'
		);
	}

	/**
	 * Méthode set_temporary_fix_activity_metadata
	 *
	 * @param array $activity_metadata_fields    Tableau contenant l'information des métadonnées.
	 */
	public function set_temporary_fix_activity_metadata( array $activity_metadata_fields ) {
		$this->activity_metadata_fields = $activity_metadata_fields;
	}

	/**
	 * Définition des custom fields et des filtres de validation.
	 */
	private function set_activity_meta_fields() {

		// Ajouter filtres de validation.
		foreach ( $this->activity_metadata_fields as $field ) {

			if ( isset( $field['filter'] ) && false !== $field['filter'] ) {

				add_filter( 'sanitize_post_meta_' . $field['id'], $field['filter'] );
			}
		}
	}

	/**
	 * Getter pour la liste des champs de métadonnées pour le Post Type pour les activités
	 *
	 * @return array
	 */
	public function get_activity_meta_fields() {

		return $this->activity_metadata_fields;
	}

	/**
	 * Affiche la table contenant la les métadonnées de date/heure.
	 *
	 * @param WP_Post $post    Instance du Post associé à cette métaboxe.
	 * @param array   $args    Tableau des arguments envoyés lors de la création de la métaboxe.
	 */
	public function render_metadata_tab( WP_Post $post, array $args ) {

		$datetime_fields = $this->get_activity_meta_fields();
		echo '<table class="form-table">';

		foreach ( $datetime_fields as $field ) {

			$this->field_helper->set_fields( $field );
		}

		wp_nonce_field( self::METABOX_NAME . '_' . $post->ID, 'save_activity_meta' );

		echo '</table>';
	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post The post object.
	 * @param bool     $update Whether this is an existing post being updated or not.
	 */
	public function save( $post_id, $post, $update ) {

		if ( false !== wp_verify_nonce( $_REQUEST['save_activity_meta'], self::METABOX_NAME . '_' . $post_id ) ) {

			$meta_fields = $this->get_activity_meta_fields();

			foreach ( $meta_fields as $field ) {

				$old = get_post_meta( $post_id, $field['id'], true );

				// Missing date format validation.
				$new = ( isset( $_POST[ $field['id'] ] ) ) ? sanitize_text_field( $_POST[ $field['id'] ] ) : $old;
				$new = sanitize_meta( $field['id'], $new, 'post' );

				if ( $new && $new !== $old ) {

					update_post_meta( $post_id, $field['id'], $new );

				} elseif ( '' === $new && $old ) {

					delete_post_meta( $post_id, $field['id'], $old );

				}
			}
		}
	}
}
