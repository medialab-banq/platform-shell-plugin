<?php
/**
 * Platform Shell Core Functions
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

// todo_refactoring : intégration plugin et fonction globales.
use Platform_Shell\PlatformShellDateTime;
use Platform_Shell\CPT\Project\Project_Type;
use Platform_Shell\Fields\Fields_Helper;
use Platform_Shell\installation\Page_Helper;
use Platform_Shell\installation\Required_Pages_Configs;
use Platform_Shell\installation\Required_Pages_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'HASH_TYPE' ) ) {
	// Valeur par défaut pour le hash. Cette valeur peut être remplacé dans le fichier wp-config.php.
	define( 'HASH_TYPE', 'sha1' );
}

if ( ! function_exists( 'platform_shell_get_file_version' ) ) {
	/**
	 * Méthode platform_shell_get_file_version
	 *
	 * @param string $path       Emplacement du fichier.
	 * @return boolean|string    Version du fichier.
	 */
	function platform_shell_get_file_version( $path = '' ) {
		$return_value = false;
		if ( ! empty( $path ) && file_exists( $path ) ) {
			$return_value = hash_file( HASH_TYPE, $path );
		}
		return $return_value;
	}
}

/**
 * Méthode custom_pre_get_posts_query
 *
 * @param \WP_Query $query    La requête à pré-filtrer.
 */
function custom_pre_get_posts_query( $query ) {

	if ( $query->is_search() || $query->is_author() || $query->is_tax( 'platform_shell_tax_proj_cat' ) ) {
		// Patch. Il faudrait revoir la gestion des query pour éviter les effets de bord.
		// Dans ce cas, les custom query sont écrasées par les handler global qui devrait en principe
		// seulement servir pour les requête par défaut (archives par ex.).
		// Plusieurs corrections possibles, si le param est à -1 (sans limite), on ne touche pas à la requête.
		// J'ai essayé d'ajouter une info de contexte supplémentaire mais ça demanderait d'autres changements.
		$is_query_with_unlimited_results = isset( $query->query_vars['posts_per_page'] ) ? -1 === intval( $query->query_vars['posts_per_page'] ) : false;
		if ( ! $is_query_with_unlimited_results ) {
			if ( $query->is_tax( 'platform_shell_tax_proj_cat' ) ) {
				$query->set( 'posts_per_page', platform_shell_theme_get_post_per_page_for_tiles() );
			} else {
				$query->set( 'posts_per_page', platform_shell_theme_get_post_per_page_for_list() );
			}
		}
		return;
	}

	if ( ! $query->is_main_query() ) {
		return;
	}
	if ( ! $query->is_post_type_archive() ) {
		return;
	}
	if ( ! is_admin() && $query->is_post_type_archive( 'project' ) ) {
		$query->set( 'posts_per_page', platform_shell_theme_get_post_per_page_for_tiles() );
		$query->set( 'post_status', 'publish' );
		$query->set( 'orderby', 'modified' );
		$query->set( 'order', 'DESC' );
	}
	if ( ! is_admin() && $query->is_post_type_archive( 'equipment' ) ) {
		$query->set( 'posts_per_page', platform_shell_theme_get_post_per_page_for_tiles() );
		$query->set( 'orderby', 'modified' );
		$query->set( 'order', 'DESC' );
	}
	if ( ! is_admin() && $query->is_post_type_archive( 'contest' ) ) {
		$query->set( 'posts_per_page', platform_shell_theme_get_post_per_page_for_tiles() );
		$query->set( 'meta_key', 'platform_shell_meta_contest_date_end' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'DESC' );
	}
	if ( ! is_admin() && $query->is_post_type_archive( 'activity' ) ) {
		$query->set( 'posts_per_page', platform_shell_theme_get_post_per_page_for_list() );
		$query->set( 'meta_key', 'platform_shell_meta_activity_date' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'ASC' );
	}
	remove_action( 'pre_get_posts', 'custom_pre_get_posts_query' );
}
add_action( 'pre_get_posts', 'custom_pre_get_posts_query' );

/**
 * Méthode platform_shell_filter_media
 *
 * Cette méthode permets d'enlever les images/médias associés à un projet de l'affichage de la bibliothèque de médias.
 *
 * @param string $where    Filtre à appliquer à la requête.
 * @return string
 */
function platform_shell_filter_media( $where ) {

	global $current_user;
	global $wp_roles;

	// phpcs:ignore WordPress --La valeur POST est utilisée pour filtrer les résultats. L'utilisation du nonce n'est pas nécessaire ici.
	$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : null;

	$current_screen = ( is_admin() ) ? get_current_screen() : null;

	if (
		is_admin() && // Seulement dans l'interface d'administration.
		! is_null( $current_screen ) &&
		! in_array( 'platform_shell_role_user', $current_user->roles, true ) && // Cacher les medias des utilisateurs.
		(
			( ! is_null( $action ) && 'query-attachments' === $action ) || // Fenêtre dynamique lors de la sélection d'une image dans un post.
			( 'upload' === $current_screen->base && 'attachment' === $current_screen->post_type ) // Bibliothèque d'images.
		) // Seulement lorsque l'on liste la bibliothèque d'images.
	) {

		$admin_and_managers_ids = implode(
			', ',
			get_users(
				[
					'role__not_in' => [ 'platform_shell_role_user' ],
					'fields'       => 'ID',
				]
			)
		);

		$where .= ' AND post_author IN (' . $admin_and_managers_ids . ')';

		// L'on obtiens la liste des ids de tous les projets.
		$project_ids = implode(
			', ',
			get_posts(
				[
					'fields'         => 'ids',
					'post_type'      => 'project',
					'posts_per_page' => -1,
					'offset'         => 0,
					'post_status'    => 'any',
				]
			)
		);

		// L'on exclut les images qui sont attachées à un projet.
		$where .= ' AND post_parent NOT IN (' . $project_ids . ')';
	}

	return $where;
}
if ( is_admin() ) {
	add_filter( 'posts_where', 'platform_shell_filter_media' );
}

