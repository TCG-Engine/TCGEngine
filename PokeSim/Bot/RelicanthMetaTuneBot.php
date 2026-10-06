<?php
require_once __DIR__.'/RelicanthDrawBot.php';
/** Separate version entry point: retain the tested fossil replacement and
 * matchup-aware Bangle/Boss policy for the meta-tuned card quantities. */
function PokeRelicanthMetaTuneChoose(array $view): ?array {
    return PokeRelicanthDrawChoose($view);
}
