jQuery(($) => {
	const config = window.mmsmPluginFeedback || null;
	const modal = $('#mmsm-uninstall-feedback-modal');

	if (!config || !modal.length) {
		return;
	}

	const otherWrap = modal.find('.mmsm-uninstall-feedback-other');
	const detailsField = modal.find('#mmsm-uninstall-feedback-details');
	const closeButton = modal.find('.mmsm-uninstall-feedback-close');
	const skipButton = modal.find('.mmsm-uninstall-feedback-skip');
	const submitButton = modal.find('.mmsm-uninstall-feedback-submit');
	let targetUrl = '';
	let pluginAction = 'deactivate';

	const setRemoveDataDefault = () => {
		modal
			.find('input[name="mmsm_remove_data"][value="' + String(config.removeDataDefault || '0') + '"]')
			.prop('checked', true);
	};

	const toggleOtherField = () => {
		const selectedReason = modal.find('input[name="mmsm_uninstall_reason"]:checked').val();
		const isOther = selectedReason === 'other';

		otherWrap.toggleClass('is-hidden', !isOther);

		if (!isOther) {
			detailsField.val('');
		}
	};

	const closeModal = () => {
		modal.addClass('is-hidden').attr('aria-hidden', 'true');
	};

	const openModal = (url, action) => {
		targetUrl = url || '';
		pluginAction = action || 'deactivate';

		modal.removeClass('is-hidden').attr('aria-hidden', 'false');
		modal.find('input[name="mmsm_uninstall_reason"]').prop('checked', false);
		detailsField.val('');
		setRemoveDataDefault();
		toggleOtherField();
		closeButton.trigger('focus');
	};

	const continuePluginAction = () => {
		if (targetUrl) {
			window.location.href = targetUrl;
		}
	};

	const saveFeedback = (skipFeedback) => {
		const payload = {
			action: 'mmsm_capture_uninstall_feedback',
			nonce: config.nonce,
			skip_feedback: skipFeedback ? '1' : '0',
			reason: modal.find('input[name="mmsm_uninstall_reason"]:checked').val() || '',
			details: detailsField.val() || '',
			remove_data: modal.find('input[name="mmsm_remove_data"]:checked').val() || '0',
			plugin_action: pluginAction,
		};

		skipButton.prop('disabled', true);
		submitButton.prop('disabled', true);

		$.post(config.ajaxUrl, payload)
			.always(() => {
				continuePluginAction();
			});
	};

	$(document).on('click', '.mmsm-uninstall-feedback-trigger', function onTriggerClick(event) {
		event.preventDefault();
		openModal($(this).attr('href'), $(this).data('mmsmPluginAction'));
	});

	modal.on('change', 'input[name="mmsm_uninstall_reason"]', toggleOtherField);

	modal.on('click', '.mmsm-uninstall-feedback-backdrop, .mmsm-uninstall-feedback-cancel', function onCloseClick(event) {
		event.preventDefault();
		closeModal();
	});

	closeButton.on('click', function onCloseButtonClick(event) {
		event.preventDefault();
		closeModal();
	});

	skipButton.on('click', function onSkipClick(event) {
		event.preventDefault();
		saveFeedback(true);
	});

	submitButton.on('click', function onSubmitClick(event) {
		event.preventDefault();
		saveFeedback(false);
	});

	$(document).on('keydown', (event) => {
		if (event.key === 'Escape' && !modal.hasClass('is-hidden')) {
			closeModal();
		}
	});
});
