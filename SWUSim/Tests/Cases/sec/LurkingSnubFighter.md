# WhenPlayed_Decline
#// SEC_189 — the exhaust is a "may". Declining leaves the enemy SOR_046 ready.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:READY

---

# WhenPlayed_ExhaustUnit
#// SEC_189 Lurking Snub Fighter (Space, 2/3, cost 3) — When Played: you may exhaust a unit. P1 plays it
#//   and exhausts the enemy SOR_046.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
