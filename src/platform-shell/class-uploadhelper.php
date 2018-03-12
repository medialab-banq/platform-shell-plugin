<?php
/**
 * Platform_Shell\UploadHelper
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell;

if ( ! function_exists( 'wp_handle_upload' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}
if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
}
/**
 * Platform_Shell UploadHelper
 *
 * @class    UploadHelper
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
/**
 * UploadHelper class.
 */
class UploadHelper {

	/**
	 * Taille maximale des téléversements autorisés sur le serveur.
	 *
	 * @var integer
	 */
	protected static $max_upload;

	/**
	 * Liste des types de fichiers acceptés
	 *
	 * @var array
	 */
	protected static $accepted_types = [
		IMAGETYPE_GIF,
		IMAGETYPE_JPEG,
		IMAGETYPE_PNG,
	];

	/**
	 * Liste des mimetypes de fichiers acceptés
	 *
	 * @var array
	 */
	protected static $accepted_mimes = [
		'image/gif',
		'image/jpeg',
		'image/png',
	];

	/**
	 * Méthode extract_uploaded_files
	 *
	 * @param array $_files    Liste des fichiers.
	 * @return array
	 */
	protected function extract_uploaded_files( array $_files = [] ) {
		$return_value = [];
		if ( is_array( $_files['name'] ) ) {
			for ( $i = 0; $i < sizeof( $_files['name'] ); $i++ ) {
				if ( UPLOAD_ERR_OK === $_files['error'][ $i ] ) {
					$return_value[] = [
						'name'     => $_files['name'][ $i ],
						'type'     => $_files['type'][ $i ],
						'tmp_name' => $_files['tmp_name'][ $i ],
						'error'    => $_files['error'][ $i ],
						'size'     => $_files['size'][ $i ],
					];
				}
			}
		} elseif ( UPLOAD_ERR_OK === $_files['error'] ) {
			$return_value[] = $_files;
		}
		return $return_value;
	}

	/**
	 * Méthode is_empty_file_info
	 *
	 * @param array $upload_file_info    Liste des attributs du fichier téléversé.
	 * @return boolean
	 */
	protected function is_empty_file_info( $upload_file_info ) {
		// Pas de fichier ni de nom.
		return ( UPLOAD_ERR_NO_FILE == $upload_file_info['error'] && empty( $upload_file_info['name'] ) );
	}

