<?php
/**
 * Generate Page Class
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */

namespace CLOSE\KnowledgeBaseChatbot\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Generate Page Class
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */
class GeneratePage {

	/**
	 * Option name for selected pages
	 *
	 * @var string
	 */
	const OPTION_SELECTED_PAGES = 'kbcb_selected_pages';

	/**
	 * File name for generated markdown
	 *
	 * @var string
	 */
	const GENERATED_FILE_NAME = 'llm-knowledge-chatbot.md';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_generate_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_kbcb_add_pages', array( $this, 'handle_add_pages' ) );
		add_action( 'wp_ajax_kbcb_remove_page', array( $this, 'handle_remove_page' ) );
		add_action( 'wp_ajax_kbcb_save_order', array( $this, 'handle_save_order' ) );
		add_action( 'wp_ajax_kbcb_regenerate', array( $this, 'handle_regenerate' ) );
	}

	/**
	 * Add generate page to WordPress admin
	 *
	 * @return void
	 */
	public function add_generate_page() {
		add_menu_page(
			__( 'KB Chatbot', 'knowledge-base-chatbot' ),
			__( 'KB Chatbot', 'knowledge-base-chatbot' ),
			'manage_options',
			'knowledge-base-chatbot-generate',
			array( $this, 'render_generate_page' ),
			'dashicons-format-chat',
			30
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_knowledge-base-chatbot-generate' !== $hook ) {
			return;
		}

		// Enqueue jQuery UI for sortable.
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'knowledge-base-chatbot-generate',
			KBCB_PLUGIN_URL . 'assets/generate.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			KBCB_VERSION,
			true
		);

		wp_localize_script(
			'knowledge-base-chatbot-generate',
			'kbcbGenerate',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'kbcb_generate' ),
				'fileUrl'  => $this->get_file_url(),
				'fileDate' => $this->get_file_date(),
			)
		);

		wp_enqueue_style(
			'knowledge-base-chatbot-generate',
			KBCB_PLUGIN_URL . 'assets/generate.css',
			array(),
			KBCB_VERSION
		);
	}

	/**
	 * Get all public post types
	 *
	 * @return array Array of post type objects.
	 */
	private function get_exportable_post_types() {
		$post_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);

		$excluded   = array( 'attachment' );
		$exportable = array();
		foreach ( $post_types as $post_type ) {
			if ( ! in_array( $post_type->name, $excluded, true ) ) {
				$exportable[ $post_type->name ] = $post_type;
			}
		}

		return $exportable;
	}

	/**
	 * Get selected pages
	 *
	 * @return array Array of selected page IDs with order.
	 */
	private function get_selected_pages() {
		$selected = get_option( self::OPTION_SELECTED_PAGES, array() );
		return is_array( $selected ) ? $selected : array();
	}

	/**
	 * Save selected pages
	 *
	 * @param array $pages Array of page IDs.
	 * @return bool
	 */
	private function save_selected_pages( $pages ) {
		return update_option( self::OPTION_SELECTED_PAGES, $pages );
	}

	/**
	 * Get file URL
	 *
	 * @return string
	 */
	private function get_file_url() {
		$file_path = ABSPATH . self::GENERATED_FILE_NAME;
		if ( file_exists( $file_path ) ) {
			return home_url( '/' . self::GENERATED_FILE_NAME );
		}
		return '';
	}

	/**
	 * Get file modification date
	 *
	 * @return string
	 */
	private function get_file_date() {
		$file_path = ABSPATH . self::GENERATED_FILE_NAME;
		if ( file_exists( $file_path ) ) {
			return gmdate( 'Y-m-d H:i:s', filemtime( $file_path ) );
		}
		return '';
	}

	/**
	 * Handle add pages AJAX
	 *
	 * @return void
	 */
	public function handle_add_pages() {
		check_ajax_referer( 'kbcb_generate', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No tienes permisos para realizar esta acción.', 'knowledge-base-chatbot' ) ) );
		}

		$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'intval', $_POST['post_ids'] ) : array();

		if ( empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No se seleccionaron páginas.', 'knowledge-base-chatbot' ) ) );
		}

		$selected = $this->get_selected_pages();

		// Add new pages to the end if they don't exist.
		foreach ( $post_ids as $post_id ) {
			if ( ! in_array( $post_id, $selected, true ) ) {
				$selected[] = $post_id;
			}
		}

		$this->save_selected_pages( $selected );

		// Auto-regenerate file when pages are added.
		$this->generate_markdown_file();

		// Get page details for response.
		$pages = array();
		foreach ( $selected as $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$pages[] = array(
					'id'    => $post_id,
					'title' => $post->post_title,
					'type'  => get_post_type_object( $post->post_type )->labels->singular_name,
				);
			}
		}

		wp_send_json_success(
			array(
				'message' => __( 'Páginas añadidas correctamente. Archivo regenerado.', 'knowledge-base-chatbot' ),
				'pages'   => $pages,
				'fileUrl' => $this->get_file_url(),
				'fileDate' => $this->get_file_date(),
			)
		);
	}

	/**
	 * Handle remove page AJAX
	 *
	 * @return void
	 */
	public function handle_remove_page() {
		check_ajax_referer( 'kbcb_generate', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No tienes permisos para realizar esta acción.', 'knowledge-base-chatbot' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'ID de página inválido.', 'knowledge-base-chatbot' ) ) );
		}

		$selected = $this->get_selected_pages();
		$key      = array_search( $post_id, $selected, true );

		if ( false !== $key ) {
			unset( $selected[ $key ] );
			$selected = array_values( $selected ); // Reindex array.
			$this->save_selected_pages( $selected );

			// Auto-regenerate file when page is removed.
			$this->generate_markdown_file();
		}

		wp_send_json_success(
			array(
				'message'  => __( 'Página eliminada correctamente. Archivo regenerado.', 'knowledge-base-chatbot' ),
				'fileUrl'  => $this->get_file_url(),
				'fileDate' => $this->get_file_date(),
			)
		);
	}

	/**
	 * Handle save order AJAX
	 *
	 * @return void
	 */
	public function handle_save_order() {
		check_ajax_referer( 'kbcb_generate', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No tienes permisos para realizar esta acción.', 'knowledge-base-chatbot' ) ) );
		}

		$order = isset( $_POST['order'] ) ? array_map( 'intval', $_POST['order'] ) : array();

		if ( empty( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'Orden inválido.', 'knowledge-base-chatbot' ) ) );
		}

		$this->save_selected_pages( $order );

		wp_send_json_success( array( 'message' => __( 'Orden guardado correctamente.', 'knowledge-base-chatbot' ) ) );
	}

	/**
	 * Handle regenerate AJAX
	 *
	 * @return void
	 */
	public function handle_regenerate() {
		check_ajax_referer( 'kbcb_generate', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'No tienes permisos para realizar esta acción.', 'knowledge-base-chatbot' ) ) );
		}

		$result = $this->generate_markdown_file();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'  => __( 'Archivo generado correctamente.', 'knowledge-base-chatbot' ),
				'fileUrl'  => $this->get_file_url(),
				'fileDate' => $this->get_file_date(),
			)
		);
	}

	/**
	 * Generate markdown file
	 *
	 * @return true|WP_Error
	 */
	private function generate_markdown_file() {
		$selected = $this->get_selected_pages();

		if ( empty( $selected ) ) {
			return new \WP_Error( 'no_pages', __( 'No hay páginas seleccionadas.', 'knowledge-base-chatbot' ) );
		}

		$markdown = "# Knowledge Base\n\n";
		$markdown .= "Generated on: " . gmdate( 'Y-m-d H:i:s' ) . " UTC\n\n";
		$markdown .= "---\n\n";

		foreach ( $selected as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$markdown .= "## " . $post->post_title . "\n\n";
			$markdown .= "**URL:** " . get_permalink( $post_id ) . "\n\n";
			$markdown .= "**Type:** " . get_post_type( $post_id ) . "\n\n";

			// Get content and clean it.
			$content = $post->post_content;
			$content = wp_strip_all_tags( $content );
			$content = html_entity_decode( $content );
			$content = preg_replace( '/\n\s*\n/', "\n\n", $content );

			$markdown .= $content . "\n\n";
			$markdown .= "---\n\n";
		}

		// Save file to WordPress root.
		$file_path = ABSPATH . self::GENERATED_FILE_NAME;
		$result    = file_put_contents( $file_path, $markdown );

		if ( false === $result ) {
			return new \WP_Error( 'file_error', __( 'Error al guardar el archivo.', 'knowledge-base-chatbot' ) );
		}

		return true;
	}

	/**
	 * Render generate page
	 *
	 * @return void
	 */
	public function render_generate_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post_types = $this->get_exportable_post_types();
		$selected   = $this->get_selected_pages();
		$first_tab  = true;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Generar Knowledge Base', 'knowledge-base-chatbot' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Selecciona las páginas que deseas incluir en el archivo de conocimiento y ordénalas según tus preferencias.', 'knowledge-base-chatbot' ); ?>
			</p>

			<div class="kbcb-generate-container">
				<!-- Selection Section -->
				<div class="kbcb-selection-section">
					<h2><?php esc_html_e( 'Seleccionar Páginas', 'knowledge-base-chatbot' ); ?></h2>
					
					<div class="kbcb-tabs">
						<nav class="nav-tab-wrapper">
							<?php foreach ( $post_types as $post_type_name => $post_type_obj ) : ?>
								<?php
								$tab_id    = 'kbcb-tab-' . $post_type_name;
								$tab_name  = $post_type_obj->labels->name;
								$active    = $first_tab ? 'nav-tab-active' : '';
								$first_tab = false;
								?>
								<a href="#<?php echo esc_attr( $tab_id ); ?>" class="nav-tab <?php echo esc_attr( $active ); ?>" data-tab="<?php echo esc_attr( $post_type_name ); ?>">
									<?php echo esc_html( $tab_name ); ?>
								</a>
							<?php endforeach; ?>
						</nav>

						<?php
						$first_content = true;
						foreach ( $post_types as $post_type_name => $post_type_obj ) :
							$tab_id        = 'kbcb-tab-' . $post_type_name;
							$tab_active    = $first_content ? 'kbcb-tab-active' : '';
							$first_content = false;

							// Get posts for this post type.
							$items = get_posts(
								array(
									'post_status' => 'publish',
									'numberposts' => -1,
									'post_type'   => $post_type_name,
									'orderby'     => 'title',
									'order'       => 'ASC',
								)
							);
							?>
							<div id="<?php echo esc_attr( $tab_id ); ?>" class="kbcb-tab-content <?php echo esc_attr( $tab_active ); ?>">
								<?php if ( ! empty( $items ) ) : ?>
									<div class="kbcb-items-list" data-type="<?php echo esc_attr( $post_type_name ); ?>">
										<?php foreach ( $items as $item ) : ?>
											<?php $is_selected = in_array( $item->ID, $selected, true ); ?>
											<label class="kbcb-item <?php echo $is_selected ? 'kbcb-item-selected' : ''; ?>">
												<input 
													type="checkbox" 
													class="kbcb-item-checkbox" 
													data-type="<?php echo esc_attr( $post_type_name ); ?>" 
													value="<?php echo esc_attr( $item->ID ); ?>"
													<?php checked( $is_selected ); ?>
												/>
												<span class="kbcb-item-title"><?php echo esc_html( $item->post_title ); ?></span>
												<span class="kbcb-item-type"><?php echo esc_html( $post_type_obj->labels->singular_name ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
									<div class="kbcb-controls">
										<button type="button" class="button kbcb-select-all" data-type="<?php echo esc_attr( $post_type_name ); ?>">
											<?php esc_html_e( 'Seleccionar todas', 'knowledge-base-chatbot' ); ?>
										</button>
										<button type="button" class="button kbcb-deselect-all" data-type="<?php echo esc_attr( $post_type_name ); ?>">
											<?php esc_html_e( 'Deseleccionar todas', 'knowledge-base-chatbot' ); ?>
										</button>
										<button type="button" class="button button-primary kbcb-add-selected" data-type="<?php echo esc_attr( $post_type_name ); ?>">
											<?php esc_html_e( 'Añadir seleccionadas', 'knowledge-base-chatbot' ); ?>
										</button>
									</div>
								<?php else : ?>
									<p>
										<?php
										/* translators: %s: Post type name */
										printf( esc_html__( 'No hay %s publicados.', 'knowledge-base-chatbot' ), esc_html( strtolower( $post_type_obj->labels->name ) ) );
										?>
									</p>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Selected Pages Section -->
				<div class="kbcb-selected-section">
					<h2><?php esc_html_e( 'Páginas Seleccionadas', 'knowledge-base-chatbot' ); ?></h2>
					<p class="description">
						<?php esc_html_e( 'Arrastra para reordenar las páginas según tus preferencias.', 'knowledge-base-chatbot' ); ?>
					</p>
					
					<div id="kbcb-selected-list" class="kbcb-selected-list">
						<?php
						if ( ! empty( $selected ) ) :
							foreach ( $selected as $post_id ) :
								$post = get_post( $post_id );
								if ( $post ) :
									$post_type_obj = get_post_type_object( $post->post_type );
									?>
									<div class="kbcb-selected-item" data-id="<?php echo esc_attr( $post_id ); ?>">
										<span class="kbcb-drag-handle dashicons dashicons-menu"></span>
										<div class="kbcb-selected-item-info">
											<span class="kbcb-selected-item-title"><?php echo esc_html( $post->post_title ); ?></span>
											<span class="kbcb-selected-item-type"><?php echo esc_html( $post_type_obj->labels->singular_name ); ?></span>
										</div>
										<button type="button" class="button kbcb-remove-item" data-id="<?php echo esc_attr( $post_id ); ?>">
											<span class="dashicons dashicons-no"></span>
										</button>
									</div>
									<?php
								endif;
							endforeach;
						else :
							?>
							<p class="kbcb-empty-message"><?php esc_html_e( 'No hay páginas seleccionadas. Añade páginas desde las pestañas de arriba.', 'knowledge-base-chatbot' ); ?></p>
							<?php
						endif;
						?>
					</div>

					<div class="kbcb-actions">
						<button type="button" id="kbcb-save-order" class="button button-secondary">
							<?php esc_html_e( 'Guardar Orden', 'knowledge-base-chatbot' ); ?>
						</button>
						<button type="button" id="kbcb-regenerate" class="button button-primary">
							<?php esc_html_e( 'Regenerar Archivo', 'knowledge-base-chatbot' ); ?>
						</button>
					</div>

					<?php if ( $this->get_file_url() ) : ?>
						<div class="kbcb-file-info">
							<h3><?php esc_html_e( 'Archivo Generado', 'knowledge-base-chatbot' ); ?></h3>
							<div class="kbcb-file-url">
								<input type="text" readonly value="<?php echo esc_attr( $this->get_file_url() ); ?>" />
								<button type="button" class="button kbcb-copy-url" title="<?php esc_attr_e( 'Copiar URL', 'knowledge-base-chatbot' ); ?>">
									<span class="dashicons dashicons-admin-page"></span>
								</button>
							</div>
							<?php if ( $this->get_file_date() ) : ?>
								<div class="kbcb-file-date">
									<span class="dashicons dashicons-calendar-alt"></span>
									<strong><?php esc_html_e( 'Última actualización:', 'knowledge-base-chatbot' ); ?></strong>
									<span id="kbcb-file-date-value"><?php echo esc_html( $this->get_file_date() ); ?></span>
									<span class="kbcb-file-date-utc"><?php esc_html_e( 'UTC', 'knowledge-base-chatbot' ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div id="kbcb-message" class="kbcb-message" style="display: none;"></div>
				</div>
			</div>
		</div>
		<?php
	}
}
