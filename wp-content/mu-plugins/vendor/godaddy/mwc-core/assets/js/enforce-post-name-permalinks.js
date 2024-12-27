document.addEventListener( 'DOMContentLoaded', function() {
	const input = document.getElementById('permalink-input-plain');
	const label = document.querySelector('label[for=permalink-input-plain]');

	if (input) {
		input.disabled = true;
	}

	if (label) {
		label.style['cursor'] = 'default';
	}
});
