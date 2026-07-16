# HalvesCost_PlaysExpensiveCheap
#// JTL_105 The Starhawk — While paying costs, you pay half as many resources, rounded up. P1 controls the
#// Starhawk and has only 2 ready resources, yet plays SOR_046 (printed cost 4, on-aspect via SOR_005's
#// Vigilance+Heroism). The halving makes it both AFFORDABLE (need ceil(4/2)=2) and PAID at 2 → 0 left.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_046
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1RESAVAILABLE:0

---

# NoStarhawk_CannotAfford
#// Control for JTL_105: with NO Starhawk in play, 2 ready resources cannot pay SOR_046's printed cost 4,
#// so the play is rejected and SOR_046 stays in hand. (Proves the Starhawk's halving is what enables the
#// cheap play in Starhawk105_HalvesCost_PlaysExpensiveCheap.)

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# HalvesEventCost
#// JTL_105 The Starhawk halves the cost of EVENTS too (rounded up), not just units. P1 controls the
#// Starhawk with only 3 ready resources and plays Vanquish (TWI_077, printed cost 5, Vigilance on-aspect
#// via the b/bw base+leader) — halved to ceil(5/2)=3, so it is both affordable and paid at 3 → 0 left.
#// Vanquish defeats P2's Alliance X-Wing.

## GIVEN
CommonSetup: bbw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: TWI_077
WithP1SpaceArena: JTL_105:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1RESAVAILABLE:0

---

# DoesNotAffectOpponentCost
#// JTL_105 The Starhawk's discount applies only to ITS CONTROLLER's costs, not the opponent's. P1 controls
#// the Starhawk; P2 tries to play SOR_046 (printed cost 4) with only 2 ready resources. P2's cost is NOT
#// halved, so the play is rejected — SOR_046 stays in P2's hand and its resources are untouched.

## GIVEN
CommonSetup: bbw/bbw/{theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 2
WithP2Hand: SOR_046
WithP1SpaceArena: JTL_105:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2RESAVAILABLE:2