/**
 * Méthode custom_wp_mail_from
 *
 * @param string $email    Addresse email utilisée pour l'envoi.
 * @return string          Addresse email utilisée pour l'envoi obtenu du panneau d'administration.
 */
function custom_wp_mail_from( $email ) {
	$email = get_option( 'admin_email' );
	return $email;
}
add_filter( 'wp_mail_from', 'custom_wp_mail_from' );

/**
 * Méthode custom_wp_mail_from
 *
 * @param string $name    Nom utilisé pour l'envoi.
 * @return string         Nom utilisé pour l'envoi obtenu du panneau d'administration.
 */
function custom_wp_mail_from_name( $name ) {
	$name = get_bloginfo( 'name' );
	return $name;
}
add_filter( 'wp_mail_from_name', 'custom_wp_mail_from_name' );

/**
 * Méthode platform_shell_format_tiny_mce
 *
 * @param string $in      Liste des éléments invalides séparé par une virgule.
 * @return string         Liste des éléments invalides séparé par une virgule codé à dur dans la méthode.
 */
function platform_shell_format_tiny_mce( $in ) {
	$in['invalid_elements'] = 'script,iframe';
	return $in;
}
add_filter( 'tiny_mce_before_init', 'platform_shell_format_tiny_mce' );

/**
 * Méthode platform_shell_get_tags_list
 *
 * @param integer $objectid    Objet pour lequel nous voulons obtenir la taxonomie.
 * @param array   $taxonomy    Taxonomie.
 * @return string
 */
function platform_shell_get_tags_list(
	$objectid, // post ou user mais une taxonomie ne supporte pas le deux (conflits de key).
	$taxonomy
) {
	$object_terms = wp_get_object_terms( $objectid, $taxonomy );
	$tags_text    = '';

	if ( $object_terms ) {
		$tags_terms = array();
		foreach ( $object_terms as $term ) {
			$tags_terms[] = $term->name;
		}
		$tags_text = implode( ', ', $tags_terms );
	}
	return $tags_text;
}

/**
 * Méthode platform_shell_the_tags
 *
 * @param integer $objectid    Objet pour lequel nous voulons obtenir la taxonomie.
 * @param array   $taxonomy    Taxonomie.
 */
function platform_shell_the_tags(
	$objectid, // post ou user mais une taxonomie ne supporte pas le deux (conflits de key).
	$taxonomy
) {
	$object_terms = wp_get_object_terms( $objectid, $taxonomy );
	if ( $object_terms ) {
		foreach ( $object_terms as $term ) {
			// phpcs:ignore WordPress --Les valeurs proviennent de la taxonomie, donc des valeurs propres.
			$name             = esc_html( $term->name );
			$link_search_term = urlencode( $name );
			$url              = site_url( '/?s=' . $link_search_term );
			echo '<span><a href="' . $url . '">' . $name . '</a></span>';
		}
	}
}

/**
 * Méthode platform_shell_tags_to_terms
 *
 * @param string $tags    Liste des tags séparé par des virgules.
 * @return array
 */
function platform_shell_tags_to_terms( $tags ) {
	// Revalider : c'est pas clair quel nettoyage / préparation il faut appliquer. (sanitize en amont suffisant?).
	// WordPress semble faire le strip des espaces.
	// Revalider le esc_attr. Provient de l'article exemple mais c'était fait sur un terme seulement.
	$terms = array_filter( explode( ',', esc_attr( $tags ) ) );
	return $terms;
}

/**
 * Méthode platform_shell_get_profile_email_text
 *
 * @param integer $user_id    Identifiant de l'utilisateur.
 * @return string
 */
function platform_shell_get_profile_email_text( $user_id ) {

	if ( get_user_meta( $user_id, 'shibboleth_account' ) ) {

		$show_email_option = platform_shell_get_option( 'platform_shell_option_shibboleth_show_real_email_to_user', 'platform_shell_settings_main_accounts', 'off' );
		$show_email        = platform_shell_option_is_checked( $show_email_option );

		if ( $show_email ) {
			$email = isset( $_SERVER['email'] ) ? sanitize_text_field( $_SERVER['email'] ) : null;

			if ( ! is_null( $email ) ) {
				return $email;
			} else {
				// Check options.
				$default               = _x( 'Ton dossier ne contient pas d’adresse de courriel.', 'settings-default', 'platform-shell-plugin' );
				$missing_email_message = platform_shell_get_option( 'platform_shell_option_shibboleth_missing_email_message', 'platform_shell_settings_main_accounts', $default );
				return $missing_email_message;
			}
		} else {
			return ''; /* Ne rien afficher. */
		}
	} else {
		$user_data = get_userdata( $user_id );
		if ( isset( $user_data ) ) {
			return $user_data->user_email;
		} else {
			return '';
		}
	}
}

/**
 * Méthode platform_shell_get_keywords_array_from_text
 *
 * @param string $keywords_text    Liste des mots clefs séparés par des virgules.
 * @return array
 */
