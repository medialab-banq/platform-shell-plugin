/**
 * common-backend.js
 *
 * @package     Platform-Shell
 * @author      Bibliothèque et Archives nationales du Québec (BAnQ)
 * @copyright   2018 Bibliothèque et Archives nationales du Québec (BAnQ)
 * @license     GPL-2.0 or (at your option) any later version
 */

jQuery( document ).ready( function ( $ ) {

    // Initialiser les "date picker".
    // Important :
    // Bug jquery (à réinvestiguer), utiliser le format yyyy-mm-dd affiche deux fois l'année, ex. 201620160839
    // Il faut utiliser le format à deux position pour avoir l'année une seule fois.
    // Voir par ex. http://stackoverflow.com/questions/5633540/cforms-js-datepicker-inserts-year-twice
    // Bug d'affichage des textes en anglais devrait être corrigé par 4.6. Voir par ex. https://make.wordpress.org/core/2016/07/06/jquery-ui-datepicker-localization-in-4-6/
    // Le Date picker ne fonctionne pas sur IOS.

    // Modernizer existe dans le thème "ephrem" pas mais pas dans adnin.
    // - Utiliser widget par défaut pour IOS mais il faudrait un contrôle sur le format de date.

    /*
     * Idéalement il faudrait dissocier le format de date qui voyage dans le système
     * des formats d'affichage mais le traitement est difficile à uniformiser :
     * Wordpress utilise datetime de mySQl mais ce format n'est pas connu de datepicker.
     * L'utilisation de altFormat et altField ne permet pas vraiment de solutionner le problème
     * puisque la valeur initialie ne semble pas récupérée de ce champs.
     *
     * Pour simplifier, le format reçu et renvoyé doit correspondre au format défini ici.
     *
     * Si jamais il fallait avoir un autre format d'affichage en anglais par ex.
     * Il faudra s'assurer que le backend envois les données dans le bon format.
     * (le dateFormat pourrait être envoyé par le backend).
     *
     * Au niveau du backend, les données doivent absolument être normalisées et ne devraient
     * pas dépendre de la locale.
     *
     */

    $( "#post" ).validate( {
        focusCleanup: false,
        errorPlacement: function ( error, element ) {
            error.appendTo( element.parent( "td" ) );
        }
    } );
} );

/*
 fonction de upload multiple de la galerie d'images secondaires pour les concours
 cette fonction utilise la galerie de Medias native de WordPress wp.media
 */
jQuery( function ( $ ) {
    // Product gallery file uploads
    var product_gallery_frame;
    var $image_gallery_ids = $( '#platform_shell_meta_gallery' );
    var $product_images = $( '#product_images_container' ).find( 'ul.product_images' );

    jQuery( '.add_product_images a' ).on( 'click', function ( event ) {

        var $el = $( this );

        event.preventDefault();

        // If the media frame already exists, reopen it.
        if ( product_gallery_frame ) {
            product_gallery_frame.open();
            return;
        }

        // Create the media frame.
        product_gallery_frame = wp.media.frames.product_gallery = wp.media( {
            // Set the title of the modal.
            title: $el.data( 'choose' ),
            button: {
                text: $el.data( 'update' )
            },
            states: [
                new wp.media.controller.Library( {
                    title: $el.data( 'choose' ),
                    filterable: 'all',
                    multiple: true
                } )
            ]
        } );

        // When an image is selected, run a callback.
        product_gallery_frame.on( 'select', function () {
            var selection = product_gallery_frame.state().get( 'selection' );
            var attachment_ids = $image_gallery_ids.val();

            selection.map( function ( attachment ) {
                attachment = attachment.toJSON();

                if ( attachment.id ) {
                    attachment_ids = attachment_ids ? attachment_ids + ',' + attachment.id : attachment.id;
                    var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

                    $product_images.append( '<li class="image" data-attachment_id="' + attachment.id + '"><img src="' + attachment_image + '" /><ul class="actions"><li><a href="#" class="delete" title="' + $el.data( 'delete' ) + '">' + $el.data( 'text' ) + '</a></li></ul></li>' );
                }
            } );

            $image_gallery_ids.val( attachment_ids );
        } );

        // Finally, open the modal.
        product_gallery_frame.open();
    } );

    // Image ordering
    $product_images.sortable( {
        items: 'li.image',
        cursor: 'move',
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        forceHelperSize: false,
        helper: 'clone',
        opacity: 0.65,
        placeholder: 'wc-metabox-sortable-placeholder',
        start: function ( event, ui ) {
            ui.item.css( 'background-color', '#f6f6f6' );
        },
        stop: function ( event, ui ) {
            ui.item.removeAttr( 'style' );
        },
        update: function () {
            var attachment_ids = '';

            $( '#product_images_container' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function () {
                var attachment_id = jQuery( this ).attr( 'data-attachment_id' );
                attachment_ids = attachment_ids + attachment_id + ',';
            } );

            $image_gallery_ids.val( attachment_ids );
        }
    } );

    // Remove images
    $( '#product_images_container' ).on( 'click', 'a.delete', function () {
        $( this ).closest( 'li.image' ).remove();

        var attachment_ids = '';

        $( '#product_images_container' ).find( 'ul li.image' ).css( 'cursor', 'default' ).each( function () {
            var attachment_id = jQuery( this ).attr( 'data-attachment_id' );
            attachment_ids = attachment_ids + attachment_id + ',';
        } );

        $image_gallery_ids.val( attachment_ids );

        // remove any lingering tooltips
        $( '#tiptip_holder' ).removeAttr( 'style' );
        $( '#tiptip_arrow' ).removeAttr( 'style' );

        return false;
    } );
} );

