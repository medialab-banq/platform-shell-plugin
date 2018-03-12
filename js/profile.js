/**
 * profile.js
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

jQuery( document ).ready( function ( $ ) {
    function responseHandler( response ) {

	if ( !response.success && !response.errors ) {
	    console.log( WP_profile_script_string.unknown_save_error );
	} else {
	    if ( response.errors ) {
		console.log( response.errors.join( '\n' ) )
	    }
	    if ( response.validation_errors ) {
		console.log( response.errors.join( '\n' ) )
	    }
	}

	// Mécanisme de redirection (ex. retourner vue consultation après succès changement).
	if ( response.redirect_to ) {
		window.location.href = response.redirect_to;
	}
    }
    if ($( "#form_edit_profile" ).length) {
	$( "#form_edit_profile" ).validate( {
	    rules: {
		    accept_terms: "required",
	    },
	    messages: {
		    accept_terms: WP_profile_script_string.validation_error_accept_missing,
	    },
	    errorPlacement: function ( error, element ) {
		switch ( element.attr( "name" ) ) {
			case 'accept_terms':
				error.insertAfter( $( "#terms" ) );
				break;
			default:
				error.insertAfter( element);
		}
	    },
	    debug:true,
	    focusCleanup: false,
	    submitHandler: function ( form, event ) {
		event.preventDefault();
		$( "#form_edit_profile input[type=submit]" ).attr('disabled' , 'disabled');
		var formData = new FormData( form );
		$.ajax( {
		    url: WP_platform_shell_utils.ajax_url,
		    data: formData,
		    dataType: 'json',
		    type: 'POST',
		    contentType: false,
		    processData: false,
		    success: function ( response ) {
			responseHandler( response );
			$("#form_edit_profile input[type=submit]").removeAttr('disabled');
		    },
		    error: function ( response ) {
			responseHandler( response );
			$("#form_edit_profile input[type=submit]").removeAttr('disabled');
		    }
		} );
		return false;
	    }
	} );
    }

    // Ajout d'une règle particulière pour le nickname / pseudonyme (validation serveur).
    // Cette validation devra quand même être refaite du côté backend.
    $( "#platform_shell_profile_nickname" ).rules( "add", {
	minlength: (typeof WP_profile_configs !== 'undefined')  && WP_profile_configs.min_pseudo_length ? parseInt(WP_profile_configs.min_pseudo_length) : 3,
	maxlength: (typeof WP_profile_configs !== 'undefined')  && WP_profile_configs.max_pseudo_length ? parseInt(WP_profile_configs.max_pseudo_length) : 50,
	required: true,
	remote: {
	    url: WP_platform_shell_utils.ajax_url,
	    type: 'post',
	    data: {
		action: 'platform_shell_validate_nickname_handler',
		'platform_shell_profile_nickname': function() {
		    return $('input[name=platform_shell_profile_nickname]' ).val();
		}
	    }
	}
    });
} );