var article_number_selector   = '#article-number',
	gallery_link_selector     = 'a[target="gallery"]';
	gallery_selector          = '#gallery',
	overlay_link_selector     = 'a[target="overlay"]';
    overlay_selector          = '#overlay',
    overlay_contents_selector = '#overlay .contents';

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
Determines which article is currently active
by checking the visibility of each article
and it's predecessor and keeps #article-number
updated with that value
*/
function updateArticleNumber() {
	
	var article_number  = $( article_number_selector );
	var last_is_visible = false;

	$( 'article' ).each( function( index ) {

		is_visible = checkVisibility( $( this ) );

		if ( !last_is_visible && is_visible ) {
			
			article_number.text( index + 1 );
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

	$( gallery_link_selector ).removeClass( 'selected ');
	$( this ).addClass( 'selected' );

	$( gallery_selector ).load( $(this).attr( 'href' ) );

}

/*
Loads a link's href into the overlay rather than following the link
*/
function openLinkInOverlay( event ) {
	
	event.preventDefault();

	toggleOverlay();

	$( overlay_contents_selector ).load( $(this).attr( 'href' ) );

}

/*
Empties overlay, toggles body scrolling
and then fades overlay in or out
*/
function toggleOverlay() {
	
	$( overlay_contents_selector ).html( '' );

	$( 'body' ).toggleClass( 'noscroll' );

	$( overlay_selector ).fadeToggle( 300 );

}

/*
Checks if ESC is pressed while the overlay
is open and closes it if so
*/
function closeOverlayOnEscPress( event ) {

	var code = event.keyCode || event.which;

	if( code == 27 && $( overlay_selector ).is( ':visible' ) ) toggleOverlay();

}

function loadFunction( jQuery ) {

	$( window ).scroll( updateArticleNumber );

	$( window ).keypress( closeOverlayOnEscPress );

	$( '#overlay .close' ).click( toggleOverlay );

	$( overlay_link_selector ).click( openLinkInOverlay );
	
	$( gallery_link_selector ).click( updateGallery ).first().click();

}
 
$( window ).on( "load", loadFunction );