jQuery(document).ready(function ($) {
	const i18n = (typeof knowledgeBaseChatbotAdmin !== 'undefined' && knowledgeBaseChatbotAdmin.i18n) ? knowledgeBaseChatbotAdmin.i18n : {};

	// Tab switching
	$('.nav-tab-wrapper .nav-tab').on('click', function (e) {
		e.preventDefault();
		const tab = $(this).data('tab');
		
		// Update tab navigation
		$('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		
		// Update tab content
		$('.knowledge-base-chatbot-tab-content').removeClass('knowledge-base-chatbot-tab-active');
		$('#knowledge-base-chatbot-tab-' + tab).addClass('knowledge-base-chatbot-tab-active');
	});

	// Select all items (pages or posts)
	$('.knowledge-base-chatbot-select-all').on('click', function () {
		const type = $(this).data('type');
		$('.knowledge-base-chatbot-item-checkbox[data-type="' + type + '"]').prop('checked', true);
	});

	// Deselect all items (pages or posts)
	$('.knowledge-base-chatbot-deselect-all').on('click', function () {
		const type = $(this).data('type');
		$('.knowledge-base-chatbot-item-checkbox[data-type="' + type + '"]').prop('checked', false);
	});

	// Export selected items (any post type)
	$('.knowledge-base-chatbot-export-selected').on('click', function () {
		const type = $(this).data('type');
		const selectedItems = $('.knowledge-base-chatbot-item-checkbox[data-type="' + type + '"]:checked')
			.map(function () {
				return $(this).val();
			})
			.get();

		if (selectedItems.length === 0) {
			showMessage(
				i18n.selectAtLeastOneToExport || 'Please select at least one item to export.',
				'error',
				type
			);
			return;
		}

		exportSelected(type, selectedItems);
	});

	// Export all items (any post type)
	$('.knowledge-base-chatbot-export-all').on('click', function () {
		const type = $(this).data('type');
		
		if (
			!confirm(
				i18n.confirmExportAll || 'Are you sure you want to export all items of this type?'
			)
		) {
			return;
		}

		exportAll(type);
	});

	/**
	 * Export selected items (generic for any post type)
	 *
	 * @param {string} postType Post type.
	 * @param {Array} postIds Array of post IDs.
	 */
	function exportSelected(postType, postIds) {
		const $button = $('.knowledge-base-chatbot-export-selected[data-type="' + postType + '"]');
		const originalText = $button.text();
		$button.prop('disabled', true).text(i18n.exporting || 'Exporting...');

		// Create form and submit
		const form = $('<form>', {
			method: 'POST',
			action: knowledgeBaseChatbotAdmin.ajaxUrl,
		});

		form.append(
			$('<input>', {
				type: 'hidden',
				name: 'action',
				value: 'knowledge-base-chatbot_export_selected',
			})
		);

		form.append(
			$('<input>', {
				type: 'hidden',
				name: 'nonce',
				value: knowledgeBaseChatbotAdmin.nonce,
			})
		);

		form.append(
			$('<input>', {
				type: 'hidden',
				name: 'post_type',
				value: postType,
			})
		);

		postIds.forEach(function (postId) {
			form.append(
				$('<input>', {
					type: 'hidden',
					name: 'post_ids[]',
					value: postId,
				})
			);
		});

		$('body').append(form);
		form.submit();
		form.remove();

		setTimeout(function () {
			$button.prop('disabled', false).text(originalText);
		}, 2000);
	}

	/**
	 * Export all items (generic for any post type)
	 *
	 * @param {string} postType Post type.
	 */
	function exportAll(postType) {
		const $button = $('.knowledge-base-chatbot-export-all[data-type="' + postType + '"]');
		const originalText = $button.text();
		$button.prop('disabled', true).text(i18n.exporting || 'Exporting...');

		// Create form and submit
		const form = $('<form>', {
			method: 'POST',
			action: knowledgeBaseChatbotAdmin.ajaxUrl,
		});

		form.append(
			$('<input>', {
				type: 'hidden',
				name: 'action',
				value: 'knowledge-base-chatbot_export_all',
			})
		);

		form.append(
			$('<input>', {
				type: 'hidden',
				name: 'nonce',
				value: knowledgeBaseChatbotAdmin.nonce,
			})
		);

		form.append(
			$('<input>', {
				type: 'hidden',
				name: 'post_type',
				value: postType,
			})
		);

		$('body').append(form);
		form.submit();
		form.remove();

		setTimeout(function () {
			$button.prop('disabled', false).text(originalText);
		}, 2000);
	}

	/**
	 * Show message
	 *
	 * @param {string} message Message text.
	 * @param {string} type Message type (success, error, warning).
	 * @param {string} itemType Item type (pages or posts).
	 */
	function showMessage(message, type, itemType) {
		const $message = $('.knowledge-base-chatbot-export-message[data-type="' + itemType + '"]');
		$message
			.removeClass('notice-success notice-error notice-warning')
			.addClass('notice notice-' + type + ' is-dismissible')
			.html('<p>' + message + '</p>')
			.show();

		setTimeout(function () {
			$message.fadeOut();
		}, 5000);
	}
});
