<?php
/**
 * Platform_Shell\Settings\Settings_Page_Site_Sections
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Settings\Pages;

use Platform_Shell\Settings\Settings_Page;
use Platform_Shell\Templates\Template_Helper;

/**
 * Settings_Page_Site_Sections
 *
 * @class        Settings_Page_Site_Sections
 * @description  Classes utilitaire CPT ( en attendant possible refactoring / regroupement ).
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class Settings_Page_Site_Sections extends Settings_Page {

	/**
	 * Instance de Template_Helper (DI)
	 *
	 * @var Template_Helper
	 */
	private $template_helper;

	/**
	 * Constructeur
	 *
	 * @param Template_Helper $template_helper    Instance de Template_Helper (DI).
	 */
	public function __construct( Template_Helper $template_helper ) {
		$this->template_helper = $template_helper;
	}

	/**
	 * Méthode de définition du menu (callback).
	 */
	public function admin_menu() {
		/* Voir aussi : https://wordpress.stackexchange.com/questions/66498/add-menu-page-with-different-name-for-first-submenu-item */
		add_submenu_page(
			$this->root_menu_slug,
			_x( 'Réglages des pages et sections', 'settings-page-title', 'platform-shell-plugin' ),
			_x( 'Pages et sections', 'settings-page-title', 'platform-shell-plugin' ),
			'platform_shell_cap_manage_basic_options',
			_x( 'reglages-pages-et-sections', 'settings-page-slug', 'platform-shell-plugin' ),
			array( $this, 'default_page_renderer_callback' )
		);
	}

	/**
	 * Méthode de configuration des configurations des sections (callback).
	 *
	 * @return array
	 */
	public function get_settings_sections() {
		$sections = [
			[
				'id'    => 'platform-shell-settings-page-site-sections-general',
				'title' => _x( 'Général', 'settings', 'platform-shell-plugin' ),
			],
			[
				'id'    => 'platform-shell-settings-page-site-sections-home',
				'title' => _x( 'Page d’accueil', 'settings', 'platform-shell-plugin' ),
			],
			[
				'id'    => 'platform-shell-settings-page-site-sections-contests',
				'title' => _x( 'Concours', 'settings', 'platform-shell-plugin' ),
			],
			[
				'id'    => 'platform-shell-settings-page-site-sections-activities',
				'title' => _x( 'Activités', 'settings', 'platform-shell-plugin' ),
			],
		];
		return $sections;
	}

	/**
	 * Méthode de configuration des configurations des champs de settings (callback).
	 *
	 * @return array
	 */
	public function get_settings_fields() {

		/* Pour exemple du format attendu seulement. */
		$openstreet_map_demo_url = 'https://www.openstreetmap.org/directions?to=grande%20biblioth%C3%A8que%20montr%C3%A9al#map=19/45.51542/-73.56194';
		$google_map_demo_url     = 'https://www.google.com/maps/place/475+Boul+de+Maisonneuve+E,+Montr%C3%A9al,+QC+H2L+5C4/@45.5154553,-73.5645016,17z/data=!4m5!3m4!1s0x4cc91bb33ac3b623:0xdc36963c5b9bfd96!8m2!3d45.5154553!4d-73.5623129';

		return [
			'platform-shell-settings-page-site-sections-general' => [
				[
					'name' => 'html_main_menus',
					'desc' => _x( 'Configuration de l’affichage des menus principaux', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'    => 'platform_shell_option_show_social_menu',
					'label'   => _x( 'Menu des liens des médias sociaux – affichage', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Afficher le menu.', 'settings', 'platform-shell-plugin' ),
					'default' => 'on',
					'type'    => 'checkbox',
				],
				[
					'name' => 'html_parent_organisation',
					'desc' => _x( 'Configuration de l’affichage du logo de l’organisation parent', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'    => 'platform_shell_option_show_parent_organisation_logo',
					'label'   => _x( 'Organisation parent', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Afficher le logo', 'settings', 'platform-shell-plugin' ),
					'default' => 'on',
					'type'    => 'checkbox',
				],
				[
					'name'    => 'platform_shell_option_parent_organisation_logo_url',
					'label'   => _x( 'Organisation parent – image', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Image du logo de l’organisation parent', 'settings', 'platform-shell-plugin' ),
					'type'    => 'file',
					'default' => '',
					'options' => [
						'button_label' => _x( 'Choisir une image (taille maximale de 300 x 40 pixels)', 'settings', 'platform-shell-plugin' ),
					],
				],
				[
					'name'        => 'platform_shell_option_parent_organisation_logo_alt',
					'label'       => _x( 'Organisation parent – texte alternatif', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Texte alternatif du logo de l’organisation parent.', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'text',
					'default'     => '',
				],
				[
					'name'        => 'platform_shell_option_parent_organisation_link',
					'label'       => _x( 'Organisation parent – lien', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Adresse URL du site de l’organisation parent.', 'settings', 'platform-shell-plugin' ),
					'placeholder' => _x( 'Entrer la donnée en format URL.', 'settings', 'platform-shell-plugin' ),
					'type'        => 'text',
					'default'     => '',
				],
				[
					'name' => 'html_plateform',
					'desc' => _x( 'Configuration de l’affichage du logo principal de la plateforme.', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'    => 'platform_shell_option_show_site_logo',
					'label'   => _x( 'Plateforme', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Afficher le logo.', 'settings', 'platform-shell-plugin' ),
					'default' => 'on',
					'type'    => 'checkbox',
				],
				[
					'name'    => 'platform_shell_site_logo_url',
					'label'   => _x( 'Plateforme – image', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Logo principal de la plateforme', 'settings', 'platform-shell-plugin' ),
					'type'    => 'file',
					'default' => '',
					'options' => [
						'button_label' => _x( 'Choisir une image (taille suggérée de 155 x 235 pixels)', 'settings', 'platform-shell-plugin' ),
					],
				],
				[
					'name'        => 'platform_shell_site_logo_alt_title',
					'label'       => _x( 'Plateforme – texte alternatif', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Texte alternatif du logo de la plateforme.', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'text',
					'default'     => '',
				],
				[
					'name'        => 'platform_shell_option_contests_admissibility_list',
					'label'       => _x( 'Liste d’admissibilité', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Liste de descriptions de groupes admissibles prédéfinie.<br/> - Cette liste sera présentée lors de la création de concours.<br/> - Format liste simple. Saisir une admissibilité par ligne. <br/>', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'textarea',
					'default'     => '',
				],
			],
			'platform-shell-settings-page-site-sections-home' => [
				[
					'name' => 'html_header_box',
					'desc' => _x( 'Configuration de la boîte d’accueil.', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'    => 'platform_shell_option_home_page_show_header_box',
					'label'   => _x( 'Boîte d’accueil – affichage', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Afficher la boîte.', 'settings', 'platform-shell-plugin' ),
					'default' => 'on',
					'type'    => 'checkbox',
				],
				[
					'name'        => 'platform_shell_option_home_page_header_box_title',
					'label'       => _x( 'Boîte d’accueil - titre ', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Titre principal de la page<br/>- Le titre ne doit pas contenir des balises HTML.', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'text', /* Texte. Sortie dans H1. */
					'default'     => '',
				],
				[
					'name' => 'html_coordinate_and_opening_hours',
					'desc' => _x( 'Configuration de la boîte des coordonnées et heures d’ouverture dans le pied de page', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'    => 'platform_shell_option_home_page_show_coordinate_and_opening_hours_box',
					'label'   => _x( 'Boîte de coordonnées et heures d’ouverture', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Afficher les coordonnées et heures d’ouverture', 'settings', 'platform-shell-plugin' ),
					'default' => 'on',
					'type'    => 'checkbox',
				],
				[
					'name'        => 'platform_shell_gmap_key',
					'label'       => _x( 'Boîte - Clé d’API pour Google Maps', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Clé d’authentification pour Google Maps dans l’outil de génération d’image de fond pour la boîte.', 'settings', 'platform-shell-plugin' ) . '<br />- Voir la <a target="_blank" href="https://developers.google.com/maps/documentation/geocoding/get-api-key?hl=fr">documentation Google</a>.',
					'placeholder' => '',
					'type'        => 'text',
					'default'     => '',
				],
				[
					'name'     => 'platform_shell_footer_location_background_url',
					'label'    => _x( 'Boîte - Image de fond (affichée en arrière-plan des coordonnées et heures d’ouverture)', 'settings', 'platform-shell-plugin' ),
					'desc'     => _x( 'Image à utiliser en arrière plan de la boîte.<br/>- Choisir une image existante ou cliquer sur le bouton « <strong> Générer une image de fond </strong>» pour générer une image correspondant au lieu de votre médialab.<br/>- Taille prévue par la maquette : 1400x377 pixels.', 'settings', 'platform-shell-plugin' ),
					'type'     => 'file',
					'default'  => '',
					'callback' => function ( $args ) {
						$args['value'] = $this->settings_api->get_option( $args['id'], $args['section'], $args['std'] );
						echo $this->template_helper->get_template( 'fields/footer-background-field.php', $args );
					},
					'options'  => [
						'button_label' => _x( 'Choisir une image de fond', 'settings', 'platform-shell-plugin' ),
					],
				],
				[
					'name'        => 'platform_shell_option_contact_adress',
					'label'       => _x( 'Adresse', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'L’adresse géographique. <br/>- 3 lignes maximum.', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'wysiwyg',
					'default'     => '',
				],
				[
					'name'        => 'platform_shell_option_itinerary_url',
					'label'       => _x( 'Itinéraire', 'settings', 'platform-shell-plugin' ),
					// translators: %1$s exemple url google map, %2$s exemple url openstreet map.
					'desc'        => sprintf( _x( 'Adresse URL d’un service de localisation permettant d’afficher l’itinéraire vers le lieu physique.<br />- Ex. : <a href="%1$s">Google Maps</a><br />- Ex. : <a href="%2$s">OpenStreetMap</a>', 'settings', 'platform-shell-plugin' ), $google_map_demo_url, $openstreet_map_demo_url ),
					'placeholder' => '',
					'type'        => 'text',
					'default'     => '',
				],
				[
					'name'        => 'platform_shell_option_contact_phone_numer',
					'label'       => _x( 'Téléphone', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Numéro de téléphone pour contact avec le public.', 'settings', 'platform-shell-plugin' ),
					'placeholder' => _x( '555 555-5555 (format suggéré)', 'settings', 'platform-shell-plugin' ),
					'type'        => 'text',
					'default'     => '',
				],
				[
					'name'        => 'platform_shell_option_opening_hours',
					'label'       => _x( 'Heures d’ouverture', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Heures d’ouverture du lieu physique. <br/> - 8 lignes maximum.', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'wysiwyg',
					'default'     => '',
				],
				[
					'name' => 'html_contributors',
					'desc' => _x( 'Configurations de la boîte des logos de partenaires dans le pied de page.', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'    => 'platform_shell_show_contributors_footer',
					'label'   => _x( 'Boîte des logos de partenaires', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Afficher la boîte contenant les logos des partenaires', 'settings', 'platform-shell-plugin' ),
					'default' => 'on',
					'type'    => 'checkbox',
				],
			],
			'platform-shell-settings-page-site-sections-contests' => [
				[
					'name' => 'html_contests',
					'desc' => _x( 'Configuration des concours.', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'        => 'platform_shell_option_contests_organizers_list',
					'label'       => _x( 'Liste des organisateurs', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Liste des organisateurs prédéfinie.<br/> - Cette liste sera présentée lors de la création de concours.<br/> - Format liste simple. Saisir un organisateur par ligne.', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'textarea',
					'default'     => '',
				],
				[
					'name'        => 'platform_shell_option_contests_type_list',
					'label'       => _x( 'Liste des types de concours', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Liste de types de concours prédéfinie (titre et icône).<br/> - Cette liste sera présentée lors de la création de concours.<br/> - Format JSON (premier niveau = array, deuxième niveau = objet) <br/> - Utiliser la liste des icônes de FontAwesome : http://fontawesome.io/icons/', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'textarea',
					'default'     => '',
				],
			],
			'platform-shell-settings-page-site-sections-activities' => [
				[
					'name' => 'html_activities',
					'desc' => _x( 'Configuration des activités.', 'settings', 'platform-shell-plugin' ),
					'type' => 'html',
				],
				[
					'name'    => 'platform_shell_option_activities_show_group_activities_link_button',
					'label'   => _x( 'Activités de groupe', 'settings', 'platform-shell-plugin' ),
					'desc'    => _x( 'Afficher le bouton de lien vers les activités de groupe.', 'settings', 'platform-shell-plugin' ),
					'default' => 'on',
					'type'    => 'checkbox',
				],
				[
					'name'        => 'platform_shell_option_activities_group_activities_button_label',
					'label'       => _x( 'Libellé du bouton', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Libellé du bouton.', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'text',
					'default'     => '',
				],
				[
					'name'        => 'platform_shell_option_activities_group_activities_url',
					'label'       => _x( 'URL des activitées de groupe', 'settings', 'platform-shell-plugin' ),
					'desc'        => _x( 'Lien vers les pages d’activités de groupe', 'settings', 'platform-shell-plugin' ),
					'placeholder' => '',
					'type'        => 'text',
					'default'     => '',
				],
			],
		];
	}
}
