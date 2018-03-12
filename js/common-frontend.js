/**
 * common-frontend.js
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

jQuery( document ).ready( function ( $ ) {

	jQuery( function ( $ ) {

		var jAddNewUpload = $( "#add-file-upload" );
		jAddNewUpload.attr( "href", "javascript:void( 0 )" ).click( function ( objEvent ) {
			AddNewUpload();
			objEvent.preventDefault();
			return( false );
		} );
	} );

	$( '.remove-row' ).attr( "href", "javascript:void( 0 )" );

	$( document ).on( 'click', '.remove-row', function () {
		//	supprime l'élément sélectionné
		var inputname = $( this ).attr( 'data-field' );
		jQuery( '#' + inputname ).fadeOut( 'slow' ).remove();
	} );

	$( ':file' ).on( 'fileselect', function ( event, numFiles, label ) {

		var input = $( this ).parents( '.input-group' ).find( ':text' ),
			log = numFiles > 1 ? numFiles + ' files selected' : label;

		if ( input.length ) {
			input.val( log );
		}

	} );

	// image principale - mode edit
	var featured_image = $( '#featured_image' );
	var input_featured_image = $( 'input[name="platform_shell_meta_featured_image"]' );

	if ( featured_image.length )
	{
		$( 'input[name="platform_shell_meta_featured_image"]' ).removeAttr( 'required' ).css( "display", "none" );
	}
	$( '#remove_featured' ).click( function ( objEvent ) {
		$( featured_image ).fadeOut( 'slow' ).remove();
		$( input_featured_image ).show().attr( 'required' );

	} );

	// galerie d'image secondaire - mode edit
	var input = $( 'input[name="platform_shell_meta_gallery"]' );

	$( "div.repeatable_image" ).each( function ( index, element ) {
		var parent = $( this );

		var remove_btn = $( this ).find( "a.remove-gallery_image" );
		remove_btn.attr( "href", "javascript:void( 0 )" ).click( function ( objEvent ) {

			var elem = $( element ).find( 'img' ).attr( "id" )
			// Regex pour l'id recherché suivi d'une virgule optionelle
			var regex = new RegExp(elem + ',?', 'i');
			input.val( input.val().replace( regex, "" ) );
			$( element ).fadeOut( 'slow' ).remove();

		} );
	} );

	// formulaire signalement

	$( "#flagForm" ).validate( {
		rules: {
			'options-radio': { required: true }
		},
		messages: {
			'options-radio': WP_common_frontend_script_string.choose_option,
		},
		errorPlacement: function ( error, element ) {
			switch ( element.attr( "name" ) ) {
				case 'options-radio':
					error.insertAfter( $( "div.radio-group" ) );
					break;
				default:
					error.insertAfter( element );
			}
		},
		focusCleanup: false,
		submitHandler: function ( form, event ) {
			event.preventDefault();

			var str = jQuery( '#flagForm' ).serialize();
			var form = $( '#flagForm' )[0]; // You need to use standart javascript object here

			var formData = new FormData( form );
			formData.append( 'action', 'platform_shell_action_reporting_flag_content' );

			jQuery.ajax( {
				url: WP_platform_shell_utils.ajax_url,
				data: formData,
				dataType: 'json',
				type: 'POST',
				contentType: false,
				processData: false,
				success: function ( returned_data ) {
					if ( returned_data.result == 'error' ) {
						jQuery( '#flag_modal' ).find( 'p.result' ).addClass( "alert alert-danger" ).html( returned_data.message );
					} else {
						jQuery( '#flag_modal' ).find( 'p.result' ).addClass( "alert alert-success" ).html( returned_data.message );
						jQuery( '#flagForm' ).fadeOut( 'fast' );
						jQuery( '#submit_handler' ).fadeOut( 'fast' );
					}
				}
			} );
			return false;

		}
	} );

} ); // end document ready

// This adds a new file upload to the form.
function AddNewUpload() {
	// Get a reference to the upload container.
	var jFilesContainer = jQuery( "#files" );

	// Get the file upload template.
	var jUploadTemplate = jQuery( "#element-templates div.row" );

	// Duplicate the upload template. This will give us a copy
	// of the templated element, not attached to any DOM.
	var jUpload = jUploadTemplate.clone();

	// At this point, we have an exact copy. This gives us two
	// problems; on one hand, the values are not correct. On
	// the other hand, some browsers cannot dynamically rename
	// form inputs. To get around the FORM input name issue, we
	// have to strip out the inner HTML and dynamically generate
	// it with the new values.
	var strNewHTML = jUpload.html();

	// Now, we have the HTML as a string. Let's replace the
	// template values with the correct ones. To do this, we need
	// to see how many upload elements we have so far.
	var intNewFileCount = ( jFilesContainer.find( "div.row" ).length + 1 );

	// Set the proper ID.
	jUpload.attr( "id", ( "file" + intNewFileCount ) );

	//
	strNewHTML = strNewHTML
		.replace(
			new RegExp( "::FIELD1::", "i" ), // Replacer toutes les instances de "::FIELD1::"
			( "file" + intNewFileCount )     // Le nouvel id de la division de l'uploader est fileX où X représente le # de l'uploader
		)
		.replace(
			new RegExp( "::FIELD8::", "i" ), // Replacer toutes les instances de "::FIELD1::"
			( intNewFileCount )              // Le nouvel id du input de l'uploader est incrémenté en fonction du # de l'uploader
		)
		;

	// Now that we have the new HTML, we can replace the
	// HTML of our new upload element.
	jUpload.html( strNewHTML );

	// At this point, we have a totally intialized file upload
	// node. Let's attach it to the DOM.
	jFilesContainer.append( jUpload );
}

jQuery( function ( $ ) {

	// We can attach the `fileselect` event to all file inputs on the page
	$( document ).on( 'change', ':file', function () {
		var input = $( this ),
		numFiles = input.get( 0 ).files ? input.get( 0 ).files.length : 1,
		/**
		 * Le premier REGEX remplace les instances de "\" avec "/"
		 * Le second REGEX supprime toutes les instances de "/"
		 */
		label = input.val().replace( /\\/g, '/' ).replace( /.*\//, '' );
		input.trigger( 'fileselect', [ numFiles, label ] );
	} );
} );

jQuery( function ( $ ) {

	var form = $( '#flagForm' );

	//all buttons inside 'form'
	var _buttons = $( '#submit_handler' );

	//attach a click handler
	_buttons.on( "click", function () {
		form.submit();
	} );
} );
