<?php
/**
 * Formulaire de signalement.
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Ne pas permettre l'exécution directe de ce fichier.
}
?>

<ul>
	<li><a href="#" id="flagArticle" role="button" data-toggle="modal" data-target="#flag_form"><i class="fa fa-flag-o" aria-hidden="true"></i> <span class="visible-md-inline visible-lg-inline"><?php echo esc_html_x( 'Signaler', 'template-reporting-fill-form', 'platform-shell-plugin' ); ?></span></a></li>
</ul>

<div class="modal fade" id="flag_form" tabindex="-1" role="dialog">
	<div id="flag_modal" class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo esc_html( $title ); ?></h4>
			</div>
			<div class="modal-body">
				<form id="flagForm" name="flag_form" enctype="multipart/form-data" >
					<div class="radio-group">
						<?php foreach ( $checkboxes as $key => $input ) : ?>
							<p> <input type="radio" name="options-radio" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>"><label><?php echo esc_html( $input ); ?>
								</label></p>
						<?php endforeach; ?>
					</div>
					<p><textarea id="other_field" name="other" maxlength="1200"></textarea></p>
					<p><input type="hidden" name="pid" value="<?php echo esc_attr( $pid ); ?>" /></p>
					<p><input type="hidden" name="ptype" value="<?php echo esc_attr( $post_type ); ?>" /></p>
				</form>
				<p class="result"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo esc_html_x( 'Fermer', 'template-reporting-fill-form', 'platform-shell-plugin' ); ?></button>
				<button id="submit_handler" type="button" class="btn btn-primary"><?php echo esc_html_x( 'Signaler', 'template-reporting-fill-form', 'platform-shell-plugin' ); ?></button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<?php
