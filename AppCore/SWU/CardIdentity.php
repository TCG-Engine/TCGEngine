<?php
// Card identity — the one place that decides what an incoming or stored identifier MEANS.
//
// SWU has two identifier shapes in circulation and will have for the foreseeable future:
//   SET_NNN  ('SOR_005', 'SOR_T02')  the internal identity, and the deck-JSON interchange format
//   FFG UID  ('2579145458')          the wire format Karabast and Petranaki send, and that
//                                    LoadDeck / CardMetaStatsAPI still emit by contract
//
// The UID is NOT going away — Stats/APIs.php documents it and our largest external consumer both
// sends and expects it. It is demoted to a wire format, translated at the boundary, and retained as
// a lookup table. This file is that boundary.
//
// ⚠ THIS IS PERMANENT RUNTIME CODE, not migration scaffolding. It lives here rather than under
// AppCore/SWU/migrations/ precisely so that deleting the migration directory after the cutover
// weekend cannot break stats ingress. AppCore/SWU/migrations/lib/IdentifierMap.php delegates to it.
//
// NO DATABASE ACCESS — everything is derived from the generated card dictionaries.
//
// Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §2

require_once __DIR__ . '/Overrides.php';   // CardIDOverride

// Class 2 — legitimate NON-card values that must survive verbatim, never translated, never dropped.
// baseID/opponentBaseID are polymorphic: Stats/APIs.php documents opposingBaseColor as a fallback
// "when absent or unrecognized", and the prod census found 258,367 rows holding colour names.
function SWUCardIdentityBaseColours(): array { return ['green', 'red', 'blue', 'yellow', 'colorless', 'colourless']; }
function SWUCardIdentitySentinels(): array   { return ['-1', '0', '1']; }

// Already an identity? SET_NNN, SET_NN, or SET_T## — the same rule the mock pipeline validates with.
function SWUCardIdentityIsSetNnn(string $v): bool
{
    return (bool)preg_match('/^[A-Z0-9]{2,5}_(T\d{2}|\d{2,3})$/', $v);
}

// Leader-unit LEGACY identifier => owning CardID, swept over the whole card pool.
//
// Exists because prod holds rows keyed by a two-sided leader's FLIPPED-side legacy id
// (ad86d54e97 => TWI_017 Chancellor Palpatine, 2,984 rows), splitting that leader's stats across two
// identities. Swept exhaustively rather than spot-checked: any two-sided leader can carry it.
//
// ⚠ Those legacy ids ORIGINATED as Strapi media-asset hashes but no longer name any file — the
// corpus is entirely SET_NNN-named (verified 2026-08-07: zero hash-named files across WebpImages/,
// concat/ and crops/). Never build an image path from one.
//
// ⚠ This map is LIVE INGRESS, not migration scaffolding — consistent with this file's header. It is
// the LAST rule SWUCardIdentityClassify() tries (after set-nnn and uuid), so a submission naming a
// two-sided leader by its flipped-side legacy id resolves here or falls to class 3 and is DROPPED.
// Re-keying the stored rows does NOT retire it: an external client can still send a legacy id, and
// nothing else rescues that.
//
// ⚠ The accessor is keyed by CardID. Resolving the loop variable through CardIDLookup() (which
// returns null for a SET_NNN) silently produced an EMPTY map and reclassified ad86d54e97 as
// unresolvable. Normalise, do not look up.
//
// The generated accessor was renamed LeaderUnitByUUID -> LeaderUnitLegacyIDByCardID on 2026-08-07.
// The fallback below exists for ONE failure mode: code deployed ahead of a dictionary regeneration,
// where only the old name is defined. Without it this function returns an empty array — silently,
// which is the exact way this code broke before. Drop the fallback once every environment has
// regenerated.
function SWUCardIdentityLeaderUnitMap(): array
{
    static $map = null;
    if ($map !== null) return $map;
    $map = [];
    $fn = function_exists('LeaderUnitLegacyIDByCardID') ? 'LeaderUnitLegacyIDByCardID'
        : (function_exists('LeaderUnitByUUID') ? 'LeaderUnitByUUID' : null);
    if ($fn === null) return $map;
    foreach (array_keys($GLOBALS['titleData'] ?? []) as $key) {
        $asset = $fn((string)$key);
        if (!is_string($asset) || $asset === '') continue;
        $cardID = function_exists('SWUNormalizeDictionaryKey')
            ? SWUNormalizeDictionaryKey((string)$key) : (string)$key;
        if ($cardID !== null && $cardID !== '') $map[$asset] = (string)$cardID;
    }
    return $map;
}

// Classify one identifier. THE function — ingress, the census and the migration map all call it, so
// they cannot disagree about what a value means.
//
// Returns ['class' => 1|2|3, 'to' => string|null, 'via' => string]:
//   class 1  a card we resolved      -> 'to' is the canonical SET_NNN to store
//   class 2  a known non-card value  -> 'to' is the ORIGINAL value, to be stored verbatim
//   class 3  unresolvable            -> 'to' is null; the caller drops the affected row and logs
//
// $poly must be true for a column that may legitimately hold a base colour.
//
// Class 2 is checked FIRST and is an explicit allowlist. An implicit "if it doesn't map, keep it"
// rule would let the mixed key space this whole effort exists to eliminate creep straight back in.
function SWUCardIdentityClassify(string $value, bool $poly = false): array
{
    if (trim($value) === '') return ['class' => 3, 'to' => null, 'via' => 'blank'];

    if ($poly && in_array(strtolower($value), SWUCardIdentityBaseColours(), true)) {
        return ['class' => 2, 'to' => $value, 'via' => 'base-colour'];
    }
    if (in_array($value, SWUCardIdentitySentinels(), true)) {
        return ['class' => 2, 'to' => $value, 'via' => 'sentinel'];
    }

    // Already an identity — still folded to its canonical printing, so reprints aggregate.
    if (SWUCardIdentityIsSetNnn($value)) {
        return ['class' => 1, 'to' => CardIDOverride($value), 'via' => 'set-nnn'];
    }

    if (function_exists('CardIDLookup')) {
        $setNnn = CardIDLookup($value);
        if ($setNnn !== null && $setNnn !== '') {
            return ['class' => 1, 'to' => CardIDOverride($setNnn), 'via' => 'uuid'];
        }
    }

    $leaderUnits = SWUCardIdentityLeaderUnitMap();
    if (isset($leaderUnits[$value])) {
        return ['class' => 1, 'to' => CardIDOverride($leaderUnits[$value]), 'via' => 'leader-unit-legacy'];
    }

    return ['class' => 3, 'to' => null, 'via' => 'unresolvable'];
}

// Convenience for callers that only want the storable value. Returns null for class 3, which is the
// signal to drop the affected row — never a reason to store the input unchanged.
function SWUCardIdentityToStored(string $value, bool $poly = false): ?string
{
    return SWUCardIdentityClassify($value, $poly)['to'];
}

// The OUTBOUND direction: what a consumer expecting the FFG UID wire format should receive.
//
// Falls back to the identity itself rather than null when a card has no UID — a preview card has
// none (GetCardUUID('HMW_004') is NULL), and emitting null would break an export that a caller can
// otherwise round-trip. Stats APIs never hit this (preview formats write no stats), but LoadDeck can.
function SWUCardIdentityToWire($cardID): ?string
{
    if ($cardID === null || $cardID === '') return $cardID;
    $id = (string)$cardID;
    if (function_exists('UUIDLookup')) {
        $uuid = UUIDLookup($id);
        if ($uuid !== null && $uuid !== '') return (string)$uuid;
    }
    return $id;
}