	/**
	 * Méthode validate_uploaded_file
	 *
	 * @param array $upload_file_info    Liste des attributs du fichier téléversé.
	 * @throws UploadException           Lorsqu'il y'a une erreur de téléversement.
	 * @return boolean
	 */
	protected function validate_uploaded_file( &$upload_file_info ) {

		$is_valid = true;
		// Verifier taille.
		if (
			( 0 == $upload_file_info['size'] && UPLOAD_ERR_NO_FILE == $upload_file_info['error'] ) ||
			( 0 == $upload_file_info['size'] && UPLOAD_ERR_OK == $upload_file_info['error'] )
		) {
			if ( ! $this->is_empty_file_info( $upload_file_info ) ) {
				$is_valid = false;
				throw new UploadException(
					sprintf(
						/* translators: %1$s: Nom du fichier */
						_x( '%1$s : Le fichier image ne peut pas être vide.', 'image-upload', 'platform-shell-plugin' ),
						$upload_file_info['name']
					)
				);
			}
		} else {
			// Vérifier si la taille du fichier est inférieur à $php_max_filesize.
			if ( UPLOAD_ERR_INI_SIZE == $upload_file_info['error'] ) {
				$is_valid = false;
				throw new UploadException(
					sprintf(
						/* translators: %1$s: Taille du fichier */
						_x(
							'%1$s : La taille du fichier ne doit pas dépasser %2$s.',
							'image-upload',
							'platform-shell-plugin'
						),
						$upload_file_info['name'],
						self::get_max_upload_filesize_localized()
					)
				);
			} elseif ( ! empty( $upload_file_info['tmp_name'] ) ) {

				// check si le fichier est réellement une image (?getimagesize est pas un vérification de format.).
				// Selon doc, retour false si n'arrive pas a déterminer taille.
				$image_size = null;
				$mime_type  = mime_content_type( $upload_file_info['tmp_name'] );

				if ( $upload_file_info['type'] !== $mime_type ) { // Correction des mimetypes incorrects.
					$upload_file_info['type'] = $mime_type;

					if ( $this->is_image_mime_accepted( $mime_type ) ) {

						// Pour gérer les cas du genre "a.c.png" nous voulons extraire que le nom primaire et l'extension du fichier.
						// Comme le paramètre "limit" de la fonction "explode" fonctionne que du début de la chaîne de charactères,.
						// nous devons inverser le nom de fichier pour extraire uniquement l'extension.
						$rev_name = strrev( $upload_file_info['name'] );

						// L'on extrait l'extension du nom de fichier.
						list( $extension, $base_name ) = explode( '.', $rev_name, 2 );

						// On retourne l'extension et le nom du fichier à l'endroit.
						$base_name = strrev( $base_name );

						$image_size = getimagesize( $upload_file_info['tmp_name'] );

						if ( false === $image_size ) {
							// Mimetype non supporté.
							$is_valid = false;
							throw new UploadException(
								sprintf(
									/* translators: %1$s: Nom du fichier */
									_x(
										'%1$s : SVP télécharge une image au format JPEG, PNG ou GIF. Il y a eu un problème avec l’une des images téléchargées.',
										'image-upload',
										'platform-shell-plugin'
									),
									$upload_file_info['name']
								)
							);
						}

						// L'on obtient l'extension à partir du mime.
						$extension = image_type_to_extension( $image_size[2] );

						// Nom de fichier corrigé.
						$upload_file_info['name'] = $base_name . '.' . $extension;

					} else {

						// Mimetype non supporté.
						$is_valid = false;
						throw new UploadException(
							sprintf(
								/* translators: %1$s: Nom du fichier */
								_x(
									'%1$s : SVP télécharge une image au format JPEG, PNG ou GIF.',
									'image-upload',
									'platform-shell-plugin'
								),
								$upload_file_info['name']
							)
						);
					}
				}

				if ( is_null( $image_size ) ) {
					$image_size = getimagesize( $upload_file_info['tmp_name'] );
				}

				if ( false === $image_size ) {
					// Incapable de déterminer taille, format inconnu.
					$is_valid = false;
					throw new UploadException(
						sprintf(
							/* translators: %1$s: Nom du fichier */
							_x(
								'%1$s : SVP télécharge une image au format JPEG, PNG ou GIF. Il y a eu un problème avec l’une des images téléchargées.',
								'image-upload',
								'platform-shell-plugin'
							),
							$upload_file_info['name']
						)
					);
				} elseif ( false === self::is_image_format_accepted( $image_size[2] ) ) {

					// Incapable de déterminer taille, format inconnu.
					$is_valid = false;
					throw new UploadException(
						sprintf(
							/* translators: %1$s: Nom du fichier */
							_x(
								'%1$s : SVP télécharge une image en format JPEG, PNG ou GIF.',
								'image-upload',
								'platform-shell-plugin'
							),
							$upload_file_info['name']
						)
					);
				} else {
					// On pourrait ajouter vérification decompression bomb ici (à tester).
					// getimagesize only extracts info from the metadata which can be unreliable.
					// https://tomjn.com/2013/02/26/clients-who-upload-huge-camera-photos-decompression-bombs/.
					if ( $upload_file_info['size'] > self::get_max_upload_filesize_bytes() ) {
						$is_valid = false;
						throw new UploadException(
							sprintf(
								/* translators: %s: Taille du fichier */
								_x(
									'%1$s : La taille du fichier ne doit pas dépasser %2$s.',
									'image-upload',
									'platform-shell-plugin'
								),
								$upload_file_info['name'],
								self::get_max_upload_filesize_localized()
							)
						);
					}
				}
			}
		}
		return $is_valid;
	}

	/**
	 * Méthode upload_gallery
	 *
	 * @param array   $upload_file_info        Liste des attributs du fichier téléversé.
	 * @param array   $errors                  Liste des erreurs.
	 * @param integer $post_id                 Identifiant du post.
	 * @param string  $existing_attachments    Liste des attachements existants.
	 */
	public function upload_gallery( &$upload_file_info, &$errors, $post_id, $existing_attachments = '' ) {

		// Séparer les fichiers en éléments séparés.
		$files       = $this->extract_uploaded_files( $upload_file_info );
		$attachments = [];

		// Pour chaque image téléversé.
		foreach ( $files as $file ) {
			// Obtenir l'ID de l'attachement.
			$attach_id = $this->handle_uploaded_file( $file, $errors, $post_id );
			// S'assurer qu'il n'y a aucune erreur.
			if ( false !== $attach_id && true !== $attach_id && empty( $errors ) ) {
				$attachments[] = $attach_id;
			} elseif ( false === $attach_id && empty( $errors ) ) {
				$errors['images'][] = $this->get_default_image_upload_error( $file );
			}
		}

		if ( empty( $errors ) ) {
			$key = 'platform_shell_meta_gallery';

			// Obtenir les images de galerie existantes.
			$old = get_post_meta( $post_id, $key, false );

			// Ajouter les attachements existants au début de la liste.
			if ( ! empty( $existing_attachments ) ) {
				array_unshift( $attachments, $existing_attachments );
			}

			// Préparer la liste d'attachements à utiliser.
			$attachments = implode( ',', $attachments );

			if ( empty( $old ) ) {

				// Créer un nouveau meta.
				add_post_meta( $post_id, $key, $attachments, true );

			} else {

				// Mettre à jour le meta existant.
				update_post_meta( $post_id, $key, $attachments );
			}
		}
	}

