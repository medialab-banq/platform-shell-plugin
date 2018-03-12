/**
 * footer-image-generator-backend.js
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

jQuery(document).ready(function($) {

	var map;

	var cleared_map = true;

	var origin   = [ 0, 0 ];
	var montreal = [45.51525, -73.56148];
	var current  = montreal;

	var title = footer_image_generator.title;
	var noncesave = footer_image_generator.noncesave;
	var noncecreate = footer_image_generator.noncecreate;
	var pinlocation = footer_image_generator.pinlocation;
	var contributors_label = footer_image_generator.contributors_label;

	var gmap_key = '';

	jQuery('.footer-background-field').each( function( index, value ) {
		var field_id = jQuery( value ).attr('id');
		map_widget( field_id );
	});

	function map_widget( field_id ) {

		// Initialise the dialog
		jQuery('#' + field_id + '_generator_modal').dialog({
			title: title,
			dialogClass: 'wp-dialog',
			autoOpen: false,
			draggable: false,
			width: 'auto',
			modal: true,
			resizable: false,
			closeOnEscape: true,
			position: {
				my: "center",
				at: "center+80 center",
				of: window,
				within: "#wpbody-content",
				colision: "flipfit"
			},
			open: function() {
			    			    
				// close dialog by clicking the overlay behind it
				jQuery('.ui-widget-overlay').bind('click', function() {
					jQuery('#my-dialog').dialog('close');
				});

				gmap_key = jQuery('#platform-shell-settings-page-site-sections-home\\[platform_shell_gmap_key\\]').val();

				if (gmap_key.length > 0) {

					jQuery(".no-geocode").addClass('hidden');
					jQuery(".has-geocode").removeClass('hidden');
					jQuery("#" + field_id + "_generator_form_search").removeClass('hidden');
					jQuery("#" + field_id + "_generator_form_searchvalue").removeClass('hidden');

				} else {

					jQuery(".has-geocode").addClass('hidden');
					jQuery(".no-geocode").removeClass('hidden');
					jQuery("#" + field_id + "_generator_form_search").addClass('hidden');
					jQuery("#" + field_id + "_generator_form_searchvalue").addClass('hidden');
				}

				load_map(field_id + '_generator_map');
			},
			create: function() {
				// style fix for WordPress admin
				jQuery('.ui-dialog-titlebar-close').addClass('ui-button');			
			},
			close: function( event, ui ) {
				jQuery("#" + field_id + "_generator_search").removeClass('hidden');
				jQuery("#" + field_id + "_generator_results").addClass('hidden');
				jQuery("#" + field_id + "_generator_loading").addClass('hidden');
				clear_map( field_id );
			}
		})

		jQuery( "#" + field_id + "_generator_form_accept" ).click( function (e) {
		    
			setTimeout( function() {
				jQuery("#" + field_id + "_generator_results").addClass('hidden');
				jQuery("#" + field_id + "_generator_loading").removeClass('hidden');
			}, 0);

			jQuery.ajax({
				url: ajaxurl,
				method: 'POST',
				data: {
					action: field_id + '_generate_save',
					image: jQuery( '#' + field_id + '_generator_img img' ).attr( 'src' ),
					position: {
						lat: current.lat,
						lng: current.lng
					},
					security: noncesave
				}
			}).success( function ( data ) {
				jQuery( '#' + field_id + '' ).val( data );
				jQuery( '#' + field_id + '_generator_modal' ).dialog( 'close' );
			});
		});

		jQuery( "#" + field_id + "_generator_form_fetch" ).click( function (e) {

			// Masquer les boutons de zoom qui ne doivent pas apparaitre dans l'image capturée.
			// Solution la plus simple de : https://stackoverflow.com/questions/16537326/leafletjs-how-to-remove-the-zoom-control
			$(".leaflet-control-zoom").css("visibility", "hidden");

			html2canvas( jQuery( '#' + field_id + '_generator_map' )[0] ).then(function(canvas) {
				jQuery( '#' + field_id + '_generator_img' ).empty();
				var image = Canvas2Image.convertToJPEG( canvas, 1400, 377 );

				setTimeout( function() {
					jQuery("#" + field_id + "_generator_search").addClass('hidden');
					jQuery("#" + field_id + "_generator_loading").removeClass('hidden');
					clear_map( field_id );
				}, 0);

				jQuery.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: field_id + '_generate_image',
						image: jQuery( image ).attr( 'src' ),
						security: noncecreate
					}
				}).success( function ( data ) {

					jQuery("#" + field_id + "_generator_loading").addClass('hidden');
					jQuery("#" + field_id + "_generator_results").removeClass('hidden');

					var imageElement = document.createElement('img');
					jQuery( imageElement ).attr( 'src', data ).appendTo( jQuery( '#' + field_id + '_generator_img' ) );
				});
			});
		});

		jQuery( "#" + field_id + "_generator_form_reset" ).click( function (e) {
			jQuery("#" + field_id + "_generator_search").removeClass('hidden');
			jQuery("#" + field_id + "_generator_results").addClass('hidden');
			load_map(field_id + '_generator_map');
		});

		jQuery( "#" + field_id + "_generator_form_search" ).click( function (e) {

			jQuery.ajax({
				url: 'https://maps.googleapis.com/maps/api/geocode/json',
				data: {
					address: jQuery( '#' + field_id + '_generator_form_searchvalue' ).val(),
					key: gmap_key
				}
			}).success( function ( data ) {
				if ( data.results.length > 0 ) {
					moveTo( data.results[0].geometry.location );
				} else {
					alert( 'Cannot find location.' );
				}
			});
		});


		// bind a button or a link to open the dialog
		jQuery('#' + field_id + '_generator').click(function(e) {
			e.preventDefault();
			jQuery('#' + field_id + '_generator_modal').dialog('open');
		});
	}

	function clear_map(id) {
		jQuery('#' + id + '_generator_map').empty();

		if (!cleared_map) {
			map.remove();
			cleared_map = true;
		}
	}

	function get_offset( coordinates, offset ) {

		var coordinatespoint = map.latLngToLayerPoint( coordinates );
		var offsetpoint = {
			x: coordinatespoint.x + offset.x,
			y: coordinatespoint.y + offset.y
		};

		return map.layerPointToLatLng( offsetpoint )
	}

	function get_offset_from_center( destination ) {

		return get_offset(
			destination,
			// Le centre est 373px à la gauche et 24 px en haut de la location recherchée.
			{
				x: -373,
				y: -24
			}
		);
	}

	function get_marker_offset() {

		return get_offset(
			map.getCenter(),
			// Le marqueur est 373px à la droite, et 24px en bas du centre de la carte.
			{
				x: 373,
				y: 24
			}
		);
	}

	function moveTo( destination ) {
		map.setView( get_offset_from_center( destination ) );
	}

	function load_map(id) {
	    
		cleared_map = false;

		map = new L.Map(id, {  fadeAnimation: false, zoomControl:true, inertia: false }).setView(origin, 18);

		map.doubleClickZoom.disable();
		map.keyboard.disable();

		var marker = L.marker( origin, {
			icon: L.icon({
				iconUrl: pinlocation,
				iconSize: [ 26, 38 ],
				iconAnchor: [ 13, 38 ]
			})
		} ).addTo(map);

		L.tileLayer.grayscale('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> ' + contributors_label}).addTo(map);

		map.on( 'move', function (e) {

			current = get_marker_offset();
			marker.setLatLng(current);
		} );

		moveTo( current );
		
		// Ajustement approximatif pour conserver mention de copyright visible en tout temps (ex. écran 320px).
		$attribution_parent = jQuery('.leaflet-control-attribution').parent();
		$attribution_parent.removeClass('leaflet-right');
		$attribution_parent.addClass('leaflet-left');
		$attribution_parent.css('margin-left', '600px');
	}
});