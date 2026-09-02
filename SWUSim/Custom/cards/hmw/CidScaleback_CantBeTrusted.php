<?php
// HMW_197
// Cost 2 - Cid Scaleback - Can't Be Trusted - [Cunning][Villainy] - Unit, Ground - Power 2 - HP 2
// Traits: Underworld - Unique
// Text: When Played: An opponent chooses a unit they control. Give a Weakness token to it.
//
// The structural twin is LAW_216 Jabba's Rancor — "An opponent chooses a ground unit they control" —
// and this follows its shape exactly, minus the caster-side "you may" it hangs off the end. Two
// player-facing steps in two different queues:
//   1. the CASTER names WHICH opponent (an "an opponent" reference is a CHOICE, never OtherPlayer());
//   2. that OPPONENT names a unit THEY control, and it takes a Weakness token.
//
// ⚠ TWIN SUNS. "An opponent" is a player reference, so this card is a 3–4 seat card and a two-seat
// shortcut would be a live bug, not future work: `OtherPlayer($p)` answers 2 for seat 1 and 1 for
// EVERY other seat, so a hardcoded version would weaken P2 whoever the caster named.
// `SWUQueueChooseOpponent` auto-resolves to an invisible PASSPARAMETER at one eligible opponent, so
// Premier stays byte-identical — no new two-player prompt.
//
// ⚠ ELIGIBILITY IS A REAL FILTER, and that is the one judgement call on this card (HMW is a preview
// set, so it is not in card-specific-rulings.md). The doctrine splits on what the effect ASKS of the
// chosen seat. Where something is done TO an opponent, a seat that cannot be affected must STAY on the
// menu — aiming at them can be the caster's best line (TS26_43, TWI_222). Where the chosen player has
// to ACT ON THEIR OWN BOARD, an opponent who cannot make the demanded choice would be choosing among
// nothing and must be filtered off. Cid is the second shape, identically to LAW_216: the opponent
// CHOOSES. So an opponent controlling no units never appears on the menu, and if NOBODY controls a
// unit there is no menu at all.
//   Counter-argument, recorded because it is not silly: in a free-for-all, naming a unit-less opponent
//   would be a way to decline an effect the card gives no "you may" for. I took the LAW_216 reading
//   (no no-op prompts). TwinSuns_OpponentWithNoUnitsIsNotOffered is the single section that changes if
//   this is ever ruled the other way.
//
// ⚠ "a unit they control" is UNQUALIFIED — no "non-leader", no arena — so the opponent's pool is both
// their arenas and includes token units and deployed LEADER units. That is `AnyUnitFilter`, which is
// what `SWUOpponentChoosesOwnUnit(..., nonLeader: false, ...)` uses.
//
// ⚠ The When Played must reach the cross-player work through an intermediate CUSTOM: `DispatchTrigger`
// restores $playerID after the trigger closure returns, so a relative-mzID decision queued for the
// opponent inline would be counted in the wrong frame. The closure only queues the caster's own seat
// picker (label strings, no mzIDs, so it is safe there); everything frame-sensitive happens in #0,
// which `ExecuteStaticMethods` does NOT $playerID-restore.

$whenPlayedAbilities["HMW_197:0"] = function ($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    // Which opponents can actually make the choice? Read each seat's OWN board.
    // ⚠ FLIPPED FRAME: $playerID is set to the opponent, so "myGroundArena"/"mySpaceArena" here read
    // THAT SEAT's arenas. Do NOT swap these for the friendly-unit helpers — in Team Suns those fan out
    // to the opponent's TEAMMATE and would wrongly make a unit-less seat eligible off its partner's
    // board. The card says "a unit THEY control", which is this seat and no other.
    // ⚠ MEASURED: the `OpponentsOf` here is NOT what keeps a Team Suns TEAMMATE off the menu —
    // SWUQueueChooseOpponent intersects whatever list it is handed with its OWN OpponentsOf($chooser),
    // so widening this loop to "every live seat but me" leaves the whole suite green. It stays because
    // it is the semantically right thing to iterate (and cheaper), NOT as a guard. Read
    // TeamSuns_TeammateIsNotOnTheMenu as covering the shared helper, not this line.
    $eligible = [];
    foreach (OpponentsOf(intval($player)) as $o) {
        $saved = $playerID;
        $playerID = intval($o);
        $units = array_merge(
            ZoneSearch('myGroundArena', AnyUnitFilter),
            ZoneSearch('mySpaceArena',  AnyUnitFilter)
        );
        $playerID = $saved;
        if (!empty($units)) $eligible[] = intval($o);
    }
    if (empty($eligible)) return;   // nobody can choose ⇒ no offer at all, on either queue
    SWUQueueChooseOpponent(intval($player), "HMW_197#0",
        "Choose_an_opponent_to_weaken_a_unit", $eligible);
};

// #0 — the caster's seat pick has landed ("P{n}" in $lastDecision). Hand the unit choice to that
// opponent. SWUOpponentChoosesOwnUnit builds the pool under THEIR frame, auto-resolves at a single
// unit, otherwise queues the MZCHOOSE on THEIR queue, and leaves $playerID = them so MZCountChoices
// validates the relative mzIDs in the frame that minted them.
$customDQHandlers["HMW_197#0"] = function ($player, $parts, $lastDecision) {
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === intval($player)) return;
    // GIVE_WEAKNESS is the shared "attach HMW_T02 to $lastDecision" handler; it runs with $player set
    // to the CHOOSER, so the opponent-frame mzID it receives is resolved in the opponent's frame. It
    // also runs the shrink sweep, which the −1 HP needs: a stat reduction to 0 remaining HP has no
    // state-based defeat of its own.
    SWUOpponentChoosesOwnUnit(intval($player), false,
        "Choose_a_unit_you_control_to_take_a_Weakness_token", "GIVE_WEAKNESS", $opp);
};
