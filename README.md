# Reader Experience

## Eerste keer

    composer dump-autoload      # optioneel, er is ook een ingebouwde autoloader
    npm install --save-dev @wordpress/scripts
    npm run build               # bouwt blocks/ naar build/blocks/

## Core

De plugin heeft momenteel geen afhankelijkheid van een core; alle modules zijn zelfstandig
en starten op `plugins_loaded`. Komt er een echte koppeling nodig (een gedeelde migratierunner,
instellingen-API of REST-basis), voeg die dan gericht toe in `Plugin::start()` in plaats van
een generieke "wacht op de core"-constructie.

## Een module of blok toevoegen

1. Maak `src/Modules/<Naam>/<Naam>Module.php` (extends `AbstractModule`).
2. Geef de blokslugs terug in `blocks()`; de bron staat in `blocks/<slug>/`.
3. Voeg de module toe in `Plugin::buildModules()` of via het filter `reader_experience/modules`.

## Kop-ankers (id's en "kopieer link"-icoontje)

Standaard **uit**: zonder filter krijgen koppen geen id, en worden er geen CSS of JS geladen.
Een site zet het aan, bijvoorbeeld vanuit een instellingenpagina in een core-plugin:

```php
add_filter( 'reader_experience/heading_anchors_post_types', fn () => array( 'post', 'page' ) );
```

| Filter | Type | Standaard | Doel |
|---|---|---|---|
| `reader_experience/heading_anchors_post_types` | `string[]` | `array()` | Post types waarvoor het aan staat |
| `reader_experience/heading_anchors_enabled` | `bool` | post type in de lijst | Eindoordeel per bericht (args: `bool`, `WP_Post`) |
| `reader_experience/heading_anchors_load_css` | `bool` | `true` | Meegeleverde CSS laden (args: `bool`, `WP_Post`) |
| `reader_experience/heading_anchors_load_js` | `bool` | `true` | Meegeleverde JS laden (args: `bool`, `WP_Post`) |
| `reader_experience/heading_anchors_config` | `array` | zie hieronder | Opties voor de JS (args: `array`, `WP_Post`) |

Opties in `heading_anchors_config`:

- `position`: `'before'` (standaard, hangend in de linkermarge, uitgelijnd op de eerste regel) of `'after'`.
- `container`: selector(s) van het artikel waarin gezocht wordt; de eerste die bestaat wint.
  Standaard `.wp-block-post-content`, `.entry-content`, `article`.
- `labels`: `copy` (knoptekst/tooltip) en `copied` (bevestiging), al vertaald.

Eigen CSS of JS gebruiken: geef `false` terug op `..._load_css` en/of `..._load_js` en laad zelf iets
voor de classes `.rx-heading-link`, `--before`/`--after` en `.rx-heading-link__toast`. De opties
staan voor onze eigen JS in `window.rxHeadingAnchors`; een vervangende JS leest ze desgewenst zelf.

**Inhoudsopgave-uitzondering:** bevat een bericht een inhoudsopgave-blok, dan krijgen de koppen
altijd een id, ook als het post type niet is aangezet; de inhoudsopgave verwijst daar naar. Alleen de
id's: knop, CSS en JS blijven uit. Staat de inhoudsopgave in een template of zijbalk in plaats van in
de berichttekst, dan is die uitzondering niet te zien en moet het post type wél aan.

## Waardering en reacties

Een toelichting bij een positieve waardering wordt alleen als reactie aangeboden als dat kan:
reacties staan open (`comments_open()`, inclusief automatisch sluiten), het bericht is niet met een
wachtwoord beveiligd en reageren vereist geen account. Anders blijft de toelichting privé. Bijsturen
kan met `reader_experience/can_publish_as_comment` (args: `bool`, post-id).
