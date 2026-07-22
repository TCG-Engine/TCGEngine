# OppDiscardUnit_Exhaust
#// JTL_201 Ahsoka Tano — When Played: An opponent discards a card; if it's a unit, you may exhaust a unit.
#// P2's only card (the unit SOR_095) is discarded, so P1 exhausts P2's SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# DiscardNonUnit_NoExhaust
#// JTL_201 Ahsoka Tano — the exhaust follows only if the discarded card is a UNIT. P2's only card is an
#// event (JTL_176 Shoot Down), so it is discarded but no exhaust is offered (no decision), and P2's SOR_046
#// stays ready.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2Hand: JTL_176
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# EmptyHand_NoDiscard
#// JTL_201 Ahsoka Tano — with the opponent's hand empty there is nothing to discard, so no exhaust option
#// arises either.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_201
WithP1Resources: 9
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:READY
