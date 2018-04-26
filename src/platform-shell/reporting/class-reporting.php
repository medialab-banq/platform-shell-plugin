<?php
/**
 * Platform_Shell\Reporting\Reporting
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Reporting;

use \Platform_Shell\Reporting\Reporting_Configs;
use \Platform_Shell\Profile;
use \Platform_Shell\Settings\Plugin_Settings;
use \Platform_Shell\CPT\Project\Project_Configs;

/**
 * Platform_Shell Reporting. fonctionnalité de signalement d'un projet ou d'un profil.
 *
 * @class    Reporting
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Reporting {
	/**
	 * Instance de Reporting_Configs (DI).
	 *
	 * @var Reporting_Configs
	 */
	private $reporting_configs;

	/**
	 * Instance de Profile (DI).
	 *
	 * @var Profile
	 */
	private $profile;

	/**
	 * Instance de Plugin_Settings (DI).
	 *
	 * @var Plugin_Settings
	 */
	private $plugin_settings;

	/**
	 * Instance de Project_Configs (DI).
	 *
	 * @var Project_Configs
	 */
	private $project_configs;

	/**
	 * Longeur maximale de la saisie du message utilisateur.
	 *
	 * @const FIELD_LENGTH
	 */
	const FIELD_LENGTH = 1200;

	/**
	 * Constructeur.
	 *
	 * @param Reporting_Configs $reporting_configs    Instance de Reporting_Configs (DI).
	 * @param Profile           $profile                        Instance de Profile (DI).
	 * @param Plugin_Settings   $plugin_settings        Instance de Plugin_Settings (DI).
	 * @param Project_Configs   $project_configs        Instance de Project_Configs (DI).
	 */
	public function __construct( Reporting_Configs $reporting_configs, Profile $profile, Plugin_Settings $plugin_settings, Project_Configs $project_configs ) {
		$this->reporting_configs = $reporting_configs;
		$this->profile           = $profile;
		$this->plugin_settings   = $plugin_settings;
		$this->project_configs   = $project_configs;
	}

	/**
	 * Méthode init.
	 */
	public function init() {
		add_action( 'wp_ajax_platform_shell_action_reporting_flag_content', array( $this, 'flag_content_handler' ) );
		add_action( 'wp_ajax_nopriv_platform_shell_action_reporting_flag_content', array( $this, 'flag_content_handler' ) );
	}

	/**
	 * Méthode permettant de récupérer le courriel du destinataire (courriel du gestionnaire ou de l'administrateur selon les configurations).
	 *
	 * @return string    Courriel
	 */
	private function get_reporting_email_recipient() {
		// Récupérer le courriel gestionnaire. Si le courriel n'est pas défini, celui d'admin de WordPress sera utilisé par défaut.
		$wordpress_admin_email     = get_option( 'admin_email' ); // Email admin de WordPress.
		$reporting_recipient_email = $this->plugin_settings->get_option( 'platform_shell_option_contact_manager_email_adress', 'platform-shell-settings-main-contacts-and-notifications', $wordpress_admin_email );
		// Si la valeur courriel est vidée après une première sauvegarde, la valeur est définie (à chaîne vide), le mécanisme de 'default' du get_option n'est pas suffisant.
		if ( '' == $reporting_recipient_email ) {
			return $wordpress_admin_email;
		} else {
			return $reporting_recipient_email;
		}
	}

	/**
	 * Méthode de traitement de la requête AJAX - formulaire de signalement.
	 *
	 * @return void
	 */
	public function flag_content_handler() {

		$project_cpt_name      = $this->project_configs->post_type_name;
		$default_error_message = _x( 'Une erreur est survenue. Veuillez communiquer avec les responsables de la plateforme.', 'reporting', 'platform-shell-plugin' );
		$data                  = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
		$params                = array();

		foreach ( $data as $key => $value ) {
			$params[ $key ] = $value;
		}

		$to        = $this->get_reporting_email_recipient();
		$post_type = ( $params['ptype'] == $project_cpt_name ) ? _x( 'projet', 'reporting', 'platform-shell-plugin' ) : _x( 'profil', 'reporting', 'platform-shell-plugin' );

		// translators: %1$s projet ou profil, %2$s destinataire.
		$subject  = sprintf( _x( 'Un avis de contenu inapproprié pour le %1$s a été envoyé à %2$s.', 'reporting', 'platform-shell-plugin' ), $post_type, $_SERVER['HTTP_HOST'] );
		$message  = '';
		$message .= sprintf( _x( 'Bonjour,', 'reporting', 'platform-shell-plugin' ), $post_type ) . '<br /><br />';
		$reason   = $this->reporting_configs->get_reporting_option_label( $params['options-radio'] );

		// translators: %1$s projet ou profil.
		$message .= sprintf( _x( 'Un avis concernant un %1$s a été envoyé pour la raison suivante : <b>%2$s</b>', 'reporting', 'platform-shell-plugin' ), $post_type, $reason );
		$message .= '<br />';
		$note     = '';

		if ( '' != $params['other'] ) {
			$note     = $params['other'];
			$note     = substr( $note, 0, self::FIELD_LENGTH );
			$message .= '<br />' . esc_html( sanitize_text_field( $note ) ) . '<br />';
		}

		// translators: %1$s projet ou profil.
		$message                 .= '<br />' . sprintf( _x( 'Détails du %1$s :', 'reporting', 'platform-shell-plugin' ), $post_type ) . '<br />';
		$project_or_profile_exist = false;

		if ( $params['ptype'] == $project_cpt_name ) {
			$post = get_post( $params['pid'] );
			if ( null != $post ) {
				$project_or_profile_exist = true;
				$project_post             = get_post( $params['pid'] );
				$project_author_user_info = get_userdata( $project_post->post_author );

				$user_profile_url = $this->profile->get_profile_url( $project_post->author_id );

				$title     = $post->post_title;
				$post_link = get_post_permalink( $post->ID );
				// translators: %1$s titre, %2$s url du projet.
				$message .= sprintf( _x( 'Titre : <a href="%2$s" >%1$s</a>', 'reporting', 'platform-shell-plugin' ), $title, esc_url( $post_link ) ) . '<br />';
				// translators: %1$s prenom %2$s nom.
				$message .= sprintf( _x( 'Auteur du projet : %1$s %2$s', 'reporting', 'platform-shell-plugin' ), $project_author_user_info->first_name, $project_author_user_info->last_name ) . '<br />';
				// translators: %1$s auteur, %2$s url du profil.
				$message .= sprintf( _x( 'Identifiant de l’usager : <a href="%2$s" >%1$s</a>', 'reporting', 'platform-shell-plugin' ), $project_author_user_info->user_login, esc_url( $user_profile_url ) );
			}
		} else {
			$user_info = get_userdata( $params['pid'] );
			if ( false != $user_info ) {
				$project_or_profile_exist = true;
				$user_profile_url         = $this->profile->get_profile_url( $params['pid'] );
				// translators: %1$s prénom, %2$s nom.
				$message .= sprintf( _x( 'Nom : %1$s %2$s', 'reporting', 'platform-shell-plugin' ), $user_info->first_name, $user_info->last_name ) . '<br />';
				// translators: %1$s identifiant, %2$s url du profil.
				$message .= sprintf( _x( 'Identifiant de l’usager : <a href="%2$s" >%1$s</a>', 'reporting', 'platform-shell-plugin' ), $user_info->user_login, esc_url( $user_profile_url ) );
			}
		}

		if ( $project_or_profile_exist ) {
			// Mécanique requise pour permettre l'envois de courriel html.
			add_filter( 'wp_mail_content_type', array( &$this, 'set_html_content_type' ), 10 );
			if ( wp_mail( $to, $subject, $message ) ) {
				$response = array(
					'result'  => 'success',
					'message' => _x( 'Merci. Ton avis au sujet d’un contenu inapproprié a bien été envoyé.', 'reporting', 'platform-shell-plugin' ),
				);
			} else {
				/* Insérer le email de contact général. */
				$response = array(
					'result'  => 'error',
					'message' => $default_error_message,
				);
			}
			// Il faut enlever le filtre pour ne pas entrer en conflit avec les autres fonctionnalités de WordPress.
			remove_filter( 'wp_mail_content_type', array( &$this, 'set_html_content_type' ), 10 );
		} else {
			// Possible détection de manipulation de données formulaire.
			$response = array(
				'result'  => 'error',
				'message' => $default_error_message,
			);
		}

		wp_send_json( $response );
		wp_die();
	}

	/**
	 * Méthode filtre permettant de définir le type de contenu du courriel de signalement.
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/wp_mail_content_type
	 * @return string    Retourne le type de contenu désiré. Valeur fixe text/html.
	 */
	public function set_html_content_type() {
		return 'text/html';
	}
}
