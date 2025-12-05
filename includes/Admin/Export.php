<?php
/**
 * Export Class for Markdown Export
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */

namespace CLOSE\KnowledgeBaseChatbot\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Export Class
 *
 * @package CLOSE\KnowledgeBaseChatbot
 * @author  Closetechnology
 */
class Export {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		// Legacy handlers for pages and posts.
		add_action( 'wp_ajax_knowledge-base-chatbot_export_pages', array( $this, 'handle_export_pages' ) );
		add_action( 'wp_ajax_knowledge-base-chatbot_export_all_pages', array( $this, 'handle_export_all_pages' ) );
		add_action( 'wp_ajax_knowledge-base-chatbot_export_posts', array( $this, 'handle_export_posts' ) );
		add_action( 'wp_ajax_knowledge-base-chatbot_export_all_posts', array( $this, 'handle_export_all_posts' ) );

		// Dynamic handlers for all post types.
		add_action( 'wp_ajax_knowledge-base-chatbot_export_selected', array( $this, 'handle_export_selected' ) );
		add_action( 'wp_ajax_knowledge-base-chatbot_export_all', array( $this, 'handle_export_all' ) );
	}

	/**
	 * Handle export of selected pages
	 *
	 * @return void
	 */
	public function handle_export_pages() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'knowledge-base-chatbot_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'knowledge-base-chatbot' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export pages.', 'knowledge-base-chatbot' ) );
		}

		// Get page IDs.
		if ( ! isset( $_POST['page_ids'] ) || ! is_array( $_POST['page_ids'] ) ) {
			wp_die( esc_html__( 'No pages selected.', 'knowledge-base-chatbot' ) );
		}

		$page_ids = array_map( 'intval', $_POST['page_ids'] );
		$markdown = $this->export_posts_to_markdown( $page_ids, 'page' );

		if ( empty( $markdown ) ) {
			wp_die( esc_html__( 'No content to export.', 'knowledge-base-chatbot' ) );
		}

		// Send file.
		$filename = 'knowledge-base-chatbot-export-pages-' . gmdate( 'Y-m-d-H-i-s' ) . '.md';
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );
		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Handle export of all pages
	 *
	 * @return void
	 */
	public function handle_export_all_pages() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'knowledge-base-chatbot_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'knowledge-base-chatbot' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export pages.', 'knowledge-base-chatbot' ) );
		}

		// Get all pages.
		$pages = get_pages(
			array(
				'post_status' => 'publish',
				'number'      => -1,
			)
		);

		if ( empty( $pages ) ) {
			wp_die( esc_html__( 'No pages found.', 'knowledge-base-chatbot' ) );
		}

		$page_ids = array_map(
			function ( $page ) {
				return $page->ID;
			},
			$pages
		);

		$markdown = $this->export_posts_to_markdown( $page_ids, 'page' );

		if ( empty( $markdown ) ) {
			wp_die( esc_html__( 'No content to export.', 'knowledge-base-chatbot' ) );
		}

		// Send file.
		$filename = 'knowledge-base-chatbot-export-all-pages-' . gmdate( 'Y-m-d-H-i-s' ) . '.md';
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );
		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Handle export of selected posts
	 *
	 * @return void
	 */
	public function handle_export_posts() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'knowledge-base-chatbot_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'knowledge-base-chatbot' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export posts.', 'knowledge-base-chatbot' ) );
		}

		// Get post IDs.
		if ( ! isset( $_POST['post_ids'] ) || ! is_array( $_POST['post_ids'] ) ) {
			wp_die( esc_html__( 'No posts selected.', 'knowledge-base-chatbot' ) );
		}

		$post_ids = array_map( 'intval', $_POST['post_ids'] );
		$markdown = $this->export_posts_to_markdown( $post_ids, 'post' );

		if ( empty( $markdown ) ) {
			wp_die( esc_html__( 'No content to export.', 'knowledge-base-chatbot' ) );
		}

		// Send file.
		$filename = 'knowledge-base-chatbot-export-posts-' . gmdate( 'Y-m-d-H-i-s' ) . '.md';
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );
		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Handle export of all posts
	 *
	 * @return void
	 */
	public function handle_export_all_posts() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'knowledge-base-chatbot_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'knowledge-base-chatbot' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export posts.', 'knowledge-base-chatbot' ) );
		}

		// Get all posts.
		$posts = get_posts(
			array(
				'post_status' => 'publish',
				'numberposts' => -1,
				'post_type'   => 'post',
			)
		);

		if ( empty( $posts ) ) {
			wp_die( esc_html__( 'No posts found.', 'knowledge-base-chatbot' ) );
		}

		$post_ids = array_map(
			function ( $post ) {
				return $post->ID;
			},
			$posts
		);

		$markdown = $this->export_posts_to_markdown( $post_ids, 'post' );

		if ( empty( $markdown ) ) {
			wp_die( esc_html__( 'No content to export.', 'knowledge-base-chatbot' ) );
		}

		// Send file.
		$filename = 'knowledge-base-chatbot-export-all-posts-' . gmdate( 'Y-m-d-H-i-s' ) . '.md';
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );
		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Handle export of selected items (generic for any post type)
	 *
	 * @return void
	 */
	public function handle_export_selected() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'knowledge-base-chatbot_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'knowledge-base-chatbot' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export content.', 'knowledge-base-chatbot' ) );
		}

		// Get post type and IDs.
		if ( ! isset( $_POST['post_type'] ) ) {
			wp_die( esc_html__( 'Post type not specified.', 'knowledge-base-chatbot' ) );
		}

		if ( ! isset( $_POST['post_ids'] ) || ! is_array( $_POST['post_ids'] ) ) {
			wp_die( esc_html__( 'No items selected.', 'knowledge-base-chatbot' ) );
		}

		$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );
		$post_ids  = array_map( 'intval', $_POST['post_ids'] );
		$markdown  = $this->export_posts_to_markdown( $post_ids, $post_type );

		if ( empty( $markdown ) ) {
			wp_die( esc_html__( 'No content to export.', 'knowledge-base-chatbot' ) );
		}

		// Get post type label for filename.
		$post_type_obj = get_post_type_object( $post_type );
		$type_label    = $post_type_obj ? $post_type_obj->labels->name : $post_type;

		// Send file.
		$filename = 'knowledge-base-chatbot-export-' . sanitize_file_name( strtolower( $type_label ) ) . '-' . gmdate( 'Y-m-d-H-i-s' ) . '.md';
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );
		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Handle export of all items (generic for any post type)
	 *
	 * @return void
	 */
	public function handle_export_all() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'knowledge-base-chatbot_export' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'knowledge-base-chatbot' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export content.', 'knowledge-base-chatbot' ) );
		}

		// Get post type.
		if ( ! isset( $_POST['post_type'] ) ) {
			wp_die( esc_html__( 'Post type not specified.', 'knowledge-base-chatbot' ) );
		}

		$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );

		// Get all posts of this type.
		$posts = get_posts(
			array(
				'post_status' => 'publish',
				'numberposts' => -1,
				'post_type'   => $post_type,
			)
		);

		if ( empty( $posts ) ) {
			$post_type_obj = get_post_type_object( $post_type );
			$type_label    = $post_type_obj ? $post_type_obj->labels->name : $post_type;
			/* translators: %s: Post type name */
			wp_die( sprintf( esc_html__( 'No %s found.', 'knowledge-base-chatbot' ), esc_html( strtolower( $type_label ) ) ) );
		}

		$post_ids = array_map(
			function ( $post ) {
				return $post->ID;
			},
			$posts
		);

		$markdown = $this->export_posts_to_markdown( $post_ids, $post_type );

		if ( empty( $markdown ) ) {
			wp_die( esc_html__( 'No content to export.', 'knowledge-base-chatbot' ) );
		}

		// Get post type label for filename.
		$post_type_obj = get_post_type_object( $post_type );
		$type_label    = $post_type_obj ? $post_type_obj->labels->name : $post_type;

		// Send file.
		$filename = 'knowledge-base-chatbot-export-all-' . sanitize_file_name( strtolower( $type_label ) ) . '-' . gmdate( 'Y-m-d-H-i-s' ) . '.md';
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $markdown ) );
		echo $markdown; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Export posts/pages to markdown format
	 *
	 * @param array  $post_ids Array of post/page IDs.
	 * @param string $post_type Post type ('page' or 'post').
	 * @return string Markdown content.
	 */
	private function export_posts_to_markdown( $post_ids, $post_type = 'page' ) {
		$markdown   = '';
		$type_label = ( 'page' === $post_type ) ? __( 'Página', 'knowledge-base-chatbot' ) : __( 'Entrada', 'knowledge-base-chatbot' );

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );

			if ( ! $post || $post_type !== $post->post_type ) {
				continue;
			}

			// Post/Page title.
			$markdown .= '# ' . $this->escape_markdown( $post->post_title ) . "\n\n";

			// Post/Page metadata.
			$post_url  = get_permalink( $post_id );
			$markdown .= '**Tipo:** ' . $type_label . "\n\n";
			$markdown .= '**URL:** ' . $post_url . "\n\n";
			$markdown .= '**Fecha de publicación:** ' . get_the_date( 'Y-m-d H:i:s', $post_id ) . "\n\n";

			// Add author if it's a post.
			if ( 'post' === $post_type ) {
				$author = get_the_author_meta( 'display_name', $post->post_author );
				if ( $author ) {
					$markdown .= '**Autor:** ' . $author . "\n\n";
				}

				// Add categories if it's a post.
				$categories = get_the_category( $post_id );
				if ( ! empty( $categories ) ) {
					$cat_names = array_map(
						function ( $cat ) {
							return $cat->name;
						},
						$categories
					);
					$markdown .= '**Categorías:** ' . implode( ', ', $cat_names ) . "\n\n";
				}

				// Add tags if it's a post.
				$tags = get_the_tags( $post_id );
				if ( ! empty( $tags ) ) {
					$tag_names = array_map(
						function ( $tag ) {
							return $tag->name;
						},
						$tags
					);
					$markdown .= '**Etiquetas:** ' . implode( ', ', $tag_names ) . "\n\n";
				}
			}

			// Post/Page content.
			$content = $post->post_content;

			// Convert HTML to markdown-like format.
			$content = $this->convert_html_to_markdown( $content );

			$markdown .= $content . "\n\n";

			// Separator between posts/pages.
			$markdown .= "---\n\n";
		}

		return $markdown;
	}

	/**
	 * Convert HTML content to markdown-like format
	 *
	 * @param string $html HTML content.
	 * @return string Markdown content.
	 */
	private function convert_html_to_markdown( $html ) {
		// Remove WordPress shortcodes.
		$html = strip_shortcodes( $html );

		// Convert blockquotes.
		$html = preg_replace( '/<blockquote[^>]*>(.*?)<\/blockquote>/is', '> $1', $html );

		// Convert code blocks.
		$html = preg_replace( '/<pre[^>]*><code[^>]*>(.*?)<\/code><\/pre>/is', '```\n$1\n```', $html );
		$html = preg_replace( '/<code[^>]*>(.*?)<\/code>/is', '`$1`', $html );

		// Convert headings.
		$html = preg_replace( '/<h1[^>]*>(.*?)<\/h1>/is', "\n# $1\n\n", $html );
		$html = preg_replace( '/<h2[^>]*>(.*?)<\/h2>/is', "\n## $1\n\n", $html );
		$html = preg_replace( '/<h3[^>]*>(.*?)<\/h3>/is', "\n### $1\n\n", $html );
		$html = preg_replace( '/<h4[^>]*>(.*?)<\/h4>/is', "\n#### $1\n\n", $html );
		$html = preg_replace( '/<h5[^>]*>(.*?)<\/h5>/is', "\n##### $1\n\n", $html );
		$html = preg_replace( '/<h6[^>]*>(.*?)<\/h6>/is', "\n###### $1\n\n", $html );

		// Convert bold.
		$html = preg_replace( '/<strong[^>]*>(.*?)<\/strong>/is', '**$1**', $html );
		$html = preg_replace( '/<b[^>]*>(.*?)<\/b>/is', '**$1**', $html );

		// Convert italic.
		$html = preg_replace( '/<em[^>]*>(.*?)<\/em>/is', '*$1*', $html );
		$html = preg_replace( '/<i[^>]*>(.*?)<\/i>/is', '*$1*', $html );

		// Convert links.
		$html = preg_replace( '/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', '[$2]($1)', $html );

		// Convert images - handle full URLs.
		$html = preg_replace_callback(
			'/<img[^>]*src=["\']([^"\']*)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*>/is',
			function ( $matches ) {
				$src = $matches[1];
				$alt = $matches[2];
				// Convert relative URLs to absolute.
				if ( ! preg_match( '/^https?:\/\//', $src ) ) {
					$src = site_url( $src );
				}
				return "![{$alt}]({$src})";
			},
			$html
		);
		$html = preg_replace_callback(
			'/<img[^>]*src=["\']([^"\']*)["\'][^>]*>/is',
			function ( $matches ) {
				$src = $matches[1];
				// Convert relative URLs to absolute.
				if ( ! preg_match( '/^https?:\/\//', $src ) ) {
					$src = site_url( $src );
				}
				return "![]({$src})";
			},
			$html
		);

		// Convert ordered lists.
		$html = preg_replace_callback(
			'/<ol[^>]*>(.*?)<\/ol>/is',
			function ( $matches ) {
				$content = $matches[1];
				$items   = preg_split( '/<li[^>]*>/', $content );
				$result  = "\n";
				$counter = 1;
				foreach ( $items as $item ) {
					$item = preg_replace( '/<\/li>/', '', $item );
					$item = trim( $item );
					if ( ! empty( $item ) ) {
						$result .= $counter . '. ' . $item . "\n";
						$counter++;
					}
				}
				return $result . "\n";
			},
			$html
		);

		// Convert unordered lists.
		$html = preg_replace_callback(
			'/<ul[^>]*>(.*?)<\/ul>/is',
			function ( $matches ) {
				$content = $matches[1];
				$items   = preg_split( '/<li[^>]*>/', $content );
				$result  = "\n";
				foreach ( $items as $item ) {
					$item = preg_replace( '/<\/li>/', '', $item );
					$item = trim( $item );
					if ( ! empty( $item ) ) {
						$result .= '- ' . $item . "\n";
					}
				}
				return $result . "\n";
			},
			$html
		);

		// Convert paragraphs.
		$html = preg_replace( '/<p[^>]*>(.*?)<\/p>/is', '$1' . "\n\n", $html );

		// Convert line breaks.
		$html = preg_replace( '/<br[^>]*\/?>/is', "\n", $html );

		// Convert horizontal rules.
		$html = preg_replace( '/<hr[^>]*\/?>/is', "\n---\n\n", $html );

		// Remove remaining HTML tags.
		$html = wp_strip_all_tags( $html );

		// Decode HTML entities.
		$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// Clean up multiple newlines.
		$html = preg_replace( '/\n{3,}/', "\n\n", $html );

		// Trim.
		$html = trim( $html );

		return $html;
	}

	/**
	 * Escape markdown special characters
	 *
	 * @param string $text Text to escape.
	 * @return string Escaped text.
	 */
	private function escape_markdown( $text ) {
		$special_chars = array( '#', '*', '_', '[', ']', '(', ')', '!', '`', '<', '>', '&' );
		foreach ( $special_chars as $char ) {
			$text = str_replace( $char, '\\' . $char, $text );
		}
		return $text;
	}
}
