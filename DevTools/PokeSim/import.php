<?php
require_once __DIR__ . '/../../PokeSim/Import/TCGdex.php';
// Default is the entire English paper TCG catalog. --deck restricts the initial download.
$options = getopt('', ['deck:', 'set:', 'refresh', 'no-images']);
$ids = null;
if (isset($options['deck'], $options['set'])) { fwrite(STDERR, "Choose --deck or --set, not both\n"); exit(1); }
if (isset($options['set'])) {
    $setId = $options['set'];
    if (!preg_match('/^[A-Za-z0-9._-]+$/D', $setId)) { fwrite(STDERR, "Invalid set identifier\n"); exit(1); }
    $set = PokeTCGdexRequest('https://api.tcgdex.net/v2/en/sets/' . $setId);
    if (empty($set['cards'])) { fwrite(STDERR, "Set has no cards\n"); exit(1); }
    $ids = array_column($set['cards'], 'id');
    PokeImportWriteJson(dirname(__DIR__,2).'/PokeSim/GeneratedCode/tcgdex/sets/'.$setId.'.json', $set);
    echo 'Importing '.$set['name'].': '.count($ids)." printings\n";
}
if (isset($options['deck'])) {
    require_once __DIR__ . '/../../PokeSim/Custom/DeckImport.php';
    $ids = array_column(PokeParseDeckText(file_get_contents($options['deck']), false), 'id');
}
try { echo 'Catalog: ' . PokeTCGdexImport(isset($options['refresh']), $ids, !isset($options['no-images'])) . " cards\n"; }
catch (Throwable $error) { fwrite(STDERR, $error->getMessage() . "\n"); exit(1); }
