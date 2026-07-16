# BaseHit_EachPlayerDiscards
#// SEC_147 Chopper (Ground, 4/1) — When this unit deals combat damage to a base: each player discards a
#//   card from their hand. SEC_147 hits P2's base for 4; P1 has exactly 1 card (auto-discards), P2 has 2
#//   and chooses one to discard.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_147:1:0
WithP1Hand: SOR_095
WithP2Hand: SOR_095
WithP2Hand: SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:0
P2HANDCOUNT:1
