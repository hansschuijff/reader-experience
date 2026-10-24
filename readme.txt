=== Reader Experience ===
Contributors: hansschuijff
Tags: table of contents, reading time, reading progress, share buttons, feedback
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.3.0
License: GPLv2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Reading tools and interaction for long articles: table of contents, reading time, progress, resume reading, sharing and ratings.

== Description ==

Long articles are easier to read when visitors can see where they are, find their way back and respond without effort. Reader Experience adds a set of small blocks for that, built for block themes and following the look of your theme.

**Reading**

* **Table of contents**: an automatic list of the h2 to h4 headings in the article that marks where the visitor is. Also usable in a sidebar or widget area.
* **Reading time**: the estimated reading time, optionally as the time remaining while reading.
* **Reading progress**: a thin bar at the top of the screen that shows how far the visitor is.
* **Resume reading**: on a return visit, offers to continue at the heading where the visitor stopped. The position stays in the visitor's own browser.
* **Heading anchors** (off by default): ids on headings and a "copy link" icon, switched on per post type with a filter.

**Interaction bar**

A bar at the bottom of the screen that holds the interaction blocks. On mobile it hides while scrolling down; on desktop it stays visible.

* **Contents button**: opens the table of contents in a side panel. On a wide screen the page moves aside so the article stays fully visible; on a phone the panel is an overlay that closes with the back button.
* **Comments link**: shows the number of comments and jumps to the comments, the comment form or an anchor of your choice.
* **Share**: the system share menu on mobile; on desktop a popover with WhatsApp, LinkedIn, Facebook, email and a field to copy the link.
* **Rating**: like or dislike, optionally followed by an explanation. A positive comment can be published as a WordPress comment; after a dislike the visitor can leave contact details.
* **Custom link**: an icon button that links to a page of your choice, for example a help page.

**Feedback in the admin**

Ratings and explanations are collected under Comments > Reader feedback, with a column on the posts screen and a dashboard widget. Share clicks are counted per channel per day and are only visible in the admin.

**Privacy**

No IP addresses or personal data are stored with ratings or share counts. Rate limiting uses a short-lived hashed fingerprint. The plugin loads nothing from third parties and shows no share counts from social networks.

== Installation ==

1. Upload the plugin zip through Plugins > Add New > Upload Plugin, or place the `reader-experience` folder in `wp-content/plugins`.
2. Activate the plugin.
3. In the site editor, open the single post template and add the Interaction bar, and optionally the Reading progress, Reading time and Resume reading blocks.
4. Add the child blocks you want in the Interaction bar: Contents button, Comments link, Share, Rating and Custom link.

Updates for sites that use [Git Updater](https://git-updater.com/) come from the GitHub releases.

== Frequently Asked Questions ==

= Why are there no share counts? =

Facebook and X no longer provide public share counts, and low numbers discourage sharing. The plugin counts clicks on its own share buttons instead, visible in the admin only.

= Does Resume reading need consent? =

It stores the reading position in the visitor's browser (`localStorage`), nothing is sent to the server. If your site needs consent before anything is stored in the browser, add it to your consent rules or leave the block out.

= My sticky menu does not move with the contents panel. =

While the panel pushes the page aside, `<html>` has the class `dwp-rx-toc-pushed` and the CSS variables `--dwp-rx-toc-push-start` and `--dwp-rx-toc-push-end`. Use them for fixed elements in your theme, or choose "Over the page" in the block settings.

= Can I change the share channels, icons or reading speed? =

Yes, with filters such as `reader_experience/share_channels`, `reader_experience/icons` and `reader_experience/words_per_minute`. All hooks are listed in the README on GitHub.

= Is the plugin translated? =

The source strings are English; a Dutch (nl_NL) translation is included.

== Changelog ==

= 0.3.0 =
* New: Resume reading block, which offers to continue where a visitor left off.
* New: Custom link block for the interaction bar, with thirteen icons or an icon font class.
* New: the Contents button opens the table of contents in a side panel that moves the page aside on wide screens, with heading level badges, collapsible groups and optional statistics.
* New: the comments link can jump to the comments, the form or an anchor of your choice.
* New: heading anchors use reserved ids (such as `h2-comments`), an id filter, and only apply to the requested post.
* Changed: the table of contents is built in the browser and no longer depends on heading ids.
* Changed: feedback page under Comments with bulk delete; calmer rating panel with Like/Dislike labels.
* Changed: data, REST routes and prefixes are English and vendor-prefixed (`dwp-rx`, `dewitteprins/reader-experience/v1`). Existing tables are renamed automatically.
* Changed: English source strings with a complete Dutch translation.
* Fix: theme styles no longer break the table of contents lists, buttons or the bar in the editor.

= 0.2.0 =
* Filters for the heading anchors.
* Plugin header lines for Git Updater.
* Fix: a rating only adds a comment when comments are open.
* Fix: version check in the release workflow.

= 0.1.0 =
* First version: interaction bar, rating, sharing, table of contents, reading time and reading progress.
