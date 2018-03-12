<?php
/**
 * Platform_Shell\PlatformShellDateTime
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell;

/**
 * PlatformShellDateTime Abstract class
 *
 * @class    PlatformShellDateTime
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */

/**
 * PlatformShellDateTime Abstract class.
 */
abstract class PlatformShellDateTime {

	/**
	 * Méthode date_filter
	 *
	 * @param string $value    Date.
	 * @return string
	 */
	public static function date_filter( $value ) {

		$date_format = self::get_save_format();

		$date = date_create_from_format( $date_format, $value );

		if ( false === $date ) {
			$value = '';
		}

		return $value;
	}

	/**
	 * Méthode format_localize_date
	 *
	 * @param string  $value          Date/heure.
	 * @param boolean $display_year    Si l'on affiche l'année associée à cette date.
	 * @return string
	 */
	public static function format_localize_date( $value, $display_year = true ) {

		$return_value = '';
		$wp_format    = self::get_wp_date_format();
		$save_format  = self::get_save_format();
		$date         = date_create_from_format( $save_format, $value );

		if ( ! $display_year ) {
			/* Enlève Y du format s'il est présent. */
			$wp_format = preg_replace( '/,?\sY/', '', $wp_format );
		}

		/**
		 * Hack / solution compromis. Il n'y pas de manière simple de sortir un affichage de date
		 * Selon les normes d'affichage en français (ex. 1er janvier). Le format de date de php supporte un argument 'S'
		 * qui donne l'équivalent en anglais mais le mécanisme n'est pas internationalisé (de plus est appliqué sur les jours 2 (nd) et 3 (th).
		 * La solution la plus simple est de forcer l'affichage.
		 * Pour éviter le débordement la transformation ne s'effecture que dans un cas très spécifique (fr_CA et format de date jour mois année ou jour mois).
		 * La mise ne italique du 'er' n'est pas faite parce que cela crée des cas supplémentaires (affichage html ou non).
		 */
		if ( false !== $date ) { // Nécessaire pour un nouveau post.
			if ( 'fr_CA' === get_locale() && '1' === $date->format( 'j' ) && ( ( 'j F Y' === $wp_format ) || ( 'j F' === $wp_format ) ) ) {
				// Modifie le format.
				$french_display_fix_display_date_format = str_replace( 'j', 'jS', $wp_format );
				$return_value                           = str_replace( '1st', '1er', date_i18n( $french_display_fix_display_date_format, $date->getTimestamp() ) );
			} else {
				$return_value = date_i18n( $wp_format, $date->getTimestamp() );
			}
		}

		return $return_value;
	}

	/**
	 * Méthode get_wp_date_format
	 *
	 * @return string
	 */
	public static function get_wp_date_format() {
		return get_option( 'date_format' );
	}

	/**
	 * Méthode get_save_format
	 *
	 * @return string
	 */
	public static function get_save_format() {
		return 'Y-m-d H:i:s';
	}

	/**
	 * Méthode get_midnight_time_format
	 *
	 * @return string
	 */
	public static function get_midnight_time_format() {
		/*
		 * La comparaison de date de concours doit ignorer les heures.
		 * (L'inscription est possible jusqu'à 23:59:59).
		 */

		// Utilisation du ! pour mettre heures à 0. Voir http://www.freeklijten.nl/2015/08/12/DateTime-createFromFormat-without-time.
		return '!Y-m-d H:i:s';
	}

	/**
	 * Méthode get_jquery_ui_date_format
	 *
	 * @return string
	 */
	public static function get_jquery_ui_date_format() {

		return self::dateformat_php_to_jqueryui( self::get_wp_date_format() );
	}

	/**
	 * Méthode get_jquery_ui_save_date_format
	 *
	 * @return string
	 */
	public static function get_jquery_ui_save_date_format() {

		return self::dateformat_php_to_jqueryui( self::get_save_format() );
	}

	/**
	 * Méthode dateformat_php_to_jqueryui
	 *
	 * Matches each symbol of PHP date format standard
	 * with jQuery equivalent codeword
	 *
	 * @param string $php_format    Format.
	 * @author Tristan Jahier
	 * @see https://stackoverflow.com/a/16725290.
	 */
	private static function dateformat_php_to_jqueryui( $php_format ) {

		$symbols_matching = [
			// Day.
			'd' => 'dd',
			'D' => 'D',
			'j' => 'd',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// Week.
			'W' => '',
			// Month.
			'F' => 'MM',
			'm' => 'mm',
			'M' => 'M',
			'n' => 'm',
			't' => '',
			// Year.
			'L' => '',
			'o' => '',
			'Y' => 'yy',
			'y' => 'y',
			// Time.
			'a' => 'am',
			'A' => 'PM',
			'B' => '',
			'g' => '12',
			'G' => '0',
			'h' => '12',
			'H' => '00',
			'i' => '00',
			's' => '00',
			'u' => '000000',
		];

		$jqueryui_format = '';
		$escaping        = false;

		$top = strlen( $php_format );

		for ( $i = 0; $i < $top; $i++ ) {

			$char = $php_format[ $i ];
			if ( '\\' === $char ) { // PHP date format escaping character.

				$i++;
				if ( $escaping ) {
					$jqueryui_format .= $php_format[ $i ];
				} else {
					$jqueryui_format .= '\'' . $php_format[ $i ];
				}
				$escaping = true;
			} else {
				if ( $escaping ) {
					$jqueryui_format .= "'";
					$escaping         = false;
				}

				if ( isset( $symbols_matching[ $char ] ) ) {
					$jqueryui_format .= $symbols_matching[ $char ];
				} else {
					$jqueryui_format .= $char;
				}
			}
		}
		return $jqueryui_format;
	}
}
