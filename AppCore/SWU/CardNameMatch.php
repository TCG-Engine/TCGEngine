<?php
/**
 * Card NAME → SET_NNN resolution, shared by SWUDeck and SWUSim.
 *
 * Dictionary-agnostic on purpose: it reads the ambient $titleData / $subtitleData globals and
 * never includes a GeneratedCardDictionaries.php itself. SWUDeck's and SWUSim's dictionaries
 * declare the same global functions, so requiring either one here would fatal the other app —
 * the CALLER loads its own dictionary first.
 *
 * SWUDeck/Custom/CardIdentifiers.php keeps its historical names (CardNicknames,
 * RankCardTitleMatches, FindCardMatches, FindCardSetCode) as thin wrappers over these.
 */

/**
 * Common nicknames and source-spelling aliases → the real card title.
 *
 * @param string $cardName lowercased, trimmed name
 * @return string the standardized card name (unchanged when there is no alias)
 */
function SWUCardNameAlias($cardName) {
    switch($cardName) {
        case "chewie":
            return "Chewbacca";
        case "flyboy":
            return "Han Solo";
        case "threepio":
            return "C-3PO";
        case "artoo":
            return "R2-D2";
        case "beebee":
            return "BB-8";
        case "baby yoda":
            return "Grogu";
        case "uwing":
            return "U-Wing Reinforcements";
        // Source-spelling aliases. An external decklist that misspells a title resolves to null and
        // the card is then silently DROPPED from the import (the melee.gg importers skip any name
        // they cannot resolve), so the deck comes back short with no error. melee.gg writes
        // "Zeb Orellios"; the card is "Zeb Orrelios".
        case "zeb orellios":
            return "Zeb Orrelios";
        default:
            return $cardName;
    }
}

/**
 * Every $titleData entry matching $cardName, best match FIRST.
 *
 * Callers universally take [0], and before 2026-09-09 the only test here was stripos() — a
 * SUBSTRING test — so [0] was simply whichever card the generated dictionary happened to list
 * earliest. A card whose ENTIRE title is a substring of some earlier-listed title was therefore
 * unreachable by name: melee.gg import resolved "A-Wing" to SOR_141 'Green Squadron A-Wing'
 * (dictionary line 121) rather than SEC_213 'A-Wing' (line 1636). A sweep of $titleData found
 * 49 titles shadowed this way — 'Vigil' behind 'Vigilance', 'Enoch' behind 'Captain Enoch',
 * 'Max Rebo' behind 'The Max Rebo Band' — so ordering, not the substring test, was the defect.
 *
 * Tiers, in order; dictionary order is preserved WITHIN each tier:
 *   1. exact title, case-insensitive
 *   2. exact title once non-alphanumerics are stripped ("Chewbacca's" ≡ "Chewbaccas")
 *   3. substring of the title
 *   4. substring of the stripped title
 *
 * Tiers 3 and 4 stay separate, and in that order, because the name lookup previously consulted
 * the stripped form ONLY when the plain form matched nothing. Merging them would let a stripped
 * hit that sits earlier in the dictionary outrank a plain hit and silently move [0] for names
 * that have no exact match at all.
 *
 * Ranking is not filtering: every id that matched before still comes back, just later.
 *
 * @param string $cardName
 * @return array SET_NNN ids, best first ($titleData is SET_NNN-keyed — the key IS the set code)
 */
function SWURankCardTitleMatches($cardName) {
    global $titleData;
    if (!is_array($titleData)) return [];

    $needle = strtolower(trim(SWUCardNameAlias(strtolower(trim((string)$cardName)))));
    if ($needle === '') return [];
    $needleStripped = preg_replace('/[^a-zA-Z0-9]/', '', $needle);

    $exact = $exactStripped = $partial = $partialStripped = [];
    foreach ($titleData as $cardID => $title) {
        $t = strtolower(trim((string)$title));
        if ($t === $needle)                                          { $exact[] = $cardID;           continue; }
        $tStripped = preg_replace('/[^a-zA-Z0-9]/', '', $t);
        if ($needleStripped !== '' && $tStripped === $needleStripped) { $exactStripped[] = $cardID;   continue; }
        if (strpos($t, $needle) !== false)                           { $partial[] = $cardID;         continue; }
        if ($needleStripped !== '' && strpos($tStripped, $needleStripped) !== false) {
            $partialStripped[] = $cardID;
        }
    }
    return array_merge($exact, $exactStripped, $partial, $partialStripped);
}

/**
 * Resolve a card name to one SET_NNN id, or null.
 *
 * Accepts melee.gg's pipe format ("Title | Subtitle"): an exact title+subtitle hit wins, then
 * the title alone.
 *
 * @param string $cardName
 * @return string|null
 */
function SWUFindCardIdByName($cardName) {
    $cardName = trim((string)$cardName);

    if(strpos($cardName, '|') !== false) {
        $parts = explode('|', $cardName);
        $characterName = trim($parts[0]);
        $subtitle = isset($parts[1]) ? trim($parts[1]) : '';

        // Normalise the title through the alias table first, exactly as SWURankCardTitleMatches
        // does, so a source that misspells a title still reaches the title+subtitle comparison
        // below. Without this the branch falls through to the title-only retry, which for a card
        // with several printings returns whichever printing sits first in the dictionary — for
        // "Zeb Orellios | Spectre Four" that is SOR_146 "Headstrong Warrior", not the LAW_045
        // "Spectre Four" asked for. A wrong printing is worse than the dropped card it replaces.
        $normalizedName = strtolower(trim(SWUCardNameAlias(strtolower($characterName))));

        global $titleData, $subtitleData;
        if (is_array($titleData)) {
            foreach ($titleData as $cardID => $title) {
                if (strtolower($title) == $normalizedName && isset($subtitleData[$cardID])
                    && strtolower($subtitleData[$cardID]) == strtolower($subtitle)) {
                    return $cardID;
                }
            }
        }

        $matches = SWURankCardTitleMatches($characterName);
        if(count($matches) > 0) {
            return $matches[0];
        }
    } else {
        $matches = SWURankCardTitleMatches($cardName);
        if(count($matches) > 0) {
            return $matches[0];
        }
    }

    // Safety net for the pipe branch (retries on the raw string), routed through the same
    // ranking so it cannot disagree with the tiers above.
    $ranked = SWURankCardTitleMatches($cardName);
    return $ranked[0] ?? null;
}
