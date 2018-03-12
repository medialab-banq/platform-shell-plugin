<?php
/**
 * Platform_Shell\Settings\Settings_Page_Social_Sharing
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Settings\Pages;

use Platform_Shell\Settings\Settings_Page;

/**
 * Classe de gestion des configurations des partage de réseaux sociaux.
 *
 * @class        Settings_Page_Social_Sharing
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class Settings_Page_Social_Sharing extends Settings_Page {

	/**
	 * Constructeur.
	 */
	public function __construct() {
	}

	/**
	 * Méthode de définition du menu (callback).
	 */
	public function admin_menu() {
		add_submenu_page(
			$this->root_menu_slug,
			_x( 'Réglages des partages sur les médias sociaux', 'settings-page-title', 'platform-shell-plugin' ),
			_x( 'Partages sur les médias sociaux', 'settings-page-title', 'platform-shell-plugin' ),
			'platform_shell_cap_manage_basic_options',
			_x( 'reglages-partages-sociaux', 'settings-page-slug', 'platform-shell-plugin' ),
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
				'id'    => 'platform-shell-settings-social-sharing-facebook',
				'title' => _x( 'Facebook', 'settings-page-tab', 'platform-shell-plugin' ),
			),
			array(
				'id'    => 'platform-shell-settings-social-sharing-twitter',
				'title' => _x( 'Twitter', 'settings-page-tab', 'platform-shell-plugin' ),
			),
			array(
				'id'    => 'platform-shell-settings-social-sharing-email',
				'title' => _x( 'Courriel', 'settings-page-tab', 'platform-shell-plugin' ),
			),
		);
		return $sections;
	}

	/**
	 * Méthode de configuration des configurations des champs de settings (callback).
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		$settings_fields = array(
			'platform-shell-settings-social-sharing-facebook' => array(
				array(
					'name' => 'html_conf_facebook',
					'desc' => _x( 'Pour le bon fonctionnement du partage sur Facebook, une extension complémentaire doit être utilisée pour la génération des métadonnées Open Graph (<a target="_blank" href="https://kb.yoast.com/kb/getting-open-graph-for-your-articles/">Exemple avec l’extension Yoast</a>). Le site doit être visible sur internet afin de permettre à Facebook de récupérer les métadonnées.', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				),
				array(
					'name'                => 'platform_shell_option_social_sharing_facebook_script_tag',
					'label'               => _x( 'Script Tag', 'settings', 'platform-shell-plugin' ),
					'desc'                => _x(
						'Code JavaScript pour compléter l’intégration Facebook.<br /> - Compte développeur Facebook requis pour la génération du script tag.<br /> - <strong>Ne pas inclure les balises &#x3C;script&#x3E;</strong>, seulement le code javascript.<br /> - Vérifier et modifier le code de langue au besoin dans l’url connect.facebook.net/<strong>en_US</strong>/sdk.js (<strong>fr_CA</strong> par exemple).<br /> - L’extension Yoast, ou solution équivalente, est requis pour la génération des métadonnées Open Graph.',
						'settings',
						'platform-shell-plugin'
					),
					'placeholder'         => '',
					'type'                => 'textarea',
					'default'             => '',
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				),
				array(
					'name'                => 'platform_shell_option_social_sharing_facebook_description_template',
					'label'               => _x( 'Modèle de description', 'settings', 'platform-shell-plugin' ),
					'desc'                => _x( 'Modèle de description utilisé lors du partage.<br /> - Message texte sans HTML.<br /> - Utiliser %post_title% pour insérer le titre du contenu partagé.<br /> - Valider avec documentation Facebook pour la longueur maximal permise (en incluant le titre s’il est utilisé).', 'settings', 'platform-shell-plugin' ),
					'placeholder'         => '',
					'type'                => 'textarea',
					'default'             => '',
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				),
			),
			'platform-shell-settings-social-sharing-twitter' => array(
				array(
					'name' => 'html_conf_twitter',
					'desc' => _x( 'Pour le bon fonctionnement du partage sur Twitter, une extension complémentaire doit être utilisée pour la génération des métadonnées Twitter Card (<a target="_blank" href="https://kb.yoast.com/kb/setting-up-twitter-cards-in-wordpress-seo/">Exemple avec l’extension Yoast</a>). Le site doit être visible sur internet afin de permettre à Twitter de récupérer les métadonnées.', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				),
				array(
					'name'                => 'platform_shell_option_social_sharing_twitter_message_template',
					'label'               => _x( 'Modèle du message', 'settings', 'platform-shell-plugin' ),
					'desc'                => _x( 'Modèle du message.<br /> - Message texte sans HTML.<br /> - Utiliser %post_title% pour insérer le titre du contenu partagé (préférablement <strong>après</strong> le message afin de couper le titre si le message est trop long).<br /> - L’url sera inséré automatiquement après le message.<br /> - Message court : la limite de 140 caractères de Twitter sera appliquée.', 'settings', 'platform-shell-plugin' ),
					'placeholder'         => '',
					'type'                => 'textarea',
					'default'             => '',
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				),
			),
			'platform-shell-settings-social-sharing-email' => array(
				array(
					'name' => 'html_conf_email',
					'desc' => _x( 'Configuration du partage courriel', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				),
				/* Titre du message. Message par défaut? */
				array(
					'name'                => 'platform_shell_option_social_sharing_email_message_template',
					'label'               => _x( 'Modèle du message courriel', 'settings', 'platform-shell-plugin' ),
					/* translators: Les textes %post_title%, %post_url%, %site_url% à utiliser tel quels sans traduction. */
					'desc'                => _x( 'Modèle du message utilisé lors du partage par courriel.<br /> - Message texte sans HTML.<br /> - Utiliser %post_title% pour insérer le titre du contenu partagé.<br /> - Utiliser %post_url% pour l’url du contenu partagé.<br /> - Utiliser %site_url% pour l’url du site.<br /> - Certains caractères pourraient poser problème. Valider avec plusieurs navigateurs / logiciels de courriel.', 'settings', 'platform-shell-plugin' ),
					'placeholder'         => '',
					'type'                => 'textarea',
					'default'             => '',
					'required_capability' => 'platform_shell_cap_manage_advanced_options',
				),
			),
		);

		return $settings_fields;
	}
}