	/**
	 * Méthode upload_thumbnail
	 *
	 * @param array   $upload_file_info    Liste des attributs du fichier téléversé.
	 * @param array   $errors              Liste des erreurs.
	 * @param integer $post_id             Identifiant du post.
	 * @param boolean $local_upload        Si le téléversement est local.
	 * @return boolean
	 */
	public function upload_thumbnail( &$upload_file_info, &$errors, $post_id, $local_upload = false ) {

		// Obtenir l'ID de l'attachement.
		$attach_id = $this->handle_uploaded_file( $upload_file_info, $errors, $post_id, $local_upload );

		// S'assurer qu'il n'y a aucune erreur.
		if ( false !== $attach_id && true !== $attach_id && empty( $errors ) ) {

			// Assigner l'attachement comme vignette.
			if ( set_post_thumbnail( $post_id, $attach_id ) === false ) {
				$errors['images'][] = $this->get_default_image_upload_error( $upload_file_info );
			}
		} elseif ( false === $attach_id && empty( $errors ) ) {

			$errors['images'][] = $this->get_default_image_upload_error( $upload_file_info );
		}

		return $attach_id;
	}

	/**
	 * Méthode generate_random_file_name
	 *
	 * @param array $upload_file_info    Liste des attributs du fichier téléversé.
	 */
	protected function generate_random_file_name( &$upload_file_info ) {
		// Pour gérer les cas du genre "a.c.png" nous voulons extraire que le nom primaire et l'extension du fichier.
		// Comme le paramètre "limit" de la fonction "explode" fonctionne que du début de la chaîne de charactères,.
		// nous devons inverser le nom de fichier pour extraire uniquement l'extension.
		$rev_name = strrev( $upload_file_info['name'] );

		// L'on extrait l'extension du nom de fichier.
		list( $extension, $base_name ) = explode( '.', $rev_name, 2 );

		// On retourne l'extension et le nom du fichier à l'endroit.
		$base_name = strrev( $base_name );
		$extension = strrev( $extension );
		$base_name = $upload_file_info['size'] . $base_name . time();

		// On utilise le même type de hashage partout dans le plugin.
		$base_name = hash( HASH_TYPE, $base_name );

		// On force les noms de fichiers d'être en minuscules pour faciliter la compatibilité avec les.
		// différents systèmes de fichiers. (FAT32, NTFS, EXT2, etc...).
		// L'on rassemble le nom de fichier et on l'assigne.
		$upload_file_info['name'] = strtolower( $base_name . '.' . $extension );

		// On s'assure qu'il n'y ait pas de nom de fichier avec le même nom existant dans le répertoire de téléversement.
		$this->get_unique_file_name( $upload_file_info );
	}

	/**
	 * Méthode get_unique_file_name
	 *
	 * @param array $upload_file_info    Liste des attributs du fichier téléversé.
	 */
	protected function get_unique_file_name( &$upload_file_info ) {
		$upload_dir = wp_upload_dir();
		if ( file_exists( $upload_dir['path'] . $upload_file_info['name'] ) ) {
			$this->generate_random_file_name( $upload_file_info );
		}
	}

	/**
	 * Méthode upload
	 *
	 * @param array   $upload_file_info    Liste des attributs du fichier téléversé.
	 * @param boolean $local_upload        Si le téléversement est local.
	 * @return array
	 */
	protected function upload( &$upload_file_info, $local_upload = false ) {
		$this->generate_random_file_name( $upload_file_info );

		$upload_configs = [
			// When called while handling a form, 'action' must be set to match the 'action' parameter in the form.
			// or the upload will be rejected. When there is no form being handled, use 'test_form' => false to.
			// bypass this test, and set 'action' to something other than the default ("wp_handle_upload") to.
			// bypass security checks requiring the file in question to be a user-uploaded file.
			// https://codex.wordpress.org/Function_Reference/wp_handle_upload.
			'test_form' => false,
		];

		if ( true === $local_upload ) {
			$upload_configs['action'] = 'local';
		}

		if ( ! $local_upload ) {
			// L'on enlève l'information contenue dans les EXIF des fichiers téléversés.
			// Le traitement n'est pas appliqué pour les fichiers importés localement (fichiers connus).
			// En néttoyant les exifs sur les fichiers temporaires, nous éliminons le risque d'avoir les exifs
			// copiés dans les thumbnails.
			$this->clean_exif( $upload_file_info['tmp_name'], $upload_file_info['type'] );
		}

		$return_value = wp_handle_upload(
			$upload_file_info,
			$upload_configs
		);

		return $return_value;
	}

