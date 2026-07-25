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

---

# ConstantOverwhelm_UndamagedTarget_NoOverflow
#// SEC_139 Miraj Scintel — the granted Overwhelm applies only while attacking a DAMAGED unit. With Miraj
#// in play, SOR_095 (3 power) attacks the UNDAMAGED SOR_128 (3/1): it dies, but because the defender was
#// undamaged no Overwhelm is granted, so the 2 excess damage is NOT dealt to P2's base.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_139:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1NODECISION

---

# MultiDefender_DamagedTarget_OverwhelmSpills
#// SEC_139 Miraj Scintel — the constant Overwhelm grant applies on a MULTI-defender attack too. Darth Maul
#//   (TWI_135, 5 power) attacks two enemies — one PRE-DAMAGED (Battlefield Marine 3/3 with 2 damage → 1 HP)
#//   and one undamaged (Vanguard Infantry 1/2). Because Maul is attacking a damaged unit, Miraj grants him
#//   Overwhelm, so the combined excess (4 + 3 = 7) spills to the opponent's base.
#//   (Regression: the Maul double-combat path previously ignored Miraj's conditional Overwhelm grant → 0.)

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: [TWI_135:1:0 SEC_139:1:0]
WithP2GroundArena: [SOR_095:1:2 SOR_108:1:0]
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2BASEDMG:7
P2GROUNDARENACOUNT:0

---

# MultiDefender_NoDamagedTarget_NoOverwhelm
#// SEC_139 Miraj Scintel — control: if NEITHER defender is damaged, Maul is not "attacking a damaged unit",
#//   so Miraj grants no Overwhelm and the excess is lost (base takes 0). Both Battlefield Marines (3/3) are
#//   undamaged; Maul defeats both but nothing spills.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: [TWI_135:1:0 SEC_139:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:0
