export const state = {
	homeURL: serverVars.homeURL,
	pluginURL: serverVars.pluginURL,
	winWidth : $( window ).width(),
	winHeight : $( window ).height(),
	sTop: 0,
	referrer: document.referrer
}

// Called on resize
function onResize(){
	state.winWidth  = $( window ).width();
	state.winHeight = $( window ).height();
}

// Called on scroll
function onScroll(){
	state.sTop = $( window ).scrollTop();
}

// Called when all fonts have rendered
function onFontsLoaded() {}

// Global in-view effects
// inView.offset(100);
// inView('.in-view-enabled').on('enter', (el) => {
//     $(el).addClass('in-view-entered');
// });

// Listen for events
$( window ).on( 'resize', onResize );
$( window ).on( 'scroll', onScroll );
$( window ).on( 'fonts-loaded', onFontsLoaded );

export default state
