<?php

// The shared attachment layer behind every "Attach to …" asset.
//
// 2026-09-16: Hellbreak assets that read "Attach to a character" had no engine support at all —
// ten DOT cards were unimplementable. An attached asset now stays in its owner's Assets zone and
// records its host's UniqueID in AttachedTo (0 = unattached), which is what the value modifiers
// gate on.
//
// The three things this pins, all of which are silent when wrong:
//   1. An UNATTACHED asset must match nothing. AttachedTo defaults to 0 and a UniqueID is never 0,
//      but a predicate written as "host === subject->UniqueID ?? 0" would make every unattached
//      asset grant its bonus to every object with no UniqueID.
//   2. An unqualified pool spans BOTH seats. The rulebook defines "allied"/"enemy" as the terms a
//      card uses when it means them, and none of the ten attach cards uses either, so restricting
//      the pool to friendly characters would silently invert DOT_095 Frozen With Fear.
//   3. Two attachments on ONE host must BOTH leave play with it. Splicing the assets array while
//      iterating it shifts every later index and strands the second one.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

include_once './HellbreakSim/ZoneClasses.php';
include_once './HellbreakSim/ZoneAccessors.php';
include_once './HellbreakSim/GamestateParser.php';
include_once './HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './HellbreakSim/Custom/GameLogic.php';

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

// A board built by hand: P1 has Dracula (monster) plus two minions, P2 has Jaws plus one minion.
global $p1Monster, $p2Monster, $p1Characters, $p2Characters, $p1Assets, $p2Assets,
       $p1Crypt, $p2Crypt, $gLocations, $gTurnNumber;
$gTurnNumber = 1;
$gLocations = [];
$p1Crypt = [];
$p2Crypt = [];

$uid = 100;
$makeCharacter = function(string $cardID, int $player, int $slot) use (&$uid) {
    // Characters: CardID Status Damage Owner Controller LocationSlot UniqueID TurnEffects Counters
    $obj = new Characters($cardID . ' 2 0 ' . $player . ' ' . $player . ' ' . $slot . ' ' . (++$uid) . ' - -', 'Characters', $player);
    $obj->UniqueID = $uid;
    return $obj;
};
$makeMonster = function(string $cardID, int $player) use (&$uid) {
    $obj = new Monster($cardID . ' 2 LURKING ' . $player . ' ' . $player . ' ' . (++$uid) . ' - -', 'Monster', $player);
    $obj->UniqueID = $uid;
    return $obj;
};
$makeAsset = function(string $cardID, int $player, int $attachedTo) use (&$uid) {
    $obj = new Assets($cardID . ' 2 ' . $player . ' ' . $player . ' ' . (++$uid) . ' ' . $attachedTo . ' - -', 'Assets', $player);
    $obj->UniqueID = $uid;
    $obj->AttachedTo = $attachedTo;
    return $obj;
};

$draculaMonster = $makeMonster('DOT_001', 1);
$jawsMonster = $makeMonster('DOT_006', 2);
$p1Minion = $makeCharacter('DOT_025', 1, 1);
$p1Other = $makeCharacter('DOT_027', 1, 1);
$p2Minion = $makeCharacter('DOT_105', 2, 1);
$p1Monster = [$draculaMonster];
$p2Monster = [$jawsMonster];
$p1Characters = [$p1Minion, $p1Other];
$p2Characters = [$p2Minion];
$p1Assets = [];
$p2Assets = [];

// ---------------------------------------------------------------------------
// 1. The predicate the value modifiers gate on
// ---------------------------------------------------------------------------
$fangs = $makeAsset('DOT_171', 1, intval($p1Minion->UniqueID));
$loose = $makeAsset('DOT_171', 1, 0);

$check(HellbreakAssetIsAttachedTo($fangs, $p1Minion),
    'an attached asset matches its host');
$check(!HellbreakAssetIsAttachedTo($fangs, $p1Other),
    'NEGATIVE: an attached asset does NOT match another minion');
$check(!HellbreakAssetIsAttachedTo($fangs, $draculaMonster),
    'NEGATIVE: an attached asset does NOT match its controller\'s monster');
$check(!HellbreakAssetIsAttachedTo($loose, $p1Minion),
    'NEGATIVE: an UNATTACHED asset (AttachedTo=0) matches nothing');
$check(!HellbreakAssetIsAttachedTo($loose, new stdClass()),
    'NEGATIVE: an unattached asset does not match an object with no UniqueID');

$check(HellbreakAttachedHostObject($fangs) === $p1Minion,
    'the host object resolves back from the link');
$check(HellbreakAttachedHostObject($loose) === null,
    'an unattached asset resolves to no host');

// ---------------------------------------------------------------------------
// 2. Pools — unqualified means BOTH seats; a monster is a character, not a minion
// ---------------------------------------------------------------------------
$characterPool = HellbreakAttachTargets(1, 'CHARACTER');
$minionPool = HellbreakAttachTargets(1, 'MINION');

$check(count($characterPool) === 5,
    'the character pool is every minion AND monster on both seats (got ' . count($characterPool) . ' of 5)');
$check(in_array('theirCharacters-0', $characterPool, true),
    'an ENEMY minion is a legal host — "a character" carries no allied/enemy qualifier');
$check(in_array('theirMonster-0', $characterPool, true),
    'an ENEMY monster is a legal host');
$check(in_array('myMonster-0', $characterPool, true),
    'your own monster is a legal host — a character is any minion or monster');

$check(count($minionPool) === 3,
    'the minion pool is every minion on both seats (got ' . count($minionPool) . ' of 3)');
$check(!in_array('myMonster-0', $minionPool, true) && !in_array('theirMonster-0', $minionPool, true),
    'NEGATIVE: a monster is NOT in the minion pool (DOT_095 attaches to a minion)');

// A character pool can never be empty: a monster is always in play while the game is running.
$check(count(HellbreakAttachTargets(2, 'CHARACTER')) === 5,
    'the pool is the same board seen from the other seat');

// ---------------------------------------------------------------------------
// 3. The host leaving play takes its attachments with it (USER RULING: to the owner's crypt)
// ---------------------------------------------------------------------------
$first = $makeAsset('DOT_171', 1, intval($p1Minion->UniqueID));
$second = $makeAsset('DOT_131', 1, intval($p1Minion->UniqueID));
$elsewhere = $makeAsset('DOT_211', 1, intval($p1Other->UniqueID));
$unattached = $makeAsset('DOT_049', 1, 0);
$p1Assets = [$first, $second, $elsewhere, $unattached];
$p1Crypt = [];

$detached = HellbreakDetachAssetsFromHost($p1Minion);

$check($detached === 2,
    'BOTH attachments on one host are detached, not just the first (got ' . $detached . ')');
$check(count($p1Assets) === 2,
    'the other two assets stay in play (got ' . count($p1Assets) . ' of 2)');
$cryptCards = array_map(fn($o) => strval($o->CardID ?? ''), $p1Crypt);
$check(in_array('DOT_171', $cryptCards, true) && in_array('DOT_131', $cryptCards, true),
    'both detached assets are in their owner\'s crypt');
$liveCards = array_map(fn($o) => strval($o->CardID ?? ''), $p1Assets);
$check(in_array('DOT_211', $liveCards, true),
    'NEGATIVE: an asset attached to a DIFFERENT host is untouched');
$check(in_array('DOT_049', $liveCards, true),
    'NEGATIVE: an unattached asset is untouched');
$check(HellbreakDetachAssetsFromHost($p1Minion) === 0,
    'detaching twice is a no-op');

// ---------------------------------------------------------------------------
// 4. DOT_171 Vampire Fangs end to end — "Attached card gains Undead, Vampire, and Bloodlust 1."
//
// This is the whole chain: the asset sits in the Assets zone, HellbreakActiveMacroSourceObjects()
// offers it as a modifier source, and its TraitModifier/KeywordModifier rows answer for the host
// only. Host is DOT_105 (Creature, Fish, no Bloodlust in its text) so every grant discriminates.
// ---------------------------------------------------------------------------
if(!file_exists('./HellbreakSim/GeneratedCode/GeneratedMacroCode.php')) {
    echo 'SKIP: GeneratedMacroCode.php is absent — run zzGameCodeGenerator.php rootName=HellbreakSim' . PHP_EOL;
} else {
    include_once './HellbreakSim/GeneratedCode/GeneratedMacroCode.php';
    include_once './HellbreakSim/Custom/CardLogic.php';

    $host = $makeCharacter('DOT_105', 1, 1);
    $bystander = $makeCharacter('DOT_110', 1, 1);
    $p1Characters = [$host, $bystander];
    $p2Characters = [];
    $fangsInPlay = $makeAsset('DOT_171', 1, intval($host->UniqueID));
    $p1Assets = [$fangsInPlay];

    $check(HellbreakObjectHasTrait($host, 'Undead', 1), 'the host GAINS Undead');
    $check(HellbreakObjectHasTrait($host, 'Vampire', 1), 'the host GAINS Vampire');
    $check(HellbreakObjectKeywordValue($host, 'Bloodlust', 1) === 1, 'the host GAINS Bloodlust 1');
    $check(HellbreakObjectHasTrait($host, 'Fish', 1), 'the host keeps its printed traits');
    $check(!HellbreakObjectHasTrait($host, 'Human', 1),
        'NEGATIVE: the host gains only the two named traits, not any trait');

    $check(!HellbreakObjectHasTrait($bystander, 'Undead', 1),
        'NEGATIVE: another minion does NOT gain Undead');
    $check(!HellbreakObjectHasTrait($bystander, 'Vampire', 1),
        'NEGATIVE: another minion does NOT gain Vampire');
    $check(HellbreakObjectKeywordValue($bystander, 'Bloodlust', 1) === 0,
        'NEGATIVE: another minion does NOT gain Bloodlust');

    // Unattached grants nothing — the state an attach asset is in before its host is chosen.
    $fangsInPlay->AttachedTo = 0;
    $check(!HellbreakObjectHasTrait($host, 'Undead', 1),
        'NEGATIVE: while UNATTACHED the asset grants nothing');
    $check(HellbreakObjectKeywordValue($host, 'Bloodlust', 1) === 0,
        'NEGATIVE: while unattached the keyword grant is off too');
    $fangsInPlay->AttachedTo = intval($host->UniqueID);

    // DURATION: the grant must END when the asset leaves play, not persist on the host.
    $p1Crypt = [];
    HellbreakDetachAssetsFromHost($host);
    $check(!HellbreakObjectHasTrait($host, 'Undead', 1),
        'the grant ENDS when the asset leaves play');
    $check(HellbreakObjectKeywordValue($host, 'Bloodlust', 1) === 0,
        'the keyword grant ends with it');
    $check(HellbreakObjectHasTrait($host, 'Fish', 1),
        'the host still has its own printed traits afterwards');

    // Cross-seat: the pool spans both seats, so the grant must work on an ENEMY host too.
    $enemyHost = $makeCharacter('DOT_105', 2, 1);
    $p2Characters = [$enemyHost];
    $p1Assets = [$makeAsset('DOT_171', 1, intval($enemyHost->UniqueID))];
    $check(HellbreakObjectHasTrait($enemyHost, 'Undead', 2),
        'an asset attached to an ENEMY character still grants to that host');
}

echo PHP_EOL . ($failures === 0 ? "GREEN ({$checks} checks)" : "RED ({$failures} of {$checks} failed)") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
