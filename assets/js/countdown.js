document.addEventListener('DOMContentLoaded', () => {
	const countdowns = document.querySelectorAll('[data-mmsm-countdown][data-state="scheduled"]');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	countdowns.forEach((countdown) => {
		const target = Number.parseInt(countdown.dataset.targetTimestamp || '', 10) * 1000;
		const values = countdown.querySelector('[data-mmsm-countdown-values]');
		let intervalId = null;

		if (!Number.isFinite(target) || target <= 0 || !values) {
			return;
		}

		const valueElements = {
			days: values.querySelector('[data-mmsm-countdown-value="days"]'),
			hours: values.querySelector('[data-mmsm-countdown-value="hours"]'),
			minutes: values.querySelector('[data-mmsm-countdown-value="minutes"]'),
			seconds: values.querySelector('[data-mmsm-countdown-value="seconds"]'),
		};

		const updateValue = (key, value) => {
			const element = valueElements[key];

			if (!element) {
				return;
			}

			const nextValue = String(value).padStart(2, '0');

			if (element.textContent === nextValue) {
				return;
			}

			element.textContent = nextValue;

			if (!reducedMotion) {
				element.classList.remove('is-ticking');
				window.requestAnimationFrame(() => element.classList.add('is-ticking'));
			}
		};

		const finish = () => {
			if (intervalId !== null) {
				window.clearInterval(intervalId);
				intervalId = null;
			}

			if (countdown.dataset.completed === 'true') {
				return;
			}

			countdown.dataset.completed = 'true';
			countdown.dataset.state = 'expired';

			if (countdown.dataset.expiryAction === 'disable_mode') {
				const instance = countdown.dataset.instance || 'maintenance';
				const reloadKey = `mmsm-countdown-reloaded:${instance}:${target}`;

				try {
					if (window.sessionStorage.getItem(reloadKey) === '1') {
						return;
					}

					window.sessionStorage.setItem(reloadKey, '1');
				} catch (error) {
					// Avoid an uncontrolled reload loop when browser storage is unavailable.
					return;
				}

				window.location.reload();
				return;
			}

			if (countdown.dataset.expiryAction === 'hide') {
				countdown.hidden = true;
				return;
			}

			if (countdown.dataset.expiryAction === 'show_message') {
				const message = countdown.querySelector('[data-mmsm-countdown-finished]');

				values.hidden = true;
				if (message) {
					message.hidden = false;
				}
			}
		};

		const update = () => {
			const remaining = Math.max(0, Math.ceil((target - Date.now()) / 1000));

			updateValue('days', Math.floor(remaining / 86400));
			updateValue('hours', Math.floor((remaining % 86400) / 3600));
			updateValue('minutes', Math.floor((remaining % 3600) / 60));
			updateValue('seconds', remaining % 60);

			if (remaining === 0) {
				finish();
			}
		};

		update();
		if (countdown.dataset.completed !== 'true') {
			intervalId = window.setInterval(update, 1000);
		}
	});
});
