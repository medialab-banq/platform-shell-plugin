<?php
/**
 * Platform_Shell\CPT\CPT_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT;

/**
 * CPT_Configs
 *
 * @class    CPT_Configs
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class CPT_Configs {

	/**
	 * Préfixe de base de la chaine de charactère pour les métadonnées
	 *
	 * @var string
	 */
	public $base_metadata_prefix;

	/**
	 * Préfixe de la chaine de charactère pour les métadonnées
	 *
	 * @var string
	 */
	public $metadata_prefix;

	/**
	 * Nom du post type
	 *
	 * @var string
	 */
	public $post_type_name;

	/**
	 * Nom du post type au pluriel
	 *
	 * @var string
	 */
	public $post_type_name_plural;

	/**
	 * Liste des status disponible à l'affichage
	 *
	 * @var array
	 */
	public $status;

	/**
	 * Liste des actions disponibles pour les status
	 *
	 * @var array
	 */
	public $status_action;

	/**
	 * Liste des tags disponible pour l'utilisation avec wp_kses
	 *
	 * @var array
	 */
	public $allowed_tags_wp_kses; // Cette variable est utilisée dans les custom post types pour nettoyer les valeurs avec wp_kses.

	/**
	 * Liste des tags qui sont disponibles pour l'utilisation
	 *
	 * @var array
	 */
	public $allowed_tags;         // Cette variable est utilisée dans les custom post types pour nettoyer les valeurs avec wp_kses.

	/**
	 * Constructeur.
	 */
	public function __construct() {

		// Configurations dynamiques par injection?
		$this->base_metadata_prefix = 'platform_shell_meta_';

		$this->status = [
			'publish' => _x( 'Publié', 'cpt-project-field', 'platform-shell-plugin' ),
			'draft'   => _x( 'En attente', 'cpt-project-field', 'platform-shell-plugin' ),
			'private' => _x( 'Projet en modération (visibilité privée)', 'cpt-project-field', 'platform-shell-plugin' ),
		];

		$this->status_action = [
			''        => _x( 'Choisir une option', 'cpt-project-field', 'platform-shell-plugin' ),
			'publish' => _x( 'Publier', 'cpt-project-field', 'platform-shell-plugin' ),
			'draft'   => _x( 'En attente', 'cpt-project-field', 'platform-shell-plugin' ),
			'private' => _x( 'Mettre le projet en modération (visibilité privée)', 'cpt-project-field', 'platform-shell-plugin' ),
		];

		$this->allowed_tags_wp_kses = wp_kses_allowed_html( 'post' );

		$not_allowed_tags = [
			'form',
			'input',
			'button',
			'fieldset',
		];

		foreach ( $not_allowed_tags as $not_allowed_tag ) {

			if ( isset( $this->allowed_tags_wp_kses[ $not_allowed_tag ] ) ) {

				unset( $this->allowed_tags_wp_kses[ $not_allowed_tag ] );
			}
		}

		$this->allowed_tags = array_keys( $this->allowed_tags_wp_kses );
	}
}
