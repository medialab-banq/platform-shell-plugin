<?php
/**
 * Formulaire de création / Edit de projet.
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$nonce_key = 'save_project_details_' . ( is_null( $project_id ) ? 'new' : $project_id );

?>
<!-- PLACEHOLDER. DONT REMOVE. -->
<div class="response " style="display:none;"> </div>
<?php
/* todo_refactoring: À REVOIR. */

if ( ! is_null( $project_id ) && is_null( $action ) ) {

	$project_id = null;
}
?>
<form id="form_project_details" class="platform_shell_form add_project" method="post" enctype="multipart/form-data" >
	<?php
		platform_shell_get_project_form_fields( $action, $project_id );
	?>
	<div class="clear"></div>
	<p>
		<?php platform_shell_get_reglement_checkbox(); ?>
		<?php wp_nonce_field( $nonce_key, 'save_project_details' ); ?>
		<input type="submit" class="btn btn-primary" name="save_project_details_button" value="<?php echo esc_attr( _x( 'Enregistrer mon projet', 'template-project-edit-form', 'platform-shell-plugin' ) ); ?>" />
		<input type="hidden" name="action"  value="platform_shell_action_add_project" />
		<?php if ( ! is_null( $project_id ) ) : ?>
			<input type="hidden" name="project_id"  value="<?php echo esc_attr( $project_id ); ?>" />
		<?php endif; ?>
	</p>
</form>
<?php
