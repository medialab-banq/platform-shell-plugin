<?php
/**
 * Platform_Shell\CPT\Banner\Banner_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2017 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT\Banner;

use Platform_Shell\CPT\CPT_Configs;

/**
 * Equipment_Banner class.
 *
 * @class    Banner_Type
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Banner_Configs extends CPT_Configs {

	/**
	 * Constructeur.
	 */
	public function __construct() {

		parent::__construct();

		$this->metadata_prefix       = $this->base_metadata_prefix . 'banner_';
		$this->post_type_name        = 'banner';
		$this->post_type_name_plural = 'banners';

		$this->labels = [
			'name'               => _x( 'Bannières', 'cpt-banner-labels-plural', 'platform-shell-plugin' ),
			'singular_name'      => _x( 'Bannière', 'cpt-banner-labels-singular', 'platform-shell-plugin' ),
			'menu_name'          => _x( 'Bannières', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'name_admin_bar'     => _x( 'Bannière', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'add_new'            => _x( 'Ajouter', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'add_new_item'       => _x( 'Ajouter une bannière', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'new_item'           => _x( 'Nouvelle bannière', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'edit_item'          => _x( 'Éditer un bannière', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'view_item'          => _x( 'Voir les bannières', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'all_items'          => _x( 'Tous les bannières', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'search_items'       => _x( 'Rechercher une bannière', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'parent_item'        => _x( 'Bannière parent', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'parent_item_colon'  => _x( 'Bannière parent:', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'not_found'          => _x( 'Aucune bannière.', 'cpt-banner-labels', 'platform-shell-plugin' ),
			'not_found_in_trash' => _x( 'Aucune bannière dans la corbeille.', 'cpt-banner-labels', 'platform-shell-plugin' ),
		];
	}
}
