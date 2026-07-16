<?php

// Recursively discovers every *.md file under this directory and registers one
// test_ function per schema TEST. Subfolder names are included in the function name.
//
// A file may hold MULTIPLE tests, split by a markdown "---" rule; each test is
// named by a single-"#" header (TitleCase). Example:
//
//   core/CommonSetup.md
//     # HandCardIdsAlias
//     ...  ->  test_core_common_setup_hand_card_ids_alias()
//     ---
//     # LeaderOverride
//     ...  ->  test_core_common_setup_leader_override()
//
// A file with no "---" is a single legacy test named entirely by its filename
// (byte-identical to the old behavior): win_con/EndGameFinalBlow.md →
// test_win_con_end_game_final_blow(). "#//" is a comment; "##" is a section header.
//
// Drop a new .md anywhere under Tests/Cases/ and it runs automatically.

(function () {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));

    // CamelCase/TitleCase path part → snake_case (same transform for file + sub-name).
    $toSnake = fn(string $p): string =>
        strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $p));

    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'md') continue;

        $absPath = $file->getPathname();

        // Relative path from this directory, forward-slash normalised.
        $rel = str_replace(DIRECTORY_SEPARATOR, '/', substr($absPath, strlen(__DIR__) + 1));

        // File portion of the function name: strip ".md", snake-case each path part.
        $noExt     = substr($rel, 0, -3);
        $fileSnake = implode('_', array_map($toSnake, explode('/', $noExt)));

        // Path relative to repo root for SchemaTestRunner::runFile().
        $relPath = "SWUSim/Tests/Cases/{$rel}";

        $segments = SchemaTestRunner::splitSegments(file_get_contents($absPath));

        // Single-test file — legacy behavior, byte-identical name + whole-file run.
        if (count($segments) === 1) {
            $fnName = "test_{$fileSnake}";
            if (!function_exists($fnName)) {
                eval("function {$fnName}() {
                    \$result = SchemaTestRunner::runFile(" . var_export($relPath, true) . ");
                    if (!\$result->passed) throw new RuntimeException(\$result->message);
                }");
            }
            continue;
        }

        // Multi-test file — one test_ function per "---"-delimited segment.
        foreach ($segments as $i => $seg) {
            $sub = ($seg['name'] !== null && $seg['name'] !== '')
                ? $toSnake(preg_replace('/[^A-Za-z0-9]/', '', $seg['name']))
                : ('test' . ($i + 1));

            $fnName = "test_{$fileSnake}_{$sub}";
            // De-dupe if two segments collapse to the same suffix.
            $base = $fnName; $n = 2;
            while (function_exists($fnName)) { $fnName = "{$base}_{$n}"; $n++; }

            $content = $seg['content'];
            $label   = "{$relPath}::" . ($seg['name'] ?? ('#' . ($i + 1)));
            eval("function {$fnName}() {
                \$result = SchemaTestRunner::runString("
                    . var_export($content, true) . ", "
                    . var_export($label, true) . ");
                if (!\$result->passed) throw new RuntimeException(\$result->message);
            }");
        }
    }
})();
