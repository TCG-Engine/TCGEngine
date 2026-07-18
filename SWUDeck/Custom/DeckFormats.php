<?php
// SWUDeck's buildable-format catalog: the subset of AppCore/SWU/Formats.php's config-driven
// formats that a player can tag a decklist with, plus the chip color shown on the deck list.
// Deliberately NOT derived from SWUListFormats() — that includes SWUSim solo modes
// (goldfish/hotseat) and would silently pick up any future format with no color assigned here.

include_once __DIR__ . '/../../AppCore/SWU/Formats.php'; // SWUGetFormat
include_once __DIR__ . '/../../AppCore/SWU/DeckValidation.php'; // SWUReprintGroup
include_once __DIR__ . '/CardIdentifiers.php';                  // NormalizeCardID

function SWUDeckBuildableFormats() {
    static $colors = [
        'premier'  => '#a8d8ff', // light blue
        'eternal'  => '#d3b8f0', // light purple
        'twinsuns' => '#a8e6c9', // seafoam green
        'open'     => '#f5b8b0', // light red / salmon
    ];
    $out = [];
    foreach (['premier', 'eternal', 'twinsuns', 'open'] as $id) {
        $f = SWUGetFormat($id);
        if ($f === null) continue; // defensive: format removed from config
        $out[$id] = [
            'displayName' => $f['displayName'],
            'color'       => $colors[$id],
        ];
    }
    return $out;
}

function SWUDeckFormatColor($formatId) {
    $catalog = SWUDeckBuildableFormats();
    return $catalog[$formatId]['color'] ?? '#cccccc';
}

function SWUDeckFormatDisplayName($formatId) {
    $catalog = SWUDeckBuildableFormats();
    return $catalog[$formatId]['displayName'] ?? $formatId;
}

// Client-facing legality data for a format: the legal SET_NNN prefixes, and every SWUDeck
// numeric UUID (across all reprints of every banned card) that should be treated as banned.
// Callers must have card dictionaries already loaded (UUIDLookup / $titleData) — true for
// SWUDeck/InitialLayout.php's context (runs after Initialize.php).
// The shared SWUReprintGroup() inverts CardIDOverride (SET_NNN → earliest SET_NNN) over a card-ID
// universe it reads from $titleData's keys. That works on SET_NNN-keyed sites (SWUSim) but NOT
// SWUDeck, whose $titleData is UUID-keyed — so reprint relationships (and thus "is this card legal
// via a reprint?" / "is this a reprint of a banned card?") come back empty. Publish SWUDeck's
// SET_NNN universe (every card's printing id, via CardIDLookup over the UUID keys) where
// SWUReprintGroup looks for it. Idempotent + cheap-once; call before any legality check.
function SWUDeckSetReprintUniverse() {
    if (isset($GLOBALS['SWUReprintUniverse']) && is_array($GLOBALS['SWUReprintUniverse'])) return;
    global $titleData;
    $sets = [];
    if (is_array($titleData)) {
        foreach (array_keys($titleData) as $uuid) {
            $s = CardIDLookup($uuid);
            if ($s !== null && $s !== '') $sets[$s] = true;
        }
    }
    $GLOBALS['SWUReprintUniverse'] = array_keys($sets);
}

function SWUDeckClientFormatData($formatId) {
    SWUDeckSetReprintUniverse(); // so the banned-card reprint expansion below sees all printings
    $fmt = SWUGetFormat($formatId);
    if ($fmt === null) {
        return ['legalSets' => [], 'bannedUUIDs' => []];
    }
    $legalSets = array_values(SWUFormatLegalSets($formatId));

    $bannedUUIDs = [];
    foreach ($fmt['banned'] as $bannedID) {
        foreach (SWUReprintGroup($bannedID) as $printing) {
            $uuid = UUIDLookup(NormalizeCardID($printing));
            if ($uuid) $bannedUUIDs[] = $uuid;
        }
    }

    return [
        'legalSets'   => $legalSets,
        'bannedUUIDs' => array_values(array_unique($bannedUUIDs)),
    ];
}

// SWUDeck-side wrapper for the Twin Suns leader-pairing rule (CR §12.2.1.a): the two leaders'
// starting sides can't combine Heroism + Villainy. $uuid is SWUDeck's native card ID scheme
// (matches Leader->CardID / keyIndicator1 etc.) — this is NOT the SET_NNN scheme
// _SWULeaderStartAlignment's start-side override table (e.g. Palpatine) and CardIDOverride's
// reprint map are keyed by, and it's also NOT the scheme SWUDeck's own $aspectData is keyed by
// (that one's UUID-keyed, confirmed via GeneratedCardDictionaries.php). So: resolve the UUID to
// its SET_NNN form via CardIDLookup() for the override/reprint lookups, and build a one-entry
// aspect map (keyed by that same SET_NNN id) from $aspectData's UUID-keyed value, rather than
// re-keying the whole (huge) $aspectData array.
function SWUDeckLeaderAlignment($uuid) {
    global $aspectData;
    $setCardID = CardIDLookup($uuid) ?? $uuid;
    $aspectValue = $aspectData[$uuid] ?? '';
    return _SWULeaderStartAlignment($setCardID, [$setCardID => $aspectValue]);
}

// A leader's deployed Leader Unit side (action-pose art) is what should show wherever a leader
// is referenced visually (deck list, identity banner) — LeaderUnitByUUID() resolves to its own
// distinct crop id; cards with no unit side (non-leaders, double-leader-face flip cards) fall
// back to the leader's own uuid, i.e. its own regular crop.
function SWUDeckLeaderCropUrl($uuid) {
    $resolvedId = function_exists('LeaderUnitByUUID') ? (LeaderUnitByUUID($uuid) ?? $uuid) : $uuid;
    return '/TCGEngine/SWUDeck/crops/' . $resolvedId . '_cropped.png';
}

// Full front-side card art (same source the builder's Leaders/Leader1/Leader2/Bases browse
// panes use — see window.SWU_PANE_IMAGE_FOLDERS in InitialLayout.php) for a leader or base UUID.
function SWUDeckWebpUrl($uuid) {
    return '/TCGEngine/SWUDeck/WebpImages/' . $uuid . '.webp';
}
