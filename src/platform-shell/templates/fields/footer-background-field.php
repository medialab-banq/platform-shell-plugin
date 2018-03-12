<?php
/**
 * Champ de sélection du fond d'écran pour le pied de page
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
<input type="text" class="regular-text wpsa-url footer-background-field" id="<?php echo $id; ?>" name="<?php echo $label_for; ?>" value="<?php echo $value; ?>">
<input type="button" class="button wpsa-browse" value="<?php echo $options['button_label']; ?>">
<input type="button" class="button" value="<?php echo esc_html_x( 'Générer une image de fond', 'option-generate-background', 'platform-shell-plugin' ); ?>" id="<?php echo $id; ?>_generator">
<p class="description">
	<?php echo $desc; ?>
</p>
<div id="<?php echo $id; ?>_generator_modal" class="hidden">
	<div style="width:calc(100vw - 220px);max-width:1400px;overflow-x:visible;overflow-y:auto;height:600px;">
		<div id="<?php echo $id; ?>_generator_search">
			<div class="no-geocode" style="width:calc(100% - 44px);margin:10px 0px 20px 0px;border-left: 4px solid #fff;box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);border-left-color: #dc3232;padding: 10px 20px;background-color: #efefef;">
				<?php echo esc_html_x( 'La fonctionnalité de recherche d\'adresse a été désactivée. Veuillez entrer une clé d\'API dans l’écran précédent afin de pouvoir utiliser l\'outil de recherche.', 'generate-background-warning', 'platform-shell-plugin' ); ?>
			</div>
			<div style="width:100%;margin:10px 0px;">
				<input class="regular-text hidden" type='text' id='<?php echo $id; ?>_generator_form_searchvalue'>
				<input class="button hidden" type='button' id='<?php echo $id; ?>_generator_form_search' value='<?php echo esc_html_x( 'Faire une recherche d’adresse', 'generate-background-search', 'platform-shell-plugin' ); ?>'>
				<input class="button" type='button' id='<?php echo $id; ?>_generator_form_fetch' value='<?php echo esc_html_x( 'Obtenir l’image', 'generate-background-fetch', 'platform-shell-plugin' ); ?>'>
			</div>
			<div style="width:100%;margin:0px 0px 10px 0px;">
				<ol>
					<li class="has-geocode">
					<?php
					echo esc_html_x( 'Faites une recherche en tapant dans la boîte l’adresse du lieu de votre médialab.', 'generate-background-instructions-1', 'platform-shell-plugin' );
					?>
					</li>
					<li>
					<?php
					echo esc_html_x( 'Déplacer la carte de manière à positionner le marqueur orange à la droite de l’image sur l’emplacement du lieu de votre médialab.', 'generate-background-instructions-2', 'platform-shell-plugin' );
					?>
					</li>
					<li>
					<?php
					echo esc_html_x( 'Cliquez sur « Obtenir l’image » pour passer à l’étape suivante.', 'generate-background-instructions-3', 'platform-shell-plugin' );
					?>
					</li>
				</ol>
			</div>
			<div id="<?php echo $id; ?>_generator_map" style="width:1400px;height:377px;margin:auto;"></div>
		</div>
		<div id="<?php echo $id; ?>_generator_loading" class="hidden">
			<div style="width:100%;height:422px;margin:auto;">
				<h3 style="text-align:center;"><?php echo esc_html_x( 'Génération de l’image de fond en cours; veuillez patienter.', 'loading', 'platform-shell-plugin' ); ?></h3>
			</div>
		</div>
		<div id="<?php echo $id; ?>_generator_results" class="hidden">
			<div style="width:auto;margin:10px 0px;">
				<input class="button" type='button' id='<?php echo $id; ?>_generator_form_accept' value='<?php echo esc_html_x( 'Choisir l’image', 'generate-background-select', 'platform-shell-plugin' ); ?>'>
				<input class="button" type='button' id='<?php echo $id; ?>_generator_form_reset' value='<?php echo esc_html_x( 'Recommencer', 'generate-background-reset', 'platform-shell-plugin' ); ?>'>
			</div>
			<div style="width:100%;margin:0px 0px 10px 0px;">
				<ul style="display: block;list-style-type: disc;margin-top: 1em;margin-bottom: 1 em;margin-left: 2em;margin-right: 0;padding-left: 0px;">
					<li>
					<?php
					echo esc_html_x( 'Cliquez sur « Choisir l’image » pour sélectionner l’image.', 'generate-background-instructions-4', 'platform-shell-plugin' );
					?>
					</li>
					<li>
					<?php
					echo esc_html_x( 'Cliquez sur « Recommencer » pour retourner à l’étape précédente.', 'generate-background-instructions-5', 'platform-shell-plugin' );
					?>
					</li>
				</ul>
			</div>
			<div id="<?php echo $id; ?>_generator_img" style="width:1400px;height:377px;margin:auto;">
				<img src="<?php echo $value; ?>">
			</div>
		</div>
	</div>
</div>