function platform_shell_get_keywords_array_from_text( $keywords_text ) {
	$keywords_array_from_base_delimiter = array_filter( explode( ',', $keywords_text ) );
	$keywords_array_trimmed             = array_map( 'trim', $keywords_array_from_base_delimiter );
	return $keywords_array_trimmed;
}

/**
 * Méthode contest_subscription_button
 */
function contest_subscription_button() {

	global $post, $current_user;

	$post_id = $post->ID;
	$user_id = $current_user->ID;

	$contest_is_live   = check_contest_status( $post_id );
	$contest_is_coming = check_contest_start_date( $post_id );
	$opening_date      = get_post_meta( get_the_ID(), 'platform_shell_meta_contest_date_open', true );

	if ( $contest_is_live ) {
		if ( $contest_is_coming ) {

			$formatted_opening_date = PlatformShellDateTime::format_localize_date( $opening_date );

			/* translators: %1$s: date */
			$contest_opening_message = sprintf( _x( 'Le concours débute le %1$s.', 'cpt-contest-subscription', 'platform-shell-plugin' ), $formatted_opening_date );
			// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
			echo '<div class="col-xs-12 concoursParticiper"><p><a href="javascript:void(0);" class="btnSquare">' . $contest_opening_message . ' </a></div>';
		} else {
			?>
			<div class="col-xs-12 concoursParticiper">
				<a href="#" class="btnSquare" role="button" data-toggle="modal" data-target="#contest_subscription_form">
					<?php
						_ex( 'Participer', 'cpt-contest-subscription', 'platform-shell-plugin' ); // phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
					?>
				</a>
			</div>
			<?php
			get_contest_form_modal( $post, $user_id );
		}
	} else {
		$link_id = get_post_meta( $post_id, 'platform_shell_meta_contest_winners_announcement_article', true );
		if ( isset( $link_id ) && ! empty( $link_id ) ) {
			$winners_link = get_the_permalink( $link_id );
			?>
			<div class="col-xs-12 concoursParticiper">
				<a href="<?php echo esc_url( $winners_link ); ?>" class="btnSquare" role="button">
				<?php
					// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
					_ex( 'Voir les lauréats', 'cpt-contest-subscription', 'platform-shell-plugin' );
				?>
				</a>
			</div>
			<?php
		}
	}
}

/**
 * Méthode check_contest_status
 *
 * @param integer $contest_id    Identifiant du concours.
 * @return boolean
 */
function check_contest_status( $contest_id ) {
	$date_end        = get_post_meta( $contest_id, 'platform_shell_meta_contest_date_end', true );
	$contest_is_live = true;

	$contest_end = date_create_from_format( PlatformShellDateTime::get_midnight_time_format(), $date_end );

	$today = new \DateTime( 'midnight' );

	// La comparaison doit se faire en ignorant le temps (temps ramené à 0).
	if ( get_post_status( $contest_id ) === 'publish' && ( $contest_end < $today ) ) {
		$contest_is_live = false;
	}
	return $contest_is_live;
}

/**
 * Méthode check_contest_start_date
 *
 * @param integer $contest_id    Identifiant du concours.
 * @return boolean
 */
function check_contest_start_date( $contest_id ) {
	$date_start        = get_post_meta( $contest_id, 'platform_shell_meta_contest_date_open', true );
	$contest_is_coming = false;

	$contest_start = date_create_from_format( PlatformShellDateTime::get_midnight_time_format(), $date_start );

	$today = new \DateTime( 'midnight' );

	// La comparaison doit se faire en ignorant le temps (temps ramené à 0).
	if ( get_post_status( $contest_id ) === 'publish' && ( $contest_start > $today ) ) {
		$contest_is_coming = true;
	}
	return $contest_is_coming;
}

/**
 * Méthode get_contest_form_modal
 *
 * @param \WP_Post $post       Contenu de la page.
 * @param integer  $user_id    Identifiant de l'utilisateur.
 */
