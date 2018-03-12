<?php
/**
 * Platform_Shell\CPT\Project\Project_Metaboxes
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Project;

use Platform_Shell\CPT\CPT_Metaboxes;

/**
 * Platform_Shell Project_Metaboxes
 *
 * @class    Project_Metaboxes
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Project_Metaboxes extends CPT_Metaboxes {

	/**
	 * Constructeur
	 *
	 * @param Project_Configs $configs    Configuration du post type.
	 */
	public function __construct( Project_Configs $configs ) { // phpcs:ignore Generic --PHPCS ne prends pas compte l'injection de paramètres.
		parent::__construct( $configs );
	}

	/**
	 * Méthode add_cpt_meta_boxes
	 */
	public function add_cpt_meta_boxes() {
		$post_type_name = $this->configs->post_type_name;
		add_meta_box(
			'platform-shell-project-type',
			_x(
				'Informations sur le projet.',
				'cpt-project-metaboxes',
				'platform-shell-plugin'
			),
			[
				$this,
				'render_project_info_box',
			],
			$post_type_name,
			'normal',
			'high'
		);
	}
	/**
	 * Méthode render_project_gallery
	 *
	 * Output Contest Images Gallery.
	 */
	public function render_project_gallery() {
		global $post;
		$this->render_metadata_not_modifiable_in_admin_notice();
		?>
		<div id="product_images_container">
			<ul class="product_images">
				<?php
				if ( metadata_exists( 'post', $post->ID, 'platform_shell_meta_gallery' ) ) {
					$project_image_gallery = get_post_meta( $post->ID, 'platform_shell_meta_gallery', true );
					foreach ( $project_image_gallery as $image ) {
						echo '<li><img src="' . esc_url( $image ) . '" width="200" height="150" /></li>';
					}
				} else {
					echo '<p>' . esc_html_x( "Le projet ne contient pas de galerie d'image", 'cpt-project-metaboxes', 'platform-shell-plugin' ) . '</p>';
				}
				?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Méthode render_metadata_not_modifiable_in_admin_notice
	 */
	public function render_metadata_not_modifiable_in_admin_notice() {
		global $wp_query;

		$permalink   = null;
		$post        = get_post();
		$post_status = $post->post_status;
		$show_link   = false;

		$permalink = get_permalink();

		// Avertissement : Vous allez modifier un projet dont vous n'êtes pas l'auteur. Cette procédure ne devrait être utilisée qu'avec...
		if ( isset( $permalink ) ) {
			echo '<p class="dashicons-before dashicons-warning">' . esc_html_x( 'Notes importantes :', 'cpt-project-metaboxes', 'platform-shell-plugin' ) . '</p>';
			echo '<p>' . wp_kses_post( _x( '- Seules quelques options peuvent être modifiés par les gestionnaires / administrateurs dans cet écran.', 'cpt-project-metaboxes', 'platform-shell-plugin' ) ) . '</p>';
			echo '<p>' . wp_kses_post( _x( '- Pour mettre le projet en modération, vous pouvez le faire en publiant le projet avec la <strong>visibilité privé</strong>.', 'cpt-project-metaboxes', 'platform-shell-plugin' ) ) . '</p>';
			$show_link = true;
		} else {
			if ( 'private' === $post_status ) {
				$in_moderation_message = wp_kses_post( _x( 'Ce projet est actuellement en modération et ne peux être vu par les utilisateurs (mode visibilité : privé). <br />Seul les gestionnaires ou les administrateurs peuvent modifier le mode de visibilité.', 'cpt-project-metaboxes', 'platform-shell-plugin' ) );
				echo '<div class="notice-warning"><p>' . $in_moderation_message . '</p></div>'; // phpcs:ignore WordPress --Cette variable est échapée à la ligne au dessus.
				$show_link = true;
			} else {
				// Vérifier si publish.
				$create_project_page_link = do_shortcode( '[platform_shell_permalink_by_page_id id="platform-shell-page-project-create-page"]' );
				/* translators: %1$s: lien vers le formulaire de création */
				echo '<p class="dashicons-before dashicons-warning">' . wp_kses_post( sprintf( _x( ' Pour créer un projet avec tous les champs disponibles, veuillez plutôt utiliser le <a href="%1$s">formulaire</a> de création de projet.', 'cpt-project-metaboxes', 'platform-shell-plugin' ), $create_project_page_link ) ) . '</p>';
			}
		}

		if ( $show_link ) {
			echo '<p>' . wp_kses_post( _x( 'Les données du projet peuvent être modifiées  en utilisant le bouton ’Modifier le projet’ à partir de la page principale du projet.', 'cpt-project-metaboxes', 'platform-shell-plugin' ) ) . '</p>';
			echo '<p><a href="' . esc_url( $permalink ) . '">' . esc_url( $permalink ) . '</a></p>';
		}
	}

	/**
	 * Méthode render_project_info_box
	 */
	public function render_project_info_box() {
		$this->render_metadata_not_modifiable_in_admin_notice();
	}
}
