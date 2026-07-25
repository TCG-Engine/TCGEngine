# BuffForceUnit
#// LOF_165 Asajj Ventress — When Played/On Attack: give another friendly Force unit +2/+0 for this phase.
#// On attack she buffs Plo Koon (6 → 8 power).

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_165:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:POWER:8

---

# WhenPlayed_BuffForceUnit
#// LOF_165 Asajj Ventress — the WHEN PLAYED half of "When Played/On Attack: give another friendly Force unit
#// +2/+0 for this phase". Played from hand with a Force unit (LOF_050 Plo Koon 6/8) and a non-Force unit
#// (SOR_095) present, only the Force unit is selectable and it becomes 8 power.

## GIVEN
CommonSetup: rrk/ggw/{myResources:5;handCardIds:LOF_165}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8

---

# NoOtherForceUnit_NoTrigger
#// LOF_165 Asajj Ventress — "does nothing if no other friendly Force units are in play". Played with only a
#// non-Force unit (SOR_095 Battlefield Marine) on the ground, the ability finds no legal target and does not
#// prompt; the Marine's stats are unchanged. No prompt appears.

## GIVEN
CommonSetup: rrk/ggw/{myResources:5;handCardIds:LOF_165}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:POWER:3
