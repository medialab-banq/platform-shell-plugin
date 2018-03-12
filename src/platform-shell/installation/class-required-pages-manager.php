<?php
/**
 * Platform_Shell\installation\Required_Pages_Manager
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\installation;

use \Platform_Shell\installation\Page_Helper;

/**
 * Gestionnaire des pages requises par la plateforme.
 *
 * @class    Required_Pages_Manager
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Required_Pages_Manager {

	/**
	 * Instance de Page_Helper.
	 *
	 * @var Page_Helper
	 */
	private $page_helper;

	/**
	 * Instance de Required_Pages_configs.
	 *
	 * @var Required_Pages_Configs
	 */
	private $required_pages_configs;

	/**
	 * Option des pages installées.
	 *
	 * @var array
	 */
	private $installed_pages_option = null;

	/**
	 * Identifiant de l'option des pages installées.
	 *
	 * @var string
	 */
	private $installed_pages_option_name = 'platform_shell_option_installed_pages';

	/**
	 * Liste des id de page protégées de la suppression.
	 *
	 * @var array
	 */
	private $delete_protected_pages_ids = null;

	/**
	 * Constructeur.
	 *
	 * @param \Platform_Shell\installation\Required_Pages_Configs $required_pages_configs    Auto DI.
	 * @param Page_Helper                                         $page_helper               Auto DI.
	 */
	public function __construct( Required_Pages_Configs $required_pages_configs, Page_Helper $page_helper ) {
		$this->required_pages_configs = $required_pages_configs;
		$this->page_helper            = $page_helper;

		$this->get_installed_pages_option();
	}

	/**
	 * Méthode pour gérer l'installation des pages (appelé par le gestionnaire d'installation du plugin).
	 */
	public function install() {
		$this->create_pages();
	}

	/**
	 * Méthode pour gérer la désintallation des pages (appelé par le gestionnaire d'installation du plugin).
	 */
	public function desinstall() {
		/*
		 * Rien pour le moment. Danger = suppression de contenu utilisateur.
		 */
	}

	/**
	 * Méthode pour récupérer l'id de page installée à partir de l'id de configuration.
	 *
	 * @param string $page_config_id    Id de configuration.
	 * @return string|null
	 */
	public function get_installed_page_id_by_required_page_config_id( $page_config_id ) {
		if ( isset( $this->installed_pages_option ) ) {
			return $this->installed_pages_option[ $page_config_id ]['page-id'];
		} else {
			return null;
		}
	}

	/**
	 * Méthode pour récupérer les id des pages protégés contre la suppression (lorsque le plugin est actif).
	 *
	 * @return array    Liste des id de page protégés.
	 */
	public function get_delete_protected_pages_id() {
		if ( null === $this->delete_protected_pages_ids ) {
			$this->delete_protected_pages_ids = array();

			if ( isset( $this->installed_pages_option ) ) {
				foreach ( $this->installed_pages_option as $key => $installed_page_info ) {
					if ( true === $installed_page_info['delete-protected'] ) {
						$this->delete_protected_pages_ids[ $installed_page_info['page-id'] ] = true;
					}
				}
			}
		}

		return $this->delete_protected_pages_ids;
	}

	/**
	 * Méthode pour créer les pages (point d'entré principal).
	 */
	private function create_pages() {
		$pages_to_create = $this->required_pages_configs->get_pages();

		$this->create_pages_from_pages_array( $pages_to_create );

		$this->save_installed_pages_option();

		wp_cache_delete( 'all_page_ids', 'pages' );
	}

	/**
	 * Méthode pour récupérer l'information des pages installées.
	 */
	private function get_installed_pages_option() {
		$installed_pages              = get_option( $this->installed_pages_option_name, null );
		$this->installed_pages_option = isset( $installed_pages ) ? $installed_pages : array();
	}

	/**
	 * Méthode pour enregistrer l'information des pages installées.
	 */
	private function save_installed_pages_option() {
		update_option( $this->installed_pages_option_name, $this->installed_pages_option, false /* Autoload */ );
	}

	/**
	 * Méthode pour créer les pages à partir de la liste de configuration.
	 *
	 * @param array $pages_to_create    Liste des pages à créer.
	 * @param int   $parent_page_id       Id de la page parent.
	 */
	private function create_pages_from_pages_array( $pages_to_create, $parent_page_id = 0 ) {

		foreach ( $pages_to_create as $key => $page ) {

			$id_of_previously_installed_page = isset( $this->installed_pages_option[ $key ] ) ? $this->installed_pages_option[ $key ]['page-id'] : null;

			$page_id = $this->page_helper->create_or_update_page( $page, $id_of_previously_installed_page, $parent_page_id );

			if ( null !== $page_id ) {
				$delete_protected                     = isset( $page['delete-protected'] ) ? $page['delete-protected'] : false;
				$installed_page_info                  = array(
					'delete-protected' => $delete_protected,
					'page-id'          => $page_id,
				);
				$this->installed_pages_option[ $key ] = $installed_page_info;
			}

			if ( isset( $page['child_pages'] ) ) {
				$this->create_pages_from_pages_array( $page['child_pages'], $page_id );
			}
		}
	}

}
