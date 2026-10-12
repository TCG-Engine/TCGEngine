<?php
/**
 * ga-scaffold.php -- emit a scenario skeleton for a GrandArchiveSim card from card data, for a human or Claude to finish.
 *
 *   php DevTools/ga-scaffold.php "Card Name" ["Other Card" ...]       skeleton(s) on stdout
 *   php DevTools/ga-scaffold.php --element=WIND --type=ACTION --limit=10   the next uncovered cards of that shape
 *   php DevTools/ga-scaffold.php --list --element=WIND                 uncovered cards grouped by shape (what to batch)
 *   php DevTools/ga-scaffold.php --unmatched --element=WIND            printed clauses no mechanic row recognises (the new effects), by shape
 *   php DevTools/ga-scaffold.php "Card Name" --probe                    also run the skeleton and print the real prompt order
 *   php DevTools/ga-scaffold.php "Card Name" --write=DevTools/ga-fixtures/wind-actions.php   append the skeleton to a fixture file
 *
 * What the skeleton fills in from card data: the deck, readyFor() (element access, [Class Bonus], [Level N+]), the seed, the play with
 * the printed reserve cost (or the cost after a printed "costs N less"), the target prompt, the covers() mechanics and printed rules
 * clauses, and the tested card. What it cannot know and leaves as TODO: window passes, the real prompt order, the observable result.
 * Use --probe (or DevTools/ga-fixture-run.sh --play) to read the real prompt order, then write the answers and the expect*() lines.
 *
 * Every skeleton slug starts with 'todo-': GaScenario::register() refuses it, so an unfinished skeleton cannot be committed by accident.
 * Rename the slug (describe the behaviour) when the scenario is finished.
 *
 * Exemplars: for each mechanic in the card text, the already-covered cards that share it and the fixture slug of one of them, so the
 * author copies a proven shape instead of re-deriving the prompt order.
 */
chdir(dirname(__DIR__));
error_reporting(E_ALL & ~E_DEPRECATED);
require 'GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php';

$args = [];
$names = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z]+)(?:=(.*))?$/', $a, $m)) $args[$m[1]] = $m[2] ?? true;
    else $names[] = $a;
}

$backlogFile = 'Tests/Integration/GrandArchiveSim/semantic-coverage-backlog.json';
$backlog = json_decode(file_get_contents($backlogFile), true)['cards'];
$byId = [];
foreach ($backlog as $c) $byId[$c['cardId']] = $c;

// ---- text helpers -------------------------------------------------------------------------------------------------------

