<?php
/**
 * Platform_Shell\Shortcodes\Shortcodes_Manager
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Shortcodes;

use Platform_Shell\CPT\Project\Project_Configs;
use Platform_Shell\CPT\Project\Project_Taxonomy_Category;
use Platform_Shell\Reporting\Reporting_Configs;
use Platform_Shell\Templates\Template_Helper;

/**
 * Platform_Shell Shortcodes_Manager
 *
 * @class Shortcodes_Manager
 * @description Classes utilitaire pour le création de champs de formulaire
 * @author Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class Shortcodes_Manager {

	/**
	 * Configuration des shortcodes
	 *
	 * @var array
	 */
	private $shortcode_objects = array();

	/**
	 * Taxonomie d'un projet
	 *
	 * @var Project_Taxonomy_Category
	 */
	private $project_taxonomy_category;

	/**
	 * Classe helper pour afficher les valeurs d'un champ.
	 *
	 * @var Template_Helper
	 */
	private $template_helper;

	/**
	 * Configuration d'un projet.
	 *
	 * @var Project_Configs
	 */
	private $project_configs;

	/**
	 * Constructeur
	 *
	 * @param Project_Taxonomy_Category                                      $project_taxonomy_category                                         Taxonomie du projet.
	 * @param Template_Helper                                                $template_helper                                                   Instance de la classe helper pour les taxonomies.
	 * @param Shortcode_Platform_Shell_Permalink_By_Page_Id                  $shortcode_platform_shell_permalink_by_page_id                     Contenu des du permalien de la page des shortcodes.
	 * @param Shortcode_Platform_Shell_Contest_Rules_Page_Content            $shortcode_platform_shell_contest_rules_page_content               Contenu de la page des règlements du concours.
	 * @param Shortcode_Platform_Shell_Project_Type_Term_Label_By_Project_Id $shortcode_platform_shell_project_type_term_label_by_project_id    Label du type de projet.
	 * @param Shortcode_Platform_Shell_Project_Info_Icons                    $shortcode_platform_shell_project_info_icons                       Contenu de l'icône d'informations sur le projet.
	 * @param Project_Configs                                                $project_configs                                                   Configuration des projets.
	 */
	public function __construct(
		Project_Taxonomy_Category $project_taxonomy_category,
		Template_Helper $template_helper,
		Shortcode_Platform_Shell_Permalink_By_Page_Id $shortcode_platform_shell_permalink_by_page_id,
		Shortcode_Platform_Shell_Contest_Rules_Page_Content $shortcode_platform_shell_contest_rules_page_content,
		Shortcode_Platform_Shell_Project_Type_Term_Label_By_Project_Id $shortcode_platform_shell_project_type_term_label_by_project_id,
		Shortcode_Platform_Shell_Project_Info_Icons $shortcode_platform_shell_project_info_icons,
		Project_Configs $project_configs
	) {
		$this->shortcode_objects['platform_shell_permalink_by_page_id']                  = $shortcode_platform_shell_permalink_by_page_id;
		$this->shortcode_objects['platform_shell_contest_rules_page_content']            = $shortcode_platform_shell_contest_rules_page_content;
		$this->shortcode_objects['platform_shell_project_type_term_label_by_project_id'] = $shortcode_platform_shell_project_type_term_label_by_project_id;
		$this->shortcode_objects['platform_shell_project_info_icons']                    = $shortcode_platform_shell_project_info_icons;
		// todo_refactoring. Dépendances requises d'un shortcode implémenté directement dans manager. À corriger shortcode implémenté dans classes dédié.
		$this->project_taxonomy_category = $project_taxonomy_category;
		$this->template_helper           = $template_helper;
		$this->project_configs           = $project_configs;
	}

	/**
	 * Méthode init
	 */
	public function init() {
		// Shortcode définis dans le manager.
		$shortcodes = [
			'platform_shell_add_project'             => [ &$this, 'shortcode_project_add_project' ],
			'platform_shell_reporting'               => [ &$this, 'shortcode_reporting_get_flag_button' ],
			'platform_shell_project_type_term_label' => [ &$this, 'shortcode_project_type_term' ],
		];
		// Ajout des shortcodes définis avec classes dédiés.
		foreach ( $this->shortcode_objects as $shortcode_id => $shortcode_object ) {
			$shortcodes[ $shortcode_id ] = [ $shortcode_object, 'run' ];
		}
		$this->register_shortcodes( $shortcodes );
	}

	/**
	 * Méthode register_shortcodes
	 *
	 * @param array $shortcodes    Un tableau contenant tous les shortcodes.
	 */
	private function register_shortcodes( $shortcodes ) {
		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, $function );
		}
	}

	/**
	 * Méthode shortcode_project_type_term
	 *
	 * @param array $atts    Attributs du shortcode.
	 * @return string        Label du terme associé au shortcode.
	 * @todo                 Refactoring. Mettre shortcode programmés dans le manager dans classes séparées.
	 */
	public function shortcode_project_type_term( $atts ) {
		return $this->project_taxonomy_category->get_term_label( $atts['name'] );
	}

	/**
	 * Méthode shortcode_project_add_project
	 *
	 * @param array $atts    Attributs du shortcode.
	 * @return string        Le code du shortcode.
	 */
	public function shortcode_project_add_project( $atts ) {
		global $current_user;
		global $wp;
		global $wp_query;

		$project_id = null;
		$project    = null;
		$action     = isset( $wp_query->query_vars['action'] ) ? $wp_query->query_vars['action'] : null;

		if ( isset( $wp_query->query_vars['project_code'] ) ) {

			$project = get_page_by_path( $wp_query->query_vars['project_code'], OBJECT, 'project' );

			if ( ! is_null( $project ) ) {
				$project_id = $project->ID;
			}
		}

		if ( '' == $current_user->ID ) {
			$current_url = site_url( add_query_arg( array(), $wp->request ) ); /* On doit pouvoir revenir à l'url courant. */
			$message     = '<div class="alert alert-danger">';
			$message    .= _x(
				'Tu dois être connecté pour faire cela.',
				'shortcode-add-project',
				'platform-shell-plugin'
			);

			$login_link = esc_url( platform_shell_get_return_to_current_page_login_url() );
			/* translators: %1$s: lien vers login */
			$message .= '<p>' . sprintf( _x( 'Tu peux le faire en <a href="%1$s" >cliquant ici.</a> ', 'shortcode-add-project', 'platform-shell-plugin' ), $login_link ) . '</p></div>';
			$template = '';

		} elseif ( 'platform-shell-page-project-edit-page' === $atts['id'] && ( is_null( $project_id ) || is_null( $action ) ) ) { // Vérification seulement. Pas besoin de sanitisation.

			$current_url = site_url( add_query_arg( array(), $wp->request ) ); /* On doit pouvoir revenir à l'url courant. */
			$message     = '<div class="alert alert-danger">';
			$message    .= _x(
				'Lien invalide.',
				'shortcode-add-project',
				'platform-shell-plugin'
			);

			$create_project_link = esc_url( do_shortcode( '[platform_shell_permalink_by_page_id id="platform-shell-page-project-create-page"]' ) );
			/* translators: %1$s: lien */
			$message .= '<p>' . sprintf( _x( 'Tu peux créer un projet en <a href="%1$s" >cliquant ici.</a> ', 'shortcode-add-project', 'platform-shell-plugin' ), $create_project_link ) . '</p></div>';

			$template = '';
		} else {

			do_action( 'platform_shell_project_edit' );

			wp_get_current_user();
			$author      = null;
			$is_updating = false;
			if ( ! is_null( $project_id ) && ! is_null( $action ) ) { // Vérification seulement. Pas besoin de sanitisation.
				$author      = get_user_by( 'ID', $project->post_author );
				$is_updating = true;
			} else {
				$project_id = null;
				$project    = null;
				$author     = $current_user;
			}
			// todo_refactoring_accessibilite : utilisation du h3...
			$avatar   = get_avatar( $author->ID, 30, '', '', array( 'class' => 'img-circle' ) );
			$message  = '<h3 class="main_creator">' . $avatar . ' ' . $author->display_name . '</h3>';
			$template = $this->template_helper->get_template(
				'projects/form-edit-project.php',
				[
					'project_id' => $project_id,
					'action'     => $action,
				]
			);
		}
		return $message . $template;
	}

	/**
	 * Méthode shortcode_reporting_get_flag_button
	 *
	 * @param array $atts Attributs du Shortcode.
	 * @return string     Code généré pour le shortcode.
	 */
	public function shortcode_reporting_get_flag_button( $atts ) {
		$show_reporting_widget  = false;
		$project_post_type_name = $this->project_configs->post_type_name; /* todo_lire de la class. */
		$current_post           = get_post();
		$current_user           = wp_get_current_user();
		$post_type              = ( ! is_null( $current_post ) ) ? $current_post->post_type : null;
		$current_user_id_string = (string) $current_user->ID;
		/* Essayer pour post type projet. */
		$pid = ( $post_type == $project_post_type_name ) ? $current_post->ID : null;

		/* Essayer pour profil. */
		if ( ! isset( $pid ) ) {
			$user_id = get_query_var( 'user_id' );
			$pid     = '' != $user_id ? $user_id : null;
		}

		// Déterminer si on se trouve sur la page de profil (ne pas utiliser l'url puisque le slug peut changer selon la langue).
		// Le calcul pourrait aussi être fait en utilisant la classer profil pour identifier le slug et faire un match sur url courant.
		if ( 'page' == $post_type ) {
			$platform_pages  = get_option( 'platform_shell_option_installed_pages' );
			$profile_page_id = isset( $platform_pages['platform-shell-page-profile']['page-id'] ) ? $platform_pages['platform-shell-page-profile']['page-id'] : null;
			$is_profile_page = $current_post->ID == $profile_page_id;
		} else {
			$is_profile_page = false;
		}

		if ( is_user_logged_in() && isset( $pid ) ) {
			$is_current_user_project_or_profile = ( ( ( $post_type == $project_post_type_name ) && $current_post->post_author == $current_user_id_string ) || $is_profile_page && $pid == $current_user_id_string );
			if ( ! $is_current_user_project_or_profile ) {
				// Ne pas afficher inutilement le reporting si c'est le projet ou le profil de l'usager connecté.
				$show_reporting_widget = true;
			}
		}
		if ( $show_reporting_widget ) {

			$type_label = ( $post_type == $project_post_type_name ) ? _x( 'projet', 'shortcode-reporting', 'platform-shell-plugin' ) : _x( 'profil', 'shortcode-reporting', 'platform-shell-plugin' );

			/* translators: %1$s: post type */
			$title                = sprintf( _x( 'Signaler un %1$s inapproprié', 'template-reporting-fill-form', 'platform-shell-plugin' ), $type_label );
			$params['post_type']  = $post_type;
			$params['title']      = $title;
			$params['pid']        = $pid;
			$params['checkboxes'] = Reporting_Configs::get_reporting_options();
			$template             = $this->template_helper->get_template( 'reporting/form-fill-reporting.php', $params );
			return $template;
		} else {
			return ''; /* Pas de reporting. */
		}
	}
}