function get_contest_form_modal( $post, $user_id ) {
	global $current_user, $wp;
	$create_project_page = do_shortcode( '[platform_shell_permalink_by_page_id id="platform-shell-page-project-create-page"]' );
	$user_have_projects  = false;
	?>
	<div class="modal fade" id="contest_subscription_form" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="modal-title">
					<?php
						/* translators: %1$s: Titre du concours. */
						echo sprintf( _x( 'Participer au concours « %1$s ».', 'cpt-contest-subscription', 'platform-shell-plugin' ), $post->post_title );
					?>
					</h4>
				</div>
				<div class="modal-body">
					<div class="response"></div>
					<form id="form_subscribe_project" class="platform_shell_form add_project" method="post" >
						<?php
						if ( ! is_user_logged_in() ) {
							$message    = '<div class="alert alert-danger">' . _x( 'Tu dois être connecté afin de pouvoir t’inscrire au concours.', 'cpt-contest-subscription-modal-form', 'platform-shell-plugin' );
							$login_link = esc_url( platform_shell_get_return_to_current_page_login_url() );
							/* translators: %1$s: Lien vers le login. */
							$message .= '<p>' . sprintf( _x( 'Tu peux le faire en <a href="%1$s" >cliquant ici.</a> ', 'cpt-contest-subscription-modal-form', 'platform-shell-plugin' ), $login_link ) . '</p></div>';
							$template = '';
							// phpcs:ignore WordPress --La valeur soulevée est une valeur générée par le système (localisée + permalien).
							echo $message;
						} else {
							$avatar_h2         = get_avatar( $current_user->ID, 40, '', '', array( 'class' => 'img-circle' ) );
							$avatar_h2_display = '<h2>' . $avatar_h2 . ' ' . $current_user->display_name . '</h2>';
							echo $avatar_h2_display; // phpcs:ignore WordPress --La valeur soulevée est générée par le système.
							$projects           = Project_Type::get_user_projects( $current_user->ID );
							$user_have_projects = count( $projects ) !== 0;
							$has_project        = false;

							if ( in_array( 'platform_shell_role_user', (array) $current_user->roles, true ) ) {
								if ( $user_have_projects ) {
									$has_project = true;
									// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
									echo '<h3>' . _x( 'Liste de mes projets', 'cpt-contest-subscription-modal-form', 'platform-shell-plugin' ) . '</h3>';
									echo '<p class="form-row"><select class="of-input" name="project_id" required >';
									// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
									echo '<option value="">' . _x( 'Choisir un de mes projets', 'cpt-contest-subscription-modal-form', 'platform-shell-plugin' ) . '</option>';
									foreach ( $projects as $project ) {
										// phpcs:ignore WordPress --La valeur soulevée est une valeur générée par le système.
										echo '<option value="' . $project->ID . '">' . $project->post_title . '</option>';
									}
								} else {
									// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
									echo '<div class="alert alert-warning">' . _x( 'Pour participer au concours, tu dois créer un projet que tu pourras ensuite utiliser pour compléter ton inscription.', 'cpt-contest-subscription-modal-form', 'platform-shell-plugin' ) . '</div>';
								}
								echo '</select></p>';
								// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée/générée.
								echo '<p style="text-align:center;"><a href="' . $create_project_page . '" class="btn btn-primary btn-md" role="button" > ' . _x( 'Créer un projet', 'cpt-contest-subscription-modal-form', 'platform-shell-plugin' ) . '</a></p>';
								if ( $has_project ) {
									platform_shell_get_reglement_checkbox();
								}
							} else {
								// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée/générée.
								echo '<div class="alert alert-danger" role="alert">' . esc_attr_x( 'Les administrateurs et gestionnaires ne peuvent pas s’inscrire aux concours.', 'cpt-contest-subscription-modal-form', 'platform-shell-plugin' ) . '</div>';
							}
						}
						?>
						<?php if ( is_user_logged_in() && in_array( 'platform_shell_role_user', (array) $current_user->roles, true ) && $user_have_projects ) : /* connecté, utilisateur Platform_Shell ayant des projets. */ ?>
							<p style="text-align:center;">
								<input type="hidden" name="contest_id"  value="<?php echo esc_attr( $post->ID ); ?>" />
								<?php wp_nonce_field( 'subscribe_project_contest_' . $post->ID, 'subscribe_project_contest' ); ?>
								<input type="submit" class="btn btn-primary" name="save_project_details" value="<?php echo esc_attr_x( 'Inscrire mon projet au concours', 'cpt-contest-subscription-modal-form', 'platform-shell-plugin' ); ?>" />
								<input type="hidden" name="action"  value="platform_shell_action_subscribe_project" />
							</p>
						<?php endif; ?>
					</form>
				</div>
				<div class="modal-footer" style="text-align: center;">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">
						<?php
							_ex( 'Fermer', 'cpt-contest-subscription-modal-form', 'platform-shell-plugin' ); // phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
						?>
					</button>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
	<?php
}

/**
 * Méthode platform_shell_get_reglement_checkbox
 */
function platform_shell_get_reglement_checkbox() {
	echo '<p> ';
	// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
	echo '<h4>' . _x( 'Règlements', 'cpt-contest-subscription-modal-form-rules', 'platform-shell-plugin' ) . '</h4>';
	echo '<div id="modaliteInfo">';
	echo do_shortcode( '[platform_shell_contest_rules_page_content]' );
	/* platform_shell_contest_rules_page_content */
	echo '</div>';
	// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
	echo '<p id="terms"><input type="checkbox" id="accept_terms" name="accept_terms" value="yes" > ' . _x( 'J’accepte.', 'cpt-contest-subscription-modal-form-rules', 'platform-shell-plugin' ) . '</p>';
	echo '</p> ';
}

/**
 * Méthode platform_shell_get_return_to_current_page_login_url
 *
 * @return string
 */
function platform_shell_get_return_to_current_page_login_url() {
	global $wp;
	$current_url = site_url( add_query_arg( array(), $wp->request ) );
	return wp_login_url( $current_url );
}

/**
 * Méthode get_project_status
 *
 * @param integer $project_id    Identifiant du projet.
 * @return boolean
 */
function get_project_status( $project_id ) {
	$is_published = false;
	$status       = get_post_status( $project_id );
	if ( 'publish' === $status ) {
		$is_published = true;
	}
	return $is_published;
}

/**
 * Méthode get_projects_x_contest
 *
 * @param integer $contest_id    Identifiant du concours.
 * @return string
 */
