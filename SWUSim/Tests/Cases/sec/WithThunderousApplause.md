# BuffThenDiscloseBuffAnother
#// SEC_129 With Thunderous Applause (Event, cost 3, Command) — "Give a unit +2/+2 for this phase. You
#//   may disclose Command → give ANOTHER unit +2/+2 for this phase."
#// Two friendly SOR_095 (3/3). Play SEC_129 → buff idx0 (+2/+2 → 5/5) → disclose SEC_080 (Command) →
#// buff the OTHER unit idx1 (+2/+2 → 5/5).

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_129
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1NODECISION

---

# DeclineDisclose_OnlyOneBuff
#// SEC_129 With Thunderous Applause — decline the disclose → only the first +2/+2 applies.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_129
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:POWER:3
P1NODECISION
