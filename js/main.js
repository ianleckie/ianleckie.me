/*
Checks whether an element is in the viewport
*/
function checkVisibility( element ) {

	var viewport_height = $(window).height();
	var scroll_top      = $(window).scrollTop();
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
	
	var article_number  = $('#article-number');
	var last_is_visible = false;

	$('article').each(function( index ) {

		is_visible = checkVisibility( $(this) );

		if ( !last_is_visible && is_visible ) {
			
			article_number.text( index + 1 );
			return false;
		
		}

		last_is_visible = is_visible;

	});

}

function loadFunction( jQuery ) {

	$( window ).scroll( updateArticleNumber );

}
 
$( window ).on( "load", loadFunction );