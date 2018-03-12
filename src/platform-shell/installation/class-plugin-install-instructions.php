<?php
/**
 * Platform_Shell\installation\Plugin_Install_Instructions
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\installation;

/**
 * Définition des instructions d'installation.
 *
 * @class    Plugin_Install_Instructions
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Plugin_Install_Instructions {
	/*
	 * Devrait être des "const" mais il y a un bug d'accès via instances ($this->inst::someconstant).
	 * Ce qui rend difficile l'utilisation des constante avec l'injection de dépendance.
	 * https://stackoverflow.com/questions/5447541/accessing-php-class-constants.
	 */

	/**
	 * Constante instruction installation.
	 *
	 * @var int
	 */
	public $install = 0;

	/**
	 * Constante instruction post post installation.
	 *
	 * @var int
	 */
	public $post_install = 1;

	/**
	 * Constante instruction post désinstallation.
	 *
	 * @var int
	 */
	public $uninstall = 2;

	/**
	 * Constante instruction réparation.
	 *
	 * @var int
	 */
	public $repair = 3;

	/**
	 * Constructeur.
	 */
	public function __construct() {

	}
}
