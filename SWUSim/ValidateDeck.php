<?php
header('Content-Type: application/json');
error_reporting(0);

include_once '../Core/HTTPLibraries.php';
include_once './Custom/DeckImport.php';

$deckLink = trim(TryPost('deckLink', ''));

if ($deckLink === '') {
    echo json_encode(['success' => false, 'message' => 'No deck link provided.']);
    exit;
}

$result = SWUResolveDeckInput($deckLink);

if (!$result['success']) {
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit;
}

$leaderID   = $result['leader'];
$baseID     = $result['base'];
$mainDeck   = $result['mainDeck'];   // expanded: one entry per copy
$sideboard  = $result['sideboard'];  // expanded: one entry per copy
$deckCount  = count($mainDeck);
$unresolved = $result['unresolved'];

$leaderName = $leaderID ? (CardTitle($leaderID) ?: $leaderID) : '';
$baseName   = $baseID   ? (CardTitle($baseID)   ?: $baseID)   : '';

// ── Premier format validation ─────────────────────────────────────────────────
$formatErrors = SWUCheckPremierFormat($leaderID, $baseID, $mainDeck, $sideboard);

// ── Import warnings (unresolved cards, missing slots) ────────────────────────
$warnings = [];
if (!$leaderID)      $warnings[] = 'No leader found.';
if (!$baseID)        $warnings[] = 'No base found.';
if ($deckCount === 0) $warnings[] = 'No deck cards found.';
if (!empty($unresolved)) {
    $sample = array_slice($unresolved, 0, 3);
    $extra  = count($unresolved) > 3 ? ' +' . (count($unresolved) - 3) . ' more' : '';
    $warnings[] = count($unresolved) . ' card(s) not recognized: ' . implode(', ', $sample) . $extra;
}

echo json_encode([
    'success'      => true,
    'leaderID'     => $leaderID,
    'leaderName'   => $leaderName,
    'baseID'       => $baseID,
    'baseName'     => $baseName,
    'deckCount'    => $deckCount,
    'sideboardCount' => count($sideboard),
    'warnings'     => $warnings,      // non-blocking import issues
    'formatErrors' => $formatErrors,  // blocking Premier rule violations
]);

// ─────────────────────────────────────────────────────────────────────────────

function SWUCheckPremierFormat($leader, $base, array $mainDeck, array $sideboard) {
    $errors = [];

    $legalSets = ['JTL', 'LOF', 'SEC', 'IBH', 'LAW', 'ASH'];

    // Cards allowed to exceed the 3-copy limit
    // key = card ID, value = max copies (PHP_INT_MAX = unlimited)
    $copyExceptions = [
        'JTL_256' => 15,  // Vulture Droid
    ];

    // Base cards that shift the minimum deck size
    $deckSizeModifiers = [
        'JTL_024' => +10,  // Data Vault  → min 60
        'JTL_025' => -5,   // Thermal Oscillator → min 45
    ];

    $minDeck = 50;
    $getSet  = function ($id) { return strtoupper(explode('_', $id)[0] ?? ''); };

    // 1. Leader set legality
    if ($leader && !in_array($getSet($leader), $legalSets, true)) {
        $errors[] = "Leader $leader ($leader) is not in a legal Premier set.";
    }

    // 2. Base set legality + deck-size modifier
    if ($base) {
        if (!in_array($getSet($base), $legalSets, true)) {
            $errors[] = "Base $base is not in a legal Premier set.";
        }
        if (isset($deckSizeModifiers[$base])) {
            $minDeck += $deckSizeModifiers[$base];
        }
    }

    // 3. Deck card legality + copy-count limits
    $cardCounts  = array_count_values($mainDeck);
    $illegalCards = [];
    $overLimit    = [];

    foreach ($cardCounts as $cardID => $count) {
        if (!in_array($getSet($cardID), $legalSets, true)) {
            $illegalCards[] = $cardID;
        }
        $limit = $copyExceptions[$cardID] ?? 3;
        if ($count > $limit) {
            $overLimit[] = "$cardID ($count copies, max $limit)";
        }
    }

    if (!empty($illegalCards)) {
        $shown = array_slice($illegalCards, 0, 5);
        $more  = count($illegalCards) > 5 ? ' +' . (count($illegalCards) - 5) . ' more' : '';
        $errors[] = 'Cards not in a legal Premier set: ' . implode(', ', $shown) . $more;
    }
    if (!empty($overLimit)) {
        $errors[] = 'Over the 3-copy limit: ' . implode('; ', $overLimit);
    }

    // 4. Minimum deck size
    $deckSize = count($mainDeck);
    if ($deckSize < $minDeck) {
        $note = ($minDeck !== 50) ? " (modified to $minDeck by base)" : '';
        $errors[] = "Deck has $deckSize cards; Premier minimum is $minDeck$note.";
    }

    // 5. Sideboard maximum
    if (count($sideboard) > 10) {
        $errors[] = 'Sideboard has ' . count($sideboard) . ' cards; maximum is 10.';
    }

    return $errors;
}
