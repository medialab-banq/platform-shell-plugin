<?php
/**
 * Platform_Shell\CPT\CPT_Metaboxes
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT;

use Platform_Shell\Fields\Fields_Helper;

/**
 * CPT_Metaboxes
 *
 * @class        CPT_Metaboxes
 * @description  Classe de base pour les configurations des metaboxes de post types personalisées.
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
abstract class CPT_Metaboxes {

	/**
	 * Configurations du custom post type.
	 *
	 * @var CPT_Configs
	 */
	protected $configs;

	/**
	 * Classe helper pour générer les champs divers
	 *
	 * @var Fields_Helper Fields_Helper
	 */
	protected $field_helper;

	/**
	 * Constructeur.
	 *
	 * @param CPT_Configs   $configs         Configurations du custom post type.
	 * @param Fields_Helper $field_helper    Classe helper pour générer les champs.
	 */
	public function __construct( CPT_Configs $configs, Fields_Helper $field_helper = null ) {
		$this->configs      = $configs;
		$this->field_helper = $field_helper;
	}
}
