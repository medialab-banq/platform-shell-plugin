/**
 * contest.js
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

jQuery( document ).ready( function ( $ ) {

	if ( $( "#form_subscribe_project" ).length ) {
		$( "#form_subscribe_project" ).validate( {
			rules: {
				accept_terms: "required",
			},
			messages: {
				accept_terms: WP_contest_script_string.validation_error_accept_missing,
				p_id: WP_contest_script_string.validation_error_project_required
			},
			errorPlacement: function ( error, element ) {
				switch ( element.attr( "name" ) ) {
					case 'accept_terms':
						error.insertAfter( $( "#terms" ) );
						break;
					default:
						error.insertAfter( element );
				}
			},
			debug: true,
			focusCleanup: false,
			submitHandler: function ( form ) {

				var str = jQuery( '#form_subscribe_project' ).serialize();

				// les données et l'action AJAX
				data = {
					action: 'platform_shell_action_subscribe_project',
					form_data: str
				}

				// Send it over to WordPress.
				$.post( WP_platform_shell_utils.ajax_url, data, function ( response ) {
					if ( response.result == 'error' ) {
						$( 'div.response' ).removeClass( "alert alert-success" ).fadeIn().addClass( "alert alert-danger" ).html( "" ).append( response.message );

					}
					if ( response.result == 'success' ) {
						$( 'div.response' ).removeClass( "alert alert-danger" ).fadeIn().addClass( "alert alert-success" ).html( "" ).append( response.message );

					}

				} )
			}
		} );
	}
} );