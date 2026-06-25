jQuery(document).ready(($) => {
	const { __ } = wp.i18n;

	const initializeColorPickers = (scope) => {
		scope.find('.mmsm-color-picker').each(function initColorPicker() {
			const input = $(this);

			if (input.hasClass('wp-color-picker')) {
				return;
			}

			input.wpColorPicker();
		});
	};

	initializeColorPickers($(document.body));

	const bypassBuilder = $('.mmsm-bypass-query-builder');

	const initializeBypassPreview = () => {
		if (!bypassBuilder.length) {
			return;
		}

		const homeUrl = String(bypassBuilder.data('homeUrl') || '');
		const keyField = bypassBuilder.find('.mmsm-bypass-query-key');
		const valueField = bypassBuilder.find('.mmsm-bypass-query-value');
		const preview = bypassBuilder.find('.mmsm-bypass-query-preview');
		const generateButton = bypassBuilder.find('.mmsm-generate-bypass-query');
		const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-';

		const generateToken = (length) => {
			const size = Number(length) || 24;
			let token = '';

			if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
				const values = new Uint32Array(size);
				window.crypto.getRandomValues(values);

				values.forEach((value) => {
					token += charset.charAt(value % charset.length);
				});

				return token;
			}

			for (let index = 0; index < size; index += 1) {
				token += charset.charAt(Math.floor(Math.random() * charset.length));
			}

			return token;
		};

		const buildPreviewUrl = () => {
			const key = String(keyField.val() || '');
			const value = String(valueField.val() || '');

			if (!homeUrl || !key) {
				return homeUrl;
			}

			try {
				const url = new URL(homeUrl);
				url.search = '';
				url.hash = '';
				url.searchParams.set(key, value);
				return url.toString();
			} catch (error) {
				const separator = homeUrl.includes('?') ? '&' : '?';
				return `${homeUrl}${separator}${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
			}
		};

		const updatePreview = () => {
			preview.text(buildPreviewUrl());
		};

		keyField.on('input', updatePreview);
		valueField.on('input', updatePreview);

		generateButton.on('click', (event) => {
			event.preventDefault();
			keyField.val('mmsm_preview');
			valueField.val(generateToken(24));
			updatePreview();
		});

		updatePreview();
	};

	initializeBypassPreview();

	const builder = $('.mmsm-social-links-builder');

	if (builder.length) {
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
							text: __('Use icon', 'maneuvrez-maintenance-studio'),
						},
						library: {
							type: ['image'],
						},
						multiple: false,
						title: __('Choose social icon', 'maneuvrez-maintenance-studio'),
					});
				}

				mediaFrame.off('select');
				mediaFrame.on('select', () => {
					const attachment = mediaFrame.state().get('selection').first().toJSON();
					const allowedMimeTypes = ['image/png', 'image/jpeg', 'image/webp'];

					if (!allowedMimeTypes.includes(attachment.mime)) {
						window.alert(__('Choose a PNG, JPG, or WEBP image.', 'maneuvrez-maintenance-studio'));
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
			initializeColorPickers(row);
			bindRow(row);
		};

		list.children('[data-social-item]').each(function initRow() {
			bindRow($(this));
		});

		builder.on('click', '.mmsm-add-social-item', function onAddItem(event) {
			event.preventDefault();
			addRow();
		});
	}
});
