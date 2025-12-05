<?php
/**
 * Admin Settings Class
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */

namespace CLOSE\KnowledgeBaseChatbot\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Settings Class
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */
class Settings {

	/**
	 * Option group name
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'knowledge-base-chatbot_settings';

	/**
	 * Option name
	 *
	 * @var string
	 */
	const OPTION_NAME = 'knowledge-base-chatbot_chat_url';

	/**
	 * Icon option name
	 *
	 * @var string
	 */
	const OPTION_ICON = 'knowledge-base-chatbot_icon';

	/**
	 * Icon size option name
	 *
	 * @var string
	 */
	const OPTION_ICON_SIZE = 'knowledge-base-chatbot_icon_size';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add settings page to WordPress admin
	 *
	 * @return void
	 */
	public function add_settings_page() {
		// Main menu page.
		add_menu_page(
			__( 'Knowledge Base Chatbot', 'knowledge-base-chatbot' ),
			__( 'KB Chatbot', 'knowledge-base-chatbot' ),
			'manage_options',
			'knowledge-base-chatbot',
			array( $this, 'render_settings_page' ),
			'dashicons-format-chat',
			30
		);

		// Settings submenu.
		add_submenu_page(
			'knowledge-base-chatbot',
			__( 'Settings', 'knowledge-base-chatbot' ),
			__( 'Settings', 'knowledge-base-chatbot' ),
			'manage_options',
			'knowledge-base-chatbot',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_chat_url' ),
				'default'           => '',
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_ICON,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_icon' ),
				'default'           => 0,
			)
		);

