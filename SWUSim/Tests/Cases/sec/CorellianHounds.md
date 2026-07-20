# EntersExhausted_OppHasGround
#// SEC_170 Corellian Hounds — when the opponent DOES control a ground unit, SEC_170 enters play
#//   exhausted (the default).

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EntersReady_NoOppGround
#// SEC_170 Corellian Hounds (Ground, 5/5, Creature, cost 5) — "If an opponent controls no ground
#//   units, this unit enters play ready." P2 has no ground units → SEC_170 enters ready.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SEC_170

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
