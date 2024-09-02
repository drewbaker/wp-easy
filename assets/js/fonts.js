/*
 * Font loader
 * SEE https://github.com/typekit/webfontloader
 */

WebFont.load(
	{
		// custom: {
		//     families: ['My Font']
		// },
		// google: {
		//     families: ['Droid Sans', 'Droid Serif:bold']
		// },
		active() {
			jQuery( window ).trigger( 'fonts-loaded' );
		},
		fontactive( familyName, fvd ) {
			// fvd = font variation description
			// SEE https://github.com/typekit/fvd
			jQuery( window ).trigger( 'font-loaded', {name: familyName, fvd: fvd} );
		}
	}
);
