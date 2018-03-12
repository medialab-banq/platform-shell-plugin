<?php
/**
 * Platform_Shell\installation\Required_Widget_Manager
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

namespace Platform_Shell\Installation;

use DI\FactoryInterface;
use \Platform_Shell\installation\Required_Menus_Manager;

/**
 * Gestionnaire des widgets requis par la plateforme.
 *
 * @class    Required_Widget_Manager
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Required_Widget_Manager {

	/**
	 * Instance FactoryInterface.
	 *
	 * @var FactoryInterface
	 */
	private $di_container;

	/**
	 * Instance Required_Menus_Manager
	 *
	 * @var Required_Menus_Manager
	 */
	private $required_menus_manager;

	/**
	 * Constructor
	 *
	 * @param FactoryInterface       $di_container              Auto DI.
	 * @param Required_Menus_Manager $required_menus_manager    Auto DI.
	 */
	public function __construct( FactoryInterface $di_container, Required_Menus_Manager $required_menus_manager ) {

		$this->di_container           = $di_container;
		$this->required_menus_manager = $required_menus_manager;
	}

	/**
	 * Méthode pour initialiser les widgets et sidebars.
	 */
	public function init() {

		$this->register_widgets();
		$this->register_sidebars();
	}

	/**
	 * Méthode pour enregistrer les widgets.
	 */
	public function register_widgets() {

		$this->register_custom_widgets();
		$this->register_main_sidebar_widget();
		$this->register_footer1_widget();
	}

	/**
	 * Méthode pour enregistrer les widgets de la plateforme.
	 *
	 * @global type $wp_widget_factory
	 */
	private function register_custom_widgets() {
		global $wp_widget_factory; /* utilisation globale WordPress inévitable. */

		/*
		 * Register_widget ne fonctionne pas avec DI (la classe du widget ne reçoit pas les injections).
		 * Solution reprise de http://www.theaveragedev.com/widget-classes-dependency-injection-02/.
		 */

		$widget = $this->di_container->get( 'Platform_Shell\Widgets\Project_Category_Filter' );
		$wp_widget_factory->widgets['Platform_Shell\Widgets\Project_Category_Filter'] = $widget;
	}

	/**
	 * Méthode pour enregistrer le sidebar main.
	 */
	private function register_main_sidebar_widget() {

		$active_widgets = get_option( 'sidebars_widgets' );

		if ( empty( $active_widgets['sidebar-project'] ) ) {
			// todo_install. Should be done post-install. Once.
			$counter                                       = 0;
			$active_widgets['sidebar-project'][ $counter ] = 'platform_shell_option_project_category_filter-' . $counter;
			$demo_widget_content[ $counter ]               = array(
				'_multiwidget' => 1,
				0              => array(),
			);
			$demo_widget_content['_multiwidget']           = 1;

			update_option( 'widget_platform_shell_option_project_category_filter', $demo_widget_content );
			update_option( 'sidebars_widgets', $active_widgets );
		}
	}

	/**
	 * Méthode pour enregistrer le sidebar footer1.
	 */
	private function register_footer1_widget() {

		$active_widgets            = get_option( 'sidebars_widgets' );
		$counter                   = 1;
		$active_widgets['footer1'] = array( $counter => 'nav_menu-' . $counter );

		// Duplication fonctionnelle avec platform_shell_theme_get_installed_menu_wp_menu_id.
		$menu_id = $this->required_menus_manager->get_wordpress_menu_id_by_platform_shell_option_menu_id( 'platform_shell_menu_secondary_footer' );

		if ( isset( $menu_id ) ) {

			$demo_widget_content = array(
				$counter       => array(
					'title'    => '', /* Ne pas afficher de titre. */
					'nav_menu' => $menu_id,
				),
				'_multiwidget' => 1,
			);
		} else {
			$demo_widget_content = array();
		}

		update_option( 'widget_nav_menu', $demo_widget_content );
		update_option( 'sidebars_widgets', $active_widgets );
	}

	/**
	 * Méthode pour enregistrer les sidebars.
	 */
	private function register_sidebars() {

		register_sidebar(
			array(
				'id'            => 'sidebar-project',
				'name'          => _x( 'Barre latérale de la liste de projets', 'widget-project-sidebar', 'platform-shell-theme' ),
				'description'   => _x( 'Contenu préconfiguré pour le bon fonctionnement de la plateforme. Utilisé pour afficher le widget « Filtre des projets par catégories » dans la liste des projets.', 'widget-project-sidebar', 'platform-shell-theme' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s sidebox shadow">',
				'after_widget'  => '</div>',
				'before_title'  => '',
				'after_title'   => '',
			)
		);

		register_sidebar(
			array(
				'id'            => 'homepage-contest',
				'name'          => _x( 'Bannière des concours', 'sidebar', 'platform-shell-theme' ),
				'description'   => _x( 'Utilisé pour présenter la bannière des concours sur la page d’accueil en utilisant le widget « Image Widget ». L’extension « Image Widget » doit être installée et activée. Taille de l’image suggérée de 240 x 400 pixels.', 'sidebar', 'platform-shell-theme' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s sidebox shadow">',
				'after_widget'  => '</div>',
				'before_title'  => '',
				'after_title'   => '',
			)
		);

		register_sidebar(
			array(
				'id'            => 'footer1',
				'name'          => _x( 'Pied de page', 'sidebar', 'platform-shell-theme' ),
				'description'   => _x( 'Contenu préconfiguré pour le bon fonctionnement de la plateforme. Utilisé pour afficher des liens dans le pied de page. ', 'sidebar', 'platform-shell-theme' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h4 class="widgettitle">',
				'after_title'   => '</h4>',
			)
		);
	}
}