		register_setting(
			self::OPTION_GROUP,
			self::OPTION_ICON_SIZE,
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_icon_size' ),
				'default'           => 56,
			)
		);

		add_settings_section(
			'knowledge-base-chatbot_main_section',
			__( 'Chat Configuration', 'knowledge-base-chatbot' ),
			array( $this, 'render_section_description' ),
			'knowledge-base-chatbot'
		);

		add_settings_field(
			'knowledge-base-chatbot_chat_url',
			__( 'Chat URL', 'knowledge-base-chatbot' ),
			array( $this, 'render_chat_url_field' ),
			'knowledge-base-chatbot',
			'knowledge-base-chatbot_main_section'
		);

		add_settings_field(
			'knowledge-base-chatbot_icon',
			__( 'Chat Icon', 'knowledge-base-chatbot' ),
			array( $this, 'render_icon_field' ),
			'knowledge-base-chatbot',
			'knowledge-base-chatbot_main_section'
		);

		add_settings_field(
			'knowledge-base-chatbot_icon_size',
			__( 'Icon Size (px)', 'knowledge-base-chatbot' ),
			array( $this, 'render_icon_size_field' ),
			'knowledge-base-chatbot',
			'knowledge-base-chatbot_main_section'
		);
	}

	/**
	 * Sanitize chat URL
	 *
	 * @param string $value Chat URL value.
	 * @return string
	 */
	public function sanitize_chat_url( $value ) {
		return esc_url_raw( $value );
	}

	/**
	 * Sanitize icon ID and validate it's an SVG or PNG
	 *
	 * @param mixed $value Icon ID value.
	 * @return int
	 */
	public function sanitize_icon( $value ) {
		$icon_id = absint( $value );

		if ( 0 === $icon_id ) {
			return 0;
		}

		// Validate that the attachment is an SVG or PNG.
		$mime_type     = get_post_mime_type( $icon_id );
		$allowed_mimes = array( 'image/svg+xml', 'image/png' );
		if ( ! in_array( $mime_type, $allowed_mimes, true ) ) {
			// If it's not SVG or PNG, reset to 0 and show error.
			add_settings_error(
				'knowledge-base-chatbot_messages',
				'knowledge-base-chatbot_icon_error',
				__( 'Solo se permiten archivos SVG o PNG para el icono del chat.', 'knowledge-base-chatbot' ),
				'error'
			);
			return 0;
		}

		return $icon_id;
	}

	/**
	 * Sanitize icon size
	 *
	 * @param mixed $value Icon size value.
	 * @return int
	 */
	public function sanitize_icon_size( $value ) {
		$size = absint( $value );
		// Ensure minimum size of 16px and maximum of 200px.
		return max( 16, min( 200, $size ) );
	}

	/**
	 * Render section description
	 *
	 * @return void
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure the chat URL that will be displayed on your website.', 'knowledge-base-chatbot' ) . '</p>';
	}

	/**
	 * Render chat URL field
	 *
	 * @return void
	 */
	public function render_chat_url_field() {
		$value = get_option( self::OPTION_NAME, '' );
		?>
		<input type="url" name="<?php echo esc_attr( self::OPTION_NAME ); ?>" id="<?php echo esc_attr( self::OPTION_NAME ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="https://knowledge-base-chatbot.grupomainjobs.com/chat/ID" />
		<p class="description">
			<?php esc_html_e( 'Enter the full URL of the chat iframe (e.g., https://knowledge-base-chatbot.grupomainjobs.com/chat/ID)', 'knowledge-base-chatbot' ); ?>
		</p>
		<?php
	}

	/**
	 * Render icon field
	 *
	 * @return void
	 */
	public function render_icon_field() {
		$icon_id  = get_option( self::OPTION_ICON, 0 );
		$icon_url = '';
		if ( $icon_id ) {
			$icon_url = wp_get_attachment_url( $icon_id );
		}
		?>
		<div class="knowledge-base-chatbot-icon-field">
			<input 
				type="hidden" 
				name="<?php echo esc_attr( self::OPTION_ICON ); ?>" 
				id="<?php echo esc_attr( self::OPTION_ICON ); ?>" 
				value="<?php echo esc_attr( $icon_id ); ?>" 
				class="knowledge-base-chatbot-icon-id"
			/>
			<div class="knowledge-base-chatbot-icon-preview" style="margin-bottom: 10px;">
				<?php if ( $icon_url ) : ?>
					<img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php esc_attr_e( 'Chat Icon', 'knowledge-base-chatbot' ); ?>" style="max-width: 100px; height: auto; display: block;" />
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No icon selected. Default icon will be used.', 'knowledge-base-chatbot' ); ?></p>
				<?php endif; ?>
			</div>
			<button 
				type="button" 
				class="button knowledge-base-chatbot-upload-icon" 
				data-field-id="<?php echo esc_attr( self::OPTION_ICON ); ?>"
			>
				<?php esc_html_e( 'Select Icon (SVG or PNG)', 'knowledge-base-chatbot' ); ?>
			</button>
			<button 
				type="button" 
				class="button knowledge-base-chatbot-remove-icon" 
				data-field-id="<?php echo esc_attr( self::OPTION_ICON ); ?>"
				style="<?php echo $icon_url ? '' : 'display: none;'; ?>"
			>
				<?php esc_html_e( 'Remove Icon', 'knowledge-base-chatbot' ); ?>
			</button>
			<p class="description">
				<?php esc_html_e( 'Upload a custom SVG or PNG icon for the chat button. If no icon is selected, the default icon will be used.', 'knowledge-base-chatbot' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render icon size field
	 *
	 * @return void
	 */
	public function render_icon_size_field() {
		$size = get_option( self::OPTION_ICON_SIZE, 56 );
		?>
		<input 
			type="number" 
			name="<?php echo esc_attr( self::OPTION_ICON_SIZE ); ?>" 
			id="<?php echo esc_attr( self::OPTION_ICON_SIZE ); ?>" 
			value="<?php echo esc_attr( $size ); ?>" 
			class="small-text" 
			min="16" 
			max="200" 
			step="1"
		/>
		<p class="description">
			<?php esc_html_e( 'Set the size of the icon in pixels (default: 56px). Minimum: 16px, Maximum: 200px.', 'knowledge-base-chatbot' ); ?>
		</p>
		<?php
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error(
				'knowledge-base-chatbot_messages',
				'knowledge-base-chatbot_message',
				__( 'Settings saved successfully.', 'knowledge-base-chatbot' ),
				'success'
			);
		}

		settings_errors( 'knowledge-base-chatbot_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'knowledge-base-chatbot' );
				submit_button( __( 'Save Settings', 'knowledge-base-chatbot' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Get chat URL
	 *
	 * @return string
	 */
	public static function get_chat_url() {
		return get_option( self::OPTION_NAME, '' );
	}

	/**
	 * Get chat icon
	 *
	 * @return string Icon URL or empty string.
	 */
	public static function get_chat_icon() {
		$icon_id = get_option( self::OPTION_ICON, 0 );
		if ( $icon_id ) {
			$icon_url = wp_get_attachment_url( $icon_id );
			if ( $icon_url ) {
				return $icon_url;
			}
		}
		// Return default icon.
		return KBCB_PLUGIN_URL . 'assets/icon-chat.svg';
	}

	/**
	 * Get icon size
	 *
	 * @return int Icon size in pixels.
	 */
	public static function get_icon_size() {
		$size = get_option( self::OPTION_ICON_SIZE, 56 );
		return absint( $size );
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on knowledge-base-chatbot pages.
		if ( 'toplevel_page_knowledge-base-chatbot' !== $hook && 'knowledge-base-chatbot_page_knowledge-base-chatbot-export' !== $hook ) {
			return;
		}

		// Enqueue WordPress media uploader only on settings page.
		if ( 'toplevel_page_knowledge-base-chatbot' === $hook ) {
			wp_enqueue_media();

			// Enqueue our script for icon upload.
			wp_enqueue_script(
				'knowledge-base-chatbot-admin',
				KBCB_PLUGIN_URL . 'assets/admin.js',
				array( 'jquery' ),
				KBCB_VERSION,
				true
			);
		}
	}
}
