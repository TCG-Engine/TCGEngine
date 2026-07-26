<?php
// SEC_087
// Cost 6 - Dedra Meero - With Verifiable Data - [Command,Villainy] - Power 5 - HP 5
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / On Attack: Create a Spy token.

// SEC_087 Dedra Meero — Ambush (auto) + On Attack: create a Spy token.
$onAttackAbilities["SEC_087:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'SEC_T01');
};
