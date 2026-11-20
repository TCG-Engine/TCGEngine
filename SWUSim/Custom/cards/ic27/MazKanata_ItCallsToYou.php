<?php
// IC27_089
// Cost 2 - Maz Kanata - It Calls to You - [Command] - Unit (Ground) 2/1
//   Traits: Underworld - unique
// Text: Hidden (This unit can't be attacked if she was played this phase.)
//       When you resource a card (including during the regroup phase): Give an Experience token to this unit.
//
// Hidden is generated keyword wiring. The trigger is an observer called from EVERY route a card takes into a player's
// resource zone — CR 7.7, plus the 07/20/2026 errata that renamed "put into play as a resource" to "resource", so a ramp
// IS resourcing a card. One call per card resourced (Stockpile's two cards → two tokens). Producers:
//   GameLogic.php — DoResourceCard (the ResourceCard macro), SWUApplyRegroupResource (the regroup step),
//     _SWURampResource (SWURampResourceReady / SWURampResourceExhausted / SWUResourceTopOfDeck: Smuggle and Plot refills,
//     every "resource the top card"), Shd122ResourceSteal (Arquitens Assault Cruiser);
//   card files — Stockpile, Overgrowth, Issue a Command, Display Piece, Expendable Mercenary, Broken Horn,
//     King Grakchawwaa, Osha.
// NOT resourcing: a control change of a resource (SHD_213 DJ), creating a Credit token, the pregame starting resources.
//   • "you" is the seat whose resource zone receives the card — Display Piece's "its controller resources it" triggers
//     the DEFEATED unit's controller's Maz. Only Mazes that seat CONTROLS react (a stolen Maz reacts for its controller).
//   • A Maz that has lost her abilities gets nothing.
//   • The token is given as the card lands. The observer has no choice in it, so the moment it resolves is unobservable.
// Tests: SWUSim/Tests/Cases/ic27/MazKanata_ItCallsToYou.md

function _SWUIc27089OnResourced(int $seat, int $count = 1): void {
    if ($seat <= 0 || $count <= 0) return;
    global $playerID;
    $saved = $playerID;
    $playerID = $seat;
    $mazes = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
            $u = GetZoneObject($mz);
            if (SWUObjGone($u) || ($u->CardID ?? '') !== 'IC27_089' || LostAbilities($u)) continue;
            $mazes[] = $mz;
        }
    }
    foreach ($mazes as $mz) {
        for ($i = 0; $i < $count; $i++) DoGiveExperienceToken($seat, $mz);
    }
    $playerID = $saved;
}
