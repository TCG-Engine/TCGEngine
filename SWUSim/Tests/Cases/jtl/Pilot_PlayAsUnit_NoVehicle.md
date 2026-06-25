# JTL_035 Tam Ryvora played as a Unit when no empty Vehicle is present.
# With 3 resources (enough for unit cost) and no Vehicle, PlayHand plays it as a unit
# with no additional decision prompt. canPilot = false (no valid vehicles).
# JTL_035: unit cost 3, arena Ground, aspects Vigilance+Villainy.
# Leader SOR_001 (Vigilance+Villainy) + Base SOR_019 (Vigilance) = no aspect penalty.

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
WithP1Resources: 3
WithP1Hand: JTL_035

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_035
P1NODECISION
