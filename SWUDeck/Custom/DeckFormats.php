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
        'padawan'  => '#f0d9a8', // amber / sand
        'open'     => '#f5b8b0', // light red / salmon
    ];
    $out = [];
    foreach (['premier', 'eternal', 'twinsuns', 'padawan', 'open'] as $id) {
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
// Publish SWUDeck's SET_NNN card universe where the shared SWUReprintGroup() and the rarity
// predicate look for it.
//
// Until 2026-08-04 this had to translate: $titleData was UUID-keyed, so SWUReprintGroup — which
// inverts CardIDOverride over $titleData's keys — came back empty on SWUDeck and reprint
// relationships ("is this card legal via a reprint?", "is this a reprint of a banned card?")
// silently did not work. The dictionaries are SET_NNN-keyed now, so this is a direct publish.
// Idempotent + cheap-once; call before any legality check.
function SWUDeckSetReprintUniverse() {
    if (isset($GLOBALS['SWUReprintUniverse']) && is_array($GLOBALS['SWUReprintUniverse'])) return;
    global $titleData, $rarityData;

    // Tokens (SET_T##) live in the dictionary — they need titles and art for stats and rendering —
    // but they are NOT deckbuildable, so they must not enter deck validation or format legality.
    // Without this, LAW_T01 (the Credit token) showed up in Padawan's rarity-legal allowlist.
    $isToken = fn($id) => (bool)preg_match('/_T\d{2}$/', (string)$id);

    $GLOBALS['SWUReprintUniverse'] = is_array($titleData)
        ? array_values(array_filter(array_keys($titleData), fn($id) => !$isToken($id)))
        : [];
    // AppCore's SWUCardRarity() reads this global. The rarity predicate fails CLOSED, so an empty
    // map would make the builder reject every card and no Padawan deck could be saved legal.
    $GLOBALS['SWURarityUniverse']  = is_array($rarityData)
        ? array_filter($rarityData, fn($id) => !$isToken($id), ARRAY_FILTER_USE_KEY)
        : [];
}

function SWUDeckClientFormatData($formatId) {
    SWUDeckSetReprintUniverse(); // so the banned-card reprint expansion below sees all printings
    $fmt = SWUGetFormat($formatId);
    if ($fmt === null) {
        return ['legalSets' => [], 'bannedIDs' => [], 'rarityLegalIDs' => null];
    }
    $legalSets = array_values(SWUFormatLegalSets($formatId));

    // Keys renamed from bannedUUIDs/rarityLegalUUIDs on 2026-08-04: these are SET_NNN ids now, and
    // a consumer left on the old name should fail loudly rather than silently compare key spaces
    // (in Filters3.js that would mean banned cards quietly rendering as legal).
    $bannedIDs = [];
    foreach ($fmt['banned'] as $bannedID) {
        foreach (SWUReprintGroup($bannedID) as $printing) {
            $bannedIDs[] = NormalizeCardID($printing);
        }
    }

    // Rarity-restricted formats (Padawan) need the browse panes filtered by rarity, which the
    // client cannot compute: cardReprintSets exposes reprint SET CODES only, not per-printing
    // rarity, so a client-side check would wrongly hide the SOR printing of Prepare for Takeoff.
    // Derive the allowlist here from the same predicate the validator uses — one source of truth,
    // and it cannot go stale. null for every unrestricted format, so their payload is unchanged.
    $rarityLegalIDs = null;
    if (!empty($fmt['legalRarities'])) {
        global $typeData;
        $rarityLegalIDs = [];
        foreach (array_keys($GLOBALS['SWURarityUniverse'] ?? []) as $setID) {
            // Leaders are exempt from the rarity rule ("Any Leader") — set check only. This mirrors
            // SWUCheckFormat, where the exemption is structural (leaders arrive in their own param).
            $ok = (($typeData[$setID] ?? '') === 'Leader')
                ? SWUCardHasLegalPrint($setID, $legalSets)
                : SWUCardHasLegalRarityPrint($setID, $legalSets, $fmt['legalRarities']);
            if ($ok) $rarityLegalIDs[] = $setID;
        }
        $rarityLegalIDs = array_values(array_unique($rarityLegalIDs));
    }

    return [
        'legalSets'      => $legalSets,
        'bannedIDs'      => array_values(array_unique($bannedIDs)),
        'rarityLegalIDs' => $rarityLegalIDs,
    ];
}

// SWUDeck-side wrapper for the Twin Suns leader-pairing rule (CR §12.2.1.a): the two leaders'
// starting sides can't combine Heroism + Villainy.
//
// Before 2026-08-04 this needed a translation layer: SWUDeck's $aspectData was UUID-keyed while
// _SWULeaderStartAlignment's start-side override table (e.g. Palpatine) and CardIDOverride's
// reprint map were SET_NNN-keyed, so the two had to be bridged per call. The dictionaries are
// SET_NNN-keyed now, so all three share one scheme and the bridge is gone.
function SWUDeckLeaderAlignment($cardID) {
    global $aspectData;
    // Since 2026-08-04 $aspectData and the override/reprint maps share one scheme, so no
    // translation is needed. A deck file still holds UUIDs, so normalise the stored id on the way
    // in — the generated façade does the same for every accessor.
    $id = function_exists('SWUNormalizeDictionaryKey') ? SWUNormalizeDictionaryKey($cardID) : $cardID;
    return _SWULeaderStartAlignment($id, [$id => $aspectData[$id] ?? '']);
}

// A leader's deployed Leader Unit side (action-pose art) is what should show wherever a leader
// is referenced visually (deck list, identity banner) — LeaderUnitLegacyIDByCardID() resolves to its own
// distinct crop id; cards with no unit side (non-leaders, double-leader-face flip cards) fall
// back to the leader's own uuid, i.e. its own regular crop.
// The interim SWUDeckArtKey() shim is GONE (2026-08-05). It mapped SET_NNN -> uuid because the art
// was still UUID-named; the shared corpus is SET_NNN-named, so that direction is now backwards.
// SWUCardImagePath()/SWUCardImageFsPath() in AppCore/SWU/CardImagePath.php are the single seam.
require_once __DIR__ . '/../../AppCore/SWU/CardImagePath.php';

function SWUDeckLeaderCropUrl($cardID) {
    // A leader's identity art is its deployed unit side, which the shared corpus names
    // "<SET_NNN>_back". Anything without one — non-leaders, and the double-leader-face flip cards —
    // falls back to its own crop, preserving the behaviour the LeaderUnitLegacyIDByCardID lookup used to give.
    if (file_exists(SWUCardImageFsPath($cardID . '_back', 'crop'))) {
        return SWUCardImagePath($cardID . '_back', 'crop');
    }
    return SWUCardImagePath($cardID, 'crop');
}

// Full front-side card art (same source the builder's Leaders/Leader1/Leader2/Bases browse
// panes use — see window.SWU_PANE_IMAGE_FOLDERS in InitialLayout.php) for a leader or base UUID.
function SWUDeckWebpUrl($cardID) {
    return SWUCardImagePath($cardID, 'card');
}
