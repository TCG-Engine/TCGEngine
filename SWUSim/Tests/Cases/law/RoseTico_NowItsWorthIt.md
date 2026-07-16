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
