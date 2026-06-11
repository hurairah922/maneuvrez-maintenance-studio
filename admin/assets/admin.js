jQuery(document).ready(($) => {
	const { __ } = wp.i18n;
	$('.mmsm-color-picker').wpColorPicker();

	const builder = $('.mmsm-social-links-builder');

	if (!builder.length) {
		return;
	}

	const list = builder.find('.mmsm-social-links-list');
	const template = builder.find('.mmsm-social-item-template').html();
	let mediaFrame = null;

	const ensureOneRow = () => {
		if (list.children('[data-social-item]').length) {
			return;
		}

		addRow();
	};

	const toggleCustomFields = (row) => {
		const platform = row.find('.mmsm-social-platform-select').val();
		const iconSource = row.find('.mmsm-social-icon-source-select').val();
		const customFields = row.find('[data-custom-fields]');
		const iconLibraryFields = row.find('[data-icon-library-fields]');
		const iconUploadFields = row.find('[data-icon-upload-fields]');

		customFields.toggleClass('is-hidden', platform !== 'custom');
		iconLibraryFields.toggleClass('is-hidden', iconSource !== 'library');
		iconUploadFields.toggleClass('is-hidden', iconSource !== 'upload');
	};

	const bindRow = (row) => {
		toggleCustomFields(row);

		row.on('change', '.mmsm-social-platform-select', function onPlatformChange() {
			toggleCustomFields($(this).closest('[data-social-item]'));
		});

		row.on('change', '.mmsm-social-icon-source-select', function onIconSourceChange() {
			toggleCustomFields($(this).closest('[data-social-item]'));
		});

		row.on('click', '.mmsm-remove-social-item', function onRemoveItem() {
			$(this).closest('[data-social-item]').remove();
			ensureOneRow();
		});

		row.on('click', '.mmsm-upload-social-icon', function onUploadIcon(event) {
			event.preventDefault();

			const currentRow = $(this).closest('[data-social-item]');

			if (!mediaFrame) {
				mediaFrame = wp.media({
					button: {
						text: __('Use icon', 'maintenance-mode-studio'),
					},
					library: {
						type: ['image'],
					},
					multiple: false,
					title: __('Choose social icon', 'maintenance-mode-studio'),
				});
			}

			mediaFrame.off('select');
			mediaFrame.on('select', () => {
				const attachment = mediaFrame.state().get('selection').first().toJSON();
				const allowedMimeTypes = ['image/png', 'image/jpeg', 'image/webp'];

				if (!allowedMimeTypes.includes(attachment.mime)) {
					window.alert(__('Choose a PNG, JPG, or WEBP image.', 'maintenance-mode-studio'));
					return;
				}

				currentRow.find('.mmsm-social-icon-id').val(attachment.id);
				currentRow.find('.mmsm-social-icon-preview').attr('src', attachment.url).removeClass('is-hidden');
				currentRow.find('.mmsm-remove-social-icon').removeClass('is-hidden');
			});

			mediaFrame.open();
		});

		row.on('click', '.mmsm-remove-social-icon', function onRemoveIcon(event) {
			event.preventDefault();

			const currentRow = $(this).closest('[data-social-item]');
			currentRow.find('.mmsm-social-icon-id').val('0');
			currentRow.find('.mmsm-social-icon-preview').attr('src', '').addClass('is-hidden');
			$(this).addClass('is-hidden');
		});
	};

	const addRow = () => {
		const nextIndex = Number(builder.attr('data-next-index')) || 0;
		const markup = template.replace(/__INDEX__/g, String(nextIndex));
		const row = $(markup);

		builder.attr('data-next-index', String(nextIndex + 1));
		list.append(row);
		bindRow(row);
	};

	list.children('[data-social-item]').each(function initRow() {
		bindRow($(this));
	});

	builder.on('click', '.mmsm-add-social-item', function onAddItem(event) {
		event.preventDefault();
		addRow();
	});
});
