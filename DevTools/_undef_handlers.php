<?php
error_reporting(E_ERROR|E_PARSE);
$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/Core/EngineActionRunner.php';
define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once $repoRoot . '/DevTools/TestAutomationBridge.php';
EngineLoadRootRuntime('GrandArchiveSim');
global $customDQHandlers, $systemDQHandlers;
$files = array_merge(glob($repoRoot . '/GrandArchiveSim/Custom/*.php'), [$repoRoot . '/GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php'], glob($repoRoot . '/Core/*.php'));
$out = [];
foreach ($files as $f) {
    $src = file_get_contents($f);
    foreach (['CUSTOM' => $customDQHandlers, 'SYSTEM' => $systemDQHandlers] as $kind => $table) {
        if (!preg_match_all('/"' . $kind . '"\s*,\s*"([^"|$\\\\]+)(["|])/', $src, $mm, PREG_OFFSET_CAPTURE)) continue;
        foreach ($mm[1] as $i => $m) {
            $name = $m[0];
            $term = $mm[2][$i][0];
            // concatenations like "Name" . $x are dynamic: skip when the literal is followed by a '.'
            $after = substr($src, $mm[2][$i][1], 12);
            if ($term === '"' && preg_match('/^"\s*\./', $after)) continue;
            if (isset($table[$name])) continue;
            $line = substr_count(substr($src, 0, $m[1]), "\n") + 1;
            $out[$kind . ' ' . $name][basename($f) . ':' . $line] = true;
        }
    }
}
ksort($out);
foreach ($out as $k => $locs) echo $k . '  ' . implode(',', array_slice(array_keys($locs), 0, 3)) . "\n";
