<?php
/**
 * Platform_Shell\installation\Page_Helper
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\installation;

/**
 * Utilitaires pour faciliter la création de page requises.
 *
 * @class    Page_Helper
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Page_Helper {

	/**
	 * Constructeur.
	 */
	public function __construct() {

	}

	/**
	 * Méthode pour créer ou mettre à jour la page (pages requises au bon fonctionnement de la plateforme).
	 *
	 * @param object $page                               Définition de la page.
	 * @param string $id_of_previously_installed_page    Identifiant de la page existante si elle existe.
	 * @param type   $post_parent                        Identifiant de la page parent si la page doit être ajoutée en child.
	 * @return type                                      Id de la page (nouvelle ou mise à jour).
	 * @throws \Exception                                Exception si erreur possible de programmation.
	 */
	public function create_or_update_page( $page, $id_of_previously_installed_page, $post_parent = 0 ) {

		$slug          = $page['slug'];
		$page_title    = isset( $page['title'] ) ? $page['title'] : '';
		$page_content  = isset( $page['content'] ) ? $page['content'] : '';
		$page_template = isset( $page['page_template'] ) ? $page['page_template'] : null;
		$page_id       = null;

		$post_status = $id_of_previously_installed_page ? get_post_status( $id_of_previously_installed_page ) : null;

		if ( is_string( $post_status ) ) {
			$page_data = array(
				'ID'         => intval( $id_of_previously_installed_page ),
				'post_name'  => $slug,
				'post_title' => $page_title,
			);

			$page_id = wp_update_post( wp_slash( $page_data ) );
		} else {

			// Utilise plusieurs valeurs par défault.
			// Voir https://developer.wordpress.org/reference/functions/wp_insert_post/ pour la liste complète.
			$page_slug_arealdy_used = $this->check_page_exist_by_slug( $slug );

			if ( true === $page_slug_arealdy_used ) {

				$old_post = $this->get_post_by_slug( $slug );

				if ( ! is_null( $old_post ) ) {

					$page_content           = $old_post->post_content;
					$page_slug_arealdy_used = false === wp_delete_post( $old_post->ID, true );
				}
			}

			if ( true !== $page_slug_arealdy_used ) {
				$page_data = array(
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_author'    => 0,
					'post_name'      => $slug,
					'post_title'     => $page_title,
					'post_content'   => $page_content,
					'post_parent'    => $post_parent,
					'comment_status' => 'closed',
					'tags_input'     => 'platform-shell-required-page',
				);

				// Configurations optionnelle (template).
				if ( isset( $page_template ) ) {
					/* Voir https://wordpress.stackexchange.com/questions/114813/define-page-template-in-wp-insert-post */
					$page_data['page_template'] = $page_template;
				}
				$page_id = wp_insert_post( wp_slash( $page_data ) );
			} else {
				throw new \Exception( 'Impossible de créer la page, la page existe déjà?' ); // Page provenant d'une création précédante??
			}
		}

		return $page_id;
	}

	/**
	 * Méthode get_post_by_slug
	 *
	 * Cette méthode retrouve une page par son slug.
	 *
	 * @param string $slug    Le slug de la page.
	 * @return NULL|mixed
	 */
	private function get_post_by_slug( $slug ) {

		$return_value = null;

		$posts = get_posts(
			[
				'name'        => $slug,
				'post_type'   => 'page',
				'numberposts' => 1,
			]
		);

		if ( ! empty( $posts ) ) {
			$return_value = array_shift( $posts );
		}

		return $return_value;
	}

	/**
	 * Méthode change_slug
	 *
	 * Cette méthode renomme les doublon potentiels lors de la création des pages requises.
	 *
	 * @param \WP_Post $post    L'instance de la page.
	 * @return boolean
	 */
	private function change_slug( $post ) {

		$updated_id = wp_update_post(
			[
				'ID'        => $post->ID,
				'post_name' => $post->post_name . '_backup',
			]
		);

		$return_value = 0 !== $updated_id;

		return $return_value;
	}

	/**
	 * Méthode pour vérifer si une page existe en utilisant son slug.
	 *
	 * @global object $wpdb    Référence global WordPress.
	 * @param string $slug     Slug de la page.
	 * @return boolean       true la page existe, false sinon.
	 */
	private function check_page_exist_by_slug( $slug ) {
		global $wpdb;

		// Post name correspond au slug du titre.
		$page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
		return ( null !== $page_found );
	}
}
