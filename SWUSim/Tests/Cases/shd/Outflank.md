# Outflank_AttackWithTwoUnits
#// SHD_128 Outflank — "Attack with 2 units (one at a time)." P1 has two ready ground units (SOR_046 3
#// power, SOR_095 3 power) and P2 has no units, so both attacks hit the base: 3 + 3 = 6. Only the first
#// attacker is chosen; the second auto-resolves (lone remaining unit, base-only).
#// COVERAGE: offer=Outflank_AttackWithTwoUnits (two ready units → a real attacker pick, and the second
#//           iteration auto-resolves once only one is left) vs Outflank_OnlyOneUnitAvailable_SingleAttack
#//           (one ready unit → the whole effect auto-resolves, P1NODECISION) ·
#//           decline=N/A ("Attack with 2 units" is mandatory — there is no choose-nothing branch, and a
#//           lone legal attacker auto-resolves rather than offering a refusal) ·
#//           boundary=1 unit available → 1 attack (3 base damage) vs 2 units available → 2 attacks
#//           (6 base damage); the same two sections ·
#//           control=N/A (one-shot event, nothing persistent is created) ·
#//           reqboundary=N/A (both attacks resolve inside the single play action)

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_128
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:6

---

# Outflank_OnlyOneUnitAvailable_SingleAttack
#// SHD_128 Outflank — "Attack with 2 units" is a maximum, not a requirement: with only ONE friendly unit
#// on the board the event still resolves, that unit makes its single attack, and the missing second
#// attack neither stalls the effect nor raises a decision. SOR_046 (3 power) hits the undefended enemy
#// base for 3 and ends exhausted; if a second attack had somehow been squeezed out of the same unit the
#// base would show 6.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_128
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
P1DISCARDCOUNT:1
