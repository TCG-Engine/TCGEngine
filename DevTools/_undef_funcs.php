<?php
// Throwaway: lists calls to functions that do not exist once the GA runtime is loaded.
error_reporting(E_ERROR|E_PARSE);
$repoRoot = dirname(__DIR__);
require_once $repoRoot . '/Core/EngineActionRunner.php';
define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once $repoRoot . '/DevTools/TestAutomationBridge.php';
EngineLoadRootRuntime('GrandArchiveSim');
$files = array_merge(glob($repoRoot . '/GrandArchiveSim/Custom/*.php'), [$repoRoot . '/GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php', $repoRoot . '/GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php']);
$out = [];
foreach ($files as $f) {
    $toks = token_get_all(file_get_contents($f));
    $n = count($toks);
    for ($i = 0; $i < $n; ++$i) {
        if (!is_array($toks[$i]) || $toks[$i][0] !== T_STRING) continue;
        $j = $i + 1; while ($j < $n && is_array($toks[$j]) && $toks[$j][0] === T_WHITESPACE) ++$j;
        if (!isset($toks[$j]) || $toks[$j] !== '(') continue;
        $k = $i - 1; while ($k >= 0 && is_array($toks[$k]) && $toks[$k][0] === T_WHITESPACE) --$k;
        $prev = $toks[$k];
        if (is_array($prev) && in_array($prev[0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW, T_NULLSAFE_OBJECT_OPERATOR, T_NS_SEPARATOR])) continue;
        if ($prev === '&' ) { $k2 = $k - 1; while ($k2 >= 0 && is_array($toks[$k2]) && $toks[$k2][0] === T_WHITESPACE) --$k2; if (is_array($toks[$k2]) && $toks[$k2][0] === T_FUNCTION) continue; }
        $name = $toks[$i][1];
        if (in_array(strtolower($name), ['array','list','isset','unset','empty','exit','die','eval','echo','print','if','while','for','foreach','switch','catch','match','function','fn','use','compact','extract'])) continue;
        if (function_exists($name)) continue;
        $out[$name][basename($f) . ':' . $toks[$i][2]] = true;
    }
}
ksort($out);
foreach ($out as $name => $locs) echo $name . ' ' . implode(',', array_slice(array_keys($locs), 0, 4)) . "\n";
