<?php
// Fetches and normalizes unreleased-card data for the mock pipeline.
// Pure functions + two HTTP calls; writes nothing. See
// docs/superpowers/specs/2026-07-29-swusim-mock-card-builder-design.md.
//
// Audited against 18 released ASH cards x 8 fields versus our official dictionaries (all 5 card
// types). Everything matches except two inherent SOURCE differences, which the tool's human
// review step is there to catch:
//   • Subtitle capitalization can differ ("Victory Is Mine" here vs "Victory is Mine" official).
//   • Aspect ORDER can differ (ASH_001 "Command,Vigilance" here vs "Vigilance,Command" official).
//     Aspect handling treats the list as a set, so order carries no meaning — but don't be
//     surprised by the diff when re-auditing.

const SWU_PREVIEW_API   = 'https://swudb.com/api/card';
const SWU_PREVIEW_IMAGE = 'https://swudb.com/cdn-cgi/image/quality=95/images';

// Enum maps — verified against cards present in BOTH the preview source and our official
// dictionaries (see preview_enum_audit below and the ASH cross-check in the test task).
function SWUPreviewCardTypes(): array {
    return [0 => 'Leader', 1 => 'Base', 2 => 'Unit', 3 => 'Event', 4 => 'Upgrade'];
}
function SWUPreviewArenas(): array {
    return [0 => 'Ground', 1 => 'Space'];
}
// NOTE the order: 1 is Aggression and 2 is Command, NOT the other way round. Verified against
// the official dictionaries (ASH_023 Ancient Henge = Aggression, ASH_010 Bo-Katan = Command);
// guessing the intuitive order silently mislabels every mono-Command/Aggression card.
function SWUPreviewAspects(): array {
    return [1 => 'Aggression', 2 => 'Command', 3 => 'Cunning', 4 => 'Vigilance',
            5 => 'Heroism', 6 => 'Villainy'];
}
function SWUPreviewRarities(): array {
    return [1 => 'Common', 2 => 'Uncommon', 3 => 'Rare', 4 => 'Legendary', 5 => 'Special'];
}

// Sets whose card numbers are TWO digits ("TS26_09"), not three — padding to 3 silently returns an
// EMPTY record for these sets. Delegates to the mock pipeline's canonical list.
require_once __DIR__ . '/MockCardMerge.php';
function SWUPreviewDoubleDigitSets(): array {
    return SWUSimDoubleDigitSets();
}

// Zero-pad a card number to the width its set uses.
function SWUPreviewPadNumber(string $set, string $number): string {
    $width = in_array(strtoupper($set), SWUPreviewDoubleDigitSets(), true) ? 2 : 3;
    return str_pad(ltrim($number, '0') === '' ? '0' : ltrim($number, '0'), $width, '0', STR_PAD_LEFT);
}

// "https://swudb.com/card/HMW/004" | "HMW/4" -> ['set' => 'HMW', 'number' => '004']
function SWUPreviewParseLink(string $link): ?array {
    if (!preg_match('#([A-Z0-9]{2,5})\s*/\s*(\d{1,3})\s*/?$#i', trim($link), $m)) return null;
    $set = strtoupper($m[1]);
    return ['set' => $set, 'number' => SWUPreviewPadNumber($set, $m[2])];
}

function _SWUPreviewPost(string $endpoint, array $payload): ?array {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, SWU_PREVIEW_API . $endpoint);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    $body = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $code !== 200) return null;
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

// One card's full record. 'language' is REQUIRED — omitting it returns HTTP 400. The number is
// padded to its SET's width: TS26 is 2-digit, so a 3-padded "009" returns an empty record.
function SWUPreviewFetchCard(string $set, string $number): ?array {
    return _SWUPreviewPost('/getPrintingInfo', [
        'expansionAbbreviation' => strtoupper($set),
        'cardNumber' => SWUPreviewPadNumber($set, $number),
        'isFoil' => false, 'language' => 'en', 'stamp' => null,
    ]);
}

// Every previewed printing in a set. Only Normal printings are returned — the Hyperspace and
// Prestige groups are alternate art of the same card, never separate cards.
function SWUPreviewFetchSetList(string $set): array {
    $res = _SWUPreviewPost('/getSetInfo', [
        'expansionAbbreviation' => strtoupper($set),
        'language' => 'en', 'pageNumber' => 1, 'pageSize' => 500,
    ]);
    if ($res === null) return [];
    $out = [];
    foreach (($res['printingGroups'] ?? []) as $group) {
        if (($group['header'] ?? '') !== 'Normal') continue;
        foreach (($group['printings'] ?? []) as $p) {
            $out[] = [
                'cardNumber'  => (string)($p['cardNumber'] ?? ''),
                'cardName'    => (string)($p['cardName'] ?? ''),
                'variantType' => 1,
            ];
        }
    }
    return $out;
}

