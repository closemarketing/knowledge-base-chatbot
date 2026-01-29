<?php
/**
 * Export Page Class
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */

namespace CLOSE\KnowledgeBaseChatbot\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Export Page Class
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */
class ExportPage {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_export_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add export page to WordPress admin
	 *
	 * @return void
	 */
	public function add_export_page() {
		add_submenu_page(
			'knowledge-base-chatbot',
			__( 'Export', 'knowledge-base-chatbot' ),
			__( 'Export', 'knowledge-base-chatbot' ),
			'manage_options',
			'knowledge-base-chatbot-export',
			array( $this, 'render_export_page' )
		);
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'kb-chatbot_page_knowledge-base-chatbot-export' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'knowledge-base-chatbot-admin',
			KBCB_PLUGIN_URL . 'assets/knowledge-base-chatbot-admin.js',
			array( 'jquery' ),
			KBCB_VERSION,
			true
		);

		wp_localize_script(
			'knowledge-base-chatbot-admin',
			'knowledgeBaseChatbotAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'knowledge-base-chatbot_export' ),
				'i18n'    => array(
					'selectAtLeastOneToExport' => __( 'Please select at least one item to export.', 'knowledge-base-chatbot' ),
					'confirmExportAll'         => __( 'Are you sure you want to export all items of this type?', 'knowledge-base-chatbot' ),
					'exporting'                => __( 'Exporting...', 'knowledge-base-chatbot' ),
				),
			)
		);

		wp_enqueue_style(
			'knowledge-base-chatbot-admin',
			KBCB_PLUGIN_URL . 'assets/knowledge-base-chatbot-admin.css',
			array(),
			KBCB_VERSION
		);
	}

	/**
	 * Get all public post types (excluding built-in attachments, revisions, etc.)
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

		// Exclude some built-in types.
		$excluded = array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block' );

		$exportable = array();
		foreach ( $post_types as $post_type ) {
			if ( ! in_array( $post_type->name, $excluded, true ) ) {
				$exportable[ $post_type->name ] = $post_type;
			}
		}

		return $exportable;
	}

	/**
	 * Render export page
	 *
	 * @return void
	 */
	public function render_export_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$post_types = $this->get_exportable_post_types();
		$first_tab  = true;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Export to Markdown', 'knowledge-base-chatbot' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Select the content you want to export to Markdown, or export all items for a given content type.', 'knowledge-base-chatbot' ); ?>
			</p>

			<div class="knowledge-base-chatbot-export-tabs">
				<nav class="nav-tab-wrapper">
					<?php foreach ( $post_types as $post_type_name => $post_type_obj ) : ?>
						<?php
						$tab_id    = 'knowledge-base-chatbot-tab-' . $post_type_name;
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
					$tab_id        = 'knowledge-base-chatbot-tab-' . $post_type_name;
					$tab_active    = $first_content ? 'knowledge-base-chatbot-tab-active' : '';
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
					<div id="<?php echo esc_attr( $tab_id ); ?>" class="knowledge-base-chatbot-tab-content <?php echo esc_attr( $tab_active ); ?>">
						<?php if ( ! empty( $items ) ) : ?>
							<div class="knowledge-base-chatbot-export-section">
								<div class="knowledge-base-chatbot-export-controls">
									<button type="button" class="button knowledge-base-chatbot-select-all" data-type="<?php echo esc_attr( $post_type_name ); ?>">
										<?php esc_html_e( 'Select all', 'knowledge-base-chatbot' ); ?>
									</button>
									<button type="button" class="button knowledge-base-chatbot-deselect-all" data-type="<?php echo esc_attr( $post_type_name ); ?>">
										<?php esc_html_e( 'Deselect all', 'knowledge-base-chatbot' ); ?>
									</button>
								</div>

								<div class="knowledge-base-chatbot-items-list" data-type="<?php echo esc_attr( $post_type_name ); ?>">
									<?php foreach ( $items as $item ) : ?>
										<label class="knowledge-base-chatbot-item">
											<input type="checkbox" class="knowledge-base-chatbot-item-checkbox" data-type="<?php echo esc_attr( $post_type_name ); ?>" value="<?php echo esc_attr( $item->ID ); ?>" />
											<span class="knowledge-base-chatbot-item-title"><?php echo esc_html( $item->post_title ); ?></span>
											<span class="knowledge-base-chatbot-item-url"><?php echo esc_url( get_permalink( $item->ID ) ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>

								<div class="knowledge-base-chatbot-export-buttons">
									<button type="button" class="button button-primary knowledge-base-chatbot-export-selected" data-type="<?php echo esc_attr( $post_type_name ); ?>">
										<?php
										/* translators: %s: Post type name */
										printf( esc_html__( 'Export selected %s', 'knowledge-base-chatbot' ), esc_html( strtolower( $post_type_obj->labels->name ) ) );
										?>
									</button>
									<button type="button" class="button button-secondary knowledge-base-chatbot-export-all" data-type="<?php echo esc_attr( $post_type_name ); ?>">
										<?php
										/* translators: %s: Post type name */
										printf( esc_html__( 'Export all %s', 'knowledge-base-chatbot' ), esc_html( strtolower( $post_type_obj->labels->name ) ) );
										?>
									</button>
								</div>

								<div class="knowledge-base-chatbot-export-message" data-type="<?php echo esc_attr( $post_type_name ); ?>" style="display: none;"></div>
							</div>
						<?php else : ?>
							<p>
								<?php
								/* translators: %s: Post type name */
								printf( esc_html__( 'No published %s found to export.', 'knowledge-base-chatbot' ), esc_html( strtolower( $post_type_obj->labels->name ) ) );
								?>
							</p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}

