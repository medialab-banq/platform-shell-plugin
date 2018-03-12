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

use \Platform_Shell\installation\Required_Pages_Manager;

/**
 * Restriction_Required_Page_Delete. Empêcher la suppression de page de plateforme (lorsque le plugin est activé!).
 *
 * @class    Restriction_Required_Page_Delete
 * @author   Bibliothèque et Archives nationales du Québec (BAnQ)
 */
class Restriction_Required_Page_Delete {

	/**
	 * Instance de Required_Pages_Manager (DI).
	 *
	 * @var Required_Pages_Manager
	 */
	private $required_page_manager;

	/**
	 * Constructeur.
	 *
	 * @param Required_Pages_Manager $required_page_manager    Instance de Required_Pages_Manager (DI).
	 */
	public function __construct( Required_Pages_Manager $required_page_manager ) {
		$this->required_page_manager = $required_page_manager;
	}

	/**
	 * Méthode init.
	 */
	public function init() {
		add_filter( 'user_has_cap', array( &$this, 'restrict_post_edit_or_delete' ), 10, 3 );
	}

	/**
	 * Méthode restrict_post_edit_or_delete.
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/user_has_cap
	 * @param array $allcaps All the capabilities of the user.
	 * @param array $cap     [0] Required capability.
	 * @param array $args    [0] Requested capability.
	 *                       [1] User ID.
	 *                       [2] Associated object ID.
	 * @return boolean
	 */
	public function restrict_post_edit_or_delete( $allcaps, $cap, $args ) {
		// Inspiré de : http://www.wpbeginner.com/wp-tutorials/how-to-block-wordpress-post-updates-and-deletion-after-a-set-period/.
		// Note : la restriction s'applique à admin aussi.
		$delete_protected_pages_ids = $this->required_page_manager->get_delete_protected_pages_id();

		// Bail out if we're not asking to edit or delete a post.
		if ( 'edit_post' != $args[0] && 'delete_post' != $args[0] || empty( $allcaps['edit_posts'] ) ) {
			return $allcaps;
		}

		// Load the post data.
		$post = get_post( $args[2] );

		if ( isset( $delete_protected_pages_ids[ $post->ID ] ) ) {

			$allcaps['delete_pages']        = false;
			$allcaps['delete_others_pages'] = false;
		}

		return $allcaps;
	}
}
