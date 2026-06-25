# JTL_035 (Tam Ryvora) in hand: pilot cost (2) affordable but unit cost (3) is not.
# Verifies that the card appears in pilotPlayableHand (pilot-glow list) even though it is
# not affordable as a unit.
# JTL_035: unit cost 3, piloting cost 2, aspects Vigilance+Villainy.
# Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.
# SOR_225 TIE/ln Fighter: Vehicle in space, no existing Pilot upgrade → eligible target.
# WithP1Resources: 2 → 2 ready: pilot cost 2 affordable, unit cost 3 NOT.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SOR_001;
  myBase:SOR_019;
  theirLeader:SOR_001;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1Hand: JTL_035
WithP1SpaceArena: SOR_225:1:0

## WHEN

## EXPECT
P1HANDPILOTPLAYABLE:0
