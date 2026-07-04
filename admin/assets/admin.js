jQuery(document).ready(($) => {
	const { __ } = wp.i18n;

	const initializeColorPickers = (scope) => {
		scope.find('.mmsm-color-picker').each(function initColorPicker() {
			const input = $(this);

			if (input.hasClass('wp-color-picker')) {
				return;
			}

			input.wpColorPicker({
				change(event, ui) {
					input.val(ui.color.toString()).trigger('input');
				},
				clear() {
					window.setTimeout(() => {
						input.trigger('input');
					}, 0);
				},
			});
		});
	};

	initializeColorPickers($(document.body));

	const initializeDesignPreview = () => {
		const preview = $('[data-design-preview]');

		if (!preview.length) {
			return;
		}

		const colorMap = {
			background_color: '--mmsm-design-preview-bg',
			surface_color: '--mmsm-design-preview-surface',
			primary_color: '--mmsm-design-preview-primary',
			heading_text_color: '--mmsm-design-preview-heading',
			body_text_color: '--mmsm-design-preview-body',
			muted_text_color: '--mmsm-design-preview-muted',
			link_text_color: '--mmsm-design-preview-link',
			button_text_color: '--mmsm-design-preview-button-text',
			border_color: '--mmsm-design-preview-border',
		};

		const updatePreview = () => {
			Object.entries(colorMap).forEach(([key, variable]) => {
				const field = $(`input[name$="[${key}]"]`);
				const color = String(field.val() || field.data('defaultColor') || '').trim();

				if (color) {
					preview.css(variable, color);
				}
			});
		};

		$(document.body).on('input change', Object.keys(colorMap).map((key) => `input[name$="[${key}]"]`).join(','), updatePreview);
		updatePreview();
	};

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

	const initializeAdvancedVisibility = () => {
		const toggleRow = (selector, isVisible) => {
			$(selector).toggleClass('is-hidden', !isVisible).attr('aria-hidden', isVisible ? 'false' : 'true');
		};

		const customLoginToggle = $('#mmsm-custom-login-enabled');
		const bypassQueryToggle = $('#mmsm-bypass-query-enabled');
		const bypassUrlsToggle = $('#mmsm-bypass-urls-enabled');

		const updateVisibility = () => {
			toggleRow('.mmsm-custom-login-dependent', customLoginToggle.prop('checked'));
			toggleRow('.mmsm-bypass-query-dependent', bypassQueryToggle.prop('checked'));
			toggleRow('.mmsm-bypass-urls-dependent', bypassUrlsToggle.prop('checked'));
		};

		customLoginToggle.on('change', updateVisibility);
		bypassQueryToggle.on('change', updateVisibility);
		bypassUrlsToggle.on('change', updateVisibility);

		updateVisibility();
	};

	const initializeCustomLoginPreview = () => {
		const slugField = $('#mmsm-custom-login-slug');
		const preview = $('.mmsm-custom-login-preview');

		if (!slugField.length || !preview.length) {
			return;
		}

		const homeUrl = String(slugField.data('homeUrl') || '');

		const sanitizeSlug = (value) => String(value || '')
			.trim()
			.replace(/^\/+|\/+$/g, '')
			.toLowerCase()
			.replace(/['’]/g, '')
			.replace(/[^a-z0-9\s_-]/g, '')
			.trim()
			.replace(/[\s_]+/g, '-')
			.replace(/-+/g, '-')
			.replace(/^-+|-+$/g, '')
			.slice(0, 60);

		const buildPreviewUrl = () => {
			const slug = sanitizeSlug(slugField.val()) || 'secure-admin';

			try {
				const url = new URL(homeUrl);
				url.search = '';
				url.hash = '';
				url.pathname = `${url.pathname.replace(/\/+$/, '')}/${slug}/`;
				return url.toString();
			} catch (error) {
				return `${homeUrl.replace(/\/+$/, '')}/${encodeURIComponent(slug)}/`;
			}
		};

		const updatePreview = () => {
			preview.text(buildPreviewUrl());
		};

		slugField.on('input', updatePreview);
		updatePreview();
	};

	initializeAdvancedVisibility();
	initializeCustomLoginPreview();
	initializeDesignPreview();

	const builder = $('.mmsm-social-links-builder');

	if (builder.length) {
		const list = builder.find('.mmsm-social-links-list');
		const template = builder.find('.mmsm-social-item-template').html();
		let mediaFrame = null;
		const socialPlatformLabels = {
			facebook: __('Facebook', 'maneuvrez-maintenance-studio'),
			instagram: __('Instagram', 'maneuvrez-maintenance-studio'),
			linkedin: __('LinkedIn', 'maneuvrez-maintenance-studio'),
			x: __('X', 'maneuvrez-maintenance-studio'),
			youtube: __('YouTube', 'maneuvrez-maintenance-studio'),
			github: __('GitHub', 'maneuvrez-maintenance-studio'),
			tiktok: __('TikTok', 'maneuvrez-maintenance-studio'),
			threads: __('Threads', 'maneuvrez-maintenance-studio'),
			website: __('Website', 'maneuvrez-maintenance-studio'),
			email: __('Email', 'maneuvrez-maintenance-studio'),
			custom: __('Link', 'maneuvrez-maintenance-studio'),
		};
		const socialPlatformIcons = {
			facebook: 'facebook-alt',
			instagram: 'admin-links',
			linkedin: 'admin-links',
			x: 'twitter',
			youtube: 'video-alt3',
			github: 'admin-links',
			tiktok: 'video-alt3',
			threads: 'share-alt',
			website: 'admin-site',
			email: 'email-alt',
			custom: 'admin-links',
		};
		const socialUrlPlaceholders = {
			email: 'hello@example.com',
			website: 'https://example.com',
			custom: 'https://example.com',
		};

		const ensureOneRow = () => {
			if (list.children('[data-social-item]').length) {
				return;
			}

			addRow();
		};

		const getSocialRowLabel = (row) => {
			const platform = row.find('.mmsm-social-platform-select').val();
			const customLabel = String(row.find('.mmsm-social-custom-name-input').val() || '').trim();

			return platform === 'custom' && customLabel ? customLabel : (socialPlatformLabels[platform] || socialPlatformLabels.custom);
		};

		const getSocialRowIconMarkup = (row) => {
			const platform = row.find('.mmsm-social-platform-select').val();
			const iconSource = row.find('.mmsm-social-icon-source-select').val();
			const iconValue = String(row.find('.mmsm-social-icon-value-select').val() || '').trim();
			const uploadUrl = String(row.find('.mmsm-social-icon-preview').attr('src') || '').trim();

			if (iconSource === 'upload' && uploadUrl) {
				return $('<img />', {
					alt: '',
					src: uploadUrl,
				});
			}

			if (iconSource === 'library' && iconValue) {
				return $('<span />', {
					class: `dashicons dashicons-${iconValue}`,
					'aria-hidden': 'true',
				});
			}

			return $('<span />', {
				class: `dashicons dashicons-${socialPlatformIcons[platform] || socialPlatformIcons.custom}`,
				'aria-hidden': 'true',
			});
		};

		const updateSocialRowPreview = (row) => {
			const platform = row.find('.mmsm-social-platform-select').val();
			const url = String(row.find('.mmsm-social-url-input').val() || '').trim();
			const color = String(row.find('.mmsm-social-icon-color-picker').val() || '').trim();
			const preview = row.find('[data-social-icon-preview]');
			const iconMarkup = getSocialRowIconMarkup(row);

			preview.empty();
			preview.append(iconMarkup);
			preview.css('color', color || '');
			row.find('.mmsm-social-url-input').attr('placeholder', socialUrlPlaceholders[platform] || 'https://example.com');
			row.find('[data-social-item-summary]').text(getSocialRowLabel(row));
			row.find('[data-social-item-subsummary]').text(url || __('Add a destination URL or email.', 'maneuvrez-maintenance-studio'));
			row.find('[data-social-item-state]')
				.toggleClass('is-ready', !!url)
				.text(url ? __('Ready', 'maneuvrez-maintenance-studio') : __('Needs URL', 'maneuvrez-maintenance-studio'));
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
			updateSocialRowPreview(row);
		};

		const bindRow = (row) => {
			toggleCustomFields(row);

			row.on('change', '.mmsm-social-platform-select', function onPlatformChange() {
				toggleCustomFields($(this).closest('[data-social-item]'));
			});

			row.on('change', '.mmsm-social-icon-source-select', function onIconSourceChange() {
				toggleCustomFields($(this).closest('[data-social-item]'));
			});

			row.on('change', '.mmsm-social-icon-library-select, .mmsm-social-icon-value-select', function onIconLibraryChange() {
				updateSocialRowPreview($(this).closest('[data-social-item]'));
			});

			row.on('input change', '.mmsm-social-url-input, .mmsm-social-custom-name-input, .mmsm-social-icon-color-picker', function onSocialRowInput() {
				updateSocialRowPreview($(this).closest('[data-social-item]'));
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
					updateSocialRowPreview(currentRow);
				});

				mediaFrame.open();
			});

			row.on('click', '.mmsm-remove-social-icon', function onRemoveIcon(event) {
				event.preventDefault();

				const currentRow = $(this).closest('[data-social-item]');
				currentRow.find('.mmsm-social-icon-id').val('0');
				currentRow.find('.mmsm-social-icon-preview').attr('src', '').addClass('is-hidden');
				$(this).addClass('is-hidden');
				updateSocialRowPreview(currentRow);
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

	const contactBuilder = $('.mmsm-contact-channels-builder');

	if (contactBuilder.length) {
		const list = contactBuilder.find('.mmsm-contact-channel-list');
		const template = contactBuilder.find('.mmsm-contact-channel-template').html();
		const channelLabels = {
			whatsapp: __('WhatsApp', 'maneuvrez-maintenance-studio'),
			messenger: __('Messenger', 'maneuvrez-maintenance-studio'),
			phone: __('Phone', 'maneuvrez-maintenance-studio'),
			email: __('Email', 'maneuvrez-maintenance-studio'),
			directions: __('Directions', 'maneuvrez-maintenance-studio'),
			custom: __('Custom Link', 'maneuvrez-maintenance-studio'),
		};
		const channelIcons = {
			whatsapp: 'dashicons-format-chat',
			messenger: 'dashicons-format-chat',
			phone: 'dashicons-phone',
			email: 'dashicons-email-alt',
			directions: 'dashicons-location-alt',
			custom: 'dashicons-admin-links',
		};
		const channelDefaults = {
			whatsapp: {
				label: __('Chat on WhatsApp', 'maneuvrez-maintenance-studio'),
				placeholder: '+923001234567',
				help: __('Use a country code selector with a local number, or enter a full international number starting with +.', 'maneuvrez-maintenance-studio'),
			},
			messenger: {
				label: __('Message on Messenger', 'maneuvrez-maintenance-studio'),
				placeholder: 'your-page-name or https://m.me/your-page-name',
				help: __('Enter a Messenger username/page name, or a valid Messenger/Facebook URL.', 'maneuvrez-maintenance-studio'),
			},
			phone: {
				label: __('Call Now', 'maneuvrez-maintenance-studio'),
				placeholder: '+923001234567',
				help: __('Use a country code selector with a local number, or enter a full international phone number starting with +.', 'maneuvrez-maintenance-studio'),
			},
			email: {
				label: __('Email Us', 'maneuvrez-maintenance-studio'),
				placeholder: 'hello@example.com',
				help: __('Enter a single email address. The public button will open a mail app.', 'maneuvrez-maintenance-studio'),
			},
			directions: {
				label: __('Get Directions', 'maneuvrez-maintenance-studio'),
				placeholder: 'https://maps.google.com/...',
				help: __('Paste a public maps or directions URL. No map embed or script is loaded.', 'maneuvrez-maintenance-studio'),
			},
			custom: {
				label: __('Open Link', 'maneuvrez-maintenance-studio'),
				placeholder: 'https://example.com/contact',
				help: __('Use a normal http or https link only.', 'maneuvrez-maintenance-studio'),
			},
		};

		const getRowIconClass = (row, type) => {
			const iconSource = row.find('.mmsm-contact-channel-icon-source').val();
			const iconValue = String(row.find('.mmsm-contact-channel-icon-value-field select').val() || '').trim();

			if (iconSource === 'none') {
				return '';
			}

			if (iconSource === 'dashicons' && iconValue) {
				return `dashicons-${iconValue}`;
			}

			return channelIcons[type] || channelIcons.custom;
		};

		const ensureOneContactRow = () => {
			if (list.children('[data-contact-channel-item]').length) {
				return;
			}

			addContactRow();
		};

		const updateContactStatus = () => {
			const enabled = contactBuilder.find('input[name$="[contact_channels_enabled]"]').prop('checked');
			const maintenance = contactBuilder.find('select[name$="[contact_channels_maintenance_display]"]').val();
			const live = contactBuilder.find('select[name$="[contact_channels_live_display]"]').val();
			const displayStyle = contactBuilder.find('select[name$="[contact_channels_display_style]"]').val();
			const validRows = list.children('[data-contact-channel-item]').filter(function hasDestination() {
				return String($(this).find('.mmsm-contact-channel-value').val() || '').trim() !== '';
			}).length;
			const destinations = [];
			const hasPublicDisplay = enabled && ((maintenance && maintenance !== 'off') || live === 'floating');
			const hasFloating = enabled && (live === 'floating' || maintenance === 'floating' || maintenance === 'both');
			const hasInsideMaintenance = enabled && (maintenance === 'inside' || maintenance === 'both');
			const needsTriggerLabel = hasFloating || (hasInsideMaintenance && displayStyle === 'reveal');

			if (maintenance && maintenance !== 'off') {
				if (maintenance === 'both') {
					destinations.push(__('maintenance page', 'maneuvrez-maintenance-studio'));
					destinations.push(__('maintenance floating button', 'maneuvrez-maintenance-studio'));
				} else {
					destinations.push(maintenance === 'inside' ? __('maintenance page', 'maneuvrez-maintenance-studio') : __('maintenance floating button', 'maneuvrez-maintenance-studio'));
				}
			}

			if (live === 'floating') {
				destinations.push(__('live-site floating button', 'maneuvrez-maintenance-studio'));
			}

			contactBuilder.toggleClass('is-contact-channels-disabled', !enabled);
			contactBuilder.find('.mmsm-contact-channels-enabled-fields').toggleClass('is-hidden', !enabled);
			contactBuilder.find('.mmsm-contact-display-dependent').toggleClass('is-hidden', !hasPublicDisplay);
			contactBuilder.find('.mmsm-contact-live-dependent').toggleClass('is-hidden', !(enabled && live === 'floating'));
			contactBuilder.find('.mmsm-contact-floating-dependent').toggleClass('is-hidden', !hasFloating);
			contactBuilder.find('.mmsm-contact-maintenance-inside-dependent').toggleClass('is-hidden', !hasInsideMaintenance);
			contactBuilder.find('.mmsm-contact-trigger-label-dependent').toggleClass('is-hidden', !needsTriggerLabel);
			contactBuilder.find('[data-contact-channels-status]')
				.toggleClass('is-on', enabled)
				.text(enabled ? __('On', 'maneuvrez-maintenance-studio') : __('Off', 'maneuvrez-maintenance-studio'));
			contactBuilder.find('[data-contact-channels-summary]').text(
				enabled
					? `${validRows} ${validRows === 1 ? __('configured row', 'maneuvrez-maintenance-studio') : __('configured rows', 'maneuvrez-maintenance-studio')} · ${destinations.length ? destinations.join(', ') : __('no public display selected', 'maneuvrez-maintenance-studio')}`
					: __('Turn them on when you are ready to show visitor contact buttons.', 'maneuvrez-maintenance-studio')
			);
			contactBuilder.find('[data-contact-step="enabled"]').toggleClass('is-complete', enabled);
			contactBuilder.find('[data-contact-step="display"]').toggleClass('is-complete', hasPublicDisplay);
			contactBuilder.find('[data-contact-step="channels"]').toggleClass('is-complete', validRows > 0);
			updateContactPreview();
		};

		const getContactRowsForPreview = () => {
			const rows = [];

			list.children('[data-contact-channel-item]').each(function collectRows() {
				const row = $(this);
				const type = row.find('.mmsm-contact-channel-type').val();
				const value = String(row.find('.mmsm-contact-channel-value').val() || '').trim();
				const customLabel = String(row.find('.mmsm-contact-channel-label-input').val() || '').trim();
				const iconSource = row.find('.mmsm-contact-channel-icon-source').val();
				const iconValue = String(row.find('.mmsm-contact-channel-icon-value-field select').val() || '').trim();

				if (!value) {
					return;
				}

				rows.push({
					type,
					label: customLabel || (channelDefaults[type] || channelDefaults.custom).label,
					icon: getRowIconClass(row, type),
				});
			});

			return rows;
		};

		const updateContactPreview = () => {
			const enabled = contactBuilder.find('input[name$="[contact_channels_enabled]"]').prop('checked');
			const maintenance = contactBuilder.find('select[name$="[contact_channels_maintenance_display]"]').val();
			const live = contactBuilder.find('select[name$="[contact_channels_live_display]"]').val();
			const shape = contactBuilder.find('select[name$="[contact_channels_button_shape]"]').val() || 'rounded';
			const display = contactBuilder.find('select[name$="[contact_channels_button_display]"]').val() || 'icon_label';
			const colorMode = contactBuilder.find('select[name$="[contact_channels_color_mode]"]').val() || 'theme';
			const position = contactBuilder.find('select[name$="[contact_channels_position]"]').val() || 'bottom_right';
			const heading = String(contactBuilder.find('input[name$="[contact_channels_heading]"]').val() || '').trim() || __('Need help?', 'maneuvrez-maintenance-studio');
			const description = String(contactBuilder.find('input[name$="[contact_channels_description]"]').val() || '').trim();
			const floatingLabel = String(contactBuilder.find('input[name$="[contact_channels_primary_label]"]').val() || '').trim() || __('Contact Us', 'maneuvrez-maintenance-studio');
			const rows = getContactRowsForPreview();
			const hasInsideMaintenance = enabled && (maintenance === 'inside' || maintenance === 'both');
			const hasFloating = enabled && (live === 'floating' || maintenance === 'floating' || maintenance === 'both');
			const hasPublicDisplay = hasInsideMaintenance || hasFloating;
			const stage = contactBuilder.find('[data-contact-preview-stage]');
			const buttons = contactBuilder.find('[data-contact-preview-buttons]');
			const previewCard = contactBuilder.find('.mmsm-contact-channels-preview-card');
			const floating = contactBuilder.find('[data-contact-preview-floating]');
			const previewNote = contactBuilder.find('[data-contact-preview-note]');

			stage
				.removeClass('is-shape-rounded is-shape-pill is-shape-circle is-shape-square is-display-icon_label is-display-icon_only is-display-label_only is-color-theme is-color-brand is-color-custom is-position-bottom_left is-position-bottom_right is-position-top_left is-position-top_right is-empty is-disabled')
				.addClass(`is-shape-${shape}`)
				.addClass(`is-display-${display}`)
				.addClass(`is-color-${colorMode}`)
				.addClass(`is-position-${position}`)
				.toggleClass('is-empty', rows.length === 0)
				.toggleClass('is-disabled', !enabled || !hasPublicDisplay);

			stage.css({
				'--mmsm-contact-preview-bg': contactBuilder.find('input[name$="[contact_channels_background_color]"]').val() || '#2271b1',
				'--mmsm-contact-preview-text': contactBuilder.find('input[name$="[contact_channels_text_color]"]').val() || '#ffffff',
				'--mmsm-contact-preview-icon': contactBuilder.find('input[name$="[contact_channels_icon_color]"]').val() || contactBuilder.find('input[name$="[contact_channels_text_color]"]').val() || '#ffffff',
				'--mmsm-contact-preview-hover-bg': contactBuilder.find('input[name$="[contact_channels_hover_background_color]"]').val() || contactBuilder.find('input[name$="[contact_channels_background_color]"]').val() || '#135e96',
				'--mmsm-contact-preview-hover-text': contactBuilder.find('input[name$="[contact_channels_hover_text_color]"]').val() || contactBuilder.find('input[name$="[contact_channels_text_color]"]').val() || '#ffffff',
			});

			contactBuilder.find('[data-contact-preview-heading]').text(heading);
			contactBuilder.find('[data-contact-preview-description]').text(
				description || (
					enabled
						? __('Contact buttons will appear here with the selected display style.', 'maneuvrez-maintenance-studio')
						: __('Turn on Contact Channels to publish this visitor contact path.', 'maneuvrez-maintenance-studio')
				)
			);
			contactBuilder.find('[data-contact-preview-floating-label]').text(floatingLabel);
			contactBuilder.find('[data-contact-preview-count]').text(
				rows.length
					? `${rows.length} ${__('ready', 'maneuvrez-maintenance-studio')}`
					: __('Preview', 'maneuvrez-maintenance-studio')
			);

			buttons.empty();
			rows.forEach((row) => {
				const button = $('<span />', {
					class: `mmsm-contact-preview-button is-${row.type}`,
				});
				if (row.icon) {
					button.append($('<span />', {
						class: `dashicons ${row.icon}`,
						'aria-hidden': 'true',
					}));
				}
				button.append($('<span />', {
					class: 'mmsm-contact-preview-label',
					text: row.label,
				}));
				buttons.append(button);
			});

			floating.find('.dashicons')
				.removeClass((index, className) => (className.match(/dashicons-[^\s]+/g) || []).join(' '))
				.addClass(rows.length === 1 && rows[0].icon ? rows[0].icon : 'dashicons-format-chat')
				.toggle(rows.length !== 1 || !!rows[0].icon);

			previewCard.toggle(hasInsideMaintenance || rows.length === 0 || !hasPublicDisplay);
			floating.toggle(hasFloating && rows.length > 0);
			previewNote.text(
				!enabled
					? __('Preview is live, but Contact Channels are currently off.', 'maneuvrez-maintenance-studio')
					: (
						hasPublicDisplay
							? __('Preview updates instantly as you change placement, labels, colors, and destinations.', 'maneuvrez-maintenance-studio')
							: __('Choose a maintenance or live-site placement to publish these buttons.', 'maneuvrez-maintenance-studio')
					)
			);
		};

		const toggleCustomColorFields = () => {
			const colorMode = contactBuilder.find('select[name$="[contact_channels_color_mode]"]').val();
			contactBuilder.find('.mmsm-contact-channel-custom-colors').toggleClass('is-hidden', colorMode !== 'custom');
		};

		const switchCustomColorPanel = (button) => {
			const group = button.closest('[data-contact-color-group]');
			const target = button.data('contactColorTab');

			group.find('[data-contact-color-tab]')
				.removeClass('is-active')
				.attr('aria-selected', 'false');
			button
				.addClass('is-active')
				.attr('aria-selected', 'true');
			group.find('[data-contact-color-panel]')
				.removeClass('is-active')
				.filter(`[data-contact-color-panel="${target}"]`)
				.addClass('is-active');
		};

		const toggleContactRowFields = (row) => {
			const type = row.find('.mmsm-contact-channel-type').val();
			const iconSource = row.find('.mmsm-contact-channel-icon-source').val();
			const isPhoneLike = type === 'whatsapp' || type === 'phone';
			const defaults = channelDefaults[type] || channelDefaults.custom;
			const value = String(row.find('.mmsm-contact-channel-value').val() || '').trim();
			const label = String(row.find('.mmsm-contact-channel-label-input').val() || '').trim();
			const iconClass = getRowIconClass(row, type);

			row.find('.mmsm-contact-channel-country-field').toggleClass('is-hidden', !isPhoneLike);
			row.find('.mmsm-contact-channel-message-field').toggleClass('is-hidden', type !== 'whatsapp');
			row.find('.mmsm-contact-channel-icon-value-field').toggleClass('is-hidden', iconSource !== 'dashicons');
			row.find('.mmsm-contact-channel-value').attr('placeholder', defaults.placeholder);
			row.find('.mmsm-contact-channel-label-input').attr('placeholder', defaults.label);
			row.find('[data-contact-channel-help]').text(defaults.help);
			row.find('[data-contact-channel-summary]').text(label || defaults.label);
			row.find('[data-contact-channel-subsummary]').text(
				value
					? `${channelLabels[type] || type}: ${value}`
					: __('Add a destination before this channel can appear publicly.', 'maneuvrez-maintenance-studio')
			);
			row.find('[data-contact-channel-state]')
				.toggleClass('is-ready', !!value)
				.text(value ? __('Ready', 'maneuvrez-maintenance-studio') : __('Needs destination', 'maneuvrez-maintenance-studio'));
			row.find('[data-contact-channel-icon-preview]')
				.removeClass((index, className) => (className.match(/dashicons-[^\s]+/g) || []).join(' '))
				.addClass(iconClass || 'dashicons-hidden')
				.toggleClass('is-hidden', !iconClass);
		};

		const normalizePairedPhoneField = (row) => {
			const type = row.find('.mmsm-contact-channel-type').val();

			if (type !== 'whatsapp' && type !== 'phone') {
				return;
			}

			const countryCode = String(row.find('.mmsm-contact-channel-country-code').val() || '').replace(/\D/g, '');
			const valueField = row.find('.mmsm-contact-channel-value');
			const value = String(valueField.val() || '');
			const valueDigits = value.replace(/\D/g, '');

			if (!countryCode || !valueDigits || !valueDigits.startsWith(countryCode)) {
				return;
			}

			const localValue = valueDigits.slice(countryCode.length);

			if (localValue) {
				valueField.val(localValue);
			}
		};

		const bindContactRow = (row) => {
			toggleContactRowFields(row);

			row.on('change', '.mmsm-contact-channel-type', function onContactTypeChange() {
				normalizePairedPhoneField($(this).closest('[data-contact-channel-item]'));
				toggleContactRowFields($(this).closest('[data-contact-channel-item]'));
				updateContactStatus();
			});

			row.on('change', '.mmsm-contact-channel-country-code', function onCountryCodeChange() {
				const currentRow = $(this).closest('[data-contact-channel-item]');
				normalizePairedPhoneField(currentRow);
				toggleContactRowFields(currentRow);
				updateContactStatus();
			});

			row.on('change', '.mmsm-contact-channel-icon-source', function onContactIconSourceChange() {
				toggleContactRowFields($(this).closest('[data-contact-channel-item]'));
				updateContactStatus();
			});

			row.on('change', '.mmsm-contact-channel-icon-value-field select', function onContactIconValueChange() {
				updateContactStatus();
			});

			row.on('input', '.mmsm-contact-channel-value, .mmsm-contact-channel-label-input', function onContactValueInput() {
				const currentRow = $(this).closest('[data-contact-channel-item]');
				toggleContactRowFields(currentRow);
				updateContactStatus();
			});

			row.on('change', '.mmsm-contact-channel-value', function onContactValueChange() {
				const currentRow = $(this).closest('[data-contact-channel-item]');
				normalizePairedPhoneField(currentRow);
				toggleContactRowFields(currentRow);
				updateContactStatus();
			});

			row.on('click', '.mmsm-remove-contact-channel', function onRemoveContactChannel() {
				$(this).closest('[data-contact-channel-item]').remove();
				ensureOneContactRow();
				updateContactStatus();
			});
		};

		const addContactRow = () => {
			const nextIndex = Number(contactBuilder.attr('data-next-index')) || 0;
			const markup = template.replace(/__INDEX__/g, String(nextIndex));
			const row = $(markup);

			contactBuilder.attr('data-next-index', String(nextIndex + 1));
			list.append(row);
			bindContactRow(row);
			updateContactStatus();
		};

		list.children('[data-contact-channel-item]').each(function initContactRow() {
			bindContactRow($(this));
		});

		contactBuilder.on('change', 'select[name$="[contact_channels_color_mode]"]', () => {
			toggleCustomColorFields();
			updateContactStatus();
		});
		contactBuilder.on('change', 'input[name$="[contact_channels_enabled]"], select[name$="[contact_channels_maintenance_display]"], select[name$="[contact_channels_live_display]"], select[name$="[contact_channels_display_style]"], select[name$="[contact_channels_position]"], select[name$="[contact_channels_button_shape]"], select[name$="[contact_channels_button_display]"]', () => {
			toggleCustomColorFields();
			updateContactStatus();
		});
		contactBuilder.on('input change', 'input[name$="[contact_channels_heading]"], input[name$="[contact_channels_description]"], input[name$="[contact_channels_primary_label]"], input[name$="[contact_channels_background_color]"], input[name$="[contact_channels_text_color]"], input[name$="[contact_channels_icon_color]"], input[name$="[contact_channels_hover_background_color]"], input[name$="[contact_channels_hover_text_color]"]', updateContactStatus);
		contactBuilder.on('click', '[data-contact-color-tab]', function onContactColorStateClick() {
			switchCustomColorPanel($(this));
		});
		contactBuilder.on('click', '.mmsm-add-contact-channel', function onAddContactChannel(event) {
			event.preventDefault();
			addContactRow();
		});

		toggleCustomColorFields();
		updateContactStatus();
	}
});
