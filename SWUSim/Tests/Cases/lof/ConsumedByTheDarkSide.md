# ExpThenDamage
#// LOF_239 Consumed by the Dark Side — Give 2 Experience tokens to a unit, then deal 2 damage to it. SOR_046
#// (3/7) becomes 5/9 from the Experience, then takes 2 damage.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_239}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# NoUnitsOnBoard_PlaysWithNoEffect
#// LOF_239 Consumed by the Dark Side — playable even with NO units on the board: there is nothing to give
#// Experience to or damage, so the event resolves with no effect. It still costs 2 (P1 goes to 0 available)
#// and lands in P1's discard. Ref: "should be playable even if there's no units on the board — Play
#// anyway".

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_239}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
