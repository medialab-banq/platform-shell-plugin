<?php
/**
 * Formulaire d'édition du profil.
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
use Platform_Shell\Fields\Fields_Helper;

if ( ! isset( $profile_user ) ) {
	throw new Exception( 'ERREUR_DEV : La variable profile_user doit être définie. Revalider appel du template.', 'template-profile-edit-form', 'platform-shell-plugin' );
}

get_header();

$nonce_key = 'save_profile_' . $profile_user->ID;

// Information publique.
$use_gravatar_option = platform_shell_get_option( 'platform_shell_option_profile_use_gravatar', 'platform_shell_settings_main_accounts', 'off' );

if ( 'on' === $use_gravatar_option ) {
	// Mettre en fonction commune. Première implémentation test.
	$gravatar_uri = platform_shell_get_gravatar_url( $profile_user->ID );
}

?>
<section class="contenu row">
	<form id="form_edit_profile" class="platform_shell_form edit_profile" method="post">
		<div class="col-sm-3 hidden-xs">
			<?php
			$avatar = get_avatar( $profile_user->ID, 150, '', '', array( 'class' => 'img-circle' ) );

			if ( ! empty( $gravatar_uri ) ) {
				$avatar = '<a target="_blank" href="' . $gravatar_uri . '">' . $avatar . '</a>';
			}

			echo $avatar;
			?>
		</div>
		<div class="col-sm-9 row col-xs-12">
			<div class="col-xs-12">
				<?php
				// Insertion icône adaptif.
				$avatar_2_modif = '<div class="pull-left visible-xs-inline">' . get_avatar( $profile_user->ID, 40, '', '', array( 'class' => ' img-circle' ) ) . '</div>';

				// Enrober l'avatar avec un lien s'il y a un lien gravatar.
				if ( ! empty( $gravatar_uri ) ) {
					$avatar_2_modif = '<a target="_blank" href="' . $gravatar_uri . '">' . $avatar_2_modif . '</a>';
				}

				$avatar_2_modif_display = '<div>' . $avatar_2_modif . '<h2>' . _x( 'Modification du profil', 'template-profile-edit-form', 'platform-shell-plugin' ) . '</h2></div>';
				echo $avatar_2_modif_display;

				if ( 'on' === $use_gravatar_option ) {

					$currentlocale        = get_locale();
					$localcomponent       = explode( '_', $currentlocale );
					$baselang             = $localcomponent[0]; // extraire fr de fr_CA par ex.
					$gravatar_service_url = 'https://' . $baselang . '.gravatar.com/';

					$small_avatar = get_avatar( $profile_user->ID, 20, '', '', array( 'class' => 'img-circle' ) );

					$uri = platform_shell_get_gravatar_url( $profile_user->ID );
					if ( ! empty( $uri ) ) {
						/* translators: 1$s avatar miniature, %2$s: Lien vers le profil gravatar de l'utilisateur. */
						$gravatar_message = sprintf( _x( 'Pour modifier ton avatar %1$s, <a href="%2$s" target="_blank">clique ici</a>.', 'template-profile-view', 'platform-shell-plugin' ), $small_avatar, $uri );
					} else {
						/* translators: 1$s avatar miniature, %2$s: Lien vers le  gravatar pour l'inscription. */
						$gravatar_message = sprintf( _x( 'Afin de pouvoir personnaliser ton avatar %1$s, tu dois créer un compte sur Gravatar en utilisant le même courriel que celui apparaissant dans ton profil. Tu peux le faire en <a href="%2$s" target="_blank">cliquant ici</a>.', 'template-profile-edit-form', 'platform-shell-plugin' ), $small_avatar, $gravatar_service_url );
					}

					echo '<div class="form-row "><label for="platform_shell_gravatar">' . _x( 'Avatar', 'template-profile-edit-form', 'platform-shell-plugin' ) . '</label> ' . $gravatar_message . '</div>';
				}

				// Déterminer data de user et passer par param.
				$form_fields = Profile::get_edit_profile_fields( $profile_user );
				foreach ( $form_fields as $field ) {

					if ( isset( $field['value'] ) ) {
						$meta = $field['value'];
					} else {
						$meta = ''; // Default.
					}

					Fields_Helper::set_frontend_fields( $field, $meta );
				}
				?>
				<?php platform_shell_get_reglement_checkbox(); ?>
				<p>
					<?php wp_nonce_field( $nonce_key, 'save_profile' ); ?>
					<input id="save_button" type="submit" class="btn btn-primary" name="save_profile_button" value="<?php echo esc_attr( _x( 'Enregistrer', 'template-profile-edit-form', 'platform-shell-plugin' ) ); ?>" />
					<input type="hidden" name="action"  value="platform_shell_edit_profile_handler" />
					<input type="hidden" name="user_id"  value="<?php echo $profile_user->ID; ?>" />
				</p>
			</div>
		</div>
	</form>
</section>
<?php
	get_footer();
