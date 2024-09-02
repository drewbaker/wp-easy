export default function clamp(num, min, max) {
	return num > max ? max : num < min ? min : num
}
