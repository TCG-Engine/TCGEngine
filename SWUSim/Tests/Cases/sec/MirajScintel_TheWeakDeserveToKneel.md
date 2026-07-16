# NoOverwhelm_WithoutMiraj
#// SEC_139 negative guard — without Miraj in play, the same attack has no Overwhelm (no base overflow).

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:6

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1NODECISION

---

# Overwhelm_OverflowVsDamaged
#// SEC_139 Miraj Scintel — "While a friendly unit is attacking a damaged unit, the attacker gains
#//   Overwhelm." With SEC_139 in play, SOR_095 (3 power) attacks the damaged SOR_046 (1 remaining HP) →
#//   kills it, 2 excess overflows to P2's base.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_139:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:6

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1NODECISION

---

# WhenPlayed_Deal3Undamaged
#// SEC_139 Miraj Scintel (Ground, 3/7) — When Played: you may deal 3 to an UNDAMAGED unit. Hits the
#//   undamaged enemy SOR_046.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_139

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
