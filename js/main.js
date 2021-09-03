function checkVisibility( element_id ) {

	var viewport_height = $(window).height();
	var scroll_top      = $(window).scrollTop();
	var element_top     = $(element_id).offset().top;
	var element_height  = $(element_id).outerHeight( true );

	return ( ( element_top < ( viewport_height + scroll_top ) ) && ( element_top > ( scroll_top - element_height ) ) );

}

function updateSectionNumber() {

	var section_ids     = ['#intro', '#experience', '#more-about-me', '#resume', '#colophon'];
	var section_element = $('#section-num');

	for (var key = 1; key < section_ids.length - 1; key++) {

		if ( checkVisibility( section_ids[0] ) ) {
			
			section_element.html( 1 );
			break;
		
		} else if ( !checkVisibility( section_ids[key - 1] ) && checkVisibility( section_ids[key] ) ) {
			
			section_element.html( key + 1 );
			break;
		
		}
	
	}

}

function loadFunction( jQuery ) {

	$( window ).scroll( updateSectionNumber );

}
 
$( window ).on( "load", loadFunction );