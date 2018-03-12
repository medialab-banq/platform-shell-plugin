<?php

namespace com\github\tommcfarlin\autoloader;

/**
 * Automatically loads the specified file.
 */
class Autoloader {

	private $root_name_space;
	private $classes_fully_qualified_path;

	/**
	 * Constructeur.
	 */
	public function __construct( $root_name_space, $classes_fully_qualified_path ) {
		$this->root_name_space              = $root_name_space;
		$this->classes_fully_qualified_path = $classes_fully_qualified_path; /* Should be an array? */

		spl_autoload_register( array( &$this, 'autoloader_function' ) );
	}

	/**
	 * Automatically loads the specified file.
	 *
	 * Examines the fully qualified class name, separates it into components, then creates
	 * a string that represents where the file is loaded on disk.
	 */
	public function autoloader_function( $class_name ) {

		if ( ( strpos( $class_name, $this->root_name_space ) === 0 /* root_name_space débute obligateoirement $class_name. */ ) ) {

			// First, separate the components of the incoming file.
			$file_path = explode( '\\', $class_name );

			/**
			 * - The first index will always be WCATL since it's part of the plugin.
			 * - All but the last index will be the path to the file.
			 */
			// Get the last index of the array. This is the class we're loading.
			if ( isset( $file_path[ count( $file_path ) - 1 ] ) ) {

				$class_file = strtolower(
					$file_path[ count( $file_path ) - 1 ]
				);

				$class_file = "class-$class_file.php";
			}

			/**
			 * Find the fully qualified path to the class file by iterating through the $file_path array.
			 * We ignore the first index since it's always the top-level package. The last index is always
			 * the file so we append that at the end.
			 */
			$fully_qualified_path = '';

			$to_last_folder = count( $file_path ) - 1;
			for ( $i = 0; $i < $to_last_folder; $i++ ) {
				$dir                   = strtolower( $file_path[ $i ] );
				$fully_qualified_path .= trailingslashit( $dir );
			}
			$fully_qualified_path .= $class_file;

			// The classname has an underscore, so we need to replace it with a hyphen for the file name.
			$fully_qualified_path = $this->classes_fully_qualified_path . str_ireplace( '_', '-', $fully_qualified_path );

			// Now we include the file.
			if ( file_exists( $fully_qualified_path ) ) {
				include_once $fully_qualified_path;
			} else {
				throw new \Exception( "Problème de résolution de path: $fully_qualified_path" );
			}
		}
	}
}
