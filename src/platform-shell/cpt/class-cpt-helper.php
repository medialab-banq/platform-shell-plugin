<?php
/**
 * Platform_Shell\CPT\CPT_Helper
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\CPT;

use Platform_Shell\Settings\Plugin_Settings;
use Exception;

/**
 * CPT_Helper
 *
 * @class        CPT_Helper
 * @description  Classes utilitaire CPT ( en attendant possible refactoring / regroupement ).
 * @author       Bibliothèque et Archives nationales du Québec ( BAnQ )
 */
class CPT_Helper {

	/**
	 * Instance des settings du plugin
	 *
	 * @var Plugin_Settings
	 */
	private $plugin_settings;

	/**
	 * Instances des configurations des post types
	 *
	 * @var CPT_Configs
	 */
	private $cpt_configs;

	/**
	 * Constructeur
	 *
	 * @param Plugin_Settings $plugin_settings Instance des settings du plugin.
	 * @param CPT_Configs     $cpt_configs     Instances des configurations des post types.
	 */
	public function __construct( Plugin_Settings $plugin_settings, CPT_Configs $cpt_configs ) {

		$this->plugin_settings = $plugin_settings;
		$this->cpt_configs     = $cpt_configs;
	}

	/**
	 * Function init_config
	 *
	 * @param CPT_Configs $cpt_configs Instances des configurations des post types.
	 */
	public function init_config( CPT_Configs $cpt_configs ) {

		$this->cpt_configs = $cpt_configs;
	}

	/**
	 * Fonction get_simple_select_list_from_option
	 *
	 * @param string $option_name  Nom de l'option.
	 * @param string $section_name Nom de la section.
	 * @param string $default      Valeur par défaut.
	 * @throws Exception           Lorsque les données sont dans un format incompatible.
	 * @return array
	 */
	public function get_simple_select_list_from_option( $option_name, $section_name, $default ) {

		$select_list = [];

		$option = $this->plugin_settings->get_option( $option_name, $section_name, $default );

		if ( '' !== $option && null !== $option ) {

			if ( ! is_array( $option ) ) {

				$option_list = explode( "\n", $option );
				$i           = 1;

				foreach ( $option_list as $value ) {

					$key_with_no_return                 = preg_replace( "/\r|\n/", '', $value );
					$select_list[ $key_with_no_return ] = $key_with_no_return;
					$i++;
				}
			} else {
				throw new Exception( 'Format de donnée incompatible. Doit obligatoirement être string avec retour de ligne -> \\r\\n' );
			}

			if ( count( $select_list ) !== 0 ) {

				// Ajouter 'choisir une option', préserver l'ordre ( https://stackoverflow.com/questions/1371016/php-prepend-associative-array-with-literal-keys ).
				// La clé vide détermine la valeur non assignée ( pour validation du required ).
				$select_list = [ '' => _x( 'Choisir une option', 'cpt-contest-field', 'platform-shell-plugin' ) ] + $select_list;
			}
		}

		return $select_list;
	}

	/**
	 * Méthode clean_html
	 *
	 * @param string $content Contenu original à néttoyer.
	 * @return string
	 */
	public function clean_html( $content ) {
		return wp_kses( $this->strip_tags_content( $content ), $this->cpt_configs->allowed_tags_wp_kses );
	}

	/**
	 * Méthode strip_tags_content
	 *
	 * Inspiré par http://php.net/manual/en/function.strip-tags.php#86964
	 *
	 * @param string $content Contenu original à néttoyer.
	 * @return string
	 */
	private function strip_tags_content( $content ) {

		if ( count( $this->cpt_configs->allowed_tags ) > 0 ) {

			$text = preg_replace(
				'@<(?!(?:' . implode( '|', $this->cpt_configs->allowed_tags ) . ')\b)(\w+)\b.*?>.*?</\1>@si',
				'',
				$content
			);

		} else {

			$text = preg_replace(
				'@<(\w+)\b.*?>.*?</\1>@si',
				'',
				$content
			);
		}

		return $content;
	}
}
