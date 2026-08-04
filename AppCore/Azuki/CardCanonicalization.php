<?php

// Functional card identity for Azuki apps. Alternate-art/promotional printings map to the
// representative used by rules, deck limits, statistics, and (for now) deckbuilding display.
// Keep art selection separate from this map when personalization is added later.
function AzukiCardCanonicalMap(): array {
    static $map = [
        'AZK01-028A_Soryu-no-Rin_E_SR_Die' => 'S1-AZK01-028_Soryu-no-Rin_E_SR_die',
        'AZK01-042A_Thunderclap_S_SR_TEX2_Die' => 'S1-AZK01-042_Thunderclap_S_SR_die',
        'AZK01-054A_Teb-Fea_E_SR_Die' => 'S1-AZK01-054_Teb-Fea_E_SR_die',
        'AZK01-064A_Zero_E_SR_Die' => 'S1-AZK01-064_Zero_E_SR_die',
        'AZP-001_IKZ!_IKZ-Token-AX-Participation_Die' => 'IKZ-002_IKZ!_IKZ-Token_Die',
        'AZP-002_IKZ!_IKZ-Token-AX-Winner_Die' => 'IKZ-002_IKZ!_IKZ-Token_Die',
        'AZP-003_IKZ_INV26-Participation_die' => 'IKZ-001_IKZ!_IKZ_die',
        'IKZ-001A_IKZ!_IKZ_Die' => 'IKZ-001_IKZ!_IKZ_die',
        'IKZ-002A_IKZ!_IKZ-Token_Die' => 'IKZ-002_IKZ!_IKZ-Token_Die',
        'S1-AZK01-079A_Gin-and-Tonika_E_SR_die' => 'S1-AZK01-079_Gin-and-Tonika_E_SR_die',
        'S1-AZK01-087A_Mizuryuus-Torrent_S_SR_die' => 'S1-AZK01-087_Mizuryuus-Torrent_S_SR_die',
        'S1-AZK01-099A_Raikos-Wrath-Shin_E_SR_die' => 'S1-AZK01-099_Raikos-Wrath-Shin_E_SR_die',
        'S1-AZK01-112A_Enrai-Shakunetsu_E_SR_die' => 'S1-AZK01-112_Enrai-Shakunetsu_E_SR_die',
        'S1-AZK01-119A_Piko-of-Thousand-Blades_L_L_die' => 'S1-AZK01-119_Piko-of-Thousand-Blades_L_L_die',
        'S1-AZK01-121A_Kagoro-of-the-Burnt-Path_L_L_die' => 'S1-AZK01-121_Kagoro-of-the-Burnt-Path_L_L_die',
        'S1-AZK01-123A_Goro-Graveloth_L_L_die' => 'S1-AZK01-123_Goro-Graveloth_L_L_die',
        'S1-AZK01-125A_Benzai-the-Sly_L_L_die' => 'S1-AZK01-125_Benzai-the-Sly_L_L_die',
        'S1-STT01-001AX1_Raizan_L_INV26-WINNER_die' => 'S1-STT01-001_Raizan_L_L_die',
        'S1-STT01-001AX2_Raizan_L_INV26-SECOND_die' => 'S1-STT01-001_Raizan_L_L_die',
        'S1-STT02-001AX_Shao_L_INV26-TOP8_die' => 'S1-STT02-001_Shao_L_L_die',
        'S1-STT03-001A_Bobu_L_L_die' => 'S1-STT03-001_Bobu_L_L_die',
        'S1-STT03-002A_Stonehaven-Gate_G_G_die' => 'S1-STT03-002_Stonehaven-Gate_G_G_die',
        'S1-STT03-013A_Stone-Masked-Ancient_E_SR_die' => 'S1-STT03-013_Stone-Masked-Ancient_E_SR_die',
        'S1-STT04-001_Zero_L_L_die__2' => 'S1-STT04-001_Zero_L_L_die',
        'S1-STT04-002A_Ragefire-Gate_G_G_die' => 'S1-STT04-002_Ragefire-Gate_G_G_die',
        'S1-STT04-014A_Scorchveil-Shinobi-Suzuka_E_SR_die' => 'S1-STT04-014_Scorchveil-Shinobi-Suzuka_E_SR_die',
        'STT01-001A_Raizan_L_AA_Die' => 'S1-STT01-001_Raizan_L_L_die',
        'STT01-002A_Surge-Gate_G_die' => 'S1-STT01-002_Surge-Gate_G_G_die',
        'STT01-011A_Raizan_E_SR_die' => 'S1-STT01-011_Raizan_E_SR_die',
        'STT01-016A_Ikazuchi_W_SR_die' => 'S1-STT01-016_Ikazuchi_W_SR_die',
        'STT02-001A_Shao_L_AA_Die' => 'S1-STT02-001_Shao_L_L_die',
        'STT02-002A_Hydromancy-Gate_G_die' => 'S1-STT02-002_Hydromancy-Gate_G_G_die',
        'STT02-013A_Mizuki_E_SR_die' => 'S1-STT02-013_Mizuki_E_SR_die',
        'STT02-013ASN_Mizuki_E_SR_die' => 'S1-STT02-013_Mizuki_E_SR_die',
        'STT02-017A_Shaos-Perseverance_S_SR_die' => 'S1-STT02-017_Shaos-Perseverance_S_SR_die'
    ];
    return $map;
}

function AzukiCanonicalCardID($cardID): string {
    $cardID = trim((string)$cardID);
    $canonicalMap = AzukiCardCanonicalMap();
    return $canonicalMap[$cardID] ?? $cardID;
}

// Backward-compatible importer names. New shared callers should use the generic functions above.
function AzukiImportedCardCanonicalMap(): array {
    return AzukiCardCanonicalMap();
}

function AzukiCanonicalImportedCardID($cardID): string {
    return AzukiCanonicalCardID($cardID);
}

function AzukiCanonicalizeResolvedDeck($resolvedDeck) {
    if (!is_array($resolvedDeck)) return $resolvedDeck;

    foreach (['leader', 'gate'] as $key) {
        if (isset($resolvedDeck[$key])) {
            $resolvedDeck[$key] = AzukiCanonicalCardID($resolvedDeck[$key]);
        }
    }

    if (isset($resolvedDeck['mainDeck']) && is_array($resolvedDeck['mainDeck'])) {
        $resolvedDeck['mainDeck'] = array_map('AzukiCanonicalCardID', $resolvedDeck['mainDeck']);
    }

    return $resolvedDeck;
}

// Ordered, unique functional representatives for browse catalogs.
function AzukiCanonicalCardCatalog(array $cardIDs): array {
    $catalog = [];
    foreach ($cardIDs as $cardID) {
        $canonicalID = AzukiCanonicalCardID($cardID);
        $catalog[$canonicalID] = $canonicalID;
    }
    return array_values($catalog);
}
