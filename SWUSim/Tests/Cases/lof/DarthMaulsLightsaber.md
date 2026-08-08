# DarthMaulAttack
#// LOF_140 Maul's Lightsaber (+4/+2) — When Played: if the attached unit is Darth Maul, you may attack with
#// him; for this attack he gains Overwhelm and can't attack bases. Attached to TWI_135 (Darth Maul, 5/6 →
#// 9/8), he attacks SOR_059 (1/3): 9 power kills it and the 6 excess overwhelms onto the base; the 1 counter
#// hits Maul.

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_140}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:6
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# DeclineBonusAttack
#// LOF_140 — attached to Darth Maul (TWI_135, boosted to 9/8), the player MAY decline the bonus attack.
#// Declining leaves Maul undamaged and deals nothing; the lightsaber's +4/+2 still applies (power 9).

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_140}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:0:POWER:9
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P2GROUNDARENACOUNT:1

---

# DeclineThenNormalAttack_NoOverwhelm
#// LOF_140 — the Overwhelm + can't-attack-bases only apply to the bonus attack. If the player declines it and
#// then attacks normally, Maul (9/8) kills SOR_059 (3 HP) but the 6 excess does NOT overwhelm to the base.

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_140}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)
- P1>AnswerDecision:PASS
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# AttachToNonMaul_NoBonusAttack
#// LOF_140 — attached to a non-Maul unit (SOR_046, 3/7 → 7/9), the When-Played bonus attack does NOT trigger
#// (no prompt), so no attack occurs. The +4/+2 still applies (power 7).

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_140}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P2BASEDMG:0
P2GROUNDARENACOUNT:1

---

# AttachToMaulTitled_NoBonusAttack
#// LOF_140 — the bonus attack requires the EXACT title "Darth Maul". SHD_090 is titled just "Maul" (7/6 →
#// 11/8), so no bonus attack triggers. The +4/+2 still applies (power 11).

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_140}
P1OnlyActions: true
WithP1GroundArena: SHD_090:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0    # attach to the friendly host (enemy is now a legal host too, CR 2.e)

## EXPECT
P1GROUNDARENAUNIT:0:POWER:11
P2BASEDMG:0
P2GROUNDARENACOUNT:1

---

# BonusAttack_CannotTargetBases
#// LOF_140 Maul's Lightsaber — the bonus attack grants Overwhelm but also "CAN'T ATTACK BASES", so with an
#// enemy unit present the enemy base must not be an offered target for that attack. Attached to Darth Maul
#// (TWI_135, 5/6 → 9/8); the selectable targets are exactly the two enemy units, never theirBase-0.
#// (Two enemy units on purpose: with one the attack target auto-resolves and there is no offer to check.)
## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_140}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1SELECTABLENOT:theirBase-0
