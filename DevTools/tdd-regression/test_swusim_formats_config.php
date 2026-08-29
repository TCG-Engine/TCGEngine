<?php
// http://localhost:3400/TCGEngine/DevTools/tdd-regression/test_swusim_formats_config.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../AppCore/SWU/Formats.php';

$checks = [];

// Premier baseline unchanged.
$premier = SWUGetFormat('premier');
$checks['premier sets'] = $premier['legalSets'] === ['JTL','LOF','SEC','IBH','LAW','ASH'];
$checks['premier copyEx'] = ($premier['copyExceptions']['JTL_256'] ?? null) === 15;

// '*' resolves to all printed sets (includes SOR and ASH).
$eternalSets = SWUFormatLegalSets('eternal');
$checks['eternal has SOR'] = in_array('SOR', $eternalSets, true);
$checks['eternal has ASH'] = in_array('ASH', $eternalSets, true);
// Open resolves '*' to every set in AllSets.php, which INCLUDES the preview sets (HMW, IC27);
// Eternal is the released-only list. The divergence is intentional — see AppCore/SWU/PreviewSets.php.
$openSets = SWUFormatLegalSets('open');
$checks['open is a superset of eternal'] = empty(array_diff($eternalSets, $openSets));
$checks['open includes preview sets eternal excludes'] = in_array('HMW', $openSets, true)
                                                      && !in_array('HMW', $eternalSets, true);

// Open has no bans; defaults fill missing keys.
$open = SWUGetFormat('open');
$checks['open no bans'] = $open['banned'] === [];
$checks['open default modifiers'] = $open['deckSizeModifiers'] === [];

// Global card-intrinsic rules (JTL_256 copy-exception, JTL_024/025 deck-size mods) apply to EVERY
// format except Open, which ignores them.
$eternal = SWUGetFormat('eternal');
$twin    = SWUGetFormat('twinsuns');
$checks['premier has global deckSize'] = ($premier['deckSizeModifiers']['JTL_024'] ?? null) === 10;
$checks['eternal gains global copyEx'] = ($eternal['copyExceptions']['JTL_256'] ?? null) === 15;
$checks['eternal gains global deckSize'] = ($eternal['deckSizeModifiers']['JTL_024'] ?? null) === 10;
$checks['twinsuns gains global rules'] = ($twin['copyExceptions']['JTL_256'] ?? null) === 15
                                      && ($twin['deckSizeModifiers']['JTL_025'] ?? null) === -5;
$checks['open ignores global copyEx'] = $open['copyExceptions'] === [];

// Disable-not-delete: preview is disabled by default.
$listed = SWUListFormats();
// 'preview' is ENABLED while an HMW preview window is open, so it is listed. The durable guarantee
// is disable-not-delete: a disabled format stays RESOLVABLE for in-flight matches even when hidden.
$checks['preview listed while enabled'] = array_key_exists('preview', $listed)
                                       === (SWUGetFormat('preview')['enabled'] === true);
$checks['preview still resolvable'] = SWUGetFormat('preview') !== null;
$checks['enabled formats listed'] = array_key_exists('premier', $listed)
                                 && array_key_exists('eternal', $listed)
                                 && array_key_exists('open', $listed);

// Unknown format is null; queue types resolve.
$checks['unknown format null'] = SWUGetFormat('nope') === null;
$checks['bo3 bestOf 3'] = (SWUGetQueueType('bo3')['bestOf'] ?? null) === 3;
$checks['bo3 sideboard on'] = (SWUGetQueueType('bo3')['sideboard'] ?? null) === true;
$checks['bo1 sideboard off'] = (SWUGetQueueType('bo1')['sideboard'] ?? null) === false;

// ── PADAWAN ──────────────────────────────────────────────────────────────────
$padawan = SWUGetFormat('padawan');
$checks['padawan resolves']       = $padawan !== null;
$checks['padawan is enabled']     = $padawan['enabled'] === true;
$checks['padawan display name']   = $padawan['displayName'] === 'Padawan';
$checks['padawan rarities']       = $padawan['legalRarities'] === ['Common'];
// Eternal pool VERBATIM — IBH stays in, so its 2 Special leaders remain legal (leaders are exempt
// from the rarity rule); IBH's 104 non-leader cards and 2 bases are all Special, so the rarity rule
// alone enforces "no Intro Battle Hoth cards".
$checks['padawan sets == eternal'] = SWUFormatLegalSets('padawan') === SWUFormatLegalSets('eternal');
$checks['padawan has IBH']         = in_array('IBH', SWUFormatLegalSets('padawan'), true);
$checks['padawan no bans']         = $padawan['banned'] === [];
$checks['padawan minDeck 50']      = $padawan['minDeck'] === 50;
$checks['padawan maxCopies 3']     = $padawan['maxCopies'] === 3;
$checks['padawan 1 leader']        = $padawan['leaderCount'] === 1;
// Vulture Droid is Common, so its 15-copy exception MUST survive into Padawan.
$checks['padawan keeps vulture exception'] = ($padawan['copyExceptions']['JTL_256'] ?? null) === 15;

