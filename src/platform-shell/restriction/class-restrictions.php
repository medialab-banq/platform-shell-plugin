<?php
/**
 * Platform_Shell\Restriction\Restriction_Required_Page_Delete
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Restriction;

use \Platform_Shell\Restriction\Restriction_Required_Page_Delete;
use \Platform_Shell\Settings\Plugin_Settings;

/**
 * Classe de gérer des restrictions (restrictions d'accès / fonctionnalités de WordPress pour les besoins de la plateforme par exemple).
 *
 * @class    Shortcode_Platform_Shell_Project_Info_Icons
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Restrictions {

	/**
	 * Information sur le user lors d'un processu de changement de courriel (hack).
	 *
	 * @var array
	 */
	private $send_email_change_mail_user_info;

	/**
	 * Instance de Restriction_Required_Page_Delete (DI).
	 *
	 * @var Restriction_Required_Page_Delete
	 */
	private $restriction_required_page_delete;

	/**
	 * Instance de Plugin_Settings (DI).
	 *
	 * @var Plugin_Settings
	 */
	private $plugin_settings;

	/**
	 * Constructeur.
	 *
	 * @param Restriction_Required_Page_Delete $restriction_required_page_delete    Instance de Restriction_Required_Page_Delete (DI).
	 * @param Plugin_Settings                  $plugin_settings                                      Instance de Plugin_Settings (DI).
	 */
	public function __construct( Restriction_Required_Page_Delete $restriction_required_page_delete, Plugin_Settings $plugin_settings ) {
		$this->restriction_required_page_delete = $restriction_required_page_delete;
		$this->plugin_settings                  = $plugin_settings;
	}

	/**
	 * Méthode init.
	 */
	public function init() {
		$this->restriction_required_page_delete->init();
		add_action( 'init', array( $this, 'on_action_init' ) );
		add_filter( 'post_row_actions', array( $this, 'remove_constest_quick_edit' ), 10, 1 );
		add_filter( 'wp_mail', array( $this, 'on_wp_mail_filter' ), 10, 1 );
		// Hack.
		add_filter( 'send_email_change_email', array( $this, 'on_send_email_change_email' ), 10, 3 );
	}

	/**
	 * Méthode admin init.
	 */
	public function on_action_init() {
		$this->add_dashboard_access_restriction();
	}

	/**
	 * Méthode filtre WordPress pour blocker l'envois de courriel au usagers ayant un compte Shibboleth.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_mail/
	 * @param array $args Voir documentation WordPress.
	 * @return array      Voir documentation WordPress.
	 */
	public function on_wp_mail_filter( $args ) {
		return $this->block_email_filter( $args );
	}

	/**
	 * Méthode on_send_email_change_email.
	 * See https://codex.wordpress.org/Plugin_API/Filter_Reference/send_email_change_email
	 *
	 * @param bool  $send     Voir documentation WordPress.
	 * @param array $user     Voir documentation WordPress.
	 * @param array $userdata Voir documentation WordPress.
	 * @return boolean
	 */
	public function on_send_email_change_email( $send, $user, $userdata ) {
		/*
		 * On pourrait bloquer simplement avec return false mais à ce moment l'admin ne sera pas au courant.
		 * du blocage de courriel. Peut-être acceptable?
		 */

		// Hack très dépendant de la séquence de filtre / code de user.php.
		$this->send_email_change_mail_user_info              = array();
		$this->send_email_change_mail_user_info['ID']        = $user['ID'];
		$this->send_email_change_mail_user_info['old_email'] = $user['user_email'];
		$this->send_email_change_mail_user_info['new_email'] = $userdata['user_email'];

		/*
		 * Ma compréhension a été faussé par les configurations du serveur, les courriels ne passent pas tout le temps.
		 * Lors de la création du compte Shibboleth, le email n'est pas donné et l'assignation provoque OBLIGATOIREMENT.
		 * l'envois du courriel de changement. On veut ignorer en tout temps le premier envois mais garder un oeil sur
		 * les changements subséquents (louches).
		 */
		if ( get_user_meta( $user['ID'], 'shibboleth_account' ) && empty( $this->send_email_change_mail_user_info['old_email'] ) ) {
			return false;
		} else {
			return true; /* On ne veut pas simplement bloquer, on veut rediriger du côté admin (pour qu'on puisse valider si ce courriel essaie de sortir. (pas de logging en place) */
		}
	}

	/**
	 * Méthode block_email_filter.
	 *
	 * @param array $args    voir on_wp_mail_filter.
	 * @return array         voir on_wp_mail_filter.
	 */
	private function block_email_filter( $args ) {
		/*
		 * Solution compromis :
		 * Il n'est pas possible de faire un override de wp_mail (certains extensions php existent mais requièrent
		 * une installation supplémentaire, semblent obsolètes et pourraient aussi entrer en conflit avec opcache (manipulation de
		 * la table de symbole).
		 * Il est possible de définir une fonction sur mesure mais il n'est pas possible alors d'appeler la fonction originale.
		 * Pour conserver la notification pour les gestionnaires et bloquer la notification aux utilisateurs
		 * les envois de courriel aux utilisateurs vont être redirigés vers email admin (cela nous permettra de
		 * voir quel cas pourraient générer des couriels intempestifs.
		 * La solution devra évidement être revue dans le contexte de la coquille.
		 * Note : Lors d'un changement de courriel, WordPress envois la notification au courriel précédant.
		 * Ça semble être un choix choix étrange, le courriel devrait probablement être envoyé à la nouvelle adresse).
		 * Cela empêche l'idée ici de fonctionner puisque le email ne correspond pas à ce qui est connu dans la BD.
		 *
		 * Solution modifiée de https://codex.wordpress.org/Plugin_API/Filter_Reference/wp_mail
		 * Cas à couvrir:
		 *  - Email à une adresse admin qui ne correspond à compte.
		 *  - Email à une adresse admin qui correspond à un compte (voir problématique changement courriel).
		 *  - Email à une adresse qui correspond à un compte créé par le plugin Shibboleth ou rôle utilisateur en général (bloquer).
		 *  - Gérer envois courriel pour changement de mot de passe, le $to correspond à l'adresse précédante, donc le
		 *  - match sur compte pour identifier user et son rôle ne peut pas être fait directement.
		 *  - 2016-12-13 - check if $to est un array ( cas particulier de iTheme Security )
		 */

		$to          = $args['to'];
		$to          = is_array( $to ) ? $to[0] : $to;
		$subject     = $args['subject'];
		$message     = $args['message'];
		$admin_email = get_option( 'admin_email' );
		$to_admin    = ( $to == $admin_email );
		$to_manager  = ( $to == $this->plugin_settings->get_option( 'platform_shell_option_contact_manager_email_adress', 'platform-shell-settings-main-contacts-and-notifications', '' ) );

		// todo : is admin and have account (email pourrait être celui de  l'admin mais sans account).
		$user = get_user_by( 'email', $to );
		if ( false == $user && isset( $this->send_email_change_mail_user_info ) ) {
			$user_id = $this->send_email_change_mail_user_info['ID'];
		} else {
			if ( false != $user ) {
				$user_id = $user->ID;
			}
		}

		/*
		 * IMPORTANT, BUG WordPress : On ne peut pas déterminer le type de compte sur le changement de courriel
		 * puisque WordPress envois le courriel à l'ancien couriel qui n'existe plus alors dans la table user.
		 *
		 * On peut le prendre comme indice que le message peut ne pas être envoyé mais ça pourrait être un problème
		 * dans le contexte d'une coquille avec usagers WordPress normaux.
		 *
		 * Probablement qu'un flag global 'USER_SHIBBOLETH' pourrait permettre de limiter le scope de la restriction.
		 */
		if ( $to_admin && isset( $user_id ) ) {
			// Laisser passer mais ajouter un avertissement.
			$message = _x( '( Attention : votre courriel est défini comme étant celui de l’administrateur du site.)', 'admin-email', 'platform-shell-plugin' ) . $message;
		} else {
			// 23-11-16 : ajout de !$to_admin dans la condition pour permettre de laisser passer les courriels envoyé à l'admin
			$block_message = false;

			// todo : ajouter commentaires explicatif.
			if ( ! ($to_admin || $to_manager) ) {
				// todo_refactoring_specifications_inconnues : pourrait ajouter && role mediala user (si plus tard il y avait ajout de compte shibboleth avec rôle gestionnaire?).
				if ( isset( $user_id ) && get_user_meta( $user_id, 'shibboleth_account' ) ) {
					/* Indice sur environnement? */
					$block_message = true;
				}
			}

			if ( $block_message ) {
				$originalto = $to;
				$to         = $admin_email;
				$subject    = $this->get_default_blocked_suject( $args['subject'] );
				$message    = $this->get_default_blocked_message( $originalto, $message );
			}
		}
		$new_wp_mail = array(
			'to'          => $to,
			'subject'     => $subject,
			'message'     => $message,
			'headers'     => $args['headers'],
			'attachments' => $args['attachments'],
		);
		return $new_wp_mail;
	}

	/**
	 * Méthode get_default_blocked_suject.
	 *
	 * @param string $subject    Courriel.
	 * @return string            Message par défaut avec le courriel du sujet.
	 */
	private function get_default_blocked_suject( $subject ) {
		// translators: %1$s courriel.
		return sprintf( _x( '(Attention : Un envoi de courriel à un utilisateur inconnu a été bloqué : %1$s.)', 'admin-email', 'platform-shell-plugin' ), $subject );
	}

	/**
	 * Méthode get_default_blocked_message.
	 *
	 * @param string $recipient    Courriel.
	 * @param string $message      Message original.
	 * @return string              Texte de restriction construit avec le message original.
	 */
	private function get_default_blocked_message( $recipient, $message ) {
		// translators: %1$s Courriel identifiant de l'utilisateur.
		return sprintf( _x( '(Attention : Le message suivant envoyé par WordPress au courriel %1$s a été bloqué.)', 'admin-email', 'platform-shell-plugin' ), $recipient ) . $message;
	}

	/**
	 * Méthode add_dashboard_access_restriction.
	 */
	private function add_dashboard_access_restriction() {
		// Solution inspirée de: https://premium.wpmudev.org/blog/limit-access-to-your-wordpress-dashboard/.
		$current_user = wp_get_current_user();
		if ( 0 != $current_user->ID ) {
			// Seuls les admins et gestionnaires  peuvent accéder au tableau de bord.
			// - Mais il faut laisser passer les requêtes ajax (ce point sera à revalider).
			if ( is_admin() && ( in_array( 'platform_shell_role_user', (array) $current_user->roles ) ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				wp_redirect( site_url() );
				exit;
			}
		}
	}

	/**
	 * Méthode remove_constest_quick_edit
	 *
	 * @param type $actions    ???.
	 * @return type
	 */
	public function remove_constest_quick_edit( $actions ) {

		// Ne pas permettre d'utiliser le quick edit (pour tout le monde incluant admin) dans les listes de 'post'.
		// Pour ne pas contourner le mécanisme de validation de concours qui n'a pas été testé avec ce mode d'édition.
		$screen = get_current_screen();
		// Limiter le contexte d'affichage (à revalider contest vs edit-contest?).
		if ( is_admin() && ( 'edit-contest' == $screen->id ) ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}
}
