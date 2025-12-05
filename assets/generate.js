jQuery(document).ready(function ($) {
	// Tab switching
	$('.nav-tab-wrapper .nav-tab').on('click', function (e) {
		e.preventDefault();
		const tab = $(this).data('tab');
		
		// Update tab navigation.
		$('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		
		// Update tab content.
		$('.kbcb-tab-content').removeClass('kbcb-tab-active');
		$('#kbcb-tab-' + tab).addClass('kbcb-tab-active');
	});

	// Select all items.
	$('.kbcb-select-all').on('click', function () {
		const type = $(this).data('type');
		$('.kbcb-item-checkbox[data-type="' + type + '"]').not(':disabled').prop('checked', true);
	});

	// Deselect all items.
	$('.kbcb-deselect-all').on('click', function () {
		const type = $(this).data('type');
		$('.kbcb-item-checkbox[data-type="' + type + '"]').prop('checked', false);
	});

	// Add selected items.
	$('.kbcb-add-selected').on('click', function () {
		const type = $(this).data('type');
		const selectedItems = $('.kbcb-item-checkbox[data-type="' + type + '"]:checked')
			.map(function () {
				return $(this).val();
			})
			.get();

		if (selectedItems.length === 0) {
			showMessage('Por favor, selecciona al menos un elemento.', 'error');
			return;
		}

		addPages(selectedItems);
	});

	// Remove item from selected list.
	$(document).on('click', '.kbcb-remove-item', function () {
		const postId = $(this).data('id');
		removePage(postId);
	});

	// Save order button.
	$('#kbcb-save-order').on('click', function () {
		saveOrder();
	});

	// Regenerate button.
	$('#kbcb-regenerate').on('click', function () {
		regenerateFile();
	});

	// Copy URL button.
	$(document).on('click', '.kbcb-copy-url', function () {
		const $input = $(this).prev('input');
		$input.select();
		document.execCommand('copy');
		showMessage('URL copiada al portapapeles.', 'success');
	});

	// Make selected list sortable.
	if ($('#kbcb-selected-list').length) {
		$('#kbcb-selected-list').sortable({
			handle: '.kbcb-drag-handle',
			placeholder: 'kbcb-selected-item-placeholder',
			update: function () {
				// Optional: Auto-save on reorder.
				// saveOrder();
			}
		});
	}

	/**
	 * Add pages to selected list
	 *
	 * @param {Array} postIds Array of post IDs.
	 */
	function addPages(postIds) {
		const $button = $('.kbcb-add-selected');
		const originalText = $button.text();
		$button.prop('disabled', true).text('Añadiendo...');

		$.ajax({
			url: kbcbGenerate.ajaxUrl,
			type: 'POST',
			data: {
				action: 'kbcb_add_pages',
				nonce: kbcbGenerate.nonce,
				post_ids: postIds
			},
			success: function (response) {
				if (response.success) {
					showMessage(response.data.message, 'success');
					
					// Update file info if provided.
					updateFileInfo(response.data.fileUrl, response.data.fileDate);
					
					// Reload page to update selected list.
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					showMessage(response.data.message || 'Error al añadir páginas.', 'error');
				}
			},
			error: function () {
				showMessage('Error de comunicación con el servidor.', 'error');
			},
			complete: function () {
				$button.prop('disabled', false).text(originalText);
			}
		});
	}

	/**
	 * Remove page from selected list
	 *
	 * @param {number} postId Post ID.
	 */
	function removePage(postId) {
		if (!confirm('¿Estás seguro de eliminar esta página de la lista?')) {
			return;
		}

		$.ajax({
			url: kbcbGenerate.ajaxUrl,
			type: 'POST',
			data: {
				action: 'kbcb_remove_page',
				nonce: kbcbGenerate.nonce,
				post_id: postId
			},
			success: function (response) {
				if (response.success) {
					// Remove item from DOM.
					$('.kbcb-selected-item[data-id="' + postId + '"]').fadeOut(function () {
						$(this).remove();
						
						// Show empty message if no items left.
						if ($('.kbcb-selected-item').length === 0) {
							$('#kbcb-selected-list').html('<p class="kbcb-empty-message">No hay páginas seleccionadas. Añade páginas desde las pestañas de arriba.</p>');
						}
					});

					// Uncheck checkbox if exists.
					$('.kbcb-item-checkbox[value="' + postId + '"]').prop('checked', false).closest('.kbcb-item').removeClass('kbcb-item-selected');

					// Update file info.
					updateFileInfo(response.data.fileUrl, response.data.fileDate);

					showMessage(response.data.message, 'success');
				} else {
					showMessage(response.data.message || 'Error al eliminar página.', 'error');
				}
			},
			error: function () {
				showMessage('Error de comunicación con el servidor.', 'error');
			}
		});
	}

	/**
	 * Save order of selected pages
	 */
	function saveOrder() {
		const order = [];
		$('.kbcb-selected-item').each(function () {
			order.push($(this).data('id'));
		});

		if (order.length === 0) {
			showMessage('No hay páginas para guardar.', 'error');
			return;
		}

		const $button = $('#kbcb-save-order');
		const originalText = $button.text();
		$button.prop('disabled', true).text('Guardando...');

		$.ajax({
			url: kbcbGenerate.ajaxUrl,
			type: 'POST',
			data: {
				action: 'kbcb_save_order',
				nonce: kbcbGenerate.nonce,
				order: order
			},
			success: function (response) {
				if (response.success) {
					showMessage(response.data.message, 'success');
				} else {
					showMessage(response.data.message || 'Error al guardar orden.', 'error');
				}
			},
			error: function () {
				showMessage('Error de comunicación con el servidor.', 'error');
			},
			complete: function () {
				$button.prop('disabled', false).text(originalText);
			}
		});
	}

	/**
	 * Regenerate markdown file
	 */
	function regenerateFile() {
		if (!confirm('¿Estás seguro de regenerar el archivo? Esto sobrescribirá el archivo existente.')) {
			return;
		}

		const $button = $('#kbcb-regenerate');
		const originalText = $button.text();
		$button.prop('disabled', true).text('Generando...');

		$.ajax({
			url: kbcbGenerate.ajaxUrl,
			type: 'POST',
			data: {
				action: 'kbcb_regenerate',
				nonce: kbcbGenerate.nonce
			},
			success: function (response) {
				if (response.success) {
					showMessage(response.data.message, 'success');
					
					// Update file info.
					updateFileInfo(response.data.fileUrl, response.data.fileDate);
				} else {
					showMessage(response.data.message || 'Error al generar archivo.', 'error');
				}
			},
			error: function () {
				showMessage('Error de comunicación con el servidor.', 'error');
			},
			complete: function () {
				$button.prop('disabled', false).text(originalText);
			}
		});
	}

	/**
	 * Update file info
	 *
	 * @param {string} fileUrl File URL.
	 * @param {string} fileDate File date.
	 */
	function updateFileInfo(fileUrl, fileDate) {
		if (!fileUrl) {
			return;
		}

		// Update or create file info section.
		if ($('.kbcb-file-info').length) {
			$('.kbcb-file-url input').val(fileUrl);
			if (fileDate && $('#kbcb-file-date-value').length) {
				$('#kbcb-file-date-value').text(fileDate);
			} else if (fileDate) {
				// Add date if it doesn't exist.
				$('.kbcb-file-url').after(`
					<div class="kbcb-file-date">
						<span class="dashicons dashicons-calendar-alt"></span>
						<strong>Última actualización:</strong>
						<span id="kbcb-file-date-value">${fileDate}</span>
						<span class="kbcb-file-date-utc">UTC</span>
					</div>
				`);
			}
		} else {
			// Create new file info section.
			const fileHtml = `
				<div class="kbcb-file-info">
					<h3>Archivo Generado</h3>
					<div class="kbcb-file-url">
						<input type="text" readonly value="${fileUrl}" />
						<button type="button" class="button kbcb-copy-url" title="Copiar URL">
							<span class="dashicons dashicons-admin-page"></span>
						</button>
					</div>
					${fileDate ? `
						<div class="kbcb-file-date">
							<span class="dashicons dashicons-calendar-alt"></span>
							<strong>Última actualización:</strong>
							<span id="kbcb-file-date-value">${fileDate}</span>
							<span class="kbcb-file-date-utc">UTC</span>
						</div>
					` : ''}
				</div>
			`;
			$('#kbcb-message').before(fileHtml);
		}
	}

	/**
	 * Show message
	 *
	 * @param {string} message Message text.
	 * @param {string} type Message type (success, error, warning).
	 */
	function showMessage(message, type) {
		const $message = $('#kbcb-message');
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
