<?php
/**
 * Platform-Shell
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 *
 * Plugin Name: Plateforme médialab BAnQ
 * Plugin URI:  https://github.com/medialab-banq/platform-shell-plugin/
 * Description: Extension WordPress permettant de créer une plateforme de médialab collaborative.
 * Author: Bibliothèque et Archives nationales du Québec (BAnQ)
 * Author URI: http://www.banq.qc.ca
 * License: GNU General Public License v2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: platform-shell-plugin
 * Domain Path: /languages
 * Version: 1.0.0
 *
 * @wordpress-plugin
 * Platform-shell-plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Platform-shell-plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Platform-shell-plugin. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

use DI\ContainerBuilder;

// BootStraping.
$plugin_version        = '1.0.0';
$base_plugin_path      = realpath( plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR;
$plugin_url            = plugin_dir_url( __FILE__ );
$plugin_path           = untrailingslashit( plugin_dir_path( __FILE__ ) );
$plugin_class_src_path = $plugin_path . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
$plugin_slug           = plugin_basename( __FILE__ );

/**
 * Fonction commun d'affichage de messages d'erreur de validation des pré-requis de fonctionnement du plugin.
 *
 * @param string      $message    Le message d'erreur.
 * @param string|null $display_context    Le contexte d'affichage (frontend/backend/null).
 */
function platform_shell_plugin_precondition_failed_message( $message, $display_context = 'backend' ) {

		$encapsulated_message  = '';
		$encapsulated_message .= '<div class="alert alert-danger notice notice-error">';
		$encapsulated_message .= $message;
		$encapsulated_message .= '</div>';

	if ( 'frontend' == $display_context ) {
		$admin_url             = admin_url();
		$encapsulated_message .= '<p><a href="' . $admin_url . '">' . 'Aller à l’écran d’administration / Go to admin screen' . '</a></p>';
	}

		$allowed_tags = [
			'a'   => [
				'href' => [],
			],
			'p'   => [
				'class' => [],
			],
			'div' => [
				'class' => [],
			],
		];

		echo wp_kses( $encapsulated_message, $allowed_tags );
}

if ( is_multisite() ) {
	/**
	 * Fonction platform_shell_missing_composer_dependency_admin_notice__error.
	 * Cette fonction affiche les messages d'erreurs si des dépendances sont manquantes.
	 */
	function platform_shell_multisite_not_supported_admin_notice__error() {
		platform_shell_get_multisite_not_supported_message( 'backend' );
	}

	/**
	 * Fonction platform_shell_get_missing_composer_message.
	 *
	 * Cette fonction affiche des messages d'erreurs si les dépendances Composer ne sont pas installées.
	 *
	 * @param string $display_context    frontend / backend.
	 */
	function platform_shell_get_multisite_not_supported_message( $display_context ) {

		$message  = '<p>L’extension ne supporte pas le mode multisite de WordPress.</p>';
		$message .= '<p>The extension does not support WordPress multisite mode.</p>';

		platform_shell_plugin_precondition_failed_message( $message, $display_context );
	}

	/* PAS DE LOCALISATION. LES FICHIERS NE SONT PAS ENCORE CHARGÉS. */
	if ( ! is_admin() ) {
		// Affichage front-end.
		platform_shell_get_multisite_not_supported_message( 'frontend' );

		die();
	} else {
		// Affichage back-end.
		add_action( 'admin_notices', 'platform_shell_multisite_not_supported_admin_notice__error' );
	}

	return;
}

// Require principaux.
if ( ! file_exists( $base_plugin_path . 'vendor' ) ) {

	/**
	 * Fonction platform_shell_missing_composer_dependency_admin_notice__error.
	 * Cette fonction affiche les messages d'erreurs si des dépendances sont manquantes.
	 */
	function platform_shell_missing_composer_dependency_admin_notice__error() {
		platform_shell_get_missing_composer_message( 'backend' );
	}

	/**
	 * Fonction platform_shell_get_missing_composer_message.
	 * Cette fonction affiche des messages d'erreurs si les dépendances Composer ne sont pas installées.
	 *
	 * @param string $display_context    frontend / backend.
	 */
	function platform_shell_get_missing_composer_message( $display_context ) {

		$message  = '<p>Installation incomplète à partir des sources. L’installation des dépendances Composer (https://getcomposer.org/) est requise.</p>';
		$message .= '<p>Incomplete installation from sources. Installation of composer dependency is required (https://getcomposer.org/).</p>';

		platform_shell_plugin_precondition_failed_message( $message, $display_context );
	}

	/* PAS DE LOCALISATION. LES FICHIERS NE SONT PAS ENCORE CHARGÉS. */
	if ( ! is_admin() ) {
		// Affichage front-end.
		platform_shell_get_missing_composer_message( 'frontend' );
		die();
	} else {
		// Affichage back-end.
		add_action( 'admin_notices', 'platform_shell_missing_composer_dependency_admin_notice__error' );
	}
	return;
}

// Require Theme.
$theme = wp_get_theme();