function get_projects_x_contest( $contest_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'platform_shell_contest_entry';
	$query = "SELECT * FROM {$table} WHERE contest_id = %d;";

	$result = $wpdb->get_results(
		$wpdb->prepare(
			$query, // phpcs:ignore WordPress --Choix dynamique de la table
			[
				$contest_id,
			]
		)
	);

	$output = '';
	if ( $result ) {
		$output = '<ul>';
		foreach ( $result as $project ) {
			$data = get_post( $project->project_id );

			$url    = get_the_permalink( $project->project_id );
			$author = get_the_author_meta( 'display_name', $data->post_author );
			$status = get_project_status( $project->project_id );

			$inline_style_for_thumnail = platform_shell_get_thumbnail_inline_style_override( $project->project_id, 'thumbnail' );

			if ( $status ) {
				$output .= '<li><a style="' . esc_attr( $inline_style_for_thumnail ) . '" href="' . esc_url( $url ) . '">
						<span class="fondDegrad">
							<span class="tuileZoneTexte">
								<span class="tuileTitre">' . $data->post_title . '</span>
								<span>' . esc_html( $author ) . '</span>
							</span>
						</span>
					</a></li>';
			}
		}
		$output .= '</ul>';
	} else {
		$output = '<br/>' . _x( 'Il n’y a pas de projet inscrit à ce concours.', 'core-functions', 'platform-shell-plugin' );
	}
	return $output;
}

/**
 * Méthode get_contests_x_project
 *
 * @param integer $project_id    Identifiant du projet.
 * @return string
 */
function get_contests_x_project( $project_id ) {
	global $wpdb;
	$table  = $wpdb->prefix . 'platform_shell_contest_entry';
	$query  = "SELECT * FROM {$table} WHERE project_id = %d;";
	$result = $wpdb->get_results(
		$wpdb->prepare(
			$query, // phpcs:ignore WordPress --Choix dynamique de la table
			[
				$project_id,
			]
		)
	);

	$output = '';

	if ( $result ) {
		$output = '<ul>';
		foreach ( $result as $contest ) {
			$status = get_post_status( $contest->contest_id );
			$c_id   = $contest->contest_id;
			$data   = get_post( $c_id );
			$url    = get_the_permalink( $c_id );
			$author = get_post_meta( $c_id, 'platform_shell_meta_contest_organizer', true );

			$inline_style_for_thumnail = platform_shell_get_thumbnail_inline_style_override( $c_id, 'thumbnail' );

			if ( 'publish' === $status ) {
				$output .= '<li><a style="' . esc_attr( $inline_style_for_thumnail ) . '" href="' . esc_url( $url ) . '">
						<span class="fondDegrad">
							<span class="tuileZoneTexte">
								<span class="tuileTitre">' . $data->post_title . '</span>
								<span>' . esc_html( $author ) . '</span>
							</span>
						</span>
					</a></li>';
			}
		}
		$output .= '</ul>';
	} else {
		$output = '<br/>' . _x( 'Ce projet n’est inscrit à aucun concours pour le moment.', 'core-functions', 'platform-shell-plugin' );
	}
	return $output;
}

/**
 * Méthode platform_shell_get_metadata_date_save_format
 *
 * @return string
 */
function platform_shell_get_metadata_date_save_format() {
	return PlatformShellDateTime::get_save_format();
}

/**
 * Méthode platform_shell_get_html_formatted_date
 *
 * @param string  $date_platform_shell_metadata    Date/heure.
 * @param boolean $display_year                    Si l'on affiche l'année associée à cette date.
 * @return string
 */
function platform_shell_get_html_formatted_date( $date_platform_shell_metadata, $display_year = true ) {
	/*
	 * JJ mois_en_texte AAAA au JJ mois_en_texte AAAA
	 * (La date d'ouverture et de fin sont affichés dans une même phrase, par exemple : 12 novembre 2016 au 2 décembre 2016).
	 * Utiliser displayYear = false pour afficher le jour et le mois sans l'année (ex. activités).
	 * Note : Idéalement, il faudrait afficher 1er pour le premier jours du mois.
	 *
	 */
	return PlatformShellDateTime::format_localize_date( $date_platform_shell_metadata, $display_year );
}

/**
 * Méthode project_modify_button
 */
function project_modify_button() {
	global $current_user, $post;

	if (
		is_user_logged_in() &&
		(
			( intval( $current_user->ID ) === intval( $post->post_author ) ) ||
			current_user_can( 'edit_others_projects', get_the_ID() )
		)
	) {

		$text      = _x( 'Modifier le projet', 'core-functions', 'platform-shell-plugin' );
		$edit_link = '';

		if ( 'publish' !== $post->post_status ) {

			$required_page_manager = new Required_Pages_Manager( new Required_Pages_Configs(), new Page_Helper() );

			$edit_page_id = $required_page_manager->get_installed_page_id_by_required_page_config_id(
				'platform-shell-page-project-edit-page'
			);

			$edit_link = home_url( '/index.php?page_id=' . $edit_page_id . '&project_code=' . $post->post_name . '&action=edit' );

		} else {

			$edit_link = esc_url(
				get_permalink( get_the_ID() ) . _x(
					'modifier', 'cpt-project-rewrite', 'platform-shell-plugin'
				)
			);
		}

		// phpcs:ignore WordPress --La valeur soulevée est une valeur localisée.
		echo "<a href='$edit_link' class='btnSquare' role='button'>$text</a>";
	}
}

/**
 * Méthode platform_shell_get_project_form_fields
 *
 * @param string  $action        Action à effectuer.
 * @param integer $project_id    Identifiant du projet.
 */
