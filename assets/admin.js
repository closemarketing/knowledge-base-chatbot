/**
 * Multichats Admin Script
 *
 * @package    CLOSE\KnowledgeBaseChatbot
 * @author     Closemarketing
 * @copyright  2025 Closemarketing
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		let fileFrame;
		const i18n = (typeof knowledgeBaseChatbotSettings !== 'undefined' && knowledgeBaseChatbotSettings.i18n) ? knowledgeBaseChatbotSettings.i18n : {};

		// Handle icon upload button.
		$(document).on('click', '.knowledge-base-chatbot-upload-icon', function(e) {
			e.preventDefault();

			const button = $(this);
			const fieldId = button.data('field-id');
			const fileInput = $('#' + fieldId);
			const preview = button.closest('.knowledge-base-chatbot-icon-field').find('.knowledge-base-chatbot-icon-preview');
			const removeBtn = button.closest('.knowledge-base-chatbot-icon-field').find('.knowledge-base-chatbot-remove-icon');

			// If the media frame already exists, reopen it.
			if (fileFrame) {
				fileFrame.open();
				return;
			}

			// Create the media frame.
			fileFrame = wp.media({
				title: i18n.mediaTitle || 'Select Icon (SVG or PNG)',
				button: {
					text: i18n.mediaButton || 'Use this icon',
				},
				multiple: false,
				library: {
					type: ['image/svg+xml', 'image/png'],
				},
			});

			// When a file is selected, run a callback.
			fileFrame.on('select', function() {
				const attachment = fileFrame.state().get('selection').first().toJSON();
				
				// Validate that it's an SVG or PNG file.
				const allowedMimes = ['image/svg+xml', 'image/png'];
				if (attachment.mime && !allowedMimes.includes(attachment.mime)) {
					alert(i18n.svgOrPngOnly || 'Please select only SVG or PNG files.');
					return;
				}
				
				// Check file extension as fallback.
				const filename = attachment.filename ? attachment.filename.toLowerCase() : '';
				if (filename && !filename.endsWith('.svg') && !filename.endsWith('.png')) {
					alert(i18n.svgOrPngOnly || 'Please select only SVG or PNG files.');
					return;
				}
				
				fileInput.val(attachment.id);
				preview.html('<img src="' + attachment.url + '" alt="' + (i18n.chatIconAlt || 'Chat icon') + '" style="max-width: 100px; height: auto; display: block;" />');
				removeBtn.show();
			});

			// Open the modal.
			fileFrame.open();
		});

		// Handle icon remove button.
		$(document).on('click', '.knowledge-base-chatbot-remove-icon', function(e) {
			e.preventDefault();

			const button = $(this);
			const fieldId = button.data('field-id');
			const fileInput = $('#' + fieldId);
			const preview = button.closest('.knowledge-base-chatbot-icon-field').find('.knowledge-base-chatbot-icon-preview');

			fileInput.val('');
			preview.html('<p class="description">' + (i18n.noIconSelected || 'No icon selected. Default icon will be used.') + '</p>');
			button.hide();
		});
	});
})(jQuery);

