<?php
// Single source of truth for opponent common-base bucketing.
// Maps a base GUID to {color, type, canonical}. Force/Splash come from the static
// table below (the API's card dictionary does NOT contain these bases); Standard 30HP
// bases resolve via CardAspect/CardHp when a dictionary is loaded, else fall through.
//
// Registry covers COMMON bases only. Rares sharing these HP values (e.g. JTL_025/028 @27HP)
// are intentionally excluded.

if (!function_exists('StatsBaseColors')) {

function StatsBaseColors() {
    return ['Green', 'Blue', 'Red', 'Yellow', 'Colorless'];
}

// type label => column-name suffix (for COMMON bases, stored in opponentdeckstats wide columns).
//  - Legacy ('') = the original winsVs{Color} columns: color-only data with unknown base type
//    (all pre-existing rows + any submission that sends only opposingBaseColor).
//  - Standard/Force/Splash = resolved 30/28/27 HP common bases.
// Rare/Special bases are NOT here — they are stored individually by baseID in opponentnamedbasestats.
function StatsBaseTypeSuffixes() {
    return ['Legacy' => '', 'Standard' => 'Standard', 'Force' => 'Force', 'Splash' => 'Splash'];
}

function StatsTypeColumnSuffix($type) {
    $s = StatsBaseTypeSuffixes();
    return isset($s[$type]) ? $s[$type] : '';
}

function AspectToColor($aspectCsv) {
    $map = ['Command' => 'Green', 'Vigilance' => 'Blue', 'Aggression' => 'Red', 'Cunning' => 'Yellow'];
    foreach (explode(',', (string)$aspectCsv) as $a) {
        $a = trim($a);
        if (isset($map[$a])) return $map[$a];
    }
    return 'Colorless';
}

// Human-friendly label for a common-base bucket, e.g. "30HP — Command".
// Type comes from ResolveOpponentBase() (Standard/Force/Splash); color is mapped
// back to its aspect since that is how players refer to bases.
function BaseGroupDisplayLabel($type, $color) {
    $typeLabel = ['Standard' => '30HP', 'Force' => 'Force', 'Splash' => 'Splash'];
    $colorToAspect = ['Green' => 'Command', 'Blue' => 'Vigilance', 'Red' => 'Aggression',
                      'Yellow' => 'Cunning', 'Colorless' => 'Colorless', '*' => 'Any'];
    $t = isset($typeLabel[$type]) ? $typeLabel[$type] : $type;
    $a = isset($colorToAspect[$color]) ? $colorToAspect[$color] : $color;
    return $t . ' — ' . $a;
}

// GUID => ['color','type','canonical']. Force = LOF 28HP commons, Splash = LAW 27HP commons.
// Two printings per color; both map to the first printing's GUID as canonical.
function StatsForceSplashRegistry() {
    return [
        // --- FORCE (28HP, LOF) ---
        '2098652813' => ['color'=>'Blue',  'type'=>'Force', 'canonical'=>'2098652813'], // LOF_020
        '0119018087' => ['color'=>'Blue',  'type'=>'Force', 'canonical'=>'2098652813'], // LOF_021
        '0450346170' => ['color'=>'Green', 'type'=>'Force', 'canonical'=>'0450346170'], // LOF_023
        '2945340801' => ['color'=>'Green', 'type'=>'Force', 'canonical'=>'0450346170'], // LOF_024
        '5396502974' => ['color'=>'Red',   'type'=>'Force', 'canonical'=>'5396502974'], // LOF_026
        '8710346686' => ['color'=>'Red',   'type'=>'Force', 'canonical'=>'5396502974'], // LOF_027
        '4352576521' => ['color'=>'Yellow','type'=>'Force', 'canonical'=>'4352576521'], // LOF_029
        '3380203065' => ['color'=>'Yellow','type'=>'Force', 'canonical'=>'4352576521'], // LOF_030
        // --- SPLASH (27HP, LAW) ---
        '5043366366' => ['color'=>'Blue',  'type'=>'Splash','canonical'=>'5043366366'], // LAW_020
        '6862472986' => ['color'=>'Blue',  'type'=>'Splash','canonical'=>'5043366366'], // LAW_021
        '2248996839' => ['color'=>'Green', 'type'=>'Splash','canonical'=>'2248996839'], // LAW_022
        '7297371836' => ['color'=>'Green', 'type'=>'Splash','canonical'=>'2248996839'], // LAW_024
        '0121172430' => ['color'=>'Red',   'type'=>'Splash','canonical'=>'0121172430'], // LAW_025
        '5020919647' => ['color'=>'Red',   'type'=>'Splash','canonical'=>'0121172430'], // LAW_027
        '2937103129' => ['color'=>'Yellow','type'=>'Splash','canonical'=>'2937103129'], // LAW_028
        '1156889063' => ['color'=>'Yellow','type'=>'Splash','canonical'=>'2937103129'], // LAW_030
    ];
}

// Promo / OP-set base reprints whose GUIDs are NOT in the card dictionary (so CardAspect
// can't classify them and CardIDOverride — being SET_NNN keyed — never reaches them).
// Map each promo base GUID straight to its common-base classification + the canonical
// dictionary GUID of the base it reprints. Add more as promo bases surface.
function PromosRegistry() {
    return [
        // GG_004 Jabba's Palace — reprint of the Cunning 30HP common (SHD_026).
        '2537094666' => ['color' => 'Yellow', 'type' => 'Standard', 'canonical' => '2376813177'],
    ];
}

// Rare/Special bases — tracked individually by base identity (NOT bucketed by color).
// Curated per set so the classification does not depend on runtime CardRarity/CardHp (which
// may not be future-proof). Add a new set's Rare/Special base GUIDs here when it releases;
// anything NOT listed falls back to a 30HP Standard common (bucketed by color).
function StatsRareSpecialBases() {
    return [
        // IBH
        '1049149674' => true, // IBH_002 Echo Caverns
        '0479107180' => true, // IBH_054 Forward Command Post
        // JTL
        '1029978899' => true, // JTL_021 Colossus
        '4028826022' => true, // JTL_024 Data Vault
        '4301437393' => true, // JTL_025 Thermal Oscillator
        '9586661707' => true, // JTL_028 Nabat Village
        '1672815328' => true, // JTL_031 Lake Country
        // LAW
        '3469239154' => true, // LAW_019 Alliance Outpost
        '7897278827' => true, // LAW_023 Great Pit of Carkoon
        '2034527101' => true, // LAW_026 Shipbreaking Yard
        '5020758299' => true, // LAW_029 Citadel Research Center
        // LOF
        '7204128611' => true, // LOF_019 Vergence Temple
        '9434212852' => true, // LOF_022 Mystic Monastery
        '9453163990' => true, // LOF_025 Temple of Destruction
        '2699176260' => true, // LOF_028 Tomb of Eilram
        // SOR
        '2429341052' => true, // SOR_019 Security Complex
        '8327910265' => true, // SOR_022 Energy Conversion Lab
        '1393827469' => true, // SOR_025 Tarkintown
        '2569134232' => true, // SOR_028 Jedha City
        // TS26
        '1352374398' => true, // TS26_009 First Battle Memorial
        '4631699773' => true, // TS26_010 Dooku's Palace
        '1546304694' => true, // TS26_011 Executioner's Arena
        '0344986336' => true, // TS26_012 Sundari Palace
        // TWI
        '6594935791' => true, // TWI_019 Pau City
        '8589863038' => true, // TWI_022 Droid Manufactory
        '6854189262' => true, // TWI_025 Shadow Collective Camp
        '9652861741' => true, // TWI_028 Petranaki Arena
    ];
}

// Existing 30HP canonicalization (moved verbatim from SubmitGameResult.php).
function Canonical30Base($baseID) {
    $canonicalBases = [
        'Cunning'    => '2376813177',
        'Command'    => '7790300585',
        'Aggression' => '2696059415',
        'Vigilance'  => '9014930596',
    ];
    $baseToAspect = [
        '2376813177' => 'Cunning',
        '7790300585' => 'Command',
        '2696059415' => 'Aggression',
        '9014930596' => 'Vigilance',
    ];
    if (isset($baseToAspect[$baseID])) {
        return $canonicalBases[$baseToAspect[$baseID]];
    }
    return $baseID;
}

// Resolve a base GUID to a classification, or null if it can't be identified.
// List-driven (NOT CardRarity/CardHp, which may not be future-proof):
//   1. known common Force/Splash base   -> ['kind'=>'common','color','type'=>'Force'|'Splash','canonical']
//   2. known Rare/Special base           -> ['kind'=>'named','baseID','name','canonical']
//   3. fallback (any other real base)    -> 30HP Standard common, color from CardAspect
// Returns null only when no card dictionary is loaded AND the base isn't in a static list,
// which lets writers fall back to the legacy color-only path.
function ResolveOpponentBase($baseID) {
    if ($baseID === null || $baseID === '') return null;

    // 1. Common Force & Splash bases (dict-independent static lists).
    $reg = StatsForceSplashRegistry();
    if (isset($reg[$baseID])) {
        $e = $reg[$baseID];
        return ['kind' => 'common', 'color' => $e['color'], 'type' => $e['type'], 'canonical' => $e['canonical']];
    }

    // 1b. Promo / OP-set base reprints (dict-independent static list).
    $promos = PromosRegistry();
    if (isset($promos[$baseID])) {
        $e = $promos[$baseID];
        return ['kind' => 'common', 'color' => $e['color'], 'type' => $e['type'], 'canonical' => $e['canonical']];
    }

    // 2. Rare/Special bases — tracked individually by name.
    $rs = StatsRareSpecialBases();
    if (isset($rs[$baseID])) {
        $name = function_exists('CardTitle') ? CardTitle($baseID) : $baseID;
        return ['kind' => 'named', 'baseID' => $baseID, 'name' => $name, 'canonical' => $baseID];
    }

    // 3. Fallback: treat as a 30HP Standard common, bucketed by color.
    if (function_exists('CardAspect')) {
        $aspect = CardAspect($baseID);
        if ($aspect !== null && $aspect !== '') {
            return ['kind' => 'common', 'color' => AspectToColor($aspect), 'type' => 'Standard',
                    'canonical' => Canonical30Base($baseID)];
        }
    }
    return null;
}

// Back-compat: existing callers expect the canonical GUID (or the original if unknown).
function NormalizeBaseID($baseID) {
    $r = ResolveOpponentBase($baseID);
    return $r ? $r['canonical'] : $baseID;
}

// Display bucket for a deck's base on the meta-stats surfaces (read-time consolidation).
// Common bases collapse by (color,type); Rare/Special bases and unresolvable GUIDs stay
// individual. Returns ['key' => string, 'displayBase' => guid]:
//   - key: 'grp:{type}:{color}' for commons (matches Stats/Decks.php), else the base GUID.
//   - displayBase: a deterministic canonical GUID for the bucket's representative card art.
function StatsBaseBucket($baseID) {
    $r = ResolveOpponentBase($baseID);
    if ($r && $r['kind'] === 'common') {
        if ($r['type'] === 'Standard') {
            // A non-canonical 30HP common has canonical == itself, so map by color instead.
            $stdByColor = ['Green' => '7790300585', 'Blue' => '9014930596',
                           'Red' => '2696059415', 'Yellow' => '2376813177'];
            $rep = isset($stdByColor[$r['color']]) ? $stdByColor[$r['color']] : (string)$baseID;
        } else {
            // Force/Splash: the registry already stores a single per-color canonical.
            $rep = $r['canonical'];
        }
        return ['key' => 'grp:' . $r['type'] . ':' . $r['color'], 'displayBase' => $rep];
    }
    // Named rare, unresolvable, or empty — keep individual.
    return ['key' => (string)$baseID, 'displayBase' => (string)$baseID];
}

} // end function_exists guard