function platform_shell_get_project_form_fields( $action = null, $project_id = null ) {

	$form_fields = Project_Type::get_project_fields(); /* todo_refactoring */

	if ( ! is_null( $action ) && ! is_null( $project_id ) ) {
		if ( 'edit' === $action ) {
			$project_info = get_post( $project_id );
			$submit_label = _x( 'Modifier mon projet', 'template-project-edit-form', 'platform-shell-plugin' );
			$mode         = $project_id;
		}
	}

	foreach ( $form_fields as $key => $field ) {
		if ( isset( $project_id ) ) {
			if ( isset( $field['meta'] ) && false === boolval( $field['meta'] ) ) {
				switch ( $field['key'] ) {
					case 'taxonomy':
						// todo_refactoring_get_term_commun : Code problème (en parallèle avec save. Devrait crasher si plus d'un terme.
						$terms = wp_get_post_terms( $project_id, 'platform_shell_tax_proj_cat' );
						foreach ( $terms as $term ) {
							$meta = $term->name;
						}
						break;
					case 'project_tags':
						$meta = platform_shell_get_tags_list( $project_id, 'platform_shell_tax_proj_tags' );
						break;
					default:
						$metafield = $field['key'];
						if ( isset( $action ) ) {
							$meta = $project_info->{$metafield};
						}
						break;
				}
			} else {
				if ( 'cocreators' === $key ) {
					$project         = get_post( $project_id );
					$field['author'] = intval( $project->post_author );
				}
				$metakey = ( isset( $field['key'] ) && '' !== $field['key'] ) ? $field['key'] : $field['id'];
				$meta    = get_post_meta( $project_id, $metakey, true );
			}
			Fields_Helper::set_frontend_fields( $field, $meta, $project_id );
		} else {
			Fields_Helper::set_frontend_fields( $field );
		}
	}
}

/**
 * Méthode platform_shell_move_yoast_to_bottom
 *
 * @return string
 */
function platform_shell_move_yoast_to_bottom() {
	return 'low';
}
add_filter( 'wpseo_metabox_prio', 'platform_shell_move_yoast_to_bottom' );


/**
 * Méthode platform_shell_metadata_pre_search
 *
 * Cette méthode permets d'enlever la limitation sur le nombre de résultats de recherches
 *
 * @param WP_Query $query    L'instance de la requête WP_Query.
 */
function platform_shell_metadata_pre_search( WP_Query &$query ) {

	if ( ! is_admin() && $query->is_main_query() ) {
		if ( $query->is_search && ! $query->is_archive ) {

			// L'on sauvegarde la valeur actuelle de 'posts_per_page' avant de la modifier pour la recherche.
			$query->set( '_posts_per_page', $query->get( 'posts_per_page' ) );

			$query->set( 'post_type', 'any' );
			$query->set( 'order', 'DESC' );
			$query->set( 'orderby', 'relevance' );
			$query->set( 'posts_per_page', -1 );

			// Nous devons mettre l'offset à 0 pour rechercher tous les résultats de recherche avant de combiner et filtrer les résultats.
			$query->set( 'offset', 0 );
		}
	}
}
add_action( 'pre_get_posts', 'platform_shell_metadata_pre_search' );

/**
 * Méthode platform_shell_metadata_results
 *
 * Cette méthode fait une recherche sur les champs de métadonnées,
 * et fusionne les résultats avec les résultats de la recherche régulière.
 *
 * @param array    $posts    Une liste des posts trouvés.
 * @param WP_Query $query    L'instance de la requête WP_Query.
 * @return array
 */
