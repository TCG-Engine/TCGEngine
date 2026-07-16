# Action_NoEventInHand_NoOp
#// SOR_177 Bib Fortuna — the Action plays only an EVENT (not a unit). Here the hand holds
#// a UNIT (SOR_095, Battlefield Marine), no events, with resources to spare. The action has
#// no legal play, so it is a full no-op: Bib stays READY (action not spent), the unit stays
#// in hand, resources unchanged, no decision pending. Guards the event-only type filter
#// (distinguishing Bib from Alliance Dispatcher SOR_093, which plays a unit).

## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0    # Bib Fortuna (ready) — index 0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1
P1RESAVAILABLE:3
P1NODECISION

---

# Action_PlaysEventDiscounted
#// SOR_177 Bib Fortuna (1/3, Shielded) — Action [Exhaust]: Play an EVENT from your hand.
#// It costs 1 resource less. Host is the only friendly unit (idx 0, ready). Hand holds
#// Surprise Strike (SOR_220, Cunning, cost 2). With exactly 1 ready resource the event is
#// played ONLY because of the −1 discount (2 → 1); it then fizzles harmlessly ("attack with
#// a unit" finds no ready friendly unit — the host just exhausted itself) and goes to the
#// discard. The single resource is spent and Bib is exhausted. Single hand event → auto.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0    # Bib Fortuna (ready) — index 0, only friendly unit

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DISCARDCOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0
