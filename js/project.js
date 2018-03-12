/**
.* project.js
.*
.* @package     Platform-Shell
.* @author      Bibliothèque et Archives nationales du Québec (BAnQ)
.* @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
.* @license     GPL-2.0 or (at your option) any later version
.*/

jQuery( document ).ready( function ( $ ) {

	$.fn.select2.defaults.set('language', select2_strings.locale);

	$.validator.addMethod( 'filesize', function ( value, element, param ) {
		// param = size (in bytes)
		// element = element to validate (<input>)
		// value = value of the element (file name)
		return this.optional( element ) || ( element.files[0].size <= param )
	} );

	$( "#form_project_details" ).validate( {
		rules: {
			accept_terms: "required",
		},
		messages: {
			platform_shell_meta_featured_image: {
				accept: WP_project_script_string.validation_error_featured_missing,
			},
			accept_terms: WP_project_script_string.validation_error_accept_missing,
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
		focusCleanup: false,
		submitHandler: function ( form, event ) {
			event.preventDefault();

			$( "#form_project_details input[type=submit]" ).attr( 'disabled', 'disabled' );

			var form = $( '#form_project_details' )[0]; // You need to use standart javascript object here

			var post_content         = '';
			var post_content_mce     = tinymce.get( 'platform_shell_meta_project_description' );
			var post_content_tag     = $( 'textarea[name=platform_shell_meta_project_description]' );
			var creative_process     = '';
			var creative_process_mce = tinymce.get( 'platform_shell_meta_project_creative_process' );
			var creative_process_tag = $( 'textarea[name=platform_shell_meta_project_creative_process]' );

			if ( null != post_content_mce && 'true' === post_content_tag.attr( 'aria-hidden' ) ) {
				post_content = post_content_mce.getContent();
			} else {
				post_content = post_content_tag.val();
			}

			if ( null != creative_process_mce && 'true' === creative_process_tag.attr( 'aria-hidden' ) ) {
				creative_process = creative_process_mce.getContent();
			} else {
				creative_process = creative_process_tag.val();
			}

			var formData = new FormData( form );
			formData.append( 'featured_image', $( 'input[name=platform_shell_meta_featured_image]' )[0].files[0] );
			formData.append( 'action', 'platform_shell_action_add_project' );
			formData.append( 'post_content', post_content );
			formData.append( 'creative_process', creative_process );

			$.ajax( {
				url: WP_platform_shell_utils.ajax_url,
				data: formData,
				dataType: 'json',
				type: 'POST',
				contentType: false,
				processData: false,
				success: function ( response ) {
					if ( response === 0 || response === null ) {

						$( 'div.response' ).removeClass( "alert alert-success" ).fadeIn().addClass( "alert alert-danger" ).html( "" ).append( WP_project_script_string.unexpected_error );
						$( 'html, body' ).animate( {
							scrollTop: $( "div.response" ).offset().top
						}, 600 );
						$( "#form_project_details input[type=submit]" ).removeAttr( 'disabled' );
					}

					if ( response.success ) {
						window.location.href = response.success.href;
					}

					if ( response.errors ) {

						var message = '';
						if ( typeof response.errors.images != 'undefined' ) {
							message += response.errors.images.join( '<br />' );
						}

						if ( typeof response.errors.unexpected_errors != 'undefined' ) {

							if ('' !== message) {
								message += '<br />';
							}

							message += response.errors.unexpected_errors.join( '<br />' );
						}

						$( 'div.response' ).removeClass( "alert alert-success" ).fadeIn().addClass( "alert alert-danger" ).html( "" ).append(  message );
						$( 'html, body' ).animate( {
							scrollTop: $( "div.response" ).offset().top
						}, 600 );
						$( "#form_project_details input[type=submit]" ).removeAttr( 'disabled' );
					}

				},
				error: function ( jqXHR, error, errorThrown ) {
					var response = jqXHR.responseJSON;

					$( "#form_project_details input[type=submit]" ).removeAttr( 'disabled' );

					if ( response && response.errors ) {

						var message = '';
						if ( typeof response.errors.images != 'undefined' ) {
							message += response.errors.images.join( '<BR />' );
						}

						if ( typeof response.errors.unexpected_errors != 'undefined' ) {
							message += response.errors.unexpected_errors.join( '<BR />' );
						}

						$( 'div.response' ).removeClass( "alert alert-success" ).fadeIn().addClass( "alert alert-danger" ).html( "" ).append(  message );
					} else {
						$( 'div.response' ).removeClass( "alert alert-success" ).fadeIn().addClass( "alert alert-danger" ).html( "" ).append( WP_project_script_string.unexpected_error );
					}

					$( 'html, body' ).animate( { scrollTop: $( "div.response" ).offset().top }, 600 );

				}
			} )
			return false;
		}
	} );

	jQuery('select#platform_shell_meta_project_cocreators').select2({
		closeOnSelect: true,
		placeholder: select2_strings.coauthor_default_text,
		minimumInputLength: 3,
		width: '100%',
		ajax: {
			delay: 250,
			url: WP_platform_shell_utils.ajax_url,
			dataType: 'json',
			type: 'POST',
			data: function (params) {
				var selected_values = jQuery('select#platform_shell_meta_project_cocreators').select2('data');
				selected_values = selected_values.map( function ( value ) {
					return parseInt( value.id )
				} );
				var queryParameters = {
					action: 'platform_shell_action_search_users',
					nonce: project_strings.user_search_nonce,
					query: params.term,
					author: jQuery('select#platform_shell_meta_project_cocreators').attr('data-author'),
					selected: selected_values
				}

				return queryParameters;
			}
		}
	});

	if ('group-creation' === jQuery('#platform_shell_meta_project_creation_type').val()) {
		jQuery('.form-row.cocreators').removeClass('hidden');
		jQuery('.form-row.cocreators .select2-container--default .select2-search--inline .select2-search__field').css( 'width', '100%' );
	}

	jQuery('#platform_shell_meta_project_creation_type').change(function () {
		if ('group-creation' === jQuery('#platform_shell_meta_project_creation_type').val()) {
			jQuery('.form-row.cocreators').removeClass('hidden');
			jQuery('.form-row.cocreators .select2-container--default .select2-selection--multiple .select2-selection__rendered li.select2-search--inline .select2-search__field').css( 'width', '320px' );
		} else {
			jQuery('.form-row.cocreators').addClass('hidden');
		}
	});
} );
