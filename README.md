# Reader Experience

Reading tools and interaction for long articles: an interaction bar (rating, comments, sharing,
table of contents), reading time, reading progress, a table of contents and heading anchors.
Feedback is collected and can be viewed in the admin. There are no share counts from third parties.

Requires WordPress 6.5 and PHP 8.1. Updates through [Git Updater](https://git-updater.com/) from
the GitHub releases.

## First time

    npm install
    npm run build               # builds blocks/ to build/blocks/
    composer dump-autoload      # optional, there is also a built-in autoloader

## Filters and actions

Everything is configurable from a theme, a site plugin or your own core plugin. The first column
is the full hook name; every name starts with `reader_experience/`.

| Hook | Type | Default | Arguments | Example |
|---|---|---|---|---|
| `reader_experience/modules` | filter | The four modules: reading, bar, sharing, rating | `ModuleInterface[] $modules` | `add_filter( 'reader_experience/modules', fn ( $modules ) => array_filter( $modules, fn ( $m ) => 'sharing' !== $m->id() ) );` |
| `reader_experience/heading_anchors_post_types` | filter | `array()` (off everywhere) | `string[] $post_types` | `add_filter( 'reader_experience/heading_anchors_post_types', fn () => array( 'post', 'page' ) );` |
| `reader_experience/heading_anchors_enabled` | filter | `true` when the post type is in the list above | `bool $enabled, WP_Post $post` | `add_filter( 'reader_experience/heading_anchors_enabled', fn ( $on, $post ) => 42 === $post->ID ? false : $on, 10, 2 );` |
| `reader_experience/heading_anchors_load_css` | filter | `true` | `bool $load, WP_Post $post` | `add_filter( 'reader_experience/heading_anchors_load_css', '__return_false' );` |
| `reader_experience/heading_anchors_load_js` | filter | `true` | `bool $load, WP_Post $post` | `add_filter( 'reader_experience/heading_anchors_load_js', '__return_false' );` |
| `reader_experience/heading_anchors_config` | filter | `position` `'before'`, `container` `.wp-block-post-content`, `.entry-content`, `article`, `labels` copy/copied (translated) | `array $config, WP_Post $post` | `add_filter( 'reader_experience/heading_anchors_config', fn ( $config ) => array_merge( $config, array( 'position' => 'after' ) ) );` |
| `reader_experience/can_publish_as_comment` | filter | `true` when comments are open, the post has no password and commenting needs no account | `bool $allowed, int $post_id` | `add_filter( 'reader_experience/can_publish_as_comment', fn ( $ok, $id ) => $ok && 7 !== $id, 10, 2 );` |
| `reader_experience/notify_on_feedback` | filter | `true` (email the administrator) | `bool $notify` | `add_filter( 'reader_experience/notify_on_feedback', '__return_false' );` |
| `reader_experience/rate_limit_enabled` | filter | `true`, but `false` when `wp_get_environment_type()` is `local` or `development` | `bool $enabled` | `add_filter( 'reader_experience/rate_limit_enabled', '__return_false' );` |
| `reader_experience/rating_spam_check` | filter | `true` (let the request through) | `bool $passes` | `add_filter( 'reader_experience/rating_spam_check', fn () => my_captcha_is_valid() );` |
| `reader_experience/feedback_text_added` | action | n/a | `int $id, int $post_id, string $text` | `add_action( 'reader_experience/feedback_text_added', 'my_log_feedback', 10, 3 );` |
| `reader_experience/feedback_contact_requested` | action | n/a | `int $id, int $post_id` | `add_action( 'reader_experience/feedback_contact_requested', 'my_crm_lead', 10, 2 );` |
| `reader_experience/share_channels` | filter | `whatsapp`, `linkedin`, `facebook`, `email` | `array $channels` (each: `label`, `icon`, `color`, `url` callable) | `add_filter( 'reader_experience/share_channels', function ( $c ) { $c['mastodon'] = array( 'label' => 'Mastodon', 'icon' => 'share', 'color' => '#6364FF', 'url' => fn ( $url, $title ) => 'https://your.instance/share?text=' . rawurlencode( "$title $url" ) ); return $c; } );` |
| `reader_experience/icons` | filter | The built-in line icons | `array $icons` (SVG per key) | `add_filter( 'reader_experience/icons', fn ( $icons ) => array_merge( $icons, array( 'share' => '<svg>…</svg>' ) ) );` |
| `reader_experience/words_per_minute` | filter | `225` | `int $wpm` | `add_filter( 'reader_experience/words_per_minute', fn () => 200 );` |

Icon keys: `comments`, `share`, `toc`, `copy`, `link`, `thumb_up`, `thumb_down`, `whatsapp`,
`linkedin`, `facebook`, `email`.

## Heading anchors (ids and the "copy link" icon)

Off by default: without a filter, headings get no id and no CSS or JS is loaded. A site switches it
on per post type (see the table), for example from a settings page in a core plugin.

Options in `heading_anchors_config`:

- `position`: `'before'` (default, hanging in the left margin, aligned with the first line) or `'after'`.
- `container`: selector(s) of the element that holds the headings; the first one that exists wins.
- `labels`: `copy` (button text and tooltip) and `copied` (confirmation), already translated.

Always return the whole array from this filter. To use your own CSS or JS, return `false` for
`heading_anchors_load_css` and/or `heading_anchors_load_js` and style the classes `.rx-heading-link`,
`--before`/`--after` and `.rx-heading-link__toast`. The options for our own JS are in
`window.rxHeadingAnchors`; a replacement script can read them if it wants to.

## Table of contents

The table of contents block builds its list in the browser from the h2 to h4 headings in the content
(`.wp-block-post-content`, with `.entry-content`, `article` and `main` as fallbacks). It does not
depend on heading ids or on the anchor links above: every heading gets `data-rx-heading="<n>"`, its
position among the headings, and the list links to that. A text edit keeps the number, so the list
still leads to about the right place; adding or removing a heading shifts the numbers after it. When
a heading has an id of its own, the link uses it as well and the address bar gets that hash. The
block stays hidden when the page has fewer headings than the block's minimum. Without JavaScript
the block does not show.

### Contents button and panel

The "Contents" button in the interaction bar opens the same list in a side panel (a `<dialog>`), like
the list view in the block editor. The panel is part of that button; there is no standalone option.
Block settings: panel title, side (left or right, default left), the deepest heading level, the
minimum number of headings (the button stays hidden below it) and collapsible sub-levels. On a large
screen the panel is non-modal and stays open while you read; on a small screen (up to 781px) it is a
modal overlay. It closes with the close button, the Contents button, Escape, a tap outside it or the
browser's back button, and on a small screen also when you choose a heading. The inline TOC block
stays available for places such as a sidebar, widget area or navigation.

## Rating and comments

A comment on a positive rating is only offered as a WordPress comment when that is possible:
comments are open (`comments_open()`, including automatic closing), the post has no password and
commenting needs no account. Otherwise the comment stays private. Adjust it with
`reader_experience/can_publish_as_comment`.

The REST routes are `dewitteprins/reader-experience/v1/rating/{id}/text` and `rx/v1/rating/{id}/follow-up`. Votes are stored as
`like` or `dislike`; follow-up actions are `publish`, `contact` and `skip`.

## Adding a module or block

1. Create `src/Modules/<Name>/<Name>Module.php` (extends `AbstractModule`).
2. Return the block slugs from `blocks()`; the source lives in `blocks/<slug>/`.
3. Add the module in `Plugin::build_modules()` or through the `reader_experience/modules` filter.

## Core

The plugin has no dependency on a core plugin; all modules are self-contained and start on
`plugins_loaded`. If a real coupling is needed (a shared migration runner, settings API or REST
base), add it in `Plugin::start()` in a targeted way instead of a generic "wait for the core"
construction.

## Code style

- WordPress Core and Docs standards, checked by `phpcs.xml`: `phpcs --standard=phpcs.xml`.
- `snake_case` for functions, methods, properties and variables; `kebab-case` for slugs, handles and
  CSS tokens; `CamelCase` only for class names and namespaces (`ReaderExperience\…`).
- Class files are named after the class (PSR-4), without a `class-` prefix.
- Source strings are English, text domain `reader-experience`; the Dutch translation is in `languages/`.

## Translations

Source strings are English. `languages/` holds the template (`reader-experience.pot`), the Dutch
translation (`reader-experience-nl_NL.po` and `.mo`) and JSON files with the strings of the block
editor scripts. The JSON files are named after the MD5 of the script path
(`build/blocks/<slug>/index.js`), which is how WordPress finds them.

Only `nl_NL` is bundled. Other variants of the same language (`nl_NL_formal`, `nl_BE`) fall back to it,
so the strings are not left in English.

After changing strings, update the template and the translation with WP-CLI, Loco Translate or Poedit:

    wp i18n make-pot . languages/reader-experience.pot --exclude=node_modules,vendor,build
