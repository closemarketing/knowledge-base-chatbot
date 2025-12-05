<?php
/**
 * Plugin Name: Knowledge Base Chatbot
 * Plugin URI:  https://close.marketing
 * Description: Plugin for knowledge base chatbot.
 * Version:     1.0.0
 * Author:      Closemarketing
 * Author URI:  https://close.marketing
 * Text Domain: knowledge-base-chatbot
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Prefix:      kbcb_
 *
 * @package CLOSE\KnowledgeBaseChatbot
 */

defined( 'ABSPATH' ) || exit;

define( 'KBCB_VERSION', '1.0.0' );
define( 'KBCB_PLUGIN', __FILE__ );
define( 'KBCB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KBCB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Load Composer autoloader.
if ( file_exists( KBCB_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	require_once KBCB_PLUGIN_PATH . 'vendor/autoload.php';
}

// Initialize plugin.
new \CLOSE\KnowledgeBaseChatbot\Plugin_Main();
