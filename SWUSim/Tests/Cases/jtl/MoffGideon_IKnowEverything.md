# BaseDamage_TaxesOpponent
#// JTL_188 Moff Gideon — When this unit deals combat damage to an opponent's base, each unit that
#// opponent plays this phase costs 1 more. Gideon hits P2's base, then P2's JTL_069 (cost 5) costs 6, so
#// P2's 6 resources are exactly consumed.

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirLeader:JTL_004;
  theirBase:JTL_019
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: JTL_188:1:0
WithP2Hand: JTL_069
WithP2Resources: 6

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2RESAVAILABLE:0
P2BASEDMG:5
