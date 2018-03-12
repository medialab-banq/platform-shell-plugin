<?php
/**
 * Platform_Shell\Settings\Map_Background_Image
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Settings;


/**
 * Gestionnaire des settings du plugin.
 *
 * @class    Plugin_Settings
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Map_Background_Image {

	/**
	 * Path du plugin.
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * Constructeur.
	 *
	 * @param string $plugin_path [description].
	 */
	public function __construct( $plugin_path ) {
		$this->plugin_path = $plugin_path;
	}

	/**
	 * Méthode init.
	 */
	public function init() {
		add_action( 'wp_ajax_platform_shell_footer_location_background_url_generate_image', [ &$this, 'generate_background_image' ] );
		add_action( 'wp_ajax_platform_shell_footer_location_background_url_generate_save', [ &$this, 'save_generated_background_image' ] );
	}

	/**
	 * Méthode save_generated_background_image
	 *
	 * Cette méthode sauvegarde une image reçue par le système.
	 */
	public function save_generated_background_image() {
		check_ajax_referer( 'platform_shell_footer_location_background_url_generate_save', 'security' );

		$image_str = ( isset( $_POST['image'] ) && ! empty( $_POST['image'] ) ) ? $_POST['image'] : null;
		$image_str = base64_decode( str_replace( 'data:image/jpeg;base64,', '', $image_str ) );
		$image     = imagecreatefromstring( $image_str );

		$position = $_POST['position'];
		$basename = 'background-image-lat-' . str_replace( '.', '_', $position['lat'] ) . '-lng-' . str_replace( '.', '_', $position['lng'] ) . '.jpg';

		$upload_dir = wp_upload_dir();

		$filename = wp_unique_filename( $upload_dir['path'], $basename );
		$filepath = $upload_dir['path'] . '/' . $filename;
		$fileurl  = $upload_dir['url'] . '/' . $filename;

		imagejpeg( $image, $filepath, 100 );

		$image_id = wp_insert_attachment(
			[
				'guid'           => $fileurl,
				'post_mime_type' => 'image/jpeg',
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'publish',
			],
			$filepath
		);

		imagedestroy( $image );

		/**
		 * Inspiré par https://wordpress.stackexchange.com/a/238296.
		 */

		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $image_id, $filepath );

		wp_update_attachment_metadata( $image_id, $attach_data );

		echo $fileurl;

		wp_die();
	}

	/**
	 * Méthode generate_background_image
	 *
	 * Cette méthode reçoit des données images en format base64, ajoute un calce semi=transparent gris et un marqueur de position à l'image reçue
	 */
	public function generate_background_image() {
		check_ajax_referer( 'platform_shell_footer_location_background_url_generate_image', 'security' );

		$image_str = ( isset( $_POST['image'] ) && ! empty( $_POST['image'] ) ) ? $_POST['image'] : null;

		$image_str = base64_decode( str_replace( 'data:image/jpeg;base64,', '', $image_str ) );
		$image     = imagecreatefromstring( $image_str );
		$size      = getimagesizefromstring( $image_str );

		$overlay = imagecreate( intval( $size[0] ), intval( $size[1] ) );

		imagefill( $overlay, 0, 0, imagecolorallocatealpha( $overlay, 0, 0, 0, 42 ) );

		$pin_path = $this->plugin_path . '/images/pin.png';
		$pin      = imagecreatefrompng( $pin_path );

		imagecopy( $image, $overlay, 0, 0, 0, 0, $size[0], $size[1] );
		imagecopy( $image, $pin, 1060, 173, 0, 0, 26, 38 );

		imagedestroy( $overlay );
		imagedestroy( $pin );

		ob_start();
		imagejpeg( $image, null, 100 );
		$result = ob_get_clean();

		imagedestroy( $image );

		echo 'data:image/jpeg;base64,' . base64_encode( $result );

		wp_die();
	}

}
