# Conventions for this repository

DRAFT: written from the code of this plugin and `core-functionality-oop`, and from what Hans said
during the style round. Hans: correct what is wrong; delete what you do not want. Items marked
"(confirm)" are guesses.

## How we work

- Advice first, then build. For anything that is hard to redo, ask before building.
- Small, separate patches: one commit per kind of change (style, feature, translation, docs).
  Mechanical renames never share a commit with behaviour changes.
- Never change hook names, block names, CSS classes, data attributes, REST routes or stored values
  while restyling. They are contracts with the site. Say so in the commit message.
- Verify before delivering: `php -l`, `npm run build`, `phpcs --standard=phpcs.xml`, and a real
  browser check (Chromium) for anything visual.

## PHP

- WordPress Core + Docs standards (`phpcs.xml`), tabs, braces on the same line.
- `snake_case` for functions, methods, properties and variables. `CamelCase` only for class names
  and namespaces. `kebab-case` for slugs, handles, CSS classes and tokens.
- Class files are named after the class (PSR-4), no `class-` prefix.
- Import with `use` at the top; never `\WP_Post` in the code. In namespaced files also import every
  function (`use function __;`, `use function is_array;`), like `core-functionality-oop`. Order:
  classes, then functions, then constants. Constants are not imported. Block render templates are in
  the global namespace and need no imports.
- File docblock with `@package`, `@author Hans Schuijff <@hansschuijff>`, `@license`, `@since`;
  a docblock for every class, method, property, constant and hook (`@since`, `@param`, `@return`).
- `declare( strict_types=1 );` with spaces inside the parentheses, as WPCS spacing suggests.
- Hooks are `reader_experience/<name>`. Every hook is documented in the code and listed in the
  README table (hook, type, default, arguments, example).

## Language and translations

- Source strings, comments and docblocks are English. Text domain `reader-experience`.
- The site is Dutch: every new string gets a nl_NL translation in `languages/`
  (`.pot`, `.po`, `.mo`, and `.json` per block editor script).
- Data and wire formats that are Dutch for historical reasons (`ja`, `nee`, `tekst`, `actie`,
  `naam`, `publiceren`, `overslaan`, `vervolg`, anchor `sectie`) stay as they are.

## CSS and JS

- Shared look in `assets/css/base.css` (handle `rx-base`); blocks add only what is specific.
- Themes style `button:hover` and `a:hover`; use doubled-class selectors where a theme could win.
- The bar must look the same in the editor and on the front end, in light and dark.
- Comments in CSS and JS are still partly Dutch (to convert, low priority).

## Git

- Patches are delivered with `git format-patch` and applied with `git am` (not `git apply`).
