<?php
/**
 * Platform_Shell\CPT\Project\Project_Taxonomy_Category
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Project;

/**
 * Project_Taxonomy_Category class
 */
class Project_Taxonomy_Category {

	/**
	 * Configuration de la taxonomie
	 *
	 * @var array
	 */
	private $terms_configs;

	/**
	 * Constructeur.
	 */
	public function __construct() {
		$this->init_terms_configs();
	}

	/**
	 * Méthode init_terms
	 *
	 * Les termes ne sont pas en saisie libre, ce qui permet aussi de gérer la localisation des termes (pas offert directement par WordPress.
	 * Récupérer la liste des termes par WordPress va retourner la liste des termes avec un indentifiant, il faut utiliser cette table pour
	 * retrouver la correspondance du texte à afficher (puisqu'il n'existe pas de champs permettant de le faire.
	 */
	private function init_terms_configs() {
		$this->terms_configs = [
			'audio'                => [
				'label'          => _x( 'Audio', 'cpt-project-taxonomy-category-term-label', 'platform-shell-plugin' ),
				'description'    => _x( 'Catégorie audio', 'cpt-project-taxonomy-category-term-description', 'platform-shell-plugin' ),
				'slug'           => _x( 'audio', 'cpt-project-taxonomy-category-term-slug', 'platform-shell-plugin' ),
				'css_icon_class' => 'fa-microphone',
			],
			'comic-book'           => [
				'label'          => _x( 'Bande dessinée', 'cpt-project-taxonomy-category-term-label', 'platform-shell-plugin' ),
				'description'    => _x( 'Catégorie bande dessinée', 'cpt-project-taxonomy-category-term-description', 'platform-shell-plugin' ),
				'slug'           => _x( 'bande-dessinee', 'cpt-project-taxonomy-category-term-slug', 'platform-shell-plugin' ),
				'css_icon_class' => 'fa-picture-o',
			],
			'drawing-illustration' => [
				'label'          => _x( 'Dessin / Illustration', 'cpt-project-taxonomy-category-term-label', 'platform-shell-plugin' ),
				'description'    => _x( 'Catégorie dessin', 'cpt-project-taxonomy-category-term-description', 'platform-shell-plugin' ),
				'slug'           => _x( 'dessin-illustration', 'cpt-project-taxonomy-category-term-slug', 'platform-shell-plugin' ),
				'css_icon_class' => 'fa-paint-brush',
			],
			'video-game'           => [
				'label'          => _x( 'Jeux vidéo', 'cpt-project-taxonomy-category-term-label', 'platform-shell-plugin' ),
				'description'    => _x( 'Catégorie jeux vidéo', 'cpt-project-taxonomy-category-term-description', 'platform-shell-plugin' ),
				'slug'           => _x( 'jeux-video', 'cpt-project-taxonomy-category-term-slug', 'platform-shell-plugin' ),
				'css_icon_class' => 'fa-gamepad',
			],
			'3d-modeling'          => [
				'label'          => _x( 'Modélisation 3D', 'cpt-project-taxonomy-category-term-label', 'platform-shell-plugin' ),
				'description'    => _x( 'Catégorie modélisation 3D', 'cpt-project-taxonomy-category-term-description', 'platform-shell-plugin' ),
				'slug'           => _x( 'modelisation-3d', 'cpt-project-taxonomy-category-term-slug', 'platform-shell-plugin' ),
				'css_icon_class' => 'fa-cubes',
			],
			'photography'          => [
				'label'          => _x( 'Photographie', 'cpt-project-taxonomy-category-term-label', 'platform-shell-plugin' ),
				'description'    => _x( 'Catégorie photographie', 'cpt-project-taxonomy-category-term-description', 'platform-shell-plugin' ),
				'slug'           => _x( 'photo', 'cpt-project-taxonomy-category-term-slug', 'platform-shell-plugin' ),
				'css_icon_class' => 'fa-camera-retro',
			],
			'video'                => [
				'label'          => _x( 'Vidéo', 'cpt-project-taxonomy-category-term-label', 'platform-shell-plugin' ),
				'description'    => _x( 'Catégorie vidéo', 'cpt-project-taxonomy-category-term-description', 'platform-shell-plugin' ),
				'slug'           => _x( 'video', 'cpt-project-taxonomy-category-term-slug', 'platform-shell-plugin' ),
				'css_icon_class' => 'fa-video-camera',
			],
			'other'                => [
				'label'          => _x( 'Autre', 'cpt-project-taxonomy-category-term-label', 'platform-shell-plugin' ),
				'description'    => _x( 'Catégorie autre', 'cpt-project-taxonomy-category-term-description', 'platform-shell-plugin' ),
				'slug'           => _x( 'autre', 'cpt-project-taxonomy-category-term-slug', 'platform-shell-plugin' ),
				'css_icon_class' => 'fa-ellipsis-h',
			],
		];
	}

	/**
	 * Méthode get_term_label
	 *
	 * @param string $term_name    Nom du terme.
	 * @return string
	 */
	public function get_term_label( $term_name ) {

		if ( isset( $this->terms_configs[ $term_name ] ) ) {
			return $this->terms_configs[ $term_name ]['label'];
		} else {
			// On pourrait faire un throw mais on laisse passer l'erreur avec un indice qu'il y a un problème.
			return _x( 'Catégorie inconnue', 'cpt-project-taxonomy-category-unknown-term', 'platform-shell-plugin' );
		}
	}

	/**
	 * Méthode get_terms_configs
	 *
	 * @return array
	 */
	public function get_terms_configs() {

		return $this->terms_configs;
	}

	/**
	 * Méthode get_term_css_icon_class
	 *
	 * @param string $term_name    Nom du terme.
	 * @return string
	 */
	public function get_term_css_icon_class( $term_name ) {

		if ( isset( $this->terms_configs[ $term_name ] ) ) {
			return $this->terms_configs[ $term_name ]['css_icon_class'];
		} else {
			// On pourrait faire un throw mais on laisse passer l'erreur avec un indice qu'il y a un problème.
			return 'fa-question';
		}
	}

	/**
	 * Méthode project_taxonomy_term_link
	 *
	 * @param string   $url         The term URL.
	 * @param \WP_Term $term      The term object.
	 * @param string   $taxonomy    The taxonomy slug.
	 * @return string
	 * @see https://wordpress.stackexchange.com/questions/94817/add-category-base-to-url-in-custom-post-type-taxonomy
	 */
	public function project_taxonomy_term_link( $url, $term, $taxonomy ) {
		$tax = get_taxonomy( $taxonomy );
		return get_bloginfo( 'url' ) . '/' . $tax->rewrite['slug'] . '/' . $term->slug;
	}
}
