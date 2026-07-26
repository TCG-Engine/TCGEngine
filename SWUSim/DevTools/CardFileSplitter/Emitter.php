<?php
// Dev-time emitter: turns a scanned+routed monolith into per-card files, the
// rewritten (shrunken) monolith, a CardID→path index, and a log of what stayed
// behind. Requires HeaderGen/Scanner/Router + the dictionaries + reprint APIs.

// Build the split plan for ONE monolith source against a target set.
// Returns:
//   files     => [ baseCardID => ['set'=>, 'basename'=>, 'reprints'=>[], 'body'=>] ]
//   remaining => the source with every moved statement removed
//   index     => [ printingCardID => "set/Basename.php" ] for every emitted card
//   left      => [ ['reason'=>, 'text'=>] ]  statements touching the target set that stayed
function splitter_emit_plan(string $monolithSource, string $targetSet, array $testMap, array $pinned = []): array {
    $stmts = splitter_scan($monolithSource);

    $files = [];
    $moveSpans = [];      // spans to delete from the monolith
    $left = [];

    foreach ($stmts as $s) {
        $r = splitter_route($s, $targetSet, $pinned);
        if ($r['action'] === 'move') {
            $base = $r['baseCardID'];
            if (!isset($files[$base])) {
                $files[$base] = [
                    'set'        => $r['set'],
                    'basename'   => splitter_card_basename($base, $testMap),
                    'reprints'   => [],
                    'statements' => [],
                ];
            }
            $files[$base]['statements'][] = rtrim($s['text']);
            // Any printing CardID referenced in this statement's LHS that isn't the base
            // is a reprint printing to advertise in the header.
            foreach (splitter_lhs_cardids($s['lhs']) as $cid) {
                if ($cid !== $base && !in_array($cid, $files[$base]['reprints'], true)) {
                    $files[$base]['reprints'][] = $cid;
                }
            }
            $moveSpans[] = $s['span'];
        } else {
            // Log leaves that reference the target set (helps the operator review).
            $touches = false;
            foreach ($s['cardIDs'] as $c) if (strtoupper(SWUCardSet(CardIDOverride($c))) === strtoupper($targetSet)) { $touches = true; break; }
            if ($touches) $left[] = ['reason'=>$r['reason'], 'text'=>trim(strtok($s['text'], "\n"))];
        }
    }

    // Finalize file bodies + header + reprint list (from the full reprint group).
    $index = [];
    foreach ($files as $base => &$fdata) {
        // Reprints = every OTHER printing in the group (union with any seen in LHS).
        foreach (SWUReprintGroup($base) as $print) {
            if ($print !== $base && !in_array($print, $fdata['reprints'], true)) $fdata['reprints'][] = $print;
        }
        sort($fdata['reprints']);
        $fdata['body'] = splitter_render_card($base, $fdata['reprints'], $fdata['statements']);

        $relpath = $fdata['set'] . '/' . $fdata['basename'] . '.php';
        foreach (SWUReprintGroup($base) as $print) $index[$print] = $relpath;
        $index[$base] = $relpath;
    }
    unset($fdata);

    // Rewrite the monolith: remove moved spans in descending order.
    usort($moveSpans, fn($a,$b) => $b[0] <=> $a[0]);
    $remaining = $monolithSource;
    foreach ($moveSpans as [$a,$b]) {
        $remaining = substr($remaining, 0, $a) . substr($remaining, $b);
    }

    return ['files'=>$files, 'remaining'=>$remaining, 'index'=>$index, 'left'=>$left];
}

// Render the final file body (header + statements). Shared by single-source
// emit and the driver's cross-monolith merge, so both produce identical output.
function splitter_render_card(string $base, array $reprints, array $statements): string {
    sort($reprints);
    return splitter_card_header($base, $reprints) . "\n" . implode("\n\n", $statements) . "\n";
}

// Distinct CardIDs appearing in an LHS assignment-key string.
function splitter_lhs_cardids(string $lhs): array {
    preg_match_all('/\b[A-Z0-9]{2,4}_\d+\b/', $lhs, $m);
    return array_values(array_unique($m[0]));
}

// Write a plan's card files to $cardsDir/<set>/<basename>.php. Returns paths written.
function splitter_write_plan(array $plan, string $cardsDir): array {
    $written = [];
    foreach ($plan['files'] as $base => $f) {
        $dir = "$cardsDir/{$f['set']}";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $path = "$dir/{$f['basename']}.php";
        file_put_contents($path, "<?php\n" . $f['body']);
        $written[] = $path;
    }
    return $written;
}

// Syntax-check a PHP source string via `php -l`. Returns true if valid.
function splitter_php_lints(string $code): bool {
    $tmp = tempnam(sys_get_temp_dir(), 'splitlint');
    file_put_contents($tmp, $code);
    $out = []; $rc = 0;
    exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $out, $rc);
    unlink($tmp);
    return $rc === 0;
}
