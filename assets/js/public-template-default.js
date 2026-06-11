document.addEventListener('DOMContentLoaded', () => {
	const shell = document.querySelector('.mmsm-shell');

	if (!shell || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}

	const updateGlow = (event) => {
		const bounds = shell.getBoundingClientRect();
		const x = ((event.clientX - bounds.left) / bounds.width) * 100;
		const y = ((event.clientY - bounds.top) / bounds.height) * 100;

		shell.style.setProperty('--mmsm-glow-x', `${x.toFixed(2)}%`);
		shell.style.setProperty('--mmsm-glow-y', `${y.toFixed(2)}%`);
	};

	window.addEventListener('pointermove', updateGlow, { passive: true });
});
