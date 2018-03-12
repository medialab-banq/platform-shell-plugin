<?php
/**
 * Platform_Shell\Shortcodes\
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Shortcodes;

use Platform_Shell\CPT\Project\Project_Taxonomy_Category;
use Exception;

/**
 * Classe Shortcode pour récupérer les icônes d'info.
 *
 * @class    Shortcode_Platform_Shell_Project_Info_Icons
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Shortcode_Platform_Shell_Project_Info_Icons {

	/**
	 * Taxonomie des catégories de projets
	 *
	 * @var Project_Taxonomy_Category
	 */
	private $project_taxonomy_category;

	/**
	 * Identifiant du projet
	 *
	 * @var integer
	 */
	private $project_id;

	/**
	 * Constructeur
	 *
	 * @param Project_Taxonomy_Category $project_taxonomy_category    Élément choisi de la taxonomie.
	 */
	public function __construct( Project_Taxonomy_Category $project_taxonomy_category ) {
		$this->project_taxonomy_category = $project_taxonomy_category;
	}

	/**
	 * Méthode run
	 *
	 * @param  array $atts    Attributs du shortcode.
	 * @return string         Données résultante du shortcode.
	 * @throws Exception      Exception lorsque qu'il y a un problème d'exécution du shortcode.
	 */
	public function run( $atts ) {
		if ( isset( $atts['project_id'] ) ) {
			$this->project_id = $atts['project_id'];
			return $this->render_info_icons();
		} else {
			throw new Exception( 'Missing project_id for Shortcode_Platform_Shell_Project_Info_Icons.' );
		}
	}

	/**
	 * Méthode get_all_level_configs
	 *
	 * @return array    Configuration pour tous les niveaux.
	 */
	private function get_all_level_configs() {
		/* Version de transition. */

		$configs = [
			'beginner'     => [
				'label'                => _x( 'Niveau facile', 'single-projects', 'platform-shell-theme' ),
				'icon_css_class'       => 'fa-tachometer',
				'icon_css_extra_class' => 'facile',
			],
			'intermediate' => [
				'label'                => _x( 'Niveau intermédiaire', 'single-projects', 'platform-shell-theme' ),
				'icon_css_class'       => 'fa-tachometer',
				'icon_css_extra_class' => 'intermediaire',
			],
			'advanced'     => [
				'label'                => _x( 'Niveau difficile', 'single-projects', 'platform-shell-theme' ),
				'icon_css_class'       => 'fa-tachometer',
				'icon_css_extra_class' => 'difficile',
			],
		];

		return $configs;
	}

	/**
	 * Méthode get_level_config
	 *
	 * @param  string $level    Niveau pour lequel nous désirons la configuration.
	 * @return array            Configuration pour le niveau sélectionné.
	 */
	private function get_level_config( $level ) {
		$configs = $this->get_all_level_configs();
		if ( isset( $configs[ $level ] ) ) {
			return $configs[ $level ];
		} else {
			return [
				'label'          => _x( 'Niveau inconnu', 'single-projects', 'platform-shell-theme' ),
				'icon_css_class' => 'fa-question',
			];
		}
	}

	/**
	 * Méthode get_all_creation_type_configs
	 *
	 * @return array    Les configurations sur les types de créations de projet.
	 */
	private function get_all_creation_type_configs() {
		$configs = [
			'individual-creation' => [
				'label'          => _x( 'Création individuelle', 'single-projects', 'platform-shell-theme' ),
				'icon_css_class' => 'fa-user',
			],
			'group-creation'      => [
				'label'          => _x( 'Création en groupe', 'single-projects', 'platform-shell-theme' ),
				'icon_css_class' => 'fa-users',
			],
		];

		return $configs;
	}

	/**
	 * Méthode get_creation_type_config
	 *
	 * @param  string $level Niveau pour lequel nous devons récuperer les configurations.
	 * @return array         Liste des paramètres pour le niveau de difficuté.
	 */
	private function get_creation_type_config( $level ) {

		$configs = $this->get_all_creation_type_configs();

		if ( isset( $configs[ $level ] ) ) {
			return $configs[ $level ];
		} else {
			return [
				'label'          => _x( 'Type de création inconnu', 'single-projects', 'platform-shell-theme' ),
				'icon_css_class' => 'fa-question',
			];
		}
	}

	/**
	 * Méthode render_info_icons
	 *
	 * @return string    Le code HTML requis pour afficher les icones reliées au projet.
	 */
	private function render_info_icons() {
		$render_output = '';

		$render_output = $render_output . $this->render_category_icon();
		$render_output = $render_output . $this->render_level_icon();
		$render_output = $render_output . $this->render_type_creation_icon();

		return $render_output;
	}

	/**
	 * Méthode render_category_icon
	 *
	 * @return string    Le code HTML requis pour afficher l'icone de la catégorie de projet.
	 */
	private function render_category_icon() {
		$category_term_name = '';

		$terms = wp_get_post_terms( $this->project_id, 'platform_shell_tax_proj_cat' );

		// Déterminer le term utilisé pour la catégorie.
		// todo_refactoring_get_cat_term?: faire un accesseur commun pour pas répéter ce loop partout.
		if ( isset( $terms ) ) {
			foreach ( $terms as $term ) {
				$category_term_name = $term->name;
			}
		}

		// Utiliser le terme pour récupérer (indépendant de la langue) pour récupérer le label (langue courante) et l'icône.
		$icon_css_class = $this->project_taxonomy_category->get_term_css_icon_class( $category_term_name );
		$label          = $this->project_taxonomy_category->get_term_label( $category_term_name );

		return '<span><i class="fa ' . $icon_css_class . '" aria-hidden="true"></i>' . $label . '</span>';
	}

	/**
	 * Méthode render_level_icon
	 *
	 * @return string    Le code HTML requis pour afficher l'icone du niveau de difficulté du projet.
	 */
	private function render_level_icon() {
		$level        = get_post_meta( $this->project_id, 'platform_shell_meta_project_level', true );
		$level_config = $this->get_level_config( $level );

		return '<span class="' . $level_config['icon_css_extra_class'] . '"><i class="fa ' . $level_config['icon_css_class'] . '" aria-hidden="true"></i>' . $level_config['label'] . '</span>';
	}

	/**
	 * Méthode render_type_creation_icon
	 *
	 * @return string    Le code HTML requis pour afficher l'icone de type de création.
	 */
	private function render_type_creation_icon() {
		$creation        = get_post_meta( $this->project_id, 'platform_shell_meta_project_creation_type', true );
		$creation_config = $this->get_creation_type_config( $creation );

		return '<span><i class="fa ' . $creation_config['icon_css_class'] . '" aria-hidden="true"></i>' . $creation_config['label'] . '</span>';
	}
}