function platform_shell_metadata_results( $posts, $query ) {

	if ( ! is_admin() && $query->is_main_query() ) {

		if ( $query->is_search && ! $query->is_archive ) {

			static $has_run = false;

			if ( ! $has_run ) {

				$has_run = true;

				/**
				 * La recherche régulière de WordPress recherche chacun des termes indépendamment,
				 * et retourne comme résultat les articles dont les deux termes sont dans un des champs recherchés.
				 *
				 * Ex.:
				 *
				 * (
				 *     (wp_posts.post_title LIKE '%foo%') OR
				 *     (wp_posts.post_excerpt LIKE '%foo%') OR
				 *     (wp_posts.post_content LIKE '%foo%')
				 * )
				 * AND
				 * (
				 *     (wp_posts.post_title LIKE '%bar%') OR
				 *     (wp_posts.post_excerpt LIKE '%bar%') OR
				 *     (wp_posts.post_content LIKE '%bar%')
				 * )
				 *
				 * Nous essayons de répliquer ce comportement avec la recherche des métadonnées.
				 *
				 * Ex.:
				 *
				 * (
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_contest_prize' AND
				 *         wp_postmeta.meta_value LIKE '%foo%'
				 *     )
				 *     OR
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_contest_judges' AND
				 *         wp_postmeta.meta_value LIKE '%foo%'
				 *     )
				 *     OR
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_contest_terms' AND
				 *         wp_postmeta.meta_value LIKE '%foo%'
				 *     )
				 *     OR
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_contest_evaluation_criteria' AND
				 *         wp_postmeta.meta_value LIKE '%foo%'
				 *     )
				 *     OR
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_project_creative_process' AND
				 *         wp_postmeta.meta_value LIKE '%foo%'
				 *     )
				 * )
				 * AND
				 * (
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_contest_prize' AND
				 *         wp_postmeta.meta_value LIKE '%bar%'
				 *     )
				 *     OR
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_contest_judges' AND
				 *         wp_postmeta.meta_value LIKE '%bar%'
				 *     )
				 *     OR
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_contest_terms' AND
				 *         wp_postmeta.meta_value LIKE '%bar%'
				 *     )
				 *     OR
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_contest_evaluation_criteria' AND
				 *         wp_postmeta.meta_value LIKE '%bar%'
				 *     )
				 *     OR
				 *     (
				 *         wp_postmeta.meta_key = 'platform_shell_meta_project_creative_process' AND
				 *         wp_postmeta.meta_value LIKE '%bar%'
				 *     )
				 * )
				 */

				$query_var    = $query->query_vars['s'];
				$search_terms = explode( ' ', $query_var );

				// La relation entre les différents termes recherchés est un "AND".
				$meta_queries = [
					'relation' => 'AND',
				];

				/**
				 * La recherche par tags est plus complexe, puisque les termes sont séparés dans
				 * des instances différentes. Nous devons donc rechercher l'expression complète
				 * ainsi que la présence de chacun des mots recherchés.
				 *
				 * Ex.:
				 *
				 * # Recherche des termes combinés (match exact de la requête)
				 * (
				 *     (
				 *         wp_terms.name = 'foo bar' AND
				 *         wp_term_taxonomy.taxonomy = 'platform_shell_tax_contest_tags'
				 *     )
				 *     OR
				 *     (
				 *         wp_terms.name = 'foo bar' AND
				 *         wp_term_taxonomy.taxonomy = 'category'
				 *     )
				 *     OR
				 *     (
				 *         wp_terms.name = 'foo bar' AND
				 *         wp_term_taxonomy.taxonomy = 'platform_shell_tax_proj_tags'
				 *     )
				 *     OR
				 *     (
				 *         wp_terms.name = 'foo bar' AND
				 *         wp_term_taxonomy.taxonomy = 'post_tag'
				 *     )
				 * )
				 * OR
				 * (
				 *     # Présence de tous les termes recherchés
				 *     (
				 *         (
				 *             wp_terms.name = 'foo' AND
				 *             wp_term_taxonomy.taxonomy = 'platform_shell_tax_contest_tags'
				 *         )
				 *         OR
				 *         (
				 *             wp_terms.name = 'foo' AND
				 *             wp_term_taxonomy.taxonomy = 'category'
				 *         )
				 *         OR
				 *         (
				 *             wp_terms.name = 'foo' AND
				 *             wp_term_taxonomy.taxonomy = 'platform_shell_tax_proj_tags'
				 *         )
				 *         OR
				 *         (
				 *             wp_terms.name = 'foo' AND
				 *             wp_term_taxonomy.taxonomy = 'post_tag'
				 *         )
				 *     )
				 *     AND
				 *     (
				 *         (
				 *             wp_terms.name = 'bar' AND
				 *             wp_term_taxonomy.taxonomy = 'platform_shell_tax_contest_tags'
				 *         )
				 *         OR
				 *         (
				 *             wp_terms.name = 'bar' AND
				 *             wp_term_taxonomy.taxonomy = 'category'
				 *         )
				 *         OR
				 *         (
				 *             wp_terms.name = 'bar' AND
				 *             wp_term_taxonomy.taxonomy = 'platform_shell_tax_proj_tags'
				 *         )
				 *         OR
				 *         (
				 *             wp_terms.name = 'bar' AND
				 *             wp_term_taxonomy.taxonomy = 'post_tag'
				 *         )
				 *     )
				 * )
				 */

				// La relation entre les différents termes recherchés est un "OR".
				$tag_queries = [
					'relation' => 'OR',
					[
						'relation' => 'OR',
						[
							'taxonomy' => 'platform_shell_tax_contest_tags',
							'field'    => 'name',
							'terms'    => $query_var,
						],
						[
							'taxonomy' => 'category',
							'field'    => 'name',
							'terms'    => $query_var,
						],
						[
							'taxonomy' => 'platform_shell_tax_proj_tags',
							'field'    => 'name',
							'terms'    => $query_var,
						],
						[
							'taxonomy' => 'post_tag',
							'field'    => 'name',
							'terms'    => $query_var,
						],
					],
				];

				$exploded_tag_queries = [
					'relation' => 'AND',
				];

				// Pour chacun des termes recherchés.
				foreach ( $search_terms as $search_term ) {

					// La relation entre chacun des termes des métadonnées est un "OR".
					$meta_queries[] = [
						'relation' => 'OR',
						[
							'key'     => 'platform_shell_meta_contest_prize',
							'value'   => $search_term,
							'compare' => 'LIKE',
						],
						[
							'key'     => 'platform_shell_meta_contest_judges',
							'value'   => $search_term,
							'compare' => 'LIKE',
						],
						[
							'key'     => 'platform_shell_meta_contest_terms',
							'value'   => $search_term,
							'compare' => 'LIKE',
						],
						[
							'key'     => 'platform_shell_meta_contest_evaluation_criteria',
							'value'   => $search_term,
							'compare' => 'LIKE',
						],
						[
							'key'     => 'platform_shell_meta_project_creative_process',
							'value'   => $search_term,
							'compare' => 'LIKE',
						],
					];

					if ( sizeof( $search_terms ) > 1 ) {
						$exploded_tag_queries[] = [
							'relation' => 'OR',
							[
								'taxonomy' => 'platform_shell_tax_contest_tags',
								'field'    => 'name',
								'terms'    => $search_term,
							],
							[
								'taxonomy' => 'category',
								'field'    => 'name',
								'terms'    => $search_term,
							],
							[
								'taxonomy' => 'platform_shell_tax_proj_tags',
								'field'    => 'name',
								'terms'    => $search_term,
							],
							[
								'taxonomy' => 'post_tag',
								'field'    => 'name',
								'terms'    => $search_term,
							],
						];
					}
				}

				// Nous devons rechercher sur tous les termes si la requête contiens plus d'un mot.
				if ( sizeof( $search_terms ) > 1 ) {
					$tag_queries[] = $exploded_tag_queries;
				}

				$meta_query = new WP_Query(
					[
						'post_type'      => 'any',
						'order'          => 'DESC',
						'orderby'        => 'relevance',
						'posts_per_page' => -1,
						'offset'         => 0,
						'meta_query'     => $meta_queries,
					]
				);

				$meta_posts = $meta_query->get_posts();

				$tag_query = new WP_Query(
					[
						'post_type'      => 'any',
						'order'          => 'DESC',
						'orderby'        => 'relevance',
						'posts_per_page' => -1,
						'offset'         => 0,
						'tax_query'      => $tag_queries,
					]
				);

				$tag_posts = $tag_query->get_posts();

				// Fusion des deux résultats de recherche.
				$posts = array_values( array_unique( array_merge( $posts, $meta_posts, $tag_posts ), SORT_REGULAR ) );

				// Calculation des valeurs de pagination en fonction des résultats reçus.
				$post_per_page = $query->get( '_posts_per_page' );
				$found_posts   = sizeof( $posts );
				$pages         = intval( ceil( $found_posts / $post_per_page ) );
				$page          = $query->get( 'paged' );
				$page          = ( 0 < $page ) ? ( $page - 1 ) : $page;
				$offset        = $page * $post_per_page;

				$query->set( 'posts_per_page', $post_per_page );
				$query->found_posts   = $found_posts;
				$query->max_num_pages = $pages;

				// Obtenir les résultats correspondant aux valeurs de pagination.
				$posts = array_slice( $posts, $offset, $post_per_page );
			}
		}
	}

	return $posts;
}
add_filter( 'posts_results', 'platform_shell_metadata_results', 1, 2 );

