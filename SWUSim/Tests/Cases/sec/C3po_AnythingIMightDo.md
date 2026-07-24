# Action_BounceSelf_BuffUnit
#// SEC_093 C-3PO (Ground, 1/3) — Action [Exhaust, return this unit to its owner's hand]: Give a unit
#//   +2/+2 for this phase. C-3PO (idx 0) returns to hand; the only remaining unit SEC_041 (1/4 → 3/6)
#//   auto-resolves as the +2/+2 target.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_093:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_041
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6

---

# Action_CanBuffEnemyUnit
#// SEC_093 C-3PO — "Give a unit +2/+2" can target ANY unit, friendly or enemy. C-3PO (idx0) returns to
#//   hand; with both a friendly SOR_095 and an enemy SOR_164 present, the +2/+2 is applied to the enemy
#//   SOR_164 Wampa (4/5 → 6/7).

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_093:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:7
P1NODECISION

---

# Action_Unavailable_WhenExhausted
#// SEC_093 C-3PO — the ability costs Exhaust, so an already-exhausted C-3PO cannot use it. Attempting to
#//   use it does nothing: C-3PO stays in the ground arena (not returned to hand) and nothing is buffed.

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_093:0:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
