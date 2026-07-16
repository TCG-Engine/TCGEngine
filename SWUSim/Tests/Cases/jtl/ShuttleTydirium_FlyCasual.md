# OnAttack_EvenCost_NoExp
#// JTL_200 Shuttle Tydirium — if the milled card has an even cost, no Experience is offered. Deck top
#// SOR_095 (cost 2, even) is milled; no decision follows.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_200:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P2BASEDMG:2
P1NODECISION

---

# OnAttack_OddCost_Exp
#// JTL_200 Shuttle Tydirium — On Attack: Discard a card from your deck. If it has an odd cost, you may
#// give an Experience token to another unit. Deck top SOR_225 (cost 1, odd) is milled, so P1 gives an
#// Experience (+1/+1) to SOR_095 (3/3 → 4/4). JTL_200 attacks the base for 2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_200:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_225

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P2BASEDMG:2
