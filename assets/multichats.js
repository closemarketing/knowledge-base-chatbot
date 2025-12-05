document.addEventListener('DOMContentLoaded', function () {
	const button = document.getElementById('multichat-button');
	const iframe = document.getElementById('multichat-iframe');

	button.addEventListener('click', function () {
		iframe.classList.toggle('active');
	});
});