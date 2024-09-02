/**
 * Returns a random number between the min and max values
 */
function randomBetween(min, max) {
	var range = {min, max}
	var delta = range.max - range.min
	return Math.round( range.min + Math.random() * delta )
}

export default randomBetween
