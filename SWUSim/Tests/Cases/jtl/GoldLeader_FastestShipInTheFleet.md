# Defending_AttackerMinusOne
#// JTL_054 Gold Leader — While this unit is defending, the attacker gets -1/-0. P2's SOR_237 (power 2)
#// attacks Gold Leader (5/5); the attacker's power is reduced to 1, so Gold Leader takes only 1 damage,
#// and its counter (5) defeats SOR_237.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: JTL_054:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_054
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENACOUNT:0
