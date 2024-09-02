/*
 * This function will set a css variable `--scrolled-percent` on :root that is a percentage (0 to 1) of the window height scrolled.
 */
import clamp from 'utils/clamp'

function setScrollPercent(s){

	const sTop      = $( window ).scrollTop();
	const winHeight = $( window ).height();

	// Calculate sTop as percentage of window height
	let sTopPercent = (sTop / winHeight);
	sTopPercent     = clamp( sTopPercent, 0, 1 );
	sTopPercent     = parseFloat( sTopPercent.toFixed( 2 ) );
	$( ':root' ).css( '--scrolled-percent', sTopPercent );

	return sTopPercent
}

export default function init() {
	$( window ).off( "scroll", setScrollPercent )
	$( window ).on( "scroll", setScrollPercent )
}