// Emit each \x01-delimited cost token bare when it already sits inside a [...] cost list, or
// wrapped in its own brackets when it stands alone in prose. Matches both forms the official
// dictionaries use: "Action [1 resource, Exhaust]" vs "It costs [5 resources] less."
function _SWUPreviewResolveCostTokens(string $t): string {
    $out = '';
    $depth = 0;
    $parts = explode("\x01", $t);
    for ($i = 0; $i < count($parts); $i++) {
        if ($i % 2 === 1) {                     // odd parts are the tokens themselves
            $out .= $depth > 0 ? $parts[$i] : '[' . $parts[$i] . ']';
            continue;
        }
        $depth += substr_count($parts[$i], '[') - substr_count($parts[$i], ']');
        if ($depth < 0) $depth = 0;
        $out .= $parts[$i];
    }
    return $out;
}

// Source markup -> the plain text with real newlines that the dictionaries store.
function SWUPreviewNormalizeText(string $raw): string {
    if (trim($raw) === '') return '';
    $t = $raw;
    // Epic-action paragraphs carry no literal label; our dictionaries store one.
    $t = str_replace('{p-epic-action}', '{p}Epic Action: ', $t);
    // Paragraphs -> newline separated.
    $t = preg_replace('/\{\/p[^}]*\}\s*/', "\n", $t);
    $t = preg_replace('/\{p[^}]*\}/', '', $t);
    // Emphasis wrappers leave their contents behind.
    $t = preg_replace('/\{\/?(b|i|u|em|strong)\}/', '', $t);
    $t = preg_replace('/\{keyword\}(.*?)\{\/keyword\}/s', '$1', $t);
    // Cost icons ({R1} resources, {T} exhaust) are BRACKET-CONTEXT SENSITIVE in the real
    // dictionaries: bare words inside an existing cost list ("Action [1 resource, Exhaust]"), but
    // bracket-wrapped when they stand alone in prose ("It costs [5 resources] less"). Resolve
    // each occurrence against its bracket depth rather than guessing one form.
    $t = preg_replace_callback('/\{R(\d+)\}/', function ($m) {
        $n = intval($m[1]);
        return "\x01" . $n . ' resource' . ($n === 1 ? '' : 's') . "\x01";
    }, $t);
    $t = str_replace('{T}', "\x01Exhaust\x01", $t);
    $t = _SWUPreviewResolveCostTokens($t);
    // Remaining icon tags become their word: {vehicle} -> Vehicle.
    $t = preg_replace_callback('/\{([a-z]+)\}/i', function ($m) {
        return ucfirst(strtolower($m[1]));
    }, $t);
    // Collapse whitespace the tag removal left behind.
    $t = preg_replace('/[ \t]+/', ' ', $t);
    $t = preg_replace('/ *\n */', "\n", $t);
    return trim($t);
}

// Traits, cleaned. The source is inconsistent: elements can carry leading spaces (ASH_240 comes
// back as ['Mandalorian', ' Trooper']) and occasionally arrive comma-joined in one string, which
// would produce a trait literally named " Trooper" that no trait check ever matches.
function _SWUPreviewTraitList($traits): array {
    $out = [];
    foreach ((array)$traits as $t) {
        foreach (explode(',', (string)$t) as $piece) {
            $piece = trim($piece);
            if ($piece !== '') $out[] = $piece;
        }
    }
    return array_values(array_unique($out));
}

// This printing's own row inside alternativePrintings (isCurrent), which carries the rarity the
// top-level field omits on some products.
function _SWUPreviewCurrentPrinting(array $rec): array {
    foreach (($rec['alternativePrintings'] ?? []) as $p) {
        if (!empty($p['isCurrent'])) return $p;
    }
    return [];
}

