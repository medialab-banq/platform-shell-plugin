<?php
/**
 * Platform_Shell\Settings\Plugin_Settings
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Settings;
use Platform_Shell\UploadHelper;

/**
 * Gestionnaire des settings du plugin.
 *
 * @class    Plugin_Settings
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Plugin_Settings {

	/**
	 * Instance de UploadHelper.
	 *
	 * @var UploadHelper
	 */

	private $upload_helper;

	/**
	 * Identifiant de l'option d'instalation de bannière pour détecter première installation.
	 *
	 * @var string
	 */
	private $installed_demo_banners_option_name = 'platform_shell_installed_demo_banners';


	/**
	 * Instance de Plugin_Settings.
	 *
	 * @var Plugin_Settings
	 */
	private $plugin_settings;

	/**
	 * Constructeur.
	 *
	 * @param type         $plugin_default_settings_config    Auto DI.
	 * @param UploadHelper $upload_helper             Auto DI.
	 */
	public function __construct( $plugin_default_settings_config, UploadHelper $upload_helper ) {
		$this->plugin_settings = $plugin_default_settings_config;
		$this->upload_helper   = $upload_helper;
	}

	/**
	 * Méthode pour récupérer la valeur d'une option / settings de la plateforme.
	 *
	 * @param string $option     Id d'option.
	 * @param string $section    Id de section.
	 * @param mixed  $default     Valeur par défaut si l'option n'a pas été définie.
	 * @return mixed
	 */
	public function get_option( $option, $section, $default = '' ) {
		/*
		* Important : Il y a une fonction équivalent au niveau du thème.
		* Compromis sur duplication du code pour minimiser couplage plugin / thème.
		*/

		/* Voir https://github.com/tareq1988/wordpress-settings-api-class */
		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;
	}

	/**
	 * Méthode pour gérer l'installation des settings (point d'entrée principal, appelé par le gestionnaire d'installation du plugin).
	 */
	public function install() {

		// Les settings de WordPress ne gèrent pas l'enregistrement initial des "defaults".
		// La fonctionnalité pourrait possiblement être ajoutée au niveau de la librairie de settings.
		$this->install_contest_demo_settings();
		$this->install_activity_demo_settings();
		$this->install_account_demo_settings();

		$this->install_home_page_demo_settings();
		$this->install_demo_settings_admissibility();
		$this->install_banner_demo_content(); /* Cas particulier, les bannières sont gérés en post type mais considérés comme "settings" de la plateforme. */
	}

	/**
	 * Méthode pour installer les settings démo/default de gestion de compte.
	 */
	private function install_account_demo_settings() {
		$this->set_option_if_not_set( 'platform_shell_option_shibboleth_missing_email_behavior', 'platform_shell_settings_main_accounts', 'ASSIGN_NEVER' );
		$this->set_option_if_not_set( 'platform_shell_option_shibboleth_show_real_email_to_user', 'platform_shell_settings_main_accounts', 'on' );
		$default_message = _x( 'Ton dossier ne contient pas d’adresse de courriel.', 'settings-default', 'platform-shell-plugin' );
		$this->set_option_if_not_set( 'platform_shell_option_shibboleth_missing_email_message', 'platform_shell_settings_main_accounts', $default_message );
	}

	/**
	 * Méthode pour installer les settings démo/default des choix de type d'activités.
	 */
	private function install_activity_demo_settings() {
		$button_label = _x( 'Activités de groupe.', 'list-activities', 'platform-shell-theme' );
		$this->set_option_if_not_set( 'platform_shell_option_activities_group_activities_button_label', 'platform-shell-settings-page-site-sections-activities', $button_label );
		$this->set_option_if_not_set( 'platform_shell_option_activities_group_activities_url', 'platform-shell-settings-page-site-sections-activities', 'https://www.google.com' );
	}

	/**
	 * Méthode pour installer les settings démo/default pour la gestion des concours.
	 */
	private function install_contest_demo_settings() {
		$this->install_contest_demo_settings_organizers();

		$this->install_contest_demo_settings_type();
	}

	/**
	 * Méthode pour installer les settings démo/default liste des organisateurs de concours.
	 */
	private function install_contest_demo_settings_organizers() {
		$organizers = [
			_x( 'Organisateur 1', 'settings-default', 'platform-shell-plugin' ),
			_x( 'Organisateur 2', 'settings-default', 'platform-shell-plugin' ),
		];

		$this->set_option_if_not_set( 'platform_shell_option_contests_organizers_list', 'platform-shell-settings-page-site-sections-contests', implode( /* double quote important. On veut retour de ligne réels. */ "\r\n", $organizers ) );
	}

	/**
	 * Méthode pour installer les settings démo/default de la page d'accueil.
	 */
	private function install_home_page_demo_settings() {
		$this->set_option_if_not_set( 'platform_shell_option_home_page_header_box_title', 'platform-shell-settings-page-site-sections-home', _x( 'Texte d’accueil à personnaliser', 'settings-default', 'platform-shell-plugin' ) );
		$this->set_option_if_not_set( 'platform_shell_option_contact_adress', 'platform-shell-settings-page-site-sections-home', _x( 'Adresse à personnaliser', 'settings-default', 'platform-shell-plugin' ) );
		$this->set_option_if_not_set( 'platform_shell_option_contact_phone_numer', 'platform-shell-settings-page-site-sections-home', _x( '555 555-5555', 'settings-default', 'platform-shell-plugin' ) );
		$this->set_option_if_not_set( 'platform_shell_option_opening_hours', 'platform-shell-settings-page-site-sections-home', _x( '(à personnaliser)<br/>Du lundi au vendredi : de 9 h à 17 h.<br/>Le samedi et le dimanche : de 9 h à 17 h.', 'settings-default', 'platform-shell-plugin' ) );
	}

	/**
	 * Méthode pour installer les settings démo/default des choix d'admissibilité (concours et activité).
	 */
	private function install_demo_settings_admissibility() {
		$admissibility = [
			_x( 'Jeunes (13 à 17 ans)', 'settings-default', 'platform-shell-plugin' ),
			_x( 'Adultes (18 ans et plus)', 'settings-default', 'platform-shell-plugin' ),
			_x( 'Tous', 'settings-default', 'platform-shell-plugin' ),
		];

		$this->set_option_if_not_set( 'platform_shell_option_contests_admissibility_list', 'platform-shell-settings-page-site-sections-general', implode( /* double quote important. On veut retour de ligne réels. */ "\r\n", $admissibility ) );
	}

	/**
	 * Méthode pour installer les settings démo/default des types de concours.
	 */
	private function install_contest_demo_settings_type() {
		$contest_types = [
			[
				'type'  => _x( 'Bande dessinée', 'settings-default', 'platform-shell-plugin' ),
				'class' => 'fa-picture-o',
			],
			[
				'type'  => _x( 'Vidéo', 'settings-default', 'platform-shell-plugin' ),
				'class' => 'fa-video-camera',
			],
			[
				'type'  => _x( 'Audio', 'settings-default', 'platform-shell-plugin' ),
				'class' => 'fa-microphone',
			],
			[
				'type'  => _x( 'Concours annuel', 'settings-default', 'platform-shell-plugin' ),
				'class' => 'fa-calendar',
			],
		];

		$json_data = wp_json_encode( $contest_types, JSON_UNESCAPED_UNICODE );
		$this->set_option_if_not_set( 'platform_shell_option_contests_type_list', 'platform-shell-settings-page-site-sections-contests', $json_data );
	}

	/**
	 * Méthode pour définir option si elle n'existe pas.
	 *
	 * @param string $option_id       Id de l'option.
	 * @param string $section_id      Id de section.
	 * @param string $option_value    Valeur de l'option à enregistrer.
	 */
	private function set_option_if_not_set( $option_id, $section_id, $option_value ) {
		$options = get_option( $section_id );

		// todo : validation du format enregistré?
		// Si on enregistre array et valeur set = string (invalide), la détection sera incorrecte.
		if ( ! isset( $options[ $option_id ] ) ) {
			if ( ! is_array( $options ) ) {
				$options = [];
			}

			$options[ $option_id ] = $option_value;
			update_option( $section_id, $options, true );
		}
	}


	/**
	 * Méthode install_banner_demo_content
	 *
	 * Méthode pour installer le contenu demo des bannière.
	 *
	 * @throws \Exception    Lorsque du contenu est manquant.
	 */
	private function install_banner_demo_content() {

		$installed_demo_banners = get_option( $this->installed_demo_banners_option_name, 0 );

		// Une fois les bannière démo installées, les administrateurs / gestionnaires peuvent changer.
		// Les bannières à leur goût. Sur la réactivation du plugin, les bannière ne sont pas réinstallées.
		if ( 0 === $installed_demo_banners ) {
			// todo: documentation Supporte banner_post_id_75.
			$base_content_demo_path = get_theme_file_path( '/images/interface/samples/headers/' );

			$banners = [
				[
					'filename' => 'bandeau_sample_big_01.jpg',
					'title'    => 'banner_front_page',
				],
				[
					'filename' => 'bandeau_sample_small_01.jpg',
					'title'    => 'banner_default',
				],
				[
					'filename' => 'bandeau_sample_small_04.jpg',
					'title'    => 'banner_project',
				],
				[
					'filename' => 'bandeau_sample_small_03.jpg',
					'title'    => 'banner_contest',
				],
				[
					'filename' => 'bandeau_sample_small_02.jpg',
					'title'    => 'banner_equipment',
				],
				[
					'filename' => 'bandeau_sample_small_02.jpg',
					'title'    => 'banner_tool',
				],
				[
					'filename' => 'bandeau_sample_small_05.jpg',
					'title'    => 'banner_activity',
				],
				[
					'filename' => 'bandeau_sample_small_01.jpg',
					'title'    => 'banner_post',
				],
				[
					'filename' => 'bandeau_sample_small_01.jpg',
					'title'    => 'banner_page',
				],
			];

			foreach ( $banners as $banner_info ) {
				// Check file exist?
				$image_path_to_use_as_banner_attachment = $base_content_demo_path . $banner_info['filename'];
				$banner_title                           = $banner_info['title'];
				if ( file_exists( $image_path_to_use_as_banner_attachment ) ) {
					if ( 0 === $this->published_banner_exists( $banner_title ) ) {
						$this->insert_banner( $banner_title, $image_path_to_use_as_banner_attachment );
					}
				} else {
					throw new \Exception( _x( 'Référence à du contenu inexistant.', 'image-upload', 'platform-shell-plugin' ) );
				}
			}

			update_option( $this->installed_demo_banners_option_name, 1, false );
		}
	}

	/**
	 * Méthode create_tmp_file_copy_from_file_path
	 *
	 * Méthode pour créer un fichier temporaire à partir d'un fichier existant.
	 *
	 * @param string $file_path    Emplacement du fichier.
	 * @return string
	 */
	private function create_tmp_file_copy_from_file_path( $file_path ) {

		$tmpfname = tempnam( sys_get_temp_dir(), 'php-platform-shell-demo-temp' );
		file_put_contents( $tmpfname, file_get_contents( $file_path ) );

		return $tmpfname;
	}

	/**
	 * Méthode insert_banner
	 *
	 * Méthode pour installer une bannière démo.
	 *
	 * @param string $title         Titre de l'image.
	 * @param string $image_path    Emplacement de l'image.
	 */
	private function insert_banner( $title, $image_path ) {
		/*
		 * Insérer une bannière = créer un post type bannière et ajouter une image mise en avant créer à partir des images du dossier de contenu démo.
		 * Utilise les fonctionnalités existantes d'upload pour compléter l'opération.
		 */

		$my_post = array(
			'post_title'   => $title,
			'post_content' => _x( 'Cette bannière a été ajoutée pour fin de démonstration, vous pouvez la modifier à votre choix.', 'settings-default', 'platform-shell-plugin' ),
			'post_status'  => 'publish',
			'post_author'  => 0,
			'post_type'    => 'banner',
		);

		// Ajouter un post type banner.
		$post_id = wp_insert_post( $my_post );

		$temp_file_path = $this->create_tmp_file_copy_from_file_path( $image_path );

		$upload_file_info = [
			'name'     => basename( $image_path ), /* ? devrait être nom de fichier sans le path? */
			'type'     => image_type_to_mime_type( IMAGETYPE_PNG ),
			'ext'      => '.jpg',
			'tmp_name' => $temp_file_path,
			'file'     => $image_path,
			'size'     => filesize( $temp_file_path ),
			'error'    => UPLOAD_ERR_OK,
		];

		$errors = []; // todo : handling error?

		$local_upload = true;

		$attach_id = $this->upload_helper->upload_thumbnail(
			$upload_file_info,
			$errors,
			$post_id,
			$local_upload
		);
	}

	/**
	 * Méthode published_banner_exists
	 *
	 * Méthode copiée et ajustée de https://developer.wordpress.org/reference/functions/post_exists/ pour tenir compte du post type et title seulement.
	 *
	 * @global \wpdb $wpdb     Instance de la classe wpdb.
	 * @param string $title      Titre du post.
	 * @return int
	 */
	function published_banner_exists( $title ) {
		global $wpdb;

		$post_title = wp_unslash( sanitize_post_field( 'post_title', $title, 0, 'db' ) );

		$query = "SELECT ID FROM $wpdb->posts WHERE post_type = %s AND post_status = %s";
		$args  = [
			'banner',
			'publish',
		];

		if ( ! empty( $title ) ) {
			$query .= ' AND post_title = %s';
			$args[] = $post_title;
		}

		if ( ! empty( $args ) ) {
			// phpcs:ignore WordPress.WP.PreparedSQL.NotPrepared --La requête est déjà préparée.
			$result = (int) $wpdb->get_var( $wpdb->prepare( $query, $args ) );
			return $result;
		}

		return 0;
	}

}