$padawanPreview = SWUGetFormat('padawan-preview');
$checks['padawan-preview resolves']  = $padawanPreview !== null;
$checks['padawan-preview rarities']  = $padawanPreview['legalRarities'] === ['Common'];
$checks['padawan-preview adds HMW']  = in_array('HMW', SWUFormatLegalSets('padawan-preview'), true);
$checks['padawan-preview keeps eternal'] =
    empty(array_diff(SWUFormatLegalSets('eternal'), SWUFormatLegalSets('padawan-preview')));

// ── NO REGRESSION: every pre-existing format stays rarity-unrestricted ───────
foreach (['premier','eternal','twinsuns','open','goldfish','hotseat','preview','twinsuns-preview'] as $f) {
    $checks["$f has no rarity restriction"] = SWUGetFormat($f)['legalRarities'] === null;
}

$checks['padawan listed'] = array_key_exists('padawan', SWUListFormats());

// ── A PREVIEW FORMAT IS ITS BASE FORMAT WITH A WIDER CARD POOL — NOTHING ELSE ─────────────────
// Every check above is a per-format spot check, and that is exactly how `twinsuns-preview` shipped
// seating TWO players: it copied Twin Suns' deckbuilding (2 leaders, 80 cards, singleton) but simply
// omitted minPlayers/maxPlayers, and SWUFormatSeatRange defaults those to 2. The format was live in
// the SWUSim dropdown, so "Twin Suns Preview" was a 2-player queue demanding a Twin Suns deck —
// SWUFormatIsRoomFormat is max > 2, so it did not even use the room flow. No spot check could catch
// an ABSENT key; only comparing the whole pair can.
//
// So this is a FAMILY check, derived rather than enumerated: a preview format must differ from its
// base on legalSets alone. Add a preview format and forget a key, and this fails by construction.
$previewBases = [
    'preview'          => 'premier',    // "Premier Preview" — the one whose name omits the suffix
    'eternal-preview'  => 'eternal',
    'padawan-preview'  => 'padawan',
    'twinsuns-preview' => 'twinsuns',
];
// Keys that define HOW the format plays. legalSets is deliberately excluded — it is the entire
// difference. displayName/enabled are presentation. Everything else must match the base exactly,
// INCLUDING when both are absent, which is why ?? null is compared rather than isset() tested.
$mirrored = ['minPlayers','maxPlayers','leaderCount','minDeck','maxCopies','legalRarities',
             'teams','uniqueTeamLeaders','banned','ignoreGlobalCardRules',
             'copyExceptions','deckSizeModifiers'];
foreach ($previewBases as $pf => $bf) {
    $P = SWUGetFormat($pf); $B = SWUGetFormat($bf);
    $checks["$pf resolves"]      = $P !== null;
    $checks["$bf resolves"]      = $B !== null;
    if ($P === null || $B === null) continue;
    foreach ($mirrored as $k) {
        $checks["$pf mirrors $bf: $k"] = ($P[$k] ?? null) === ($B[$k] ?? null);
    }
    // The seat range is asserted through the FUNCTION the lobby actually calls, not the raw keys.
    // A default that silently fills a missing key is the failure mode being guarded, so reading the
    // array directly would re-introduce the blind spot the raw-key check above already covers.
    $checks["$pf seats like $bf"] = SWUFormatSeatRange($pf) === SWUFormatSeatRange($bf);
    $checks["$pf is a room format iff $bf is"] =
        SWUFormatIsRoomFormat($pf) === SWUFormatIsRoomFormat($bf);
    // The point of existing: the preview pool must add the preview sets on top of the base's pool.
    $checks["$pf pool is a superset of $bf"] =
        empty(array_diff(SWUFormatLegalSets($bf), SWUFormatLegalSets($pf)));
    $checks["$pf is flagged preview"] = SWUFormatIsPreview($pf) === true;
    $checks["$bf is NOT flagged preview"] = SWUFormatIsPreview($bf) === false;
}
// A map that outlives its entries rots into a check nobody is running. Every enabled preview format
// must appear above — otherwise a newly added one is silently unguarded, which is the whole bug.
foreach (array_keys(SWUListFormats()) as $f) {
    if (!SWUFormatIsPreview($f)) continue;
    $checks["preview format '$f' is covered by \$previewBases"] = isset($previewBases[$f]);
}
$checks['no teamsuns-preview (deliberate — Team Suns previews are not offered)'] =
    SWUGetFormat('teamsuns-preview') === null;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
// Exit code so a runner can gate on this without grepping for the word PASS.
exit(empty($fails) ? 0 : 1);