/**
 * Méthode pour récupérer une définition de style en ligne pour afficher un thumbnail.
 *
 * @param int|object $post    Post ou post id.
 * @param string     $size    Identifiant de taille de vignette.
 * @return string             Définition de style pour afficher une image en arrière plan.
 */
function platform_shell_get_thumbnail_inline_style_override( $post, $size ) {
	$thumbnail_url = get_the_post_thumbnail_url( $post, $size );

	if ( false !== $thumbnail_url ) {
		return 'background: url(' . $thumbnail_url . ') no-repeat; background-size: cover;';
	} else {
		return '';
	}
}

/**
 * Méthode pour récupérer l'url gravatar d'un usager (à partir de son courriel).
 *
 * @param string $user_id    Id WordPress de l'utilisateur.
 * @return string
 */
function platform_shell_get_gravatar_url( $user_id ) {
	$email      = platform_shell_get_profile_email_text( $user_id );
	$avatar_url = '';

	if ( ! empty( $email ) ) {
		// Utilise fonctions provenant de la documentation WordPress (https://codex.wordpress.org/Using_Gravatars).
		$hash = md5( strtolower( trim( $email ) ) );

		// Vérification minimale si l'avatar est valide. Solution reprise de : http://qnimate.com/wordpress-check-if-gravatar-exists/.
		$validation_uri = 'http://www.gravatar.com/avatar/' . $hash . '?d=404';
		// phpcs:ignore -- Cas d'utilisation non problématique. Ignore warning.
		$headers        = @get_headers( $validation_uri );

		if ( false != $headers ) {
			if ( preg_match( '|200|', $headers[0] ) ) {
				$avatar_url = 'http://www.gravatar.com/' . $hash;
			}
		}
	}

	return $avatar_url;
}

/**
 * Méthode display_json_response
 *
 * @param string $response    La réponse en format JSON.
 * @see https://wordpress.stackexchange.com/a/184238
 */
function platform_shell_display_json_response( $response ) {

	// L'on vide le buffer d'output pour ne pas envoyer les erreurs lors des calls AJAXs.
	if ( ob_get_length() ) {
		ob_clean();
	}

	// Header pour JSON.
	header( 'Content-Type: application/json' );
	echo wp_json_encode( $response );
	wp_die();
}

/**
 * Fonction permettant de récupérer la liste des créateurs du projet.
 *
 * @param WP_Post $project    L'instance du projet.
 * @return array
 */
function platform_shell_plugin_get_creators( WP_Post $project ) {

	$creators = [
		intval( $project->post_author ),
	];

	if ( 'project' === $project->post_type ) {
		$cocreators = get_post_meta( $project->ID, 'platform_shell_meta_project_cocreators', true );

		if ( ! empty( $cocreators ) ) {

			$cocreators = explode( ',', $cocreators );

			$cocreators = get_users(
				[
					'include' => $cocreators,
					'fields'  => [
						'ID',
					],
					'orderby' => 'display_name',
				]
			);

			array_walk(
				$cocreators,
				function ( &$value ) {
						$value = intval( $value->ID );
				}
			);

			$creators = array_merge( $creators, $cocreators );
		}
	}

	return $creators;
}

/**
 * Fonction permettant de récupérer la liste des noms des créateurs du projet séparés par des virgules.
 *
 * @param WP_Post $project    L'instance du projet.
 * @return string
 */
function platform_shell_plugin_get_creators_list( WP_Post $project ) {

	$creators = platform_shell_plugin_get_creators( $project );

	array_walk(
		$creators, function ( &$creator ) {
				$creator = get_the_author_meta( 'display_name', $creator );
		}
	);

	return implode( ', ', $creators );
}
