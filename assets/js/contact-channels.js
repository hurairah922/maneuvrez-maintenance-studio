document.addEventListener('DOMContentLoaded', () => {
	const widgets = document.querySelectorAll('[data-mmsm-contact-channels]');

	widgets.forEach((widget) => {
		const trigger = widget.querySelector('.mmsm-contact-channel-trigger');
		const menu = widget.querySelector('.mmsm-contact-channel-menu');

		if (!trigger || !menu) {
			return;
		}

		const setOpen = (isOpen) => {
			trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			menu.hidden = !isOpen;
			widget.classList.toggle('is-open', isOpen);
		};

		trigger.addEventListener('click', () => {
			setOpen(trigger.getAttribute('aria-expanded') !== 'true');
		});

		document.addEventListener('click', (event) => {
			if (!widget.contains(event.target)) {
				setOpen(false);
			}
		});

		document.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				setOpen(false);
				trigger.focus();
			}
		});
	});
});
