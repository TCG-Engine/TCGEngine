# OnAttack_StripsForce
#// LOF_033 Nameless Terror — On Attack: each enemy unit loses the Force trait for this phase.
#// LOF_033 (3/3 Creature) attacks the P2 base, stripping Force from Plo Koon (LOF_050, a Force unit).
#// Then SOR_095 (3+1 LOF_090 = 4) attacks Plo Koon: LOF_090's "+2 vs a Force unit" no longer applies
#// (Plo Koon lost Force this phase), so it deals 4 instead of 6 — proving the trait suppression.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_090
WithP1GroundArena: LOF_033:1:0
WithP2GroundArena: LOF_050:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# WhenPlayedExhaust
#// LOF_033 Nameless Terror — When Played: You may exhaust a Force unit. P1 plays it and exhausts Plo Koon.
#// (The On Attack "enemy units lose the Force trait" half is deferred — trait suppression infra.)

## GIVEN
CommonSetup: bbk/ggw/{myResources:3;handCardIds:LOF_033}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
