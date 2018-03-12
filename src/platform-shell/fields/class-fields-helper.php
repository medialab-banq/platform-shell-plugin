<?php
/**
 * Platform_Shell\Fields\Fields_Helper
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Fields;

use Platform_Shell\PlatformShellDateTime;
use Exception;


/**
 * Platform_Shell Fields_Helper
 *
 * @class Fields_Helper
 * @description Classes utilitaire pour le création de champs de formulaire
 * @author Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class Fields_Helper {

	/**
	 * Constructeur.
	 */
	public function __construct() {}

	/**
	 * Méthode init
	 */
	public function init() {}

	/**
	 * Méthode set_fields
	 *
	 * Création des champs de formulaires pour les 'custom fields'.
	 *
	 * @param array $field    Les paramètres du champ qu'il faut afficher.
	 */
	public static function set_fields( $field ) {

		global $post;

		$meta           = get_post_meta( $post->ID, $field['id'], true );
		$require_fields = ( isset( $field['require'] ) && 'true' === $field['require'] ) ? '<span class="required">*</span>' : '';
		$required       = ( isset( $field['require'] ) && 'true' === $field['require'] ) ? 'required' : '';

		// Ignorer les champs metadata.
		if ( 'metadata' !== $field['type'] ) {
			echo '<tr><td><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . ' ' . wp_kses_post( $require_fields ) . '</label>';
		}
		switch ( $field['type'] ) {
			// Text.
			case 'text':
				echo '<input type="text" name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $meta ) . '" /><br /><span class="description">' . wp_kses_post( $field['desc'] ) . '</span>';
				break;
			// Textarea.
			case 'textarea':
				echo '<textarea name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" cols="80" rows="4">' . wp_kses_post( $meta ) . '</textarea><br /><span class="description">' . wp_kses_post( $field['desc'] ) . '</span>';
				break;
			case 'wysiwyg':
				echo '<span class="description">' . wp_kses_post( $field['desc'] ) . '</span>';
				wp_editor( $meta, $field['id'], $field['options'] );
				break;
			case 'date':
				// type = text au lieu de date.
				// Les changements à faire pour supporter IOS ( utiliser widget natif ), ne pas affiche widget natif + date picker en même temps ( Chrome )
				// + traitement des dates ( selon le widget utilisé ) demanderait plus de temps de validation.;
				// Tag readonly : solution pour minimiser les validations à faire sur manipulation de données.
				// Forcer utilisation du widget datepicker. Conséquence : Il ne sera pas possible d'utiliser IOS pour faire la saisie d'un concours.
				echo '<input type="text" id="' . esc_attr( $field['id'] . '_datepicker' ) . '" value="' . esc_attr( PlatformShellDateTime::format_localize_date( $meta ) ) . '" class="datepicker" readonly="readonly" />';
				echo '<input type="hidden" id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $meta ) . '" />';
				echo '<input type="hidden" id="' . esc_attr( $field['id'] . '_datetime' ) . '" name="' . esc_attr( $field['id'] . '_datetime' ) . '" value="' . esc_attr( $meta ) . '" />';
				$date_format = PlatformShellDateTime::get_jquery_ui_date_format();
				$save_format = PlatformShellDateTime::get_jquery_ui_save_date_format();
				$input_id    = esc_attr( $field['id'] );
				// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped --Le contenu proviens des méthode du système, ou est echappé de façon correcte.
				echo <<<EOT
<script type="text/javascript">
	jQuery( document ).ready( function ( $ ) {
		jQuery( '#{$input_id}_datepicker' ).datepicker( {
			dateFormat: "{$date_format}", /*  AAAA-MM-JJ selon tableau des métadonnées. dd-mm-yy ( jquery + jquery bug ) */
			altField: "#{$input_id}",
			altFormat: "{$save_format}"
		} );
	} );
</script>
EOT;
				break;
			case 'datetime':
				echo '<input type="datetime" id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $meta ) . '" class="datepicker" />';
				break;
			case 'datetime-local':
				echo '<input id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $meta ) . '" class="datepicker" />';
				break;
			// Select Box.
			case 'select':
				$output = '';
				// Fix bug. Si on change les configurations, la valeur enregistrée peut être différente des valeurs connues.
				// Ajouter la valeur manquante + todo : afficher message informatif?
				$count_options = count( $field['options'] );
				if ( 0 === $count_options ) {
					/* translators: %1$s: Nom du champs */
					$message = sprintf( _x( ' - Erreur : ( <strong>%1$s</strong> ) Aucunes valeurs disponibles pour l’affichage de la liste. Veuillez vérifier les configurations de la plateforme.', 'profile-admin-notice-warning', 'platform-shell-plugin' ), trim( $field['label'], '.' ), $meta );
					echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>'; /* Cette erreur sera affichée dans le haut de l'écran avec les autre notices. */
					echo esc_html_x( 'Erreur : Aucunes valeurs disponibles pour l’affichage de la liste. Veuillez vérifier les configurations de la plateforme.', 'profile-admin-notice-warning', 'platform-shell-plugin' ); /* Affichage au même niveau que le widget. */
					break;
				}
				$options_list        = self::add_meta_to_option_list_if_unknown_value( $field['options'], $meta );
				$count_options_after = count( $options_list );
				if ( $count_options !== $count_options_after /* Vérifier s'il y a eu ajout d'une clé. */ ) {
					/* translators: %1$s: Nom du champs, %2$s: Valeur actuelle */
					$message = sprintf( _x( ' - Attention : ( <strong>%1$s</strong> ) La valeur « %2$s » n’est pas définie dans la liste des valeurs connues et a été ajoutée automatiquement à la liste des choix disponibles.', 'profile-admin-notice-warning', 'platform-shell-plugin' ), trim( $field['label'], '.' ), $meta );
					// Cette erreur sera affichée dans le haut de l'écran avec les autre notices.
					echo '<div class="notice notice-warning"><p>' . wp_kses_post( $message ) . '</p></div>';
				}
				$output .= '<select class="of-input" name="' . $field['id'] . '" id="' . $field['id'] . '">';
				foreach ( $options_list as $key => $option ) {
					$selected = '';
					if ( '' !== $meta ) {
						if ( $meta === $key ) {
							$selected = ' selected="selected"';
						}
					}
					$output .= '<option' . $selected . ' value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
				}
				$output .= '</select><br /><span class="description">' . $field['desc'] . '</span>';
				echo $output; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped --Tous les champs sont soit echappés correctements ou généré par le système.
				break;
			case 'upload':
				if ( isset( $field['desc'] ) && '' !== $field['desc'] ) {
					echo '<p class="input-desc">' . wp_kses_post( $field['desc'] ) . '</p>';
				}
				echo '<input type="file" name="' . esc_attr( $field['id'] ) . '" ' . esc_attr( $required ) . ' accept="image/*">';
				echo '<input type="hidden" name="file_path" >';
				if ( isset( $meta ) && ! empty( $meta ) ) {
					echo wp_kses_post( $meta );
				}
				break;
			// Ignorer les champs metadata.
			case 'metadata':
				// Pas d'élément à créer. La structure de donnée pourrait être utilisée pour la validation par ex.
				break;
		} // end switch
		// Ignorer les champs metadata.
		if ( 'metadata' !== $field['type'] ) {
			echo '</td></tr>';
		}
	}

	/**
	 * Méthode add_meta_to_option_list_if_unknown_value
	 *
	 * @param array  $options_list   La liste d'options.
	 * @param string $meta           La valeur méta courante.
	 * @return array
	 */
	private static function add_meta_to_option_list_if_unknown_value( $options_list, $meta ) {
		$meta_is_known_value = false;
		if ( isset( $meta ) ) {
			foreach ( $options_list as $key => $option ) {
				if ( $meta === $key ) {
					$meta_is_known_value = true;
				}
			}
		}
		if ( ! $meta_is_known_value ) {
			// Ajouter la clé manquante pour permettre l'affichage correct du select.
			$options_list = $options_list + [
				$meta => $meta,
			];
		}
		return $options_list;
	}

	/**
	 * Méthode set_frontend_fields
	 *
	 * Création des champs de formulaires frontend.
	 *
	 * @param array   $field      La configuration du champ.
	 * @param string  $meta       La valeur de la métadonnée.
	 * @param integer $post_id    L'ID du post.
	 * @throws Exception          Lorsqu'un champ non supporté est demandé.
	 */
	public static function set_frontend_fields( $field, $meta = '', $post_id = null ) {

		$require_fields = ( isset( $field['require'] ) && 'true' === $field['require'] ) ? '<span class="required">*</span>' : '';
		$required       = ( isset( $field['require'] ) && 'true' === $field['require'] ) ? 'required' : '';
		$disabled       = ( isset( $field['disabled'] ) && 'true' === $field['disabled'] ) ? 'disabled' : '';
		$value          = ( isset( $field['value'] ) ) ? $field['value'] : '';
		$class          = ( isset( $field['class'] ) && '' !== $field['class'] ) ? $field['class'] : '';
		$max_length     = ( isset( $field['max_length'] ) && '' !== $field['max_length'] ) ? 'maxlength="' . esc_attr( $field['max_length'] ) . '"' : '';
		echo '<div class="form-row ' . esc_attr( $class ) . '">';
		echo '<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] . ' ' . $require_fields ) . '</label>';
		switch ( $field['type'] ) {
			// text.
			case 'text':
				if ( isset( $field['desc'] ) && '' !== $field['desc'] ) {
					echo '<p class="input-desc">' . wp_kses_post( $field['desc'] ) . '</p>';
				}

				// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped --Tous les champs sont soit echappés correctements ou généré par le système.
				echo '<input type="text" name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $meta ) . '"  ' . $required . ' ' . $max_length . ' ' . $disabled . ' /> ';
				break;
			// textarea.
			case 'textarea':
				if ( isset( $field['desc'] ) && '' !== $field['desc'] ) {
					echo '<p class="input-desc">' . wp_kses_post( $field['desc'] ) . '</p>';
				}

				// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped --Tous les champs sont soit echappés correctements ou généré par le système.
				echo '<textarea name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" rows="4" ' . $max_length . ' ' . $required . '>' . wp_kses_post( $meta ) . '</textarea> ';
				break;
			case 'wysiwyg':
				if ( isset( $field['desc'] ) && '' !== $field['desc'] ) {
					echo '<p>' . wp_kses_post( $field['desc'] ) . '</p>';
				}
				if ( $required ) {
					add_filter(
						'the_editor', [
							__CLASS__,
							'add_required_attribute_to_wp_editor',
						], 10, 1
					);
				}
				wp_editor(
					$meta, $field['id'], [
						'media_buttons' => false,
						'textarea_name' => $field['id'],
						'editor_height' => 250,
						'tinymce'       => [
							'menu'        => '[]', // Pour désactiver un menu, il faut envoyer un array json vide.
							'toolbar1'    => ' bold italic | link | alignleft aligncenter alignright alignjustify | bullist numlist ',
							'toolbar2'    => false,
							'elementpath' => false,
							'statusbar'   => false,
						],
					]
				);
				break;
			case 'date':
				throw new Exception( 'Classe Fields, set_frontend_fields() : Type de champs date non-supporté ( incomplet / non validé ).' );
			case 'datetime':
				throw new Exception( 'Classe Fields, set_frontend_fields() : Type de champs datetime non-supporté ( incomplet / non validé ).' );
			case 'datetime-local':
				throw new Exception( 'Classe Fields, set_frontend_fields() : Type de champs datetime-local non-supporté ( incomplet / non validé ).' );
			case 'select':
				if ( isset( $field['desc'] ) && '' !== $field['desc'] ) {
					echo '<p class="input-desc">' . wp_kses_post( $field['desc'] ) . '</p>';
				}
				$output = '<select class="of-input" name="' . $field['id'] . '" id="' . $field['id'] . '" ' . $required . ' >';
				foreach ( $field['options'] as $key => $option ) {
					$selected = '';
					if ( '' !== $meta && $meta === $key ) {
						$selected = ' selected="selected"';
					}
					$output .= '<option' . $selected . ' value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
				}
				$output .= '</select>';
				echo $output; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped --Tous les champs sont soit echappés correctements ou généré par le système.
				break;
			case 'upload':
				if ( isset( $field['desc'] ) && '' !== $field['desc'] ) {
					echo '<p class="input-desc">' . wp_kses_post( $field['desc'] ) . '</p>';
				}

				// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped --Tous les champs sont soit echappés correctements ou généré par le système.
				echo '<input type="file" accept="image/*" name="' . esc_attr( $field['id'] ) . '" ' . $required . '>';

				if ( isset( $field['key'] ) && 'post_thumbnail' === $field['key'] ) {
					list ( $url, $width, $height, $is_intermediate ) = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'thumbnail' );
					if ( isset( $url ) && '' !== $url ) {
						echo '<div id="featured_image" class="featured_image"><img src="' . esc_attr( $url ) . '" width="' . esc_attr( $width ) . '" height="' . esc_attr( $height ) . '" /><br /><a id="remove_featured" class="button remove-row">' . esc_html_x( 'Effacer', 'fields-helper', 'platform-shell-plugin' ) . '</a></div>';
					}
				} else {
					echo '<p>' . wp_kses_post( $meta ) . '</p>';
				}
				break;
			case 'multiupload':
				if ( isset( $field['desc'] ) && '' !== $field['desc'] ) {
					echo '<p class="input-desc">' . wp_kses_post( $field['desc'] ) . '</p>';
				}
				echo '<div class="input-group">
							<label class="input-group-btn">
							<span class="btn btn-primary">' . esc_html_x( 'Choisir', 'fields-helper', 'platform-shell-plugin' ) . ' <input type="file" name="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '" style="display: none;" multiple>
							</span>
							</label>
							<input type="text" class="form-control" readonly>
						</div>';
				break;
			case 'repeatable':
				if ( isset( $field['desc'] ) && '' !== $field['desc'] ) {
					echo '<p class="input-desc">' . wp_kses_post( $field['desc'] ) . '</p>';
				}
				$repeat  = '<div class="repeatable-wrapper"><div id="files">
								<div id="file1" class="row">
									<span><a class="button remove-row" data-field="file1"  title="' . esc_attr_x( 'Supprimer', 'fields-helper', 'platform-shell-plugin' ) . '"><i class="fa fa-minus-circle" aria-hidden="true"></i></a></span><input type="file" accept="image/*" name="' . esc_attr( $field['key'] ) . '[]" id="' . esc_attr( $field['id'] ) . '" class="repeatable" />
								</div>
							</div>';
				$repeat .= '<div id="element-templates" style="display: none ;">
									<div id="::FIELD1::" class="row">
										<span><a class="button remove-row" data-field="::FIELD1::" title="' . esc_attr_x( 'Supprimer', 'fields-helper', 'platform-shell-plugin' ) . '"><i class="fa fa-minus-circle" aria-hidden="true"></i></a></span><input type="file" accept="image/*" name="' . esc_attr( $field['key'] ) . '[]" id="' . esc_attr( $field['key'] ) . '_::FIELD8::" class="repeatable" />
									</div>
								</div>';
				$repeat .= '<p><a class="btn btn-primary" id="add-file-upload">' . _x( 'Ajouter', 'fields-helper', 'platform-shell-plugin' ) . '</a></p></div>';

				// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped --Tous les champs sont soit echappés correctements ou généré par le système.
				echo $repeat;
				if ( '' !== $meta ) {
					echo '<div class="gallery-wrapper"><input type="hidden" name="' . esc_attr( $field['key'] ) . '" value="' . esc_attr( $meta ) . '" />';
					$meta = explode( ',', $meta );
					foreach ( $meta as $id ) {
						list ( $url, $width, $height, $is_intermediate ) = wp_get_attachment_image_src( $id, 'thumbnail' );
						echo '<div class="repeatable_image"><img id="' . esc_attr( $id ) . '" src="' . esc_url( $url ) . '" width="' . esc_attr( $width ) . '" height="' . esc_attr( $height ) . '"/><br /><a class="button remove-gallery_image">' . esc_html_x( 'Effacer', 'fields-helper', 'platform-shell-plugin' ) . '</a></div>';
					}
					echo '</div>';
				}
				break;
			case 'multi-users':
				echo '<select data-author="' . $field['author'] . '" name="' . $field['id'] . '[]" id="' . $field['id'] . '" multiple="multiple" ' . $required . '>';

				if ( ! empty( $meta ) ) {
					$users = get_users(
						[
							'include' => explode( ',', $meta ),
							'fields'  => [
								'ID',
								'display_name',
							],
							'orderby' => 'display_name',
						]
					);

					foreach ( $users as $user ) {
						echo "<option value='{$user->ID}' selected='selected'>{$user->display_name}</option>";
					}
				}

				echo '</select>';

				break;
			default:
				echo '<label class="error">' . _x( 'Ce champ est d\'un type non supporté.', 'invalid-field', 'platform-shell-plugin' ) . '</label>';
				break;
		} // end switch
		echo '</div>';
	}

	/**
	 * Ajoute le tags 'required' à l'éditeur de WordPress ( TinyMCE ).
	 *
	 * @param string $editor Le code html de l'éditeur de texte TinyMCE.
	 * @return string Le code html de l'éditeur de texte TinyMCE
	 */
	public static function add_required_attribute_to_wp_editor( $editor ) {
		$editor = str_replace( '<textarea', '<textarea required="required"', $editor );
		return $editor;
	}

	/**
	 * Méthode show_repeatble_fields
	 *
	 * @param string $meta    L'url du fichier source de l'image.
	 */
	public static function show_repeatble_fields( $meta ) {
		$output = '';
		foreach ( $meta as $img ) {
			$output .= '<img src="' . esc_url( $meta ) . '" width="100"/>';
		}
		// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped --Tous les champs sont soit echappés correctements ou généré par le système.
		echo $output;
	}
}
