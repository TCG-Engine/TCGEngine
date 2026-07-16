# DeclineDisclose_NoSpyTokens
#// SEC_141 The Galleon — decline the disclose → no Spy tokens created.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SEC_141
WithP1Hand: SEC_133
WithP1Hand: SEC_133

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION

---

# WhenPlayed_Disclose_ThreeSpyTokens
#// SEC_141 The Galleon (Space, 6/6, Aggression/Villainy, cost 7) — When Played: you may disclose
#//   AggressionAggressionVillainy → create 3 Spy tokens (Spy = SEC_T01, a Ground token unit).
#// Disclose two SEC_133 (Aggression,Villainy each → cover AggAggVillainy) → 3 Spy tokens in the ground arena.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SEC_141
WithP1Hand: SEC_133
WithP1Hand: SEC_133

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_141
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION
