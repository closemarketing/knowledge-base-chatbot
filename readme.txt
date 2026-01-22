=== Knowledge Base Chatbot ===
Contributors: closemarketing
Tags: knowledge base, chatbot, ai, llm, markdown, export, documentation
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate a Markdown knowledge base file from selected WordPress content (Pages, Posts, and custom post types) to power an external chatbot / LLM workflow.

== Description ==

Knowledge Base Chatbot helps you curate the exact content you want to use as a "knowledge base" and generates a single Markdown file (`llm-knowledge-chatbot.md`) that can be consumed by an external chatbot or LLM pipeline.

From the WordPress admin, you can:

- Select content from any public post type that is available in the admin (Pages, Posts, and most custom post types).
- Add selected items to a curated list.
- Reorder the list via drag & drop.
- Regenerate the Markdown file at any time (it is also regenerated automatically after adding/removing items).

The generated file is saved in your WordPress root folder and can be accessed via a public URL such as:
`https://example.com/llm-knowledge-chatbot.md`

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. In wp-admin, open **KB Chatbot** from the left menu.
4. Select the content you want to include and click **Add selected**.
5. Reorder the selected items and click **Save order** (optional).
6. Click **Regenerate file** to create/update `llm-knowledge-chatbot.md`.

== Frequently Asked Questions ==

= Where is the Markdown file saved? =
The file is saved in the WordPress installation root, using the filename `llm-knowledge-chatbot.md`.

= Does the file become publicly accessible? =
Yes. Since the file is created in the web root, it can be accessible at `https://your-site.com/llm-knowledge-chatbot.md` (depending on your server configuration).

If you need this file to be private, you should restrict access at the server level (recommended).

= What content types can I include? =
The generator lists public post types that are visible in the admin. This usually includes Pages, Posts, and custom post types created by your theme/plugins (excluding media attachments).

= Does this plugin send my content to any external service? =
No. The plugin generates a local Markdown file on your server. It does not upload content anywhere by itself.

= What permissions are required to use the generator? =
Only administrators (users with the `manage_options` capability) can access the generator and perform actions.

= The file is not being generated. What should I check? =
- Ensure your WordPress root folder is writable by WordPress / PHP.
- Confirm you have selected at least one item.

== Screenshots ==

1. KB Chatbot admin page (content selection by post type).
2. Selected items list with drag & drop ordering.
3. Generated file URL and last updated time.

== Changelog ==

= 1.0.0 =
* Initial release.
* Admin generator to curate content and create `llm-knowledge-chatbot.md`.

== Upgrade Notice ==

= 1.0.0 =
Initial release.