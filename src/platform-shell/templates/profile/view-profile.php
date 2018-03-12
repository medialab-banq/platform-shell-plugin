<?php
/**
 * Vue du profil.
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Ne pas permettre l'exécution directe de ce fichier.
}
use Platform_Shell\Profile;
use Platform_Shell\Admin\Admin_Notices;
if ( ! isset( $profile_user ) ) {
	throw new Exception( _x( 'ERREUR_DEV : La variable profile_user doit être définie. Revalider appel du template.', 'template-profile-view', 'platform-shell-plugin' ) );
	exit();
}
?>
<?php get_header(); ?>
<section class="contenu row fondGris">
	<div class="col-xs-12">
		<div class="lienEntete"><?php echo do_shortcode( '[platform_shell_reporting]' ); ?></div>
	</div>
	<!-- Message Alert Succes -->
	<?php
	$admin_notices = new Admin_Notices( 'PROFILE', $profile_user->ID );
	$admin_notices->show_frontend_notices();
	$current_user_id         = get_current_user_id();
	$is_current_user_profile = ( is_user_logged_in() && $profile_user->ID == $current_user_id );

	$gravatar_uri = '';

	// Information publique.
	$use_gravatar_option = platform_shell_get_option( 'platform_shell_option_profile_use_gravatar', 'platform_shell_settings_main_accounts', 'off' );

	if ( 'on' === $use_gravatar_option ) {
		// Mettre en fonction commune. Première implémentation test.
		$gravatar_uri = platform_shell_get_gravatar_url( $profile_user->ID );
	}
	?>
	<div class="col-sm-3 hidden-xs">
		<?php
		$avatar = get_avatar( $profile_user->ID, 150, '', '', array( 'class' => 'img-circle' ) );
		// Enrober l'avatar avec un lien s'il y a un lien gravatar.
		if ( ! empty( $gravatar_uri ) ) {
			$avatar = '<a target="_blank" href="' . $gravatar_uri . '">' . $avatar . '</a>';
		}
		echo $avatar;
		?>
	</div>
	<div class="col-sm-9 row col-xs-12">
		<div class="col-sm-8 col-xs-12">
			<?php
			$avatar_2 = '<div class="pull-left visible-xs-inline">' . get_avatar( $profile_user->ID, 40, '', '', array( 'class' => ' img-circle' ) ) . '</div>';

			// Enrober l'avatar avec un lien s'il y a un lien gravatar.
			if ( ! empty( $gravatar_uri ) ) {
				$avatar_2 = '<a target="_blank" href="' . $gravatar_uri . '">' . $avatar_2 . '</a>';
			}

			$avatar_h2_display = '<div>' . $avatar_2 . '<h2>' . $profile_user->display_name . '</h2></div>';
			echo $avatar_h2_display;

			if ( $is_current_user_profile ) {
				/* translators: %1$s: Prénom de l'utilisateur */
				echo '<p>' . sprintf( _x( 'Prénom : %1$s', 'template-profile-view', 'platform-shell-plugin' ), get_user_meta( $profile_user->ID, 'first_name', true ) ) . '</p>';
				/* translators: %1$s: Nom de l'utilisateur */
				echo '<p>' . sprintf( _x( 'Nom : %1$s', 'template-profile-view', 'platform-shell-plugin' ), get_user_meta( $profile_user->ID, 'last_name', true ) ) . '</p>';

				/* translators: %s: Courriel de l'utilisateur */
				echo '<p>' . sprintf( _x( 'Courriel : %1$s', 'template-profile-view', 'platform-shell-plugin' ), platform_shell_get_profile_email_text( $current_user_id ) ) . '</p>';
			}

			?>
		</div>
		<div class="col-sm-4 col-xs-12">
			<?php
			if ( $is_current_user_profile ) {
				$edit_profile_url = Profile::get_profile_url( $profile_user->ID, true /* edit */ );
				echo '<p><a href="' . $edit_profile_url . '" class="btn btn-primary" role="button">' . _x( 'Modifier mon profil', 'template-profile-view', 'platform-shell-plugin' ) . '</a></p>';
			}
			?>
		</div>
		<div class="col-sm-4 col-xs-12">
			<div class="concoursMots">
				<?php platform_shell_the_tags( $profile_user->ID, 'profiles_tags' ); ?>
			</div>
		</div>
	</div>
</div>
</section>
<!-- INCLURE ICI LA SECTION CONTENANT LES PROJETS LIES À CET UTILISATEUR -->
<section class="contenu row">
	<article id="accueilProjets" class="col-lg-9 col-sm-8 col-xs-12 listeProjets" >
		<?php
		// todo_refactoring : déplacer maximum de code hors du template.
		$get_query_function = function() use ( &$profile_user, &$is_current_user_profile ) {
			// si connecté et est profil de l'utilisateur, il faut afficher les projets non publiés.
			$show_unpublished = $is_current_user_profile; /* Afficher les projets non-publiés si on affiche le profil de l'usager connecté. */
			return platform_shell_theme_get_projects_list_by_user_query( $profile_user->ID, $show_unpublished );
		};
		$params_array = [
			'title'                    => _x( 'Mes projets', 'template-profile-view', 'platform-shell-plugin' ),
			'allItems'                 => _x( 'Tous les projets', 'template-profile-view', 'platform-shell-plugin' ),
			'noItems'                  => _x( 'Aucun projet disponible', 'template-profile-view', 'platform-shell-plugin' ),
			'get_query_function_name'  => $get_query_function,
			'get_query_function_param' => $profile_user->ID,
			'the_tile_function_name'   => 'platform_shell_theme_the_project_tile',
			'itemsListUrl'             => null,
			'displayContext'           => 'profile',
		];
		platform_shell_theme_the_tile_container( $params_array );
		?>
	</article>
</section>
<?php
	get_footer();