function gsText($id): string { return trim(preg_replace('/[ \t]+/', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", strval(CardEffect($id)))))); }

// Printed clauses: one per paragraph, markdown asterisks kept exactly as printed (the audit compares clauses to card text).
function gsClauses($id): array {
    $out = [];
    foreach (preg_split('/\n\s*\n|\n/', gsText($id)) as $line) { $line = trim($line); if ($line !== '') $out[] = $line; }
    return $out ?: [gsText($id)];
}

function gsQuote(string $s): string { return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], $s) . "'"; }

function gsSlug(string $name): string { return 'todo-' . trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') . '-describe-the-behaviour'; }

function gsElementOf($id): string { return trim(explode(',', strval(CardElement($id)))[0]); }

// Mechanics found in the printed text. The table lives in ga-fixtures/mechanics.php (one row per mechanic); returns key => [tag, hint].
function gsMechanicTable(): array { static $t = null; return $t ??= require __DIR__ . '/ga-fixtures/mechanics.php'; }

function gsFeatures(string $text): array {
    $found = [];
    foreach (gsMechanicTable() as $key => $row) if (preg_match($row['re'], $text)) $found[$key] = [$row['tag'], $row['hint']];
    return $found;
}

// Printed clauses that no mechanic row recognises: the NEW effects, which have no hint, exemplar or tag and need a hand-written fixture.
function gsUnmatchedClauses($id): array {
    $out = [];
    foreach (gsClauses($id) as $clause) {
        $matched = false;
        foreach (gsMechanicTable() as $row) if (preg_match($row['re'], $clause)) { $matched = true; break; }
        if (!$matched) $out[] = $clause;
    }
    return $out;
}

// The target the first printed "target ..." needs, as [description, seeds, mz answer].
function gsTarget(string $text): ?array {
    if (!preg_match('/\btarget\b([^.\/]*)/i', $text, $m)) return null;
    $t = strtolower($m[1]);
    if (preg_match('/champion/', $t)) return ['a champion', [], str_contains($t, 'your') ? 'myField-0' : 'theirField-0'];
    if (preg_match('/\b(ally|allies|animal|beast|human|ranger|unit)\b/', $t)) {
        $mine = str_contains($t, 'you control') || str_contains($t, 'your');
        if (str_contains($t, 'unit') && !$mine) return ['a unit (the opposing champion)', [], 'theirField-0'];
        if ($mine) return ['an ally you control', [['myField', 'Giant Tortoise', 1]], 'myField-1'];
        return ['an opposing ally', [['theirField', 'Giant Tortoise', 2]], 'theirField-1'];
    }
    if (preg_match('/graveyard/', $t)) return ['a card in a graveyard', [['myGraveyard', 'Giant Tortoise', 1]], 'myGraveyard-0'];
    if (preg_match('/\b(weapon|item|regalia|potion|phantasia|domain)\b/', $t)) return ['a regalia/item', [], 'myField-1'];
    if (preg_match('/player/', $t)) return ['a player', [], '1'];
    return ['something', [], '?'];
}

// Reserve cost after the printed reduction the readyFor flags unlock.
function gsEffectiveCost(int $cost, string $text, bool $classBonus, int $level): int {
    if ($classBonus && preg_match('/\[Class Bonus\][^\/\n]*?costs? (\d+) less/i', $text, $m)) $cost -= (int)$m[1];
    if (preg_match('/Efficiency/i', $text) && (!preg_match('/\[Class Bonus\][^\/\n]*Efficiency/i', $text) || $classBonus)) $cost -= $level;
    return max(0, $cost);
}

// Exemplars: covered cards sharing a mechanic, with one fixture slug each.
function gsExemplars(array $features, string $selfId, array $backlog): array {
    static $index = null;
    if ($index === null) {
        $index = [];
        foreach ($backlog as $c) {
            if (empty($c['existingFixtures']) || empty($c['semanticFixtures'])) continue;
            $f = gsFeatures(gsText($c['cardId']));
            foreach (array_keys($f) as $k) $index[$k][] = [$c['name'], $c['semanticFixtures'][0], $c['cardId']];
        }
    }
    $out = [];
    foreach (array_keys($features) as $k) {
        if (empty(gsMechanicTable()[$k]['exemplar'])) continue;
        $pick = array_values(array_filter($index[$k] ?? [], fn($e) => $e[2] !== $selfId));
        // Same element and type first: their prompt order is the most likely to match.
        $score = fn($e) => (gsElementOf($e[2]) === gsElementOf($selfId) ? 2 : 0) + (strval(CardType($e[2])) === strval(CardType($selfId)) ? 1 : 0);
        usort($pick, fn($a, $b) => $score($b) <=> $score($a));
        if ($pick) $out[$k] = array_slice($pick, 0, 2);
    }
    return $out;
}

// ---- skeleton -----------------------------------------------------------------------------------------------------------

function gsSkeleton(string $id, array $backlog, array $byId, bool $probe = false): string {
    $name = CardName($id);
    $type = strval(CardType($id));
    $text = gsText($id);
    $cost = (int)CardCost_reserve($id);
    $features = gsFeatures($text);
    $classBonus = isset($features['class-bonus']);
    $level = 0;
    if (preg_match('/\[Level (\d+)\+\]/', $text, $m)) $level = (int)$m[1];
    $target = gsTarget($text);
    $isAlly = str_contains($type, 'ALLY');
    $isAttack = str_contains($type, 'ATTACK');
    $isAction = str_contains($type, 'ACTION');
    $isChampion = str_contains($type, 'CHAMPION');
    $speed = strval(CardSpeed($id));
    $fast = str_contains($text, 'Fast Activation') || stripos($speed, 'fast') !== false;
    $mechanics = array_values(array_unique(array_merge($byId[$id]['mechanics'] ?? [], array_map(fn($f) => $f[0], $features))));
    $clauses = array_map('gsQuote', gsClauses($id));

    $readyArgs = [gsQuote($name)];
    if ($classBonus) $readyArgs[] = 'classBonus: true';
    if ($level > 0) $readyArgs[] = "level: {$level}";
    $payNow = gsEffectiveCost($cost, $text, $classBonus, max($level, 1));

    $lines = [];
    $lines[] = "// {$name} | {$type} | " . CardElement($id) . " | " . CardClasses($id) . " | reserve {$cost}" . (CardCost_memory($id) ? ' memory ' . CardCost_memory($id) : '') . ($isAlly ? ' | ' . CardPower($id) . '/' . CardLife($id) : '') . " | id {$id}";
    foreach (preg_split('/\n/', $text) as $t) if (trim($t) !== '') $lines[] = '//   ' . $t;
    $lines[] = "gaScenario(" . gsQuote(gsSlug($name)) . ")";
    $lines[] = "    ->deckTemplate('quiet', " . gsQuote($name) . ")";   // no fast cards in the opening hands: no priority window between steps
    $lines[] = "    ->readyFor(" . implode(', ', $readyArgs) . ")";
    if ($target) foreach ($target[1] as [$zone, $card, $p]) $lines[] = "    ->seed('" . (str_starts_with($zone, 'their') ? 'my' . substr($zone, 5) : $zone) . "', '{$card}'" . (str_starts_with($zone, 'their') ? ', player: 2' : '') . ")   // TODO: board for target {$target[0]} (an opponent's card is seeded into THEIR own 'my' zone with player: 2)";
    $lines[] = "    ->seed('myHand', " . gsQuote($name) . ")";

    if ($probe) {
        // Only the play and the payment: ga-autoplay answers everything after this and prints the prompt order.
        if ($isAttack) $lines[] = "    ->reachTurnThree()->playSlot(7)" . ($payNow > 0 ? "->pay({$payNow})" : '');
        else $lines[] = "    ->play(" . gsQuote($name) . ")" . ($payNow > 0 ? "->pay({$payNow})" : '');
    } elseif ($isAttack) {
        $lines[] = "    ->reachTurnThree()";
        $lines[] = "    ->playSlot(7)->pay({$payNow})->target('theirField-0')   // TODO: window passes / extra prompts";
    } elseif ($isAlly || str_contains($type, 'ITEM') || str_contains($type, 'REGALIA') || str_contains($type, 'WEAPON') || str_contains($type, 'DOMAIN') || str_contains($type, 'PHANTASIA')) {
        $enterTarget = $target ? "->answer(1, '{$target[2]}')" : '';
        $lines[] = "    ->play(" . gsQuote($name) . ")->pay({$payNow}){$enterTarget}   // TODO: On Enter answers (target / YES) -- run --probe; a seeded fast card or a fast card in the opening hand would add priority windows";
    } elseif ($isChampion) {
        $lines[] = "    // TODO: champions level up through the Lorraine recipe: deckTemplate('lorraine-windslice', 'Dungeon Guide')->endTurn()->endTurn(2)->target('myMaterial-0')...";
    } else {
        $window = $fast ? "'both'" : "''";
        $tgt = $target ? ", target: '{$target[2]}'" : '';
        $lines[] = "    ->castSpell(" . gsQuote($name) . ", pay: {$payNow}, window: {$window}{$tgt})   // TODO: window/target order -- run --probe";
        $lines[] = "    ->passBoth()";
    }
    $lines[] = "    // TODO: observable result of every printed clause (expectDamage / expectPower / expectLife / expectZoneCount / expectProperty / expectCardExists)";
    $lines[] = "    ->expectDqEmpty()";
    $lines[] = "    ->tested(" . gsQuote($name) . ")";
    $lines[] = "    ->covers([" . implode(', ', array_map('gsQuote', $mechanics)) . "], " . implode(', ', $clauses) . ")";
    $lines[] = "    ->register();   // TODO: rename the slug to describe the behaviour";

    $twins = [];
    if ($classBonus) $twins[] = 'the twin without the class bonus (the bonus clause must NOT happen)';
    if ($level > 0) $twins[] = "the twin one level below {$level}";
    if (isset($features['cost-reduction']) || isset($features['efficiency'])) $twins[] = 'a refusal at the reduced cost without the bonus: ->rejects(1, <answer>, <label>)';
    if (isset($features['additional-cost'])) $twins[] = 'a refusal when the additional cost cannot be paid';
    if (isset($features['imbue'])) $twins[] = 'imbued and not-imbued variants';
    if (preg_match('/\bup to\b|\bmay\b/i', $text)) $twins[] = 'the optional branch declined';
    $notes = [];
    if ($twins) $notes[] = "// Twins to write: " . implode('; ', $twins);
    foreach ($features as $k => [$tag, $hint]) if ($hint !== '') $notes[] = "// Hint ({$k}): {$hint}";
    foreach (gsUnmatchedClauses($id) as $u) $notes[] = "// UNMATCHED (new effect, no mechanic row): " . $u;
    foreach (gsExemplars($features, $id, $backlog) as $k => $ex) {
        $notes[] = "// Exemplar ({$k}): " . implode('; ', array_map(fn($e) => "{$e[0]} -> {$e[1]}", $ex));
    }
    return implode("\n", array_merge($notes ? $notes : [], $lines)) . "\n";
}

// ---- modes --------------------------------------------------------------------------------------------------------------

function gsUncovered(array $backlog, ?string $element, ?string $type, bool $exalted = false): array {
    $out = [];
    foreach ($backlog as $c) {
        if (!empty($c['existingFixtures'])) continue;
        $id = $c['cardId'];
        $parts = array_map('trim', explode(',', strval(CardElement($id))));
        $isEx = in_array('EXALTED', $parts, true);
        $parts = array_values(array_diff($parts, ['EXALTED']));
        if ($element !== null && ($parts[0] ?? '') !== $element) continue;
        if ($isEx !== $exalted) continue;
        if ($type !== null && !in_array($type, array_map('trim', explode(',', strval(CardType($id)))), true)) continue;
        $out[] = $id;
    }
    return $out;
}

if (isset($args['unmatched'])) {
    // New effects: printed clauses no mechanic row recognises, grouped by their shape (numbers and names blanked) so repeats stand out.
    $element = isset($args['element']) ? strtoupper($args['element']) : null;
    $ids = gsUncovered($backlog, $element, isset($args['type']) ? strtoupper($args['type']) : null, isset($args['exalted']));
    $groups = [];
    foreach ($ids as $id) foreach (gsUnmatchedClauses($id) as $clause) {
        $shape = strtolower(preg_replace(['/\d+/', '/\[[^\]]*\]/', '/[*_]+/', '/\s+/'], ['N', '', '', ' '], $clause));
        $groups[$shape]['cards'][CardName($id)] = true;
        $groups[$shape]['example'] ??= $clause;
    }
    uasort($groups, fn($a, $b) => count($b['cards']) <=> count($a['cards']));
    $promote = 0;
    foreach ($groups as $g) if (count($g['cards']) >= 3) $promote++;
    echo count($groups), " unmatched clause shapes over ", count($ids), " cards; {$promote} repeat on 3+ cards (promote those to a row in ga-fixtures/mechanics.php)\n";
    foreach (array_slice($groups, 0, (int)($args['limit'] ?? 40), true) as $g) printf("%3d  %-90s %s\n", count($g['cards']), mb_substr($g['example'], 0, 90), implode(', ', array_slice(array_keys($g['cards']), 0, 3)));
    exit(0);
}

if (isset($args['list'])) {
    $element = isset($args['element']) ? strtoupper($args['element']) : null;
    $ids = gsUncovered($backlog, $element, isset($args['type']) ? strtoupper($args['type']) : null, isset($args['exalted']));
    $groups = [];
    $unmatched = 0;
    foreach ($ids as $id) {
        $f = array_keys(gsFeatures(gsText($id)));
        $shape = implode('+', array_slice(array_values(array_filter($f, fn($k) => gsMechanicTable()[$k]['shape'] ?? true)), 0, 3)) ?: 'plain';
        $groups[strval(CardType($id)) . ' / ' . $shape][] = CardName($id);
        $unmatched += count(gsUnmatchedClauses($id)) > 0 ? 1 : 0;
    }
    uasort($groups, fn($a, $b) => count($b) <=> count($a));
    echo count($ids), " uncovered cards", $element ? " ({$element})" : '', "; {$unmatched} with a clause no mechanic row recognises (php DevTools/ga-scaffold.php --unmatched" . ($element ? " --element={$element}" : '') . ")\n";
    foreach ($groups as $shape => $cards) printf("%3d  %-48s %s\n", count($cards), $shape, implode(', ', array_slice($cards, 0, 6)) . (count($cards) > 6 ? ', ...' : ''));
    exit(0);
}

$ids = [];
foreach ($names as $n) {
    if (str_starts_with($n, '#')) { $ids[] = substr($n, 1); continue; }
    global $nameData;
    $hit = false;
    foreach ($nameData as $id => $nm) if (strcasecmp($nm, $n) === 0) { $ids[] = $id; $hit = true; }
    if (!$hit) fwrite(STDERR, "No card named '{$n}'\n");
}
if (isset($args['element']) || isset($args['type'])) {
    $more = gsUncovered($backlog, isset($args['element']) ? strtoupper($args['element']) : null, isset($args['type']) ? strtoupper($args['type']) : null, isset($args['exalted']));
    usort($more, fn($a, $b) => strcmp(CardName($a), CardName($b)));
    $ids = array_merge($ids, array_slice($more, 0, (int)($args['limit'] ?? 10)));
}
if (!$ids) { fwrite(STDERR, "usage: php DevTools/ga-scaffold.php \"Card\" ... | --element=WIND --type=ACTION --limit=10 | --list --element=WIND\n"); exit(1); }

$out = '';
foreach ($ids as $id) $out .= gsSkeleton($id, $backlog, $byId) . "\n";

if (isset($args['write']) && is_string($args['write'])) {
    $file = $args['write'];
    if (!is_file($file)) file_put_contents($file, "<?php\n/**\n * Scaffolded fixtures -- finish each skeleton (rename its todo- slug) before committing.\n */\n\n");
    file_put_contents($file, $out, FILE_APPEND);
    echo "Appended " . count($ids) . " skeleton(s) to {$file}\n";
}

if (isset($args['probe'])) {
    // Run each skeleton in a throwaway fixture file and print the autoplay prompt order. The probe slug avoids the todo- guard.
    foreach ($ids as $id) {
        $sk = gsSkeleton($id, $backlog, $byId, true);
        $slug = 'scaffold-probe-' . strtolower(preg_replace('/[^a-z0-9]/i', '', $id));
        $sk = preg_replace("/gaScenario\('todo-[^']*'\)/", "gaScenario('{$slug}')", $sk, 1);
        $sk = str_replace("->expectDqEmpty()\n", '', $sk);
        $probeFile = __DIR__ . '/ga-fixtures/zz-scaffold-probe-' . $slug . '.php';
        file_put_contents($probeFile, "<?php\n" . $sk);
        try {
            echo "######## " . CardName($id) . " (probe)\n";
            passthru('php ' . escapeshellarg(__DIR__ . '/create-prd-ability-fixtures.php') . ' --fixture=' . escapeshellarg($slug) . ' 2>&1 | grep -E "WARN|FAIL|Fatal|ERROR|Unknown|refused|rejected" | head -5');
            passthru('php ' . escapeshellarg(__DIR__ . '/ga-autoplay.php') . ' --test=' . escapeshellarg($slug) . ' 2>&1 | cut -c1-230');
        } finally {
            @unlink($probeFile);
            $dir = 'Tests/Integration/GrandArchiveSim/' . $slug;
            if (is_dir($dir)) { foreach (glob($dir . '/*') as $f) @unlink($f); @rmdir($dir); }
        }
    }
    exit(0);
}

if (!isset($args['write'])) echo $out;
