<?php
require_once __DIR__ . '/../AppCore/SWU/CardIdentity.php';   // accept UID or SET_NNN
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
        'LOF_020' => ['color'=>'Blue',  'type'=>'Force', 'canonical'=>'LOF_020'], // LOF_020
        'LOF_021' => ['color'=>'Blue',  'type'=>'Force', 'canonical'=>'LOF_020'], // LOF_021
        'LOF_023' => ['color'=>'Green', 'type'=>'Force', 'canonical'=>'LOF_023'], // LOF_023
        'LOF_024' => ['color'=>'Green', 'type'=>'Force', 'canonical'=>'LOF_023'], // LOF_024
        'LOF_026' => ['color'=>'Red',   'type'=>'Force', 'canonical'=>'LOF_026'], // LOF_026
        'LOF_027' => ['color'=>'Red',   'type'=>'Force', 'canonical'=>'LOF_026'], // LOF_027
        'LOF_029' => ['color'=>'Yellow','type'=>'Force', 'canonical'=>'LOF_029'], // LOF_029
        'LOF_030' => ['color'=>'Yellow','type'=>'Force', 'canonical'=>'LOF_029'], // LOF_030
        // --- SPLASH (27HP, LAW) ---
        'LAW_020' => ['color'=>'Blue',  'type'=>'Splash','canonical'=>'LAW_020'], // LAW_020
        'LAW_021' => ['color'=>'Blue',  'type'=>'Splash','canonical'=>'LAW_020'], // LAW_021
        'LAW_022' => ['color'=>'Green', 'type'=>'Splash','canonical'=>'LAW_022'], // LAW_022
        'LAW_024' => ['color'=>'Green', 'type'=>'Splash','canonical'=>'LAW_022'], // LAW_024
        'LAW_025' => ['color'=>'Red',   'type'=>'Splash','canonical'=>'LAW_025'], // LAW_025
        'LAW_027' => ['color'=>'Red',   'type'=>'Splash','canonical'=>'LAW_025'], // LAW_027
        'LAW_028' => ['color'=>'Yellow','type'=>'Splash','canonical'=>'LAW_028'], // LAW_028
        'LAW_030' => ['color'=>'Yellow','type'=>'Splash','canonical'=>'LAW_028'], // LAW_030
    ];
}

// Promo / OP-set base reprints whose GUIDs are NOT in the card dictionary (so CardAspect
// can't classify them and CardIDOverride — being SET_NNN keyed — never reaches them).
// Map each promo base GUID straight to its common-base classification + the canonical
// dictionary GUID of the base it reprints. Add more as promo bases surface.
function PromosRegistry() {
    return [
        // GG_004 Jabba's Palace — reprint of the Cunning 30HP common (SHD_026).
        '2537094666' => ['color' => 'Yellow', 'type' => 'Standard', 'canonical' => 'SEC_026'],
    ];
}

// Rare/Special bases — tracked individually by base identity (NOT bucketed by color).
// Curated per set so the classification does not depend on runtime CardRarity/CardHp (which
// may not be future-proof). Add a new set's Rare/Special base GUIDs here when it releases;
// anything NOT listed falls back to a 30HP Standard common (bucketed by color).
function StatsRareSpecialBases() {
    return [
        // IBH
        'IBH_002' => true, // IBH_002 Echo Caverns
        'IBH_054' => true, // IBH_054 Forward Command Post
        // JTL
        'JTL_021' => true, // JTL_021 Colossus
        'JTL_024' => true, // JTL_024 Data Vault
        'JTL_025' => true, // JTL_025 Thermal Oscillator
        'JTL_028' => true, // JTL_028 Nabat Village
        'JTL_031' => true, // JTL_031 Lake Country
        // LAW
        'LAW_019' => true, // LAW_019 Alliance Outpost
        'LAW_023' => true, // LAW_023 Great Pit of Carkoon
        'LAW_026' => true, // LAW_026 Shipbreaking Yard
        'LAW_029' => true, // LAW_029 Citadel Research Center
        // LOF
        'LOF_019' => true, // LOF_019 Vergence Temple
        'LOF_022' => true, // LOF_022 Mystic Monastery
        'LOF_025' => true, // LOF_025 Temple of Destruction
        'LOF_028' => true, // LOF_028 Tomb of Eilram
        // SOR
        'SOR_019' => true, // SOR_019 Security Complex
        'SOR_022' => true, // SOR_022 Energy Conversion Lab
        'SOR_025' => true, // SOR_025 Tarkintown
        'SOR_028' => true, // SOR_028 Jedha City
        // TS26
        'TS26_09' => true, // TS26_09 First Battle Memorial
        'TS26_10' => true, // TS26_10 Dooku's Palace
        'TS26_11' => true, // TS26_11 Executioner's Arena
        'TS26_12' => true, // TS26_12 Sundari Palace
        // TWI
        'TWI_019' => true, // TWI_019 Pau City
        'TWI_022' => true, // TWI_022 Droid Manufactory
        'TWI_025' => true, // TWI_025 Shadow Collective Camp
        'TWI_028' => true, // TWI_028 Petranaki Arena
    ];
}

// Existing 30HP canonicalization (moved verbatim from SubmitGameResult.php).
function Canonical30Base($baseID) {
    $canonicalBases = [
        'Cunning'    => 'SEC_026',
        'Command'    => 'SEC_021',
        'Aggression' => 'SEC_024',
        'Vigilance'  => 'JTL_020',
    ];
    $baseToAspect = [
        'SEC_026' => 'Cunning',
        'SEC_021' => 'Command',
        'SEC_024' => 'Aggression',
        'JTL_020' => 'Vigilance',
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

    // The registry is keyed by SET_NNN (re-keyed 2026-08-05, spec §7), but a caller can still hand
    // us an FFG UID: stored stats rows carry them until the migration runs, and read paths run
    // against pre-migration data during the window. Normalise first so BOTH shapes resolve — a
    // miss here does not error, it silently stops consolidating bases, which is the worst kind.
    if (function_exists('SWUCardIdentityIsSetNnn') && !SWUCardIdentityIsSetNnn((string)$baseID)) {
        $c = SWUCardIdentityClassify((string)$baseID, true);
        if ($c['class'] === 1) $baseID = $c['to'];
    }

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
            $stdByColor = ['Green' => 'SEC_021', 'Blue' => 'JTL_020',
                           'Red' => 'SEC_024', 'Yellow' => 'SEC_026'];
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
