# RegroupDefeat
#// JTL_216 Contracted Hunter — When the regroup phase starts: Defeat this unit. P1 passes to end the
#// action phase; at regroup start the Hunter is defeated and goes to the discard.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_216:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# Ambush_OnPlay
#// JTL_216 Contracted Hunter has Ambush — played from hand (no Unit/Pilot prompt) it may immediately attack
#// an enemy unit. It hits P2's SOR_046 (3/7) for 4 and takes the 3 counter, ending exhausted.

## GIVEN
CommonSetup: yyk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: JTL_216
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_216
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:4