// A printing from an EARLIER expansion means this is a reprint; fold onto the earliest printing.
function SWUPreviewClassify(array $rec): array {
    $self = _SWUPreviewCurrentPrinting($rec);
    $selfSet = strtoupper((string)($self['expansionAbbreviation'] ?? ''));
    $allSets = require __DIR__ . '/../../AppCore/SWU/AllSets.php';
    $selfOrder = $allSets[$selfSet] ?? PHP_INT_MAX;

    $earliest = null;
    $earliestOrder = PHP_INT_MAX;
    foreach (($rec['alternativePrintings'] ?? []) as $p) {
        $pSet = strtoupper((string)($p['expansionAbbreviation'] ?? ''));
        $pNum = SWUPreviewPadNumber($pSet, (string)($p['cardNumber'] ?? ''));
        if ($pSet === '' || $pSet === $selfSet) continue;
        $order = $allSets[$pSet] ?? PHP_INT_MAX;
        if ($order === PHP_INT_MAX) continue;   // promo / OP sets are not canonical printings
        if ($order < $earliestOrder) { $earliestOrder = $order; $earliest = $pSet . '_' . $pNum; }
    }
    if ($earliest !== null && $earliestOrder < $selfOrder) {
        return ['kind' => 'reprint', 'canonical' => $earliest];
    }
    return ['kind' => 'new', 'canonical' => null];
}

// Source record -> a flat mock entry (SWUSim/Custom/CardMocks.php shape).
function SWUPreviewToMock(array $rec): array {
    $types    = SWUPreviewCardTypes();
    $arenas   = SWUPreviewArenas();
    $aspects  = SWUPreviewAspects();
    $rarities = SWUPreviewRarities();

    $self      = _SWUPreviewCurrentPrinting($rec);
    $selfSet   = strtoupper((string)($self['expansionAbbreviation'] ?? ''));
    $rarityInt = $rec['rarity'] ?? ($self['rarity'] ?? null);

    $aspectNames = [];
    foreach (($rec['aspects'] ?? []) as $a) {
        if (isset($aspects[$a])) $aspectNames[] = $aspects[$a];
    }

    $front = (string)($rec['frontImagePath'] ?? '');
    $back  = (string)($rec['backImagePath'] ?? '');

    // The front text blob holds the card's own ability AND its epic action. Normalization labels
    // the epic-action paragraph, so split on that label to fill the two fields separately.
    $frontText = SWUPreviewNormalizeText((string)($rec['frontAbilityText'] ?? ''));
    $epic = '';
    if (($pos = strpos($frontText, 'Epic Action: ')) !== false) {
        $epic = trim(substr($frontText, $pos));
        $frontText = trim(substr($frontText, 0, $pos));
    }

    $arenaInt = $rec['arena'] ?? null;

    return [
        'title'    => (string)($rec['cardName'] ?? ''),
        'subtitle' => (string)($rec['title'] ?? ''),
        'type'     => $types[$rec['cardType'] ?? -1] ?? (string)($rec['cardTypeDescription'] ?? ''),
        'arena'    => $arenaInt === null ? '' : ($arenas[$arenaInt] ?? (string)($rec['arenaDescription'] ?? '')),
        'cost'     => isset($rec['cost']) ? intval($rec['cost']) : null,
        'power'    => isset($rec['power']) ? intval($rec['power']) : null,
        'hp'       => isset($rec['hitPoints']) ? intval($rec['hitPoints']) : null,
        'upgradePower' => isset($rec['powerBonus']) ? intval($rec['powerBonus']) : null,
        'upgradeHp'    => isset($rec['hitPointBonus']) ? intval($rec['hitPointBonus']) : null,
        'aspect'   => $aspectNames,
        // Bases keep their location trait ("Seatos", "Tatooine"). The OFFICIAL API omits traits for
        // every base, but CardTraitSupplement.php now backfills them, so a mocked base does NOT
        // lose its trait on release day — the supplement supplies the same value.
        'trait'    => _SWUPreviewTraitList($rec['traits'] ?? []),
        'text'     => $frontText,
        'epicAction' => $epic,
        'deployText' => SWUPreviewNormalizeText((string)($rec['backAbilityText'] ?? '')),
        'unique'   => (bool)($rec['isUnique'] ?? false),
        'rarity'   => $rarityInt !== null ? ($rarities[$rarityInt] ?? '') : '',
        'set'      => $selfSet,
        'imageUrl'     => $front !== '' ? SWU_PREVIEW_IMAGE . $front : '',
        'imageUrlBack' => $back  !== '' ? SWU_PREVIEW_IMAGE . $back  : '',
        // The source carries the LEADER side's name and traits alongside the DEPLOYED side's
        // arena/stats — it has no deployed-side name or trait line. The human supplies these.
        'leaderUnitTitle' => '', 'leaderUnitSubtitle' => '', 'leaderUnitTrait' => [],
        'leaderUnitArena' => '', 'leaderUnitType' => '',
    ];
}
