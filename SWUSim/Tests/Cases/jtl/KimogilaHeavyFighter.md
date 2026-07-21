# ExhaustsUnitsDamaged
#// JTL_222 Kimogila Heavy Fighter — When Played: 3 indirect to a player; exhaust each unit damaged this
#// way. P1 plays JTL_222 and aims the indirect at P2. P2 assigns all 3 onto its SOR_046 (3/7, survives),
#// which is then exhausted by JTL_222 (it took damage this way).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_222
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:3

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# PartlyToBase_OnlyUnitExhausted
#// JTL_222 Kimogila Heavy Fighter — only UNITS damaged by the indirect are exhausted, not the base. P2
#// splits the 3 indirect as 2 to its SOR_046 and 1 to its base: SOR_046 (damaged this way) is exhausted;
#// the base just takes 1.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_222
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:2,myBase-0:1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:1

---

# SelfTarget_ExhaustFriendlyAndLeader
#// JTL_222 Kimogila Heavy Fighter — the "3 indirect; exhaust each unit damaged this way" can be aimed at
#// YOURSELF. P1 targets itself and spreads 1 each onto a friendly ground unit (SOR_046), a friendly space
#// unit (SOR_237), and the deployed leader (JTL_001, ground index 1). All three are damaged and exhausted
#// this way — including the leader.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myLeader:JTL_001:1:1:1
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_222
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:mySpaceArena-0:1,myGroundArena-0:1,myGroundArena-1:1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:JTL_001
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:1:EXHAUSTED
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:EXHAUSTED

---

# AllToBase_NothingExhausted
#// JTL_222 Kimogila Heavy Fighter — if the whole 3 indirect lands on a base, no unit was "damaged this
#// way", so nothing is exhausted. P2 (the target) puts all 3 on its own base; its SOR_046 stays ready.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_222
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:3

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:READY

---

# AlreadyExhausted_NoOp
#// JTL_222 Kimogila Heavy Fighter — exhausting a unit that is ALREADY exhausted is a harmless no-op. P1
#// self-targets, putting 1 on its base and 2 on its already-exhausted friendly SOR_237: the X-Wing takes
#// the 2 damage and simply stays exhausted.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP1Hand: JTL_222
WithP1SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myBase-0:1,mySpaceArena-0:2

## EXPECT
P1BASEDMG:1
P1SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:EXHAUSTED
