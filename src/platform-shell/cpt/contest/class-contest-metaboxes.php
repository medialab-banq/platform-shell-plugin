<?php
/**
 * Platform_Shell\CPT\Contest\Contest_Metaboxes
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Contest;

use Platform_Shell\CPT\CPT_Metaboxes;
use Platform_Shell\Fields\Fields_Helper;
use WP_Post;

/**
 * Contest_Metaboxes
 *
 * @class        Contest_Metaboxes
 * @description  Classe de base pour les définitions de post types personalisées.
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class Contest_Metaboxes extends CPT_Metaboxes {

	/**
	 * Tableau contenant l'information des métadonnées
	 *
	 * @var array
	 */
	private $temporary_fix_contest_metadata; /* todo_refactoring : Dépendance problématique. */

	/**
	 * Constructeur.
	 *
	 * @param Contest_Configs $configs         Instance des configurations du post type.
	 * @param Fields_Helper   $field_helper    Classe helper pour les differents champs des metaboxes.
	 */
	public function __construct( Contest_Configs $configs, Fields_Helper $field_helper ) { // phpcs:ignore Generic --PHPCS ne prends pas compte l'injection de paramètres.

		parent::__construct( $configs, $field_helper );
	}

	/**
	 * Méthode set_temporary_fix_contest_metadata
	 *
	 * @param array $temporary_fix_contest_metadata    Tableau contenant l'information des métadonnées.
	 */
	public function set_temporary_fix_contest_metadata( array $temporary_fix_contest_metadata ) {
		$this->temporary_fix_contest_metadata = $temporary_fix_contest_metadata;
	}

	/**
	 * Méthode get_contest_metadata
	 *
	 * @return array
	 */
	private function get_contest_metadata() {
		return $this->temporary_fix_contest_metadata;
	}

	/**
	 * Add Meta Box to posts type.
	 */
	public function add_cpt_meta_boxes() {
		$post_type_name = $this->configs->post_type_name;
		add_meta_box( 'platform-shell-contest-images', _x( 'Galerie d’images', 'cpt-contest-metaboxes', 'platform-shell-plugin' ), array( $this, 'render_contest_image_gallery_meta_box' ), $post_type_name, 'side', 'low' );
		add_meta_box( 'platform-shell-contest-video', _x( 'Vidéo', 'cpt-contest-metaboxes', 'platform-shell-plugin' ), array( $this, 'render_contest_video_meta_box' ), $post_type_name, 'side', 'low' );
		add_meta_box( 'platform-shell-contest-date', _x( 'Dates', 'cpt-contest-metaboxes', 'platform-shell-plugin' ), array( $this, 'render_contest_date_meta_box' ), $post_type_name, 'normal', 'high' );
		add_meta_box( 'platform-shell-contest-terms', _x( 'Modalités', 'cpt-contest-metaboxes', 'platform-shell-plugin' ), array( $this, 'render_contest_terms_meta_box' ), $post_type_name, 'normal', 'high' );
		add_meta_box( 'platform-shell-contest-prize', _x( 'Prix et jury', 'cpt-contest-metaboxes', 'platform-shell-plugin' ), array( $this, 'render_contest_prize_meta_box' ), $post_type_name, 'normal', 'high' );
		add_meta_box( 'platform-shell-contest-projects', _x( 'Projets', 'cpt-contest-metaboxes', 'platform-shell-plugin' ), array( $this, 'render_contest_project_meta_box' ), $post_type_name, 'normal', 'high' );
	}

	/**
	 * Output Contest Images Gallery.
	 *
	 * @param WP_Post $post    Instance du Post associé à cette métaboxe.
	 * @param array   $args    Tableau des arguments envoyés lors de la création de la métaboxe.
	 */
	public function render_contest_image_gallery_meta_box( WP_Post $post, array $args ) {

		wp_nonce_field( $this->configs->post_type_name . '_' . $post->ID, $this->configs->post_type_name );
		?>
		<div id="product_images_container">
			<ul class="product_images">
				<?php
				if ( metadata_exists( 'post', $post->ID, 'platform_shell_meta_gallery' ) ) {
					$contest_image_gallery = get_post_meta( $post->ID, 'platform_shell_meta_gallery', true );
				} else {
					// Backwards compatibilité?
					$attachment_ids        = get_posts( 'post_parent=' . $post->ID . '&numberposts=-1&post_type=attachment&orderby=menu_order&order=ASC&post_mime_type=image&fields=ids' );
					$attachment_ids        = array_diff( $attachment_ids, array( get_post_thumbnail_id() ) );
					$contest_image_gallery = implode( ',', $attachment_ids );
				}

				$attachments = array_filter( explode( ',', $contest_image_gallery ) );
				$update_meta = false;

				if ( ! empty( $attachments ) ) {
					foreach ( $attachments as $attachment_id ) {
						$attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );

						// If attachment image is missing, skip the rest of this iteration.
						if ( empty( $attachment ) ) {
							$update_meta = true; // We need to recalculate the saved metadata.
							continue;
						}

						$escaped_attachment_id      = esc_attr( $attachment_id );
						$escaped_localised_data_tip = esc_attr_x( 'Effacer l’image', 'cpt-contest-metaboxes', 'platform-shell-plugin' );
						$localized_link_text        = esc_html_x( 'Effacer', 'cpt-contest-metaboxes', 'platform-shell-plugin' );

						// phpcs:ignore WordPress --Le code est généré par le système, et les arguments sont échappés de façon correcte.
						echo <<<EOT
				<li class="image" data-attachment_id="{$escaped_attachment_id}">
					{$attachment}
					<ul class="actions">
						<li>
							<a href="#" class="delete tips" data-tip="{$escaped_localised_data_tip}">
								{$localized_link_text}
							</a>
						</a>
					</ul>
				</li>
EOT;
						// Rebuild ids to be saved.
						$updated_gallery_ids[] = $attachment_id;
					}
					// Need to update product meta to set new gallery ids.
					if ( $update_meta ) {
						update_post_meta( $post->ID, 'platform_shell_meta_gallery', implode( ',', $updated_gallery_ids ) );
					}
				}
				?>
			</ul>
			<input type="hidden" id="platform_shell_meta_gallery" name="platform_shell_meta_gallery" value="<?php echo esc_attr( $contest_image_gallery ); ?>" />
		</div>
		<p class="add_product_images hide-if-no-js">
			<a href="#" data-choose="<?php echo esc_attr_x( 'Ajouter une image', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>" data-update="<?php echo esc_attr_x( 'Ajouter une image', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>" data-delete="<?php echo esc_attr_x( 'Effacer', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>" data-text="<?php echo esc_attr_x( 'Effacer', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>">
				<?php echo esc_html_x( 'Ajouter une image', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Sortie Contest Video MediaBox.
	 *
	 * @param WP_Post $post    Instance du Post associé à cette métaboxe.
	 * @param array   $args    Tableau des arguments envoyés lors de la création de la métaboxe.
	 */
	public function render_contest_video_meta_box( WP_Post $post, array $args ) {
		$videos_meta_fields = $this->get_contest_metadata();
		echo '<table class="form-table">';
		foreach ( $videos_meta_fields['videos'] as $field ) {
			$this->field_helper->set_fields( $field );
		} // end foreach
		echo '</table>'; // End table.
	}

	/**
	 * Sortie Contest Date MediaBox.
	 *
	 * @param WP_Post $post    Instance du Post associé à cette métaboxe.
	 * @param array   $args    Tableau des arguments envoyés lors de la création de la métaboxe.
	 */
	public function render_contest_date_meta_box( WP_Post $post, array $args ) {
		$dates_meta_fields = $this->get_contest_metadata();
		echo '<div>' . esc_html_x( 'Veuillez cliquer sur les champs grisés suivants pour afficher le sélecteur de date.', 'cpt-contest-metaboxes', 'platform-shell-plugin' ) . '</div>';
		echo '<br/>';
		echo '<div>' . esc_html_x( 'Note : Celui-ci pourrait ne pas s’afficher correctement sur certaines plateformes (ex. : IOS).', 'cpt-contest-metaboxes', 'platform-shell-plugin' ) . '</div>';
		echo '<table class="form-table">';
		foreach ( $dates_meta_fields['dates'] as $field ) {
			$this->field_helper->set_fields( $field );
		}
		echo '</table>';
	}

	/**
	 * Output Contest Terms MediaBox.
	 *
	 * @param WP_Post $post    Instance du Post associé à cette métaboxe.
	 * @param array   $args    Tableau des arguments envoyés lors de la création de la métaboxe.
	 */
	public function render_contest_terms_meta_box( WP_Post $post, array $args ) {
		$terms_meta_fields = $this->get_contest_metadata();

		if ( metadata_exists( 'post', $post->ID, 'platform_shell_meta_contest_sponsor_image' ) ) {
			$main_banner_image = get_post_meta( $post->ID, 'platform_shell_meta_contest_sponsor_image', true );
		} else {
			$main_banner_image = '';
		}
		$attachment = $main_banner_image;
		?>
		<label>
			<?php echo esc_html_x( 'Image du commanditaire', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>
			<span class="required" aria-required="true">*</span>
		</label>
		<p class="add_banner_images hide-if-no-js">
			<a href="#" data-choose="<?php echo esc_attr_x( 'Ajouter une image', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>" data-update="<?php echo esc_attr_x( 'Ajouter une image', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>" data-delete="<?php echo esc_attr_x( 'Effacer', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>" data-text="<?php echo esc_attr_x( 'Effacer', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>">
				<?php echo esc_html_x( 'Ajouter une image', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>
			</a>
		</p>
		<div id="main_banner_image">
			<?php
			if ( ! empty( $attachment ) ) {
				echo '<span class="banner_image" data-attachment_id="' . esc_attr( $attachment ) . '">';
				echo wp_get_attachment_image( $attachment, 'thumbnail' );
				echo '<ul class="actions"><li><a href="#" class="delete" >' . esc_html_x( 'Effacer', 'cpt-contest-metaboxes', 'platform-shell-plugin' ) . '</a></li></ul></span>';
			} else {
				echo '<p class="image"></p>';
			}
			?>
			<input type="hidden" id="platform_shell_meta_contest_sponsor_image" name="platform_shell_meta_contest_sponsor_image" value="<?php echo esc_attr( $main_banner_image ); ?>" />
		</div>
		<?php
		echo '<table class="form-table">';
		foreach ( $terms_meta_fields['terms'] as $field ) {
			$this->field_helper->set_fields( $field );
		} // end foreach
		echo '</table>'; // End table.
	}

	/**
	 * Sortie Contest Prize And Judges MediaBox.
	 *
	 * @param WP_Post $post    Instance du Post associé à cette métaboxe.
	 * @param array   $args    Tableau des arguments envoyés lors de la création de la métaboxe.
	 */
	public function render_contest_prize_meta_box( WP_Post $post, array $args ) {
		$prize_meta_fields = $this->get_contest_metadata();
		$main_prize        = get_post_meta( $post->ID, 'platform_shell_meta_contest_main_prize', true );

		if ( metadata_exists( 'post', $post->ID, 'platform_shell_meta_contest_main_prize_image' ) ) {
			$main_prize_image = get_post_meta( $post->ID, 'platform_shell_meta_contest_main_prize_image', true );
		} else {
			$main_prize_image = '';
		}

		$attachment = $main_prize_image;
		echo '<table class="form-table">';
		?>
		<tr><td>
				<fieldset>
					<legend><h4><?php echo esc_html_x( 'Prix vedette', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?></h4></legend>
					<div class="col-xs-12" style="margin-bottom:12px;">
						<label for="platform_shell_meta_contest_main_prize">
							<?php echo esc_html_x( 'Prix vedette', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>
							<span class="required" aria-required="true">*</span>
						</label><br>
						<input type="text" name="platform_shell_meta_contest_main_prize" id="platform_shell_meta_contest_main_prize" value="<?php echo esc_attr( $main_prize ); ?>" size='95'>
						<br><span class="description"></span>
					</div>
					<div class="col-xs-12" >
						<label><?php echo esc_html_x( 'Image du prix vedette', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?><span class="required" aria-required="true">*</span></label>
						<p class="add_prize_images hide-if-no-js">
							<a href="#" data-choose="<?php echo esc_attr_x( 'Ajouter une image', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>" data-update="<?php echo esc_attr_x( 'Ajouter une image', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>" data-delete="<?php echo esc_attr_x( 'Effacer', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>" data-text="<?php echo esc_attr_x( 'Effacer', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>">
								<?php echo esc_html_x( 'Ajouter une image', 'cpt-contest-metaboxes', 'platform-shell-plugin' ); ?>
							</a>
						</p>
						<div id="main_prize_image">
							<?php
							if ( ! empty( $attachment ) ) {
								echo '<span class="prize_image" data-attachment_id="' . esc_attr( $attachment ) . '">';
								echo wp_get_attachment_image( $attachment, 'thumbnail' );
								echo '<ul class="actions"><li><a href="#" class="delete" >' . esc_html_x( 'Effacer', 'cpt-contest-metaboxes', 'platform-shell-plugin' ) . '</a></li></ul></span>';
							} else {
								echo '<p class="image"></p>';
							}
							?>
							<input type="hidden" id="platform_shell_meta_contest_main_prize_image" name="platform_shell_meta_contest_main_prize_image" value="<?php echo esc_attr( $main_prize_image ); ?>" />
						</div>
					</div>
				</fieldset>
			</td></tr>
		<?php
		foreach ( $prize_meta_fields['prize'] as $field ) {
			$this->field_helper->set_fields( $field );
		} // end foreach
		echo '</table>'; // End table.
	}

	/**
	 * Sortie Contest Associated Projects MediaBox.
	 *
	 * @param WP_Post $post    Instance du Post associé à cette métaboxe.
	 * @param array   $args    Tableau des arguments envoyés lors de la création de la métaboxe.
	 */
	public function render_contest_project_meta_box( WP_Post $post, array $args ) {
		echo '<table class="form-table"><tr><td>';
		echo '<strong>' . esc_html_x( 'Projets inscrits', 'cpt-contest-metaboxes', 'platform-shell-plugin' ) . '</strong><br />';
		echo get_projects_x_contest( $post->ID ); // phpcs:ignore WordPress --Contenu généré par l'application.
		echo '</td></tr></table>';
		$winners_meta_fields = $this->get_contest_metadata();
		echo '<table class="form-table">';
		foreach ( $winners_meta_fields['winners'] as $field ) {
			$this->field_helper->set_fields( $field );
		} // end foreach
		echo '</table>'; // End table.
	}
}
