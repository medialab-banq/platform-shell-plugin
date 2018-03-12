<?php
/**
 * Platform_Shell\Reporting\Reporting_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Reporting;

/**
 * Reporting_Configs.
 *
 * @class    Reporting_Configs
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Reporting_Configs {

	/**
	 * Constructeur.
	 */
	public function __construct() {
	}

	/**
	 *  Méthode pour récupérer la liste des options de type de signalement.
	 *
	 * @return array
	 */
	public static function get_reporting_options() {
		$options = array();

		$options['A'] = _x( 'Contenu violent', 'reporting-form-option', 'platform-shell-plugin' );
		$options['B'] = _x( 'Contenu sexuellement explicite ou pornographique', 'reporting-form-option', 'platform-shell-plugin' );
		$options['C'] = _x( 'Contenu à caractère haineux ou dénigrant', 'reporting-form-option', 'platform-shell-plugin' );
		$options['D'] = _x( 'Contenu comportant du matériel protégé par des droits d’auteur', 'reporting-form-option', 'platform-shell-plugin' );
		$options['E'] = _x( 'Actes dangereux ou pernicieux', 'reporting-form-option', 'platform-shell-plugin' );
		$options['F'] = _x( 'Actes d’intimidation ou de violence', 'reporting-form-option', 'platform-shell-plugin' );
		$options['G'] = _x( 'Autre', 'reporting-form-option', 'platform-shell-plugin' );

		return $options;
	}

	/**
	 * Méthode pour retourner la valeur texte de l'option de signalement choisie.
	 *
	 * @param string $option_value    Code de signalement.
	 * @return string
	 */
	public function get_reporting_option_label( $option_value ) {
		$label = '';

		$options = $this->get_reporting_options();

		if ( array_key_exists( $option_value, $options ) ) {
			$label = $options[ $option_value ];
		} else {
			$label = _x( 'Motif inconnu.', 'reporting', 'platform-shell-plugin' );
		}
		return $label;
	}
}
