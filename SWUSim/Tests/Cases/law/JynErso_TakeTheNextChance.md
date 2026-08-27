# WhenPlayedEitherExpOrExhaust
#// LAW_067 Jyn Erso (2/2) — When Played: either give an Experience token to a unit OR exhaust a unit.
#// Choose Exhaust; exhaust the enemy SOR_046.

## GIVEN
CommonSetup: gyw/bgw/{myResources:2}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_067

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# WhenPlayedGiveExpToEnemyUnit
#// LAW_067 Jyn Erso — the Experience branch can target ANY unit. Choose Experience, then the enemy
#// SOR_046 -> it gains an Experience token (+1/+1 -> 4/8).

## GIVEN
CommonSetup: gyw/bgw/{myResources:2}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_067

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:GiveExperience
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:8

---

# WhenPlayedGiveExpToFriendlyUnit
#// LAW_067 Jyn Erso — Experience branch targeting a friendly unit. The already-seated SEC_080 is
#// myGroundArena-0; it gains an Experience token (+1/+1 -> 4/4).

## GIVEN
CommonSetup: gyw/bgw/{myResources:2}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_067

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:GiveExperience
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4

---

# WhenPlayedGiveExpToItself
#// LAW_067 Jyn Erso — Experience branch can target Jyn herself. She enters play at myGroundArena-1;
#// target her -> she gains an Experience token (+1/+1 -> 3/3).

## GIVEN
CommonSetup: gyw/bgw/{myResources:2}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_067

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:GiveExperience
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_067
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3

---

# WhenPlayedExhaustNoOtherUnitExhaustsSelf
#// LAW_067 Jyn Erso — choosing Exhaust with no other unit in play: Jyn is the only legal target, so she
#// exhausts herself and remains in the ground arena.

## GIVEN
CommonSetup: gyw/bgw/{myResources:2}
WithP1Hand: LAW_067

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_067
P1GROUNDARENAUNIT:0:EXHAUSTED
