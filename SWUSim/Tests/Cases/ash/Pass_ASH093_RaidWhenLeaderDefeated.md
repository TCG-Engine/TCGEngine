# ASH_093 Captain Pellaeon (Ground, 2/4) — While a leader unit has been defeated this phase, he gains
# Raid 3. P1's SOR_046 attacks and defeats P2's deployed Iden Versio (4/4, pre-damaged to 3 HP); then
# ASH_093 attacks the enemy base with Raid 3 → 2 + 3 = 5 damage.
## GIVEN
CommonSetup: brw/bbk/{
  theirLeader:SOR_002:1:1:0:1;
  myLeader:SOR_013;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1GroundArena: ASH_093:1:0
WithP1GroundArena: SOR_046:1:0
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
## WHEN
- P1>AttackGroundArena:1:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
