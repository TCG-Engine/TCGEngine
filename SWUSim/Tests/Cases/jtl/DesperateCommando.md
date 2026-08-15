# WhenDefeated_Debuff
#// JTL_060 Desperate Commando — When Defeated: You may give a unit -1/-1 for this phase. JTL_060 (2/2)
#// attacks SOR_046 and is defeated by the counter; its When Defeated gives SOR_046 -1/-1 (power 2, HP 6).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_060:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6

---

# SimulateRequestBoundary_WhenDefeatedDebuffTarget
#// JTL_060 Desperate Commando — the optional When Defeated "-1/-1 for this phase" target choice ends the
#// request in production, and it is offered by a unit that is ALREADY GONE from the arena, so the answer
#// arrives in a fresh process with no in-memory trace of the defeated source. The pending offer and the
#// phase-duration debuff it writes must both survive serialization. Mirrors WhenDefeated_Debuff with the
#// boundary inserted before the answer: SOR_046 still ends at 2/6.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_060:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
