<?php

// Recursively discovers every *.md file under this directory and registers one
// test_ function per schema. Subfolder names are included in the function name.
//
// e.g. win_con/EndGameFinalBlow.md → test_win_con_end_game_final_blow()
//
// Drop a new .md anywhere under Tests/Cases/ and it runs automatically.

(function () {
    $repoRoot = dirname(__DIR__, 3); // SWUSim/Tests/Cases → repo root
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));

    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'md') continue;

        $absPath = $file->getPathname();

        // Relative path from this directory, forward-slash normalised.
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', substr($absPath, strlen(__DIR__) + 1));

        // Build function name: strip .md, replace path separators + CamelCase → snake_case.
        $noExt   = substr($rel, 0, -3); // strip ".md"
        $parts   = explode('/', $noExt);
        $snake   = implode('_', array_map(
            fn($p) => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $p)),
            $parts
        ));
        $fnName  = "test_{$snake}";

        // Path relative to repo root for SchemaTestRunner::runFile().
        $relPath = "SWUSim/Tests/Cases/{$rel}";

        if (!function_exists($fnName)) {
            eval("function {$fnName}() {
                \$result = SchemaTestRunner::runFile(" . var_export($relPath, true) . ");
                if (!\$result->passed) throw new RuntimeException(\$result->message);
            }");
        }
    }
})();