	/**
	 * Méthode attach
	 *
	 * @param array   $upload_file_info    Information sur le fichier.
	 * @param array   $upload_results      Résultat de la méthode "upload".
	 * @param integer $post_id             Identifiant du post.
	 * @throws UploadException             Lorsqu'il y'a une erreur de téléversement.
	 * @return number|\WP_Error
	 */
	protected function attach( &$upload_file_info, $upload_results, $post_id ) {
		$attachment = [
			'guid'           => $upload_results['url'],
			'post_mime_type' => $upload_results['type'],
			// Ce regex enlève l'extension du fichier du nom du fichier pour obtenir le titre de l'attachement.
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $upload_results['file'] ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];
		// Insère l'attachement, et récupère l'id de l'attachement.
		$attach_id = wp_insert_attachment( $attachment, $upload_results['file'], $post_id );
		// Génère le métadata de l'attachement, et génère les vignettes pour l'image.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_results['file'] );
		if ( wp_update_attachment_metadata( $attach_id, $attach_data ) === false ) {
			throw new UploadException(
				$this->get_default_image_upload_error( $upload_file_info )
			);
		}
		return $attach_id;
	}

	/**
	 * Méthode handle_uploaded_file
	 *
	 * @param array   $upload_file_info    Liste des attributs du fichier téléversé.
	 * @param array   $errors              Liste des erreurs.
	 * @param integer $post_id             Identifiant du post.
	 * @param boolean $local_upload        Si le téléversement est local.
	 * @throws UploadException             Lorsqu'il y'a une erreur de téléversement.
	 * @return boolean
	 */
	protected function handle_uploaded_file( &$upload_file_info, &$errors, $post_id, $local_upload = false ) {
		$attach_id = false;
		// Valider que le fichier n'est pas vide.
		if ( ! $this->is_empty_file_info( $upload_file_info ) ) {
			try {
				// On valie le fichier à téléverser.
				if ( $this->validate_uploaded_file( $upload_file_info ) ) {
					// Téléversement du fichier dans le dossier de téléversement WordPress Standard.
					// Ex: /wp-content/uploads/2017/11/image.png.
					$upload_results = $this->upload( $upload_file_info, $local_upload );
					// Attacher le fichier au post.
					$attach_id = $this->attach( $upload_file_info, $upload_results, $post_id );
				} else {
					throw new UploadException(
						$this->get_default_image_upload_error( $upload_file_info )
					);
				}
			} catch ( UploadException $e ) { // Attraper les exceptions, et les retourner sous la forme d'erreurs.
				$errors['images'][] = $e->getMessage();
			}
		} else {
			$attach_id = true;
		}
		return $attach_id;
	}

	/**
	 * Méthode clean_exif
	 *
	 * Cette méthode sert à corriger des problèmes de rotation d'images avec certaines images prise par des appareils
	 * mobiles et à supprimer des informations personnelles (géolocation) qui sont inscrites dans les images.
	 *
	 * @param string $filename     L'URI du fichier sur le serveur.
	 * @param string $mime_type    Le mime type du fichier sur le serveur.
	 * @throws \Exception          Lors d'une erreur d'upload de fichier.
	 */
	protected function clean_exif( $filename, $mime_type ) {

		$is_jpeg = 'image/jpeg' === $mime_type;

		if ( true === $is_jpeg && function_exists( 'exif_read_data' ) ) {
			try {

				// Même si le type d'image est supporté par la function, un avertissement peut être
				// retourné si la méthode ne peut parser l'image.
				// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
				$exif = @exif_read_data( $filename );

				if ( false !== $exif && isset( $exif['Orientation'] ) ) {

					$img_new = imagecreatefromjpeg( $filename );

					if ( false === $img_new ) {

						throw new \Exception( $this->get_default_image_upload_error() );

					} else {
						$orientation = $exif['Orientation'];
						$deg         = null;

						switch ( $orientation ) {
							case 3:
								$deg = 180;
								break;
							case 6:
								$deg = 270;
								break;
							case 8:
								$deg = 90;
								break;
							default:
								break;
						}

						if ( ! is_null( $deg ) ) {
							$img_new = imagerotate( $img_new, $deg, 0 );
						}

						imagejpeg( $img_new, $filename, 100 );
					}
				}
			} catch ( \Exception $e ) {
				write_log( $e );
			}
		}
	}

