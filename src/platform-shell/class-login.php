<?php
/**
 * Platform_Shell\Login
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell;

/**
 * Class gestion particulière du login.
 *
 * @class    Login
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Login {
	/*
	* Note importante : La fonctionnalité est partiellement dupliqué de la protection d'accès au tableau de bord.
	* Normalement, WordPress redirige au profil usager qui se trouve du côté dashboard et la protection d'accès est appliquée.
	* Si le comportement de protection d'accès redirige vers l'accueil, le comportement est le même que celui du redirect de login
	* et la fonctionnalité est alors dupliquée. Pour permettre de modifier le comportement de protection d'accès sans affecter
	* le comportement désiré pour le login, les fonctionnalités seront traitées séparément.
	*/

	/**
	 * Constructeur.
	 */
	public function __construct() {
	}

	/**
	 * Méthode init.
	 */
	public function init() {
		add_filter( 'login_redirect', array( $this, 'on_filter_login_redirect' ), 10, 3 );
	}

	/**
	 * Méthode filtre WordPress permettant de modifier le comportement du login.
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/login_redirect
	 * @param string $redirect_to URL to redirect to.
	 * @param string $requested_redirect_to URL the user is coming from.
	 * @param object $user Logged user's data.
	 * @return string
	 */
	public function on_filter_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		// Est-ce qu'il y a un usager défini?
		if ( isset( $user->roles ) && is_array( $user->roles ) ) {
			// Identifier les usagers.
			if ( in_array( 'platform_shell_role_user', (array) $user->roles ) ) {
				// Retour à la page d'accueil au lieu du profil.
				if ( isset( $requested_redirect_to ) && strpos( $redirect_to, admin_url() ) == false ) {
					return $requested_redirect_to;
				} else {
					return site_url();
				}
			} else {
				// Aller à la destination prévue.
				return $redirect_to;
			}
		} else {
			return $redirect_to;
		}
	}
}
