# ThreeExhausted_Shield
#// JTL_199 Blade Squadron B-Wing — When Played: If another player controls 3+ exhausted units, give a
#// Shield token to a unit. P2 has 3 exhausted units, so P1 shields the newly-played JTL_199.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_199
WithP1Resources: 3
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_095:0:0
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_199
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# TwoExhausted_NoShield
#// JTL_199 Blade Squadron B-Wing — with only 2 exhausted enemy units the condition is not met, so no
#// Shield is granted (no decision pending).

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_199
WithP1Resources: 3
WithP2GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_199
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION
