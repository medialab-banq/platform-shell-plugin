<?php
/**
 * Platform_Shell\installation\Required_Pages_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\installation;

/**
 * Gestion des configuration des pages requises de la plateforme.
 *
 * @class    Required_Pages_Configs
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Required_Pages_Configs {

	/**
	 * LIstes de pages requises.
	 *
	 * @var type array
	 */
	private $pages;

	/**
	 * Constructeur.
	 */
	public function __construct() {
	}

	/**
	 * Méthode pour retourner la liste des pages requises.
	 *
	 * @return type
	 */
	public function get_pages() {
		$this->lazy_init_pages();
		return $this->pages;
	}

	/**
	 * Méthodes pour initialiser les pages requises (lazy).
	 */
	private function lazy_init_pages() {
		if ( null === $this->pages ) {
			$content_pages = self::get_content_pages();
			$form_pages    = self::get_form_pages();
			$this->pages   = array_merge( $content_pages, $form_pages );
		}
	}

	/**
	 * Méthode pour récupérer le slug de la page à partir de son id.
	 *
	 * @param string $id     Id de la page de la plateforme.
	 * @return string        Slug de la page.
	 * @throws \Exception    Exception si la page n'est pas retrouvée.
	 */
	public function get_page_slug_by_id( $id ) {
		$this->lazy_init_pages();
		if ( isset( $this->pages[ $id ] ) ) {
			// En fait, il faudrait plutôt retrouver l'id de la page installée pour être plus indépendant par rapport au rename manuels?
			$page_slug = $this->pages[ $id ]['slug'];
			return $page_slug;
		} else {
			throw new \Exception( 'Impossible de trouver le slug de la page :.' . $id );
		}
	}

	/**
	 * Méthode pour récupérer les configurations d'une page de la plateforme.
	 *
	 * @param string $id    Id de page de la plateforme.
	 * @return string       Configurations de la page.
	 * @throws \Exception   Exception si la configuration n'existe pas.
	 */
	public function get_page_configs_by_id( $id ) {
		$this->lazy_init_pages();
		if ( isset( $this->pages[ $id ] ) ) {
			return $this->pages[ $id ];
		} else {
			throw new \Exception( 'Impossible de trouver les configurations de la page :' . $id );
		}
	}

	/**
	 * Méthode pour récupérer les configurations de pages de contenu de la plateforme.
	 *
	 * @return array
	 */
	private function get_content_pages() {
		// ATTENTION: Cette manière d'initialiser les pages implique que les pages vont être crées en utilisant
		// la langue active. Problématique pour slug ou WordPress va le gérer automatiquement?
		// Éventuellement les pages devraient être doublées? (analyse de la localisation à compléter).
		$pages = array(
			'platform-shell-page-profile'         => array(
				'slug'             => _x( 'profil', 'required-page-slug', 'platform-shell-plugin' ),
				'title'            => _x( 'Profil', 'required-page-title', 'platform-shell-plugin' ),
				'content'          => '',
				'delete-protected' => true,
			),
			'platform-shell-page-general-rules'   => array(
				'slug'             => _x( 'reglement-general', 'required-page-slug', 'platform-shell-plugin' ),
				'title'            => _x( 'Règlement général', 'required-page-title', 'platform-shell-plugin' ),
				'content'          => '',
				'delete-protected' => true,
			),
			'platform-shell-page-whats-new'       => array(
				'slug'             => _x( 'quoi-de-neuf', 'required-page-slug', 'platform-shell-plugin' ),
				'title'            => _x( 'Quoi de neuf?', 'required-page-title', 'platform-shell-plugin' ),
				'content'          => '',
				'page_template'    => 'list-qdn.php',
				'delete-protected' => true,
			),
			'platform-shell-page-about'           => array(
				'slug'        => _x( 'a-propos', 'required-page-slug', 'platform-shell-plugin' ),
				'title'       => _x( 'À propos', 'required-page-title', 'platform-shell-plugin' ),
				'content'     => '',
				'child_pages' => array(
					'platform-shell-page-space-layout' => array(
						'slug'    => _x( 'plan-espace', 'required-page-slug', 'platform-shell-plugin' ),
						'title'   => _x( 'Plan de l’espace', 'required-page-title', 'platform-shell-plugin' ),
						'content' => '',
					),
					'platform-shell-page-staff'        => array(
						'slug'    => _x( 'personnel', 'required-page-slug', 'platform-shell-plugin' ),
						'title'   => _x( 'Personnel', 'required-page-title', 'platform-shell-plugin' ),
						'content' => '',
					),
					'platform-shell-page-organisation-profile' => array(
						'slug'    => _x( 'profil-organisation', 'required-page-slug', 'platform-shell-plugin' ),
						'title'   => _x( 'Profil d’organisation', 'required-page-title', 'platform-shell-plugin' ),
						'content' => '',
					),
				),
			),
			'platform-shell-page-site-plan'       => array(
				'slug'    => _x( 'plan-du-site', 'required-page-slug', 'platform-shell-plugin' ),
				'title'   => _x( 'Plan du site', 'required-page-title', 'platform-shell-plugin' ),
				'content' => '',
			),
			'platform-shell-page-code-of-conduct' => array(
				'slug'    => _x( 'netiquette', 'required-page-slug', 'platform-shell-plugin' ),
				'title'   => _x( 'Nétiquette', 'required-page-title', 'platform-shell-plugin' ),
				'content' => '',
			),
			'platform-shell-page-accessibility'   => array(
				'slug'    => _x( 'accessibilite', 'required-page-slug', 'platform-shell-plugin' ),
				'title'   => _x( 'Accessibilité', 'required-page-title', 'platform-shell-plugin' ),
				'content' => '',
			),
			'platform-shell-page-contact'         => array(
				'slug'    => _x( 'contact', 'required-page-slug', 'platform-shell-plugin' ),
				'title'   => _x( 'Contact', 'required-page-title', 'platform-shell-plugin' ),
				'content' => '',
			),
		);
		return $pages;
	}

	/**
	 * Méthode pour récupérer les configurations de pages de formulaires de la plateforme.
	 *
	 * @return array
	 */
	private function get_form_pages() {
		$pages = [
			'platform-shell-page-project-create-page' => [
				'slug'             => _x( 'creer-un-projet', 'required-page-slug', 'platform-shell-plugin' ),
				'title'            => _x( 'Créer un projet', 'required-page-title', 'platform-shell-plugin' ),
				'content'          => '[platform_shell_add_project id="platform-shell-page-project-create-page"]',
				'delete-protected' => true,
			],
			'platform-shell-page-project-edit-page'   => [
				'slug'             => _x( 'modifier-le-projet', 'required-page-slug', 'platform-shell-plugin' ),
				'title'            => _x( 'Modifier le projet', 'required-page-title', 'platform-shell-plugin' ),
				'content'          => '[platform_shell_add_project id="platform-shell-page-project-edit-page"]',
				'delete-protected' => true,
			],
		];
		return $pages;
	}
}
