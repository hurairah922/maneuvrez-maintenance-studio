document.addEventListener('DOMContentLoaded', () => {
	const root = document.querySelector('.mmsm-orb-shell');

	if (!root || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}

	const updateMotion = (clientX, clientY) => {
		const bounds = root.getBoundingClientRect();
		const x = ((clientX - (bounds.left + bounds.width / 2)) / bounds.width) * 10;
		const y = ((clientY - (bounds.top + bounds.height / 2)) / bounds.height) * 10;

		root.style.setProperty('--mmsm-shift-x', `${x.toFixed(2)}px`);
		root.style.setProperty('--mmsm-shift-y', `${y.toFixed(2)}px`);
	};

	window.addEventListener('pointermove', (event) => {
		updateMotion(event.clientX, event.clientY);
	});

	window.addEventListener('pointerleave', () => {
		root.style.setProperty('--mmsm-shift-x', '0px');
		root.style.setProperty('--mmsm-shift-y', '0px');
	});
});
