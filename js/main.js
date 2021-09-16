var dev_mode = true;

var section_number_selector   = '#section-number',
    gallery_link_selector     = 'a[target="gallery"]',
    gallery_selector          = '#gallery',
    overlay_link_selector     = 'a[target="overlay"]',
    overlay_selector          = '#overlay',
    overlay_contents_selector = '#overlay .contents',
    overlay_section_selector  = '#overlay section',
    overlay_close_selector    = '#overlay .close',
    code_link_selector        = '#code-nav a';

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
	
	var section_number  = $( section_number_selector );
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

	current_height = $( gallery_selector ).height();

	$( gallery_link_selector ).removeClass( 'selected ');
	$( this ).addClass( 'selected' );

	$( gallery_selector ).load( $(this).attr( 'href' ) + ( ( dev_mode ) ? '?t=' + Date.now() : '' ), function () {

		// change height to trigger CSS transition
		auto_height = $( gallery_selector ).css( 'height', 'auto' ).height();

		$( gallery_selector ).height( current_height ).height( auto_height );

	} );

}

/*
Loads a link's href into the overlay rather than following the link
*/
function openLinkInOverlay( event ) {
	
	event.preventDefault();

	toggleOverlay();

	$( overlay_selector ).removeClass().addClass( $(this).attr( 'href' ) );

	$( overlay_contents_selector ).load( $(this).attr( 'href' ) + ( ( dev_mode ) ? '?t=' + Date.now() : '' ) );


}

/*
Empties overlay, toggles body scrolling and then fades overlay in or out
*/
function toggleOverlay() {

	$( overlay_contents_selector ).html('');

	$( 'body' ).toggleClass( 'noscroll' );

	$( overlay_selector ).fadeToggle( 300 );

}

/*
Checks if ESC is pressed while the overlay is open and closes it if so
*/
function closeOverlayOnEscPress( event ) {

	var code = event.keyCode || event.which;

	if( code == 27 && $( overlay_selector ).is( ':visible' ) ) toggleOverlay();

}

/*
Loads the sample code found at uri into the element identified
by target_selector, then processes it with prism using the
specified lang value
*/
function loadExampleCode( target_selector, uri, lang ) {

	$.get( uri, function( data ) {

		$( target_selector ).html( Prism.highlight(data, Prism.languages[lang], lang) );

	});

}

/*
Manages hiding and showing of code example sections
*/
function selectCodeExample( event ) {

	event.preventDefault();

	$( code_link_selector ).removeClass( 'selected ');
	$( this ).addClass( 'selected' );

	$( overlay_section_selector ).hide();
	$( $(this).attr( 'href' ) ).show();

	window.__CPEmbed();

}

function loadFunction( jQuery ) {

	$( window ).scroll( updateSectionNumber );

	$( window ).keypress( closeOverlayOnEscPress );

	$( overlay_close_selector ).click( toggleOverlay );

	$( overlay_link_selector ).click( openLinkInOverlay );
	
	$( gallery_link_selector ).click( updateGallery ).first().click();

}
 
$( window ).on( 'load', loadFunction );