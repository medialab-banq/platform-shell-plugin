<?php
/**
 * Platform_Shell\Admin\Admin_Notices
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Admin;

/**
 * Admin_Notices
 *
 * @class    Admin_Notices
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Admin_Notices {
	/*
	* Mécanisme minimal pour la gestion de messages de la plateforme.
	* avec le mécanisme WordPress existant de "admin notice".
	*/

	/**
	 * Durée de vie du message, normale.
	 *
	 * @const    MESSAGE_LIFETIME_NORMAL
	 */
	const MESSAGE_LIFETIME_NORMAL = 0;

	/**
	 * Durée de vie du message, afficher une seule fois.
	 *
	 * @const    MESSAGE_LIFETIME_USE_ONCE
	 */
	const MESSAGE_LIFETIME_USE_ONCE = 1;

	/**
	 * Contexte d'affichage backend.
	 *
	 * @const    DISPLAY_CONTEXT_BACKEND
	 */
	const DISPLAY_CONTEXT_BACKEND = 'backend';

	/**
	 * Contexte d'affichage front-end.
	 *
	 * @const    DISPLAY_CONTEXT_FRONTEND
	 */
	const DISPLAY_CONTEXT_FRONTEND = 'frontend';

	/**
	 * Identifiant de classe css pour affichage backend.
	 *
	 * @const     DISPLAY_CLASS_BACKEND
	 */
	const DISPLAY_CLASS_BACKEND = 'notice';

	/**
	 * Identifiant de classe css pour affichage frontend.
	 *
	 * @const    DISPLAY_CLASS_FRONTEND
	 */
	const DISPLAY_CLASS_FRONTEND = 'alert';

	/**
	 * Identifiant suffixe css d'erreur pour affichage frontend.
	 *
	 * @const    TYPE_CLASS_ERROR_SUFFIXE_FRONTEND
	 */
	const TYPE_CLASS_ERROR_SUFFIXE_FRONTEND = 'danger';

	/**
	 * Identifiant de metadonnée.
	 *
	 * @var string
	 */
	private static $admin_notices_meta_key = 'platform_shell_Admin_Notices';

	/**
	 * Identifiant contexte POST / PROFILE. POST par défaut.
	 *
	 * @var string
	 */
	private $context = 'POST'; // POST / PROFILE.

	/**
	 * Id de post or user. -1 pour admin.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Constructeur.
	 *
	 * @param string $context Contexte DISPLAY_CONTEXT_FRONTEND | DISPLAY_CONTEXT_BACKEND.
	 * @param string $id      post_id ou user_id.
	 */
	public function __construct( $context, $id ) {
		$this->context = $context;
		$this->id      = $id;
	}

	/**
	 * Méthode pour ajouter un message de notification.
	 *
	 * @param string $message     Message texte.
	 * @param string $type        error | warning | success | info.
	 * @param int    $lifetime    MESSAGE_LIFETIME_NORMAL | MESSAGE_LIFETIME_USE_ONCE.
	 * @return boolean
	 */
	public function add_message( $message = '', $type = 'info', $lifetime = self::MESSAGE_LIFETIME_NORMAL ) {
		if ( ! empty( $message ) ) {
			$new_notice = array(
				'message'  => $message,
				'type'     => $type,
				'lifetime' => $lifetime,
			);

			// Pas de validation sur existence du post. Prend pour acquis que cette vérification est faite en amont.
			$admin_notices = $this->get_notices();
			if ( empty( $admin_notices ) ) {
				$admin_notices = array();
			}
			array_push( $admin_notices, $new_notice );
			return $this->set_notices( $admin_notices );
		}
		return false;
	}

	/**
	 * Méthode clear_notices.
	 */
	public function clear_notices() {
		if ( 'POST' == $this->context ) {
			delete_post_meta( $this->id, self::$admin_notices_meta_key );
		} else {
			delete_user_meta( $this->id, self::$admin_notices_meta_key );
		}
	}

	/**
	 * Méthode clear_use_once_noticesé
	 */
	public function clear_use_once_notices() {

		$admin_notices = $this->get_notices();

		// Filter les messages / notification à utilisation unique (use_once).
		if ( ! empty( $admin_notices ) ) {
			$filtered_notices = array_filter(
				$admin_notices, function ( $e ) {
					return self::MESSAGE_LIFETIME_USE_ONCE != $e['lifetime'];
				}
			);

			// Mettre à jour la liste notification.
			if ( count( $admin_notices ) != count( $filtered_notices ) ) {
				$this->set_notices( $filtered_notices );
			}
		};
	}

	/**
	 * Méthode get_notices.
	 *
	 * @return array    Liste des messages de notification.
	 */
	private function get_notices() {
		// Récupérer les notices enregistrées.
		if ( 'POST' == $this->context ) {
			return get_post_meta( $this->id, self::$admin_notices_meta_key, true );
		} else {
			return get_user_meta( $this->id, self::$admin_notices_meta_key, true );
		}
	}

	/**
	 * Méthode set_notices.
	 *
	 * @param array $notices    Liste des messages de notification.
	 * @return mixed
	 * @see https://codex.wordpress.org/Function_Reference/update_post_meta
	 */
	private function set_notices( $notices ) {
		// Enregistrer les notices.
		if ( 'POST' == $this->context ) {
			return update_post_meta( $this->id, self::$admin_notices_meta_key, $notices );
		} else {
			return update_user_meta( $this->id, self::$admin_notices_meta_key, $notices );
		}
	}

	/**
	 * Méthode show_frontend_notices.
	 */
	public function show_frontend_notices() {
		$this->show_notices( self::DISPLAY_CONTEXT_FRONTEND );
	}

	/**
	 * Méthode show_admin_notices.
	 */
	public function show_admin_notices() {
		$this->show_notices( self::DISPLAY_CONTEXT_BACKEND );
	}

	/**
	 * Méthode show_notices.
	 *
	 * @param string $display_context    DISPLAY_CONTEXT_BACKEND | DISPLAY_CONTEXT_FRONTEND.
	 */
	private function show_notices( $display_context ) {
		$notices = $this->get_notices();

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {

				$message = $notice['message'];

				if ( self::DISPLAY_CONTEXT_BACKEND == $display_context ) {
					$class_identifier = self::DISPLAY_CLASS_BACKEND;
				} elseif ( self::DISPLAY_CONTEXT_FRONTEND == $display_context ) {
					$class_identifier = self::DISPLAY_CLASS_FRONTEND;
				}

				$type = $notice['type'];

				if ( ( self::DISPLAY_CONTEXT_FRONTEND == $display_context ) && ( 'error' == $type ) ) {
					$type = self::TYPE_CLASS_ERROR_SUFFIXE_FRONTEND; /* Ajustement pour l'erreur bootstrap, danger au lieu de error. */
				}

				$notice_type_class = $class_identifier . '-' . $type;
				$class             = $class_identifier . ' ' . $notice_type_class;
				printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
			}
		}

		// Lorsqu'on affiche les notices, il faut mettre à jour la liste.
		// pour nettoyer les messages à utilisation unique.
		$this->clear_use_once_notices();
	}
}
