# EntersExhausted_UniqueOnly
#// LAW_223 Rose Tico — guard: controlling only a UNIQUE unit (SOR_181 Jabba the Hutt) does NOT satisfy
#// "a non-unique unit", so Rose enters EXHAUSTED (proves the rule is non-unique, not any-unit).

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP1Hand: LAW_223

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_223
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# EntersReady_WithNonUnique
#// LAW_223 Rose Tico (5/5 ground, Resistance) — "If you control a non-unique unit, this unit enters play
#// ready." P1 controls SEC_080 (non-unique) → Rose (played at index 1) enters READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_223

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_223
P1GROUNDARENAUNIT:1:READY

---

# EntersExhausted_NoUnits
#// LAW_223 Rose Tico — controlling NO units at all does not satisfy "a non-unique unit", so Rose enters
#// EXHAUSTED. Played into an empty board (P2 has a unit, but that is not friendly).

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_223

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_223
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# EntersReady_NonUniqueSpaceUnit
#// LAW_223 Rose Tico — the non-unique unit can be in EITHER arena. Controlling only SOR_178 Cartel Spacer
#// (a non-unique SPACE unit) still lets Rose (a ground unit) enter play READY.

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
P1OnlyActions: true
WithP1SpaceArena: SOR_178:1:0
WithP1Hand: LAW_223

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_223
P1GROUNDARENAUNIT:0:READY
