# FighterAttack_DiscardSelfDamage
#// JTL_156 Trench Run — Attack with a Fighter; +4/+0 and granted On Attack: discard 2 from the defender's
#// deck, deal the cost difference (unpreventable) to this unit. SOR_237 (2 power) gets +4 → 6, mills
#// SOR_225(cost 1)/SOR_237(cost 2) from P2's deck (diff 1 → 1 self-damage), then hits P2's base for 6.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_156
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2Deck: SOR_225
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:6
P1SPACEARENAUNIT:0:DAMAGE:1
P2DECKCOUNT:0
