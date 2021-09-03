function checkVisibility( element ) {

	var viewport_height = $(window).height();
	var scroll_top      = $(window).scrollTop();
	var element_top     = $(element).offset().top;
	var element_height  = $(element).outerHeight( true );

	return ( ( element_top < ( viewport_height + scroll_top ) ) && ( element_top > ( scroll_top - element_height ) ) );

}

function updateSectionNumber() {

	var section_ids = ['#intro', '#experience', '#more-about-me', '#resume', '#colophon'];
	var section_div = $('#section-num');

	for (var key = 0; key < section_ids.length - 1; key++) {

		if ( checkVisibility( section_ids[0] ) ) {
			section_div.html( 1 );
			break;
		} else if ( key > 0 && !checkVisibility( section_ids[key - 1] ) && checkVisibility( section_ids[key] ) ) {
			section_div.html( key + 1 );
			break;
		}
	}

}

function loadFunction( jQuery ) {

	updateSectionNumber();

	$( window ).scroll( updateSectionNumber );

}
 
$( window ).on( "load", loadFunction );