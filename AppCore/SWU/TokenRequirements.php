<?php
// Which physical tokens a deck might need, derived from its cards' rules text. Powers the
// deckbuilder's "Needs Tokens: …" line.
//
// PURE: card ids in, ordered token names out. No database, no gamestate, no HTTP, no superglobals —
// so every rule is pinned by DevTools/tdd-regression/test_swu_token_requirements.php without
// standing up a deck.
//
// ⚠ The CALLER must have loaded a card dictionary (for CardText). This file deliberately requires
// neither: SWUDeck's and SWUSim's dictionaries declare the same function names, so a process can
// hold only one, and this code must work with whichever the caller chose.
//
// KNOWN LIMITATION — a leader's DEPLOYED side is invisible here. CardText() on a leader returns only
// its front face; CardOtherOrientation() resolves 0 of 158 leaders; and there is NO card entity for
// a deployed side anywhere in the data (LeaderUnitLegacyIDByCardID() returns a legacy stats-row
// identifier, not a card). A leader whose deployed side creates a token its front never mentions is missed — measured
// blast radius is 2 of 148: SEC_011 Governor Pryce (Spy) and TWI_017 Chancellor Palpatine (Clone
// Trooper). This under-reports, never misreports.
//
// Design: docs/superpowers/specs/2026-08-06-swudeck-token-requirements-design.md §2
require_once __DIR__ . '/Tokens.php';

// "an Experience or Shield token" -> "an Experience token or Shield token".
// SWU elides the first noun in a pair. Without this, LAW_019 Alliance Outpost reports Shield and
// Credit but silently loses Experience.
function _SWUTokenNormalizeText(string $text): string
{
    if ($text === '') return '';
    static $alt = null;
    if ($alt === null) {
        $alt = implode('|', array_map(
            fn($n) => preg_quote($n, '/'),
            array_keys(SWUTokenCatalog())
        ));
    }
    return (string)preg_replace(
        '/\b(' . $alt . ')\s+(or|and)\s+(' . $alt . ')(\s+tokens?)\b/i',
        '$1$4 $2 $3$4',
        $text
    );
}

// Does this (already normalised) card text require $tokenName?
function _SWUTokenTextRequires(string $text, string $tokenName): bool
{
    if ($text === '') return false;

    // The Force is the one token whose title never appears in the "<name> token" construction —
    // cards say "use the Force", "the Force is with you", or "lose your Force token", never
    // "The Force token". The idioms alone match 66 cards and strictly contain the 55 that say
    // "Force token"; all three are matched so future phrasing has a second chance.
    //
    // These patterns deliberately do NOT fire on the Force TRAIT ("loses the Force trait").
    if ($tokenName === 'The Force') {
        return (bool)preg_match('/\buse the Force\b|\bthe Force is with you\b|\bForce tokens?\b/i', $text);
    }

    // Shielded creates a Shield token on entry, and 14 cards carry the keyword without ever writing
    // "Shield token". Also catches cards that GRANT Shielded to something else.
    if ($tokenName === 'Shield' && preg_match('/\bShielded\b/i', $text)) return true;

    // The primary rule. Requiring the literal word "token" is what keeps traits and card titles out:
    // 26 cards say "Spy" and 14 say "Mandalorian" meaning the trait.
    return (bool)preg_match('/\b' . preg_quote($tokenName, '/') . '\s+tokens?\b/i', $text);
}

// Token display names a deck might need, in catalog order, deduplicated.
// $cardIDs may contain duplicates, empty strings and nulls — all are tolerated.
function SWUDeckRequiredTokens(array $cardIDs): array
{
    $catalog = SWUTokenCatalog();
    $names   = array_keys($catalog);
    $needed  = [];

    $seen = [];
    foreach ($cardIDs as $raw) {
        $id = trim((string)$raw);
        if ($id === '' || isset($seen[$id])) continue;
        $seen[$id] = true;

        $text = _SWUTokenNormalizeText((string)CardText($id));
        if ($text === '') continue;

        foreach ($names as $name) {
            if (isset($needed[$name])) continue;               // already required; skip the regex
            if (_SWUTokenTextRequires($text, $name)) $needed[$name] = true;
        }
        if (count($needed) === count($names)) break;           // everything already required
    }

    // Catalog order, NOT discovery order — the UI prints this array as-is and the tests assert
    // exact arrays.
    return array_values(array_filter($names, fn($n) => isset($needed[$n])));
}
