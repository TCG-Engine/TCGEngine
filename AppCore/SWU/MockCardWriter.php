<?php
// Reads/writes the tracked mock registry and the reprint override table. Used by
// zzPreviewTool.php. Rewrites the whole registry via var_export so the output stays
// diff-friendly and safely quoted (apostrophes, newlines, unicode).

require_once __DIR__ . '/MockCardMerge.php';

const SWU_MOCK_FILE_HEADER = <<<'PHP'
<?php
// Mock (preview) card definitions — TRACKED SOURCE, merged into the generated card
// dictionaries at generation time by AppCore/SWU/MockCardMerge.php.
//
// Keyed by the ordinary SET_NNN CardID. Card logic, tests and decks use that ID; only the
// IMAGE files carry a "mock_" prefix. Official data always wins: once a card exists in the
// real API response its mock entry is ignored and reported as superseded.
//
// Managed by zzPreviewTool.php?rootName=SWUSim (dev only) — hand-editing is fine too.
// This file must stay inert at runtime: it returns data and registers nothing.
//
// SCAFFOLD-IGNORE: pure DATA, not a card implementation. Tools that infer "is this card implemented?"
// by grepping quoted CardIDs under SWUSim/Custom/ must skip this file, or every card listed here looks
// implemented and gets no stub / is dropped from gap reports. This marker lives in the WRITER's header
// constant on purpose — patching it into the file by hand is lost on the next mock write.
//
// Fields (all optional except title/type/set):
//   title, subtitle, type, arena, cost, power, hp, upgradePower, upgradeHp,
//   aspect[], trait[], text, epicAction, deployText, unique, rarity, set,
//   imageUrl, imageUrlBack,
//   leaderUnitTitle, leaderUnitSubtitle, leaderUnitTrait[], leaderUnitArena, leaderUnitType
PHP;

// SWUIsMockCardID() lives in MockCardMerge.php (required above) — it must be set-aware, since a
// flat 3-digit rule rejects every legitimate TS26 card.

function _SWURenderMockFile(array $entries): string {
    ksort($entries);
    return SWU_MOCK_FILE_HEADER . "\nreturn " . var_export($entries, true) . ";\n";
}

// Create or replace one entry. Returns true on success, false on a bad id or write failure.
function SWUWriteMockCard(string $cardID, array $mock, string $path = ''): bool {
    if ($path === '') $path = SWUMockCardsPath();
    if (!SWUIsMockCardID($cardID)) return false;
    $entries = SWULoadMockCards($path);
    $entries[$cardID] = $mock;
    return file_put_contents($path, _SWURenderMockFile($entries)) !== false;
}

// Remove one entry. False when it wasn't there.
function SWUDeleteMockCard(string $cardID, string $path = ''): bool {
    if ($path === '') $path = SWUMockCardsPath();
    $entries = SWULoadMockCards($path);
    if (!isset($entries[$cardID])) return false;
    unset($entries[$cardID]);
    return file_put_contents($path, _SWURenderMockFile($entries)) !== false;
}

// Insert a reprint mapping just before the switch's default arm, matching the file's existing
// `case "X": return "Y"; //Card Name` form. False when the mapping already exists (idempotent)
// or the file shape isn't recognized — never a partial write.
function SWUWriteReprintOverride(string $cardID, string $canonical, string $name, string $path = ''): bool {
    if ($path === '') $path = __DIR__ . '/../../AppCore/SWU/Overrides.php';
    if (!SWUIsMockCardID($cardID) || !SWUIsMockCardID($canonical)) return false;
    $src = @file_get_contents($path);
    if ($src === false) return false;
    if (strpos($src, 'case "' . $cardID . '":') !== false) return false;   // already mapped
    $needle = '    default: return $cardID;';
    if (strpos($src, $needle) === false) return false;
    $line = '    case "' . $cardID . '": return "' . $canonical . '"; //' . $name . "\n";
    $src = str_replace($needle, $line . $needle, $src);
    return file_put_contents($path, $src) !== false;
}
