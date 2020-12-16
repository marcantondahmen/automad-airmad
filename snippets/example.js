+function() {

	let form = document.getElementById('example'),
		inputs = form.querySelectorAll('input'),
		reset = form.querySelector('.reset');

	inputs.forEach((input) => {
		input.addEventListener('change', () => {
			form.submit();
		});
	});

	reset.addEventListener('click', () => {
		inputs.forEach((input) => {
			input.value = '';
		});
		form.submit();
	});

}();