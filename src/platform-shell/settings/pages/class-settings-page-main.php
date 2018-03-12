<?php
/**
 * Platform_Shell\Settings\Settings_Page_Main
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Settings\Pages;

use \Platform_Shell\Settings\Settings_Page;

/**
 * Classe de gestion de l'écran de settings généraux.
 *
 * @class        Settings_Page_Main
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class Settings_Page_Main extends Settings_Page {

	/**
	 * Constructeur.
	 */
	public function __construct() {
	}

	/**
	 * Méthode de définition du menu (callback).
	 */
	public function admin_menu() {

		/* Voir aussi : https://wordpress.stackexchange.com/questions/66498/add-menu-page-with-different-name-for-first-submenu-item */
		add_submenu_page(
			$this->root_menu_slug,
			_x( 'Réglages principaux', 'settings-page-title', 'platform-shell-plugin' ),
			_x( 'Principaux', 'settings-menu-title', 'platform-shell-plugin' ),
			'platform_shell_cap_manage_basic_options',
			$this->root_menu_slug,
			array( $this, 'default_page_renderer_callback' )
		);
	}

	/**
	 * Méthode de configuration des configurations des sections (callback).
	 *
	 * @return array
	 */
	public function get_settings_sections() {
		$sections = array(
			array(
				'id'    => 'platform-shell-settings-main-contacts-and-notifications',
				'title' => _x( 'Contacts et notifications', 'settings-page-tab', 'platform-shell-plugin' ),
			),
			array(
				'id'    => 'platform_shell_settings_main_accounts',
				'title' => _x( 'Gestion de compte et authentification', 'settings-page-tab', 'platform-shell-plugin' ),
			),
		);
		return $sections;
	}

	/**
	 * Méthode de configuration des configurations des champs de settings (callback).
	 *
	 * @return array
	 */
	function get_settings_fields() {

		$settings_fields = [
			'platform-shell-settings-main-contacts-and-notifications' => [
				[
					'name' => 'html_contact_emails',
					'desc' => _x( 'Configuration du courriel.', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'                => 'platform_shell_option_contact_manager_email_adress',
					'label'               => _x( 'Courriel gestionnaire', 'settings-field-label', 'platform-shell-plugin' ),
					// translators: %1$s adresse courriel.
					// phpcs:ignore -- Ignorer ligne trop longue, configuration texte.
					'desc'                => sprintf( _x( 'Courriel utilisé pour les contacts avec le public et recevoir les notifications de gestion du système.<BR /><strong>Notes importantes : </strong><BR />- Un seul courriel. Veuillez utiliser un groupe courriel ou une boîte de courriel commune si plusieurs personnes doivent avoir accès aux courriels.<BR />- L’adresse de messagerie (courriel administrateur) définie dans les réglages de WordPress (<strong>%1$s</strong>) sera utilisée par défaut pour certains processus, par exemple le signalement, si le courriel gestionnaire de la plateforme n’est pas défini. Cependant, le courriel administrateur ne sera pas présenté comme courriel de contact aux utilisateurs. Il est fortement recommandé de définir un courriel gestionnaire séparé à cette fin.', 'settings-field-description', 'platform-shell-plugin' ), get_option( 'admin_email' ) ),
					'placeholder'         => _x( 'courriel@', 'settings-field-placeholder', 'platform-shell-plugin' ),
					'type'                => 'text',
					'default'             => '',
					'sanitize_callback'   => 'sanitize_text_field',
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				],
			],
			'platform_shell_settings_main_accounts' => [
				[
					'name' => 'html_profile_configs',
					'desc' => _x( 'Configuration de la gestion du profil de l’utisateur', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'    => 'platform_shell_option_profile_use_gravatar',
					'label'   => _x( 'Fonctionnalité Gravatar', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Permettre l’utilisation de <a href="https://fr.gravatar.com/" target="_blank">Gravatar</a>.', 'settings', 'platform-shell-plugin' ),
					'default' => 'off',
					'type'    => 'checkbox',
				],
				[
					'name' => 'html_shibboleth_configs',
					'desc' => _x( 'Configuration de l’intégration Shibboleth', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				array(
					'name'                => 'platform_shell_option_shibboleth_missing_email_behavior',
					'label'               => _x( 'Assignation d’un couriel fictif', 'settings', 'platform-shell-plugin' ),
					'desc'                => _x( 'Si l’annuaire contient des comptes sans courriels, l’assignation d’un courriel fictif permet de compléter la création automatique du compte dans WordPress (courriel obligatoire).<BR /><strong>Note importante : </strong><BR />- Les notifications courriels, pour le changement de mot de passe par exemple, ne vont pas fonctionner pour les comptes avec courriel fictif.', 'settings', 'platform-shell-plugin' ),
					'type'                => 'select',
					'default'             => 'ASSIGN_NEVER',
					/* todo: externalise types. */
					'options'             => array(
						'ASSIGN_NEVER'   => 'Aucun comptes (échec de provisionnement si courriel manquant)',
						'ASSIGN_MISSING' => 'Comptes sans courriels',
						'ASSIGN_ALL'     => 'Tous les comptes',
					),
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				),
				[
					'name'    => 'platform_shell_option_shibboleth_show_real_email_to_user',
					'label'   => _x( 'Afficher le courriel réel dans le profil.', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Afficher le courriel.<BR /><BR /> Le courriel ne sera visible que par le propriétaire du compte.', 'settings', 'platform-shell-plugin' ),
					'default' => 'on',
					'type'    => 'checkbox',
				],
				[
					'name'                => 'platform_shell_option_shibboleth_missing_email_message',
					'label'               => _x( 'Message à afficher pour courriel réel manquant.', 'settings-field-label', 'platform-shell-plugin' ),
					'desc'                => _x( 'Le courriel réel provenant de l’annuaire est affiché si l’option « <strong>Afficher le courriel réel dans le profil</strong> » est activée.<BR />- Le message est affiché lorsqu’il n’y a pas de courriel défini.<BR />- Le courriel est récupéré des attributs de profil Shibboleth (shibboleth headers).<BR />- Le courriel fictif n’est jamais affiché.', 'settings', 'platform-shell-plugin' ),
					'placeholder'         => '',
					'type'                => 'text',
					'default'             => '',
					'sanitize_callback'   => 'sanitize_text_field',
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				],
			],
		];

		return $settings_fields;
	}

}
