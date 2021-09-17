/*
Set some variables
*/
var dev_mode = true;

var section_number   = '#section-number',
    gallery_link     = 'a[target="gallery"]',
    gallery          = '#gallery',
    gallery_loading  = '#gallery-outer .loading',
    overlay_link     = 'a[target="overlay"]',
    overlay          = '#overlay',
    overlay_loading  = '#overlay .loading',
    overlay_contents = '#overlay .contents',
    overlay_section  = '#overlay section',
    overlay_close    = '#overlay .close',
    code_link        = '#code-nav a';

var last_details_id, last_details_pos_top, last_details_pos_left;

var details_open = false;

/*
Checks whether an element is in the viewport
*/
function checkVisibility( element ) {

	var viewport_height = $( window ).height();
	var scroll_top      = $( window ).scrollTop();
	var element_top     = element.offset().top;
	var element_height  = element.outerHeight( true );

	return ( ( element_top < ( viewport_height + scroll_top ) ) && ( element_top > ( scroll_top - element_height ) ) );

}

/*
Determines which section is currently active by checking the visibility of
each section and it's predecessor and keeps #section-number updated with
that value
*/
function updateSectionNumber() {
	
	var section_number  = $( section_number );
	var last_is_visible = false;

	$( 'section' ).each( function( index ) {

		is_visible = checkVisibility( $( this ) );

		if ( !last_is_visible && is_visible ) {
			
			section_number.text( index + 1 );
			return false;
		
		}

		last_is_visible = is_visible;

	});

}

/*
Loads a link's href into the gallery rather than following the link
*/
function updateGallery( event ) {

	event.preventDefault();

	current_height = $( gallery ).height();

	$( gallery_link ).removeClass( 'selected ');
	$( this ).addClass( 'selected' );

	$( gallery ).hide();
	$( gallery_loading ).show();

	$( gallery ).load( $(this).attr( 'href' ) + ( ( dev_mode ) ? '?t=' + Date.now() : '' ), function () {

		if ( $( gallery + ' img' ).length ) {

			$( gallery + ' img' ).on( 'load', function() {

				resizeGallery( current_height );

				$( gallery_loading ).hide();
				$( gallery ).show();

			} );

		} else {

			resizeGallery( current_height );

			$( gallery_loading ).hide();
			$( gallery ).show();

		}

	} );

}

/*
Resizes the gallery div based on the size of the new content in order to
trigger the CSS transition
*/
function resizeGallery( current_height ) {

	auto_height = $( gallery ).css( 'height', 'auto' ).height();

	$( gallery ).height( current_height ).height( auto_height );

}

/*
Loads a link's href into the overlay rather than following the link
*/
function openLinkInOverlay( event ) {
	
	event.preventDefault();

	toggleOverlay();

	// clone the loading div to the contents so it's visible until the linked page loads
	overlay_loading_clone = $( overlay_loading ).clone().appendTo( overlay_contents ).show();

	$( overlay ).removeClass().addClass( $(this).attr( 'href' ) );

	$( overlay_contents ).load( $(this).attr( 'href' ) + ( ( dev_mode ) ? '?t=' + Date.now() : '' ) );

}

/*
Empties overlay, toggles body scrolling and then fades overlay in or out
*/
function toggleOverlay() {

	$( overlay_contents ).html('');

	$( 'body' ).toggleClass( 'noscroll' );

	$( overlay ).fadeToggle( 300 );

}

/*
Checks if ESC is pressed while either the overlay or portfolio details is open
and closes it if so
*/
function closeOverlayOnEscPress( event ) {

	var code = event.keyCode || event.which;

	if ( code == 27 ) {

		if ( details_open ) {

			$( last_details_id + ' .close-details' ).click();

		} else if ( $( overlay ).is( ':visible' ) ) {

			toggleOverlay();

		}

	}

}

/*
Loads the sample code found at uri into the element identified
by target, then processes it with prism using the
specified lang value
*/
function loadExampleCode( target, uri, lang ) {

	$.get( uri, function( data ) {

		$( target ).html( Prism.highlight(data, Prism.languages[lang], lang) );

	});

}

/*
Manages hiding and showing of code example sections
*/
function selectCodeExample( event ) {

	event.preventDefault();

	$( code_link ).removeClass( 'selected ');
	$( this ).addClass( 'selected' );

	$( overlay_section ).hide();
	$( $(this).attr( 'href' ) ).show();

	window.__CPEmbed();

}

/*
Opens the selected portfolio details item, and sets some variables so it can
be closed later
*/
function openPortfolioDetails( event ) {

	event.preventDefault();

	details_id = $( this ).attr( 'href' );

	last_details_id = details_id;

	last_details_pos_top  = $( details_id ).css( 'top' );
	last_details_pos_left = $( details_id ).css( 'left' );

	$( details_id ).css( 'top', 0 );
	$( details_id ).css( 'left', 0 );

	details_open = true;

}

/*
Closes the last opened portfolio details item
*/
function closePortfolioDetails( event ) {

	event.preventDefault();

	$( last_details_id ).css( 'top', last_details_pos_top );
	$( last_details_id ).css( 'left', last_details_pos_left );

	details_open = false;

}

/*
Everything to be run after the main page loads
*/
function loadFunction( jQuery ) {

	$( window ).scroll( updateSectionNumber );

	$( document ).keyup( closeOverlayOnEscPress );

	$( window ).resize( resizeGallery );

	$( overlay_close ).click( toggleOverlay );

	$( overlay_link ).click( openLinkInOverlay );
	
	$( gallery_link ).click( updateGallery ).first().click();

}
 
$( window ).on( 'load', loadFunction );