# Decline_ForceOnly
#// LOF_123 Directed by the Force — decline branch: P1 plays the event (gains the Force) but declines the
#// optional "play a unit" — the unit stays in hand and no unit enters play.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:LOF_123,SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# ForceAndPlayUnit
#// LOF_123 Directed by the Force — "The Force is with you. You may play a unit from your hand (paying its
#// cost)." P1 plays the event (gains the Force) then plays SOR_095 (Heroism, cost 3) from hand.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:LOF_123,SOR_095}
P1OnlyActions: true

## WHEN
#// The played event (LOF_123) is marked removed but not compacted until after the effect resolves, so the
#// unit still in hand sits at myHand-1 at the moment the choice is offered.
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
