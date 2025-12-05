<?php
/**
 * Plugin Main Class
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */

namespace CLOSE\KnowledgeBaseChatbot;

defined( 'ABSPATH' ) || exit;

use CLOSE\KnowledgeBaseChatbot\Admin\GeneratePage;

/**
 * Plugin Main Class
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */
class Plugin_Main {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		if ( is_admin() ) {
			new GeneratePage();
		}
	}
}