	/**
	 * Méthode is_image_mime_accepted
	 *
	 * @param integer $mime   One of the IMAGETYPE_XXX constants indicating the type of the image.
	 * @return boolean
	 */
	public static function is_image_mime_accepted( $mime ) {
		return in_array( $mime, self::$accepted_mimes );
	}

	/**
	 * Méthode is_image_format_accepted
	 *
	 * @param integer $format   One of the IMAGETYPE_XXX constants indicating the type of the image.
	 * @return boolean
	 */
	public static function is_image_format_accepted( $format ) {
		return in_array( $format, self::$accepted_types );
	}

	/**
	 * Méthode get_max_upload_filesize_bytes
	 *
	 * @return NULL|number
	 */
	public static function get_max_upload_filesize_bytes() {
		$max_upload_filesize = self::get_max_upload_filesize_value();
		return ( ! empty( $max_upload_filesize ) && isset( $max_upload_filesize['bytes'] ) ) ? $max_upload_filesize['bytes'] : null;
	}

	/**
	 * Méthode get_max_upload_filesize_localized
	 *
	 * @return string
	 */
	public static function get_max_upload_filesize_localized() {
		$max_upload_filesize = self::get_max_upload_filesize_value();
		$max_upload_filesize = ( ! empty( $max_upload_filesize ) && isset( $max_upload_filesize['values'] ) ) ? $max_upload_filesize['values'] : null;
		if ( ! is_null( $max_upload_filesize ) ) {
			// Ce regex extrait la valeur numérique et le suffixe des valeurs de téléversement. (Ex 32M).
			preg_match( '/(\d+)([a-zA-Z]*)/', $max_upload_filesize, $matches );
			$max_upload_filesize = $matches[1] . ' ' . $matches[2] . _x( 'o', 'octet-suffix-filesize', 'platform-shell-plugin' );
		}
		return $max_upload_filesize;
	}

	/**
	 * Méthode get_max_upload_filesize_value
	 *
	 * @return number
	 */
	public static function get_max_upload_filesize_value() {

		if ( empty( self::$max_upload ) || is_null( self::$max_upload ) ) {

			$upload_max_filesize = ini_get( 'upload_max_filesize' );
			$post_max_size       = ini_get( 'post_max_size' );
			$memory_limit        = ini_get( 'memory_limit' );
			$wp_memory_limit     = WP_MEMORY_LIMIT;

			$upload_max_filesize_value = [
				'values' => $upload_max_filesize,
				'bytes'  => wp_convert_hr_to_bytes( $upload_max_filesize ),
			];

			$post_max_size_value = [
				'values' => $post_max_size,
				'bytes'  => wp_convert_hr_to_bytes( $post_max_size ),
			];

			$memory_limit_value = [
				'values' => $memory_limit,
				'bytes'  => wp_convert_hr_to_bytes( $memory_limit ),
			];

			$wp_memory_limit_value = [
				'values' => $wp_memory_limit,
				'bytes'  => wp_convert_hr_to_bytes( $wp_memory_limit ),
			];

			if ( $memory_limit_value['bytes'] < $wp_memory_limit_value['bytes'] ) {
				$memory_limit_value = $wp_memory_limit_value;
			}

			$values = [
				$upload_max_filesize_value,
				$post_max_size_value,
				$memory_limit_value,
			];

			self::$max_upload = array_reduce(
				$values, function ( $carry, $item ) {

					if ( empty( $carry ) || ! isset( $carry['bytes'] ) || ( $carry['bytes'] > $item['bytes'] ) ) {
						$carry = $item;
					}
					return $carry;
				}
			);
		}

		return self::$max_upload;
	}

	/**
	 * Méthode get_default_image_upload_error
	 *
	 * Méthode pour retourner l'erreur générique de téléversement.
	 *
	 * @param array $upload_file_info    Informations sur le fichier en cause.
	 * @return string
	 */
	private function get_default_image_upload_error( &$upload_file_info = null ) {

		$error = _x( 'Erreur inconnue dans le processus de téléversement de l’image', 'image-upload', 'platform-shell-plugin' );

		if ( is_array( $upload_file_info ) && isset( $upload_file_info['name'] ) ) {
			$error = $upload_file_info['name'] . ' : ' . $error;
		}

		return $error;
	}
}
