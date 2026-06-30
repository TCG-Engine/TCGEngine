# JTL_035 (Tam Ryvora) in hand: pilot cost (2) affordable but unit cost (3) is not, NO Vehicle present.
# Verifies that the card does NOT appear in pilotPlayableHand when there is no eligible
# Vehicle target, even though the pilot cost is affordable.
# JTL_035: unit cost 3, piloting cost 2, aspects Vigilance+Villainy.
# Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.
# No units in either arena → SWUGetPilotValidTargets returns empty → no glow.

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

## WHEN

## EXPECT
P1HANDPILOTPLAYABLENOT:0
