$( function() {

	manage_colorbox();

	$('#nav-show').on( 'click', function( e ) {

		e.preventDefault();

		if ( $(this).hasClass('icon-chevron-down') ) {

			$(this).removeClass('icon-chevron-down');
			$(this).addClass('icon-chevron-up');
			$('#nav').slideDown();

		} else {

			$(this).addClass('icon-chevron-down');
			$(this).removeClass('icon-chevron-up');
			$('#nav').slideUp();

		}

	});

	// get rid of the bottom border on hover for links that contain images
	$('a:has(img)').hover( function() {

		$(this).css({border: 'none'});

	});

	$('#close').on( 'click', function( e ) {

		e.preventDefault();

		if ( $('#embeds').hasClass('closed') ) {

			$('#embeds').removeClass('closed');
			$(this).removeClass('icon-chevron-left');
			$(this).addClass('icon-chevron-right');

		} else {

			$('#embeds').addClass('closed');
			$(this).removeClass('icon-chevron-right');
			$(this).addClass('icon-chevron-left');

		}

	});

	twitter_height();

});

$(window).resize(function() {

	twitter_height();

	manage_colorbox();

});

// resize the twitter embed to match the window height
function twitter_height() {

	var height = $(window).height();
	var em = $('body').css('font-size');

	em = em.substring( 0, em.length - 2 );

	var margin = 2.5 * em;

	height = height - margin;

	$('.twitter-timeline').attr({height: height});

}

function manage_colorbox() {

	if ( $(window).width() > 500 ) {

		$('.cb').colorbox();

	} else {

		$.colorbox.remove();

		$('.cb').on( 'click', function( e ) {

			e.preventDefault();

		});

	}

}