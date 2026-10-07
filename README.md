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