jQuery( function ( $ ) {

    var prize_image_frame;
    var $image_container = $( '#platform_shell_meta_contest_main_prize_image' );
    var $prize_images = $( '#main_prize_image' );

    jQuery( '.add_prize_images a' ).on( 'click', function ( event ) {

        var $el = $( this );

        event.preventDefault();

        // If the media frame already exists, reopen it.
        if ( prize_image_frame ) {
            prize_image_frame.open();
            return;
        }

        // Create the media frame.
        prize_image_frame = wp.media.frames.product_gallery = wp.media( {
            // Set the title of the modal.
            title: $el.data( 'choose' ),
            button: {
                text: $el.data( 'update' )
            },
            states: [
                new wp.media.controller.Library( {
                    title: $el.data( 'choose' ),
                    filterable: 'all',
                    multiple: true
                } )
            ]
        } );

        // When an image is selected, run a callback.
        prize_image_frame.on( 'select', function () {
            var selection = prize_image_frame.state().get( 'selection' );
            var attachment_id = $image_container.val();

            selection.map( function ( attachment ) {
                attachment = attachment.toJSON();

                if ( attachment.id ) {
                    attachment_id = attachment.id;
                    var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

                    $prize_images.append( '<span class="prize_image" data-attachment_id="' + attachment.id + '"><img src="' + attachment_image + '" /><ul class="actions"><li><a href="#" class="delete" title="' + $el.data( 'delete' ) + '">' + $el.data( 'text' ) + '</a></li></ul></span>' );
                }
            } );

            $image_container.val( attachment_id );
        } );

        // Finally, open the modal.
        prize_image_frame.open();
    } );

    // Remove images
    $( '#main_prize_image' ).on( 'click', 'a.delete', function () {

        var image = $( this ).closest( 'span.prize_image' );
        $( this ).closest( 'span.prize_image' ).remove();
        $image_container.val( '' );


        return false;
    } );
} );

/******* image commanditaire ******/
jQuery( function ( $ ) {

    var banner_image_frame;
    var $image_container = $( '#platform_shell_meta_contest_sponsor_image' );
    var $prize_images = $( '#main_banner_image' );

    jQuery( '.add_banner_images a' ).on( 'click', function ( event ) {


        var $el = $( this );

        event.preventDefault();

        // If the media frame already exists, reopen it.
        if ( banner_image_frame ) {
            banner_image_frame.open();
            return;
        }

        // Create the media frame.
        banner_image_frame = wp.media.frames.product_gallery = wp.media( {
            // Set the title of the modal.
            title: $el.data( 'choose' ),
            button: {
                text: $el.data( 'update' )
            },
            states: [
                new wp.media.controller.Library( {
                    title: $el.data( 'choose' ),
                    filterable: 'all',
                    multiple: true
                } )
            ]
        } );

        // When an image is selected, run a callback.
        banner_image_frame.on( 'select', function () {
            var selection = banner_image_frame.state().get( 'selection' );
            var attachment_id = $image_container.val();

            selection.map( function ( attachment ) {
                attachment = attachment.toJSON();

                if ( attachment.id ) {
                    attachment_id = attachment.id;
                    var attachment_image = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

                    $prize_images.append( '<span class="prize_image" data-attachment_id="' + attachment.id + '"><img src="' + attachment_image + '" /><ul class="actions"><li><a href="#" class="delete" title="' + $el.data( 'delete' ) + '">' + $el.data( 'text' ) + '</a></li></ul></span>' );
                }
            } );

            $image_container.val( attachment_id );
        } );

        // Finally, open the modal.
        banner_image_frame.open();
    } );

    // Remove images
    $( '#main_banner_image' ).on( 'click', 'a.delete', function () {

        var image = $( this ).closest( 'span.banner_image' );
        $( this ).closest( 'span.banner_image' ).remove();
        $image_container.val( '' );


        return false;
    } );
} );