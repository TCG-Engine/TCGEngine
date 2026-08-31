<?php
// SEC_035
// Cost 5 - Darth Sion - Lord of Pain - [Vigilance,Villainy] - Power 5 - HP 5
// Text: When Played: Give an Experience token to this unit for each enemy unit that was defeated this phase. / When Defeated: If this unit had 7 or more power, return him to his owner's hand.

// SEC_035 Darth Sion — When Played: give an Experience token to him for each enemy unit defeated this
// phase. When Defeated: if he had 7 or more power, return him to his owner's hand.
$whenPlayedAbilities["SEC_035:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $n = GlobalEffectCount(intval($player), 'SWU_ENEMY_DEFEATED');
    for ($i = 0; $i < $n; $i++) DoGiveExperienceToken(intval($player), $mzID);
};

// SEC_035 Darth Sion — When Defeated: if this unit had 7 or more power, return him to his owner's hand.
// His power-at-defeat (Experience subcards included) is snapshotted in CollectWhenDefeatedTriggers while
// the subcards are still attached; here they're already stripped, so we read the snapshot, not the live
// (base-5) power. Mandatory (not "you may").
$whenDefeatedAbilities["SEC_035:0"] = function($player, $mzID) {
    global $playerID, $gSec035DefeatSnapshot;
    // ⚠ DO NOT unset, and fall back to the PERSISTED twin — the third member of the ASH_195 Helgait /
    // JTL_104 Raddus family (2026-08-30). $gSec035DefeatSnapshot dies with the request, and a JTL_002
    // Thrawn / JTL_169 Shadow Caster replay is answered in a LATER one, so the replay saw $snap === null
    // and returned immediately.
    // ⚠ UNLIKE its two siblings this has NO observable consequence today, and there is deliberately no
    // test: on a replay Sion is already in hand, so `_SWUFindDiscardMzID` returns null and the correct
    // behaviour is ALSO "do nothing". The fix is defensive — it keeps the frozen data available so the
    // replay is decided by the discard check (the real gate) rather than by whether a global happened
    // to survive. If this handler ever gains an effect that is not idempotent, the bug becomes live.
    $snap = $gSec035DefeatSnapshot[$mzID] ?? null;
    if ($snap === null) {
        $pk = 'SWU_SEC035_' . str_replace('-', '_', $mzID);
        $pw = intval(GetSWUVar($pk . '_PWR', '0'));
        $ow = intval(GetSWUVar($pk . '_OWN', '0'));
        if ($pw > 0 && $ow > 0) $snap = ['power' => $pw, 'owner' => $ow];
    }
    if ($snap === null || intval($snap['power']) < 7) return;
    $owner = intval($snap['owner']);
    $playerID = $owner;
    $dmz = _SWUFindDiscardMzID($owner, 'SEC_035');
    if ($dmz === null) return;                    // already moved / not in discard → nothing to return
    SWUReturnFromDiscardToHand($owner, $dmz);
};