if ( 'Plateforme médialab BAnQ' !== $theme->name && 'Plateforme médialab BAnQ' !== $theme->parent_theme ) {

	/**
	 * Fonction platform_shell_missing_theme_dependency_admin_notice_error
	 *
	 * Cette fonction affiche les messages d'erreurs si des dépendances sont manquantes
	 */
	function platform_shell_missing_theme_dependency_admin_notice_error() {
		platform_shell_get_missing_theme_message( 'backend' );
	}

	/**
	 * Fonction platform_shell_get_missing_theme_message
	 * Cette fonction affiche des messages d'erreurs si les dépendances theme ne sont pas installées
	 *
	 * @param string $display_context    frontend / backend.
	 */
	function platform_shell_get_missing_theme_message( $display_context ) {

		$message  = '<p>Le thème « Plateforme médialab BAnQ » est requis. Veuiller installer et activer ce thème.</p>';
		$message .= '<p>The "Plateform médialab BAnQ" theme is required. Please install and activate this theme.</p>';

		platform_shell_plugin_precondition_failed_message( $message, $display_context );
	}

	/* PAS DE LOCALISATION. LES FICHIERS NE SONT PAS ENCORE CHARGÉS. */
	if ( ! is_admin() ) {
		// Affichage front-end.
		platform_shell_get_missing_theme_message( 'frontend' );
		die();
	} else {
		// Affichage back-end.
		add_action( 'admin_notices', 'platform_shell_missing_theme_dependency_admin_notice_error' );
	}

	return;
}

// Vérification de mise à jour.
require 'src/lib/plugin-update-checker-4.4/plugin-update-checker.php';
$update_checker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/medialab-banq/platform-shell-plugin/',
	__FILE__,
	'platform-shell-plugin'
);

require $base_plugin_path . 'vendor/autoload.php';
require $plugin_path . '/src/lib/autoloader/class-autoloader.php'; /* doc. Utilisation autoloader custom plutôt que celui de composer. */
require $base_plugin_path . 'src/platform-shell/core-functions.php';

// Enregistrer un "autoloader" pour le chargement des classes du plugin.
$classes_fully_qualified_path = $plugin_class_src_path;
$plugin_root_namespace        = 'Platform_Shell';

$autoloader = new \Com\Github\Tommcfarlin\Autoloader\Autoloader( $plugin_root_namespace, $classes_fully_qualified_path );

// Chargement des settings par défaut.
$default_settings_file = $base_plugin_path . 'src/platform-shell/settings/default-plugin-settings-config.php';
if ( file_exists( $default_settings_file ) ) {
	// todo_novembre_settings : Ajout chargement fichier de configs (override partiel ou complet?).
	// Format array plain ou class?
	$plugin_default_settings_config = include $default_settings_file; /* autoloader not registered yet. todo_eg: possible d'améliorer?) */
	if ( ! is_array( $plugin_default_settings_config ) ) {
		throw new \Exception( 'Problème de chargement du fichier de configurations.' );
	}
} else {
	throw new \Exception( 'Fichier de configurations par défaut manquant.' );
}

// Require pour admin seulement.
if ( is_admin() ) {
	require $plugin_path . '/src/lib/wordpress-settings-api-class-master/src/class-settings-api.php';
}

if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN === true ) {
	$plugin_url = str_replace( 'http://', 'https://', $plugin_url );
}

$builder = new ContainerBuilder();
$builder->addDefinitions(
	[
		'plugin.version'                               => $plugin_version,
		'plugin.url'                                   => $plugin_url,
		'plugin.path'                                  => $plugin_path,
		'plugin.slug'                                  => $plugin_slug,
		'plugin.default_settings_config'               => $plugin_default_settings_config,
		'lazy_install_manager'                         => DI\object( 'Platform_Shell\installation\Plugin_Install_Manager' )->lazy(),
		'Platform_Shell\Main'                          => DI\object()->constructorParameter( 'plugin_url', \DI\get( 'plugin.url' ) )
			->constructorParameter( 'plugin_path', \DI\get( 'plugin.path' ) )
			->constructorParameter( 'plugin_slug', \DI\get( 'plugin.slug' ) )
			->constructorParameter( 'lazy_plugin_install_manager', \DI\get( 'lazy_install_manager' ) ),
		'Platform_Shell\Admin\Admin'                   => DI\object()->constructorParameter( 'plugin_url', \DI\get( 'plugin.url' ) )
			->constructorParameter( 'plugin_path', \DI\get( 'plugin.path' ) ),
		'Platform_Shell\Templates\Template_Helper'     => DI\object()->constructorParameter( 'plugin_path', \DI\get( 'plugin.path' ) ),
		'Platform_Shell\Settings\Plugin_Settings'      => DI\object()->constructorParameter( 'plugin_default_settings_config', \DI\get( 'plugin.default_settings_config' ) ),
		'Platform_Shell\Settings\Map_Background_Image' => DI\object()->constructorParameter( 'plugin_path', \DI\get( 'plugin.path' ) ),
		'Platform_Shell\installation\Plugin_Install_Manager' => DI\object()->constructorParameter( 'plugin_version', \DI\get( 'plugin.version' ) ),
	]
);

$di_container = $builder->build();

/* dependency injection composition root */
$plugin_main = $di_container->get( 'Platform_Shell\Main' );

// hook sur activation/desactivation du module.
register_activation_hook( __FILE__, array( $plugin_main, 'plugin_install' ) );
register_deactivation_hook( __FILE__, array( $plugin_main, 'plugin_uninstall' ) );
