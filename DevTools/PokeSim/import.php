<?php
require_once __DIR__ . '/../../PokeSim/Import/TCGdex.php';
// Default is the entire English paper TCG catalog. --deck restricts the initial download.
$options = getopt('', ['deck:', 'refresh', 'no-images']);
$ids = null;
if (isset($options['deck'])) {
    require_once __DIR__ . '/../../PokeSim/Custom/DeckImport.php';
    $ids = array_column(PokeParseDeckText(file_get_contents($options['deck']), false), 'id');
}
try { echo 'Catalog: ' . PokeTCGdexImport(isset($options['refresh']), $ids, !isset($options['no-images'])) . " cards\n"; }
catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
