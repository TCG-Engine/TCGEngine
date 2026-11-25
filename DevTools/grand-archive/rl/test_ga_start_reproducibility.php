<?php
// A previous game's last acting seat must not influence the next game's shuffle.
define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once __DIR__ . '/../../TestAutomationBridge.php';
chdir(dirname(__DIR__, 3));
$deck = str_replace('60 Esteemed Knight', '30 Esteemed Knight' . PHP_EOL . '30 Weaving Manastream', file_get_contents(__DIR__ . '/benchmark_control_ga.txt'));
$dirs = [];
try {
  $hashes = [];
  foreach ([1, 2, 1] as $i => $previousSeat) {
    $GLOBALS['playerID'] = $previousSeat;
    $name = 'ga_start_repro_' . getmypid() . '_' . $i;
    $dirs[] = 'GrandArchiveSim/Games/' . $name;
    $result = BridgeStartSelfplayGame('GrandArchiveSim', $name, 4000016, $deck, $deck, false);
    $hashes[] = $result['gamestateHash'];
  }
  if (count(array_unique($hashes)) !== 1) throw new RuntimeException('Starting state depends on the previous acting seat');
  echo "Starting states identical after either previous acting seat\n";
} finally {
  foreach ($dirs as $dir) {
    foreach (glob($dir . '/*') ?: [] as $file) if (is_file($file)) unlink($file);
    rmdir($dir);
  }
}
