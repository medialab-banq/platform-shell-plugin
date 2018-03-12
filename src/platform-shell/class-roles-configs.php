<?php
/**
 * Platform_Shell\Roles_Configs
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell;

/**
 * Classe des configurations de rôles.
 *
 * @class    Roles_Configs
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Roles_Configs {

	/**
	 * Identifiant rôle admin.
	 *
	 * @var string
	 */
	private $admin_role;

	/**
	 * Identifiant rôle user.
	 *
	 * @var string
	 */
	private $user_role;

	/**
	 * Identifiant rôle manager.
	 *
	 * @var string
	 */
	private $manager_role;

	/**
	 * Constructeur.
	 */
	public function __construct() {
		$this->admin_role   = 'administrator';               /* WordPress */
		$this->user_role    = 'platform_shell_role_user';    /*  Custom   */
		$this->manager_role = 'platform_shell_role_manager'; /*  Custom   */
	}

	/**
	 * Méthode ?
	 *
	 * @param string $name    Nom de la propriété à récupérer.
	 * @return string
	 */
	public function __get( $name ) {
		return $this->$name;
	}

	/**
	 * Méthode pour retourner la liste des rôles.
	 *
	 * @return array
	 */
	public function get_roles() {
		return [ $this->user_role, $this->manager_role, $this->admin_role ];
	}

	/**
	 * Méthode pour retourner la liste des rôles ayant des droits plus élevés.
	 *
	 * @return type
	 */
	public function get_elevated_roles() {
		return [ $this->manager_role, $this->admin_role ];
	}
}
