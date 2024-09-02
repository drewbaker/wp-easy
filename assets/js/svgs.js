// init global cache object and assign local var
let cache = {};
let count = 0;
let total;

// Search for unloaded SVG <img> tags and replace them
export function initSVGs(){

	// Set total and counter
	let $svgs = $( 'img[data-svg=""]' );
	total     = $svgs.length;

	// If no SVGs on page, fire callback event
	if ( total === count ) {
		$( document ).trigger( 'svgsLoaded', [count] );
	}

	// loop all svgs
	$svgs.each(
		function(){

			// get URL from this SVG
			var imgURL = $( this ).attr( 'src' );

			// if not cached, make new AJAX request
			if ( ! cache[imgURL] ) {
				cache[imgURL] = $.get( imgURL ).promise();
			}

			// when we have SVG data, replace img with data
			cache[imgURL].done( replaceSVG.bind( this ) );

		}
	);
}

// Replace single svg
function replaceSVG( data ){

	// get img and attributes
	let $img       = $( this ),
		attributes = $img.prop( "attributes" );

	// Increment counter
	count++;

	// Clone the SVG tag, ignore the rest
	let $svg = $( data ).find( 'svg' ).clone();

	// Remove any invalid XML tags as per http://validator.w3.org
	$svg = $svg.removeAttr( 'xmlns:a' );

	// Loop through IMG attributes and add them to SVG
	$.each(
		attributes,
		function(){
			$svg.attr( this.name, this.value );
		}
	);

	$svg.removeAttr( 'src' );
	$svg.attr( 'data-svg', 'replaced' );

	// Replace image with new SVG
	$img.replaceWith( $svg );

	// If this is the last svg, fire callback event
	if ( total === count ) {
		$( document ).trigger( 'svgsLoaded', [count] );
	}
}

initSVGs()
