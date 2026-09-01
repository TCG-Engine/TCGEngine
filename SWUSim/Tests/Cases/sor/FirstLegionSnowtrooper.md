# VsDamaged_Overwhelm
#// SOR_130 First Legion Snowtrooper (2/3) — the Overwhelm half. Attacking a DAMAGED
#// low-HP enemy: P2's Battlefield Marine (SOR_095, 3/3) starts with 2 damage. The
#// Snowtrooper attacks at 2+2 = 4 power → Marine's damage becomes 2+4 = 6 vs printed
#// HP 3 → defeated with 3 excess, which Overwhelm spills to P2's base (3 damage).
#// (Both units die in the exchange; base takes the overflow.)
#// COVERAGE: offer=N/A (a purely static combat modifier — the condition is evaluated from the
#//           declared defender at damage time and no decision or candidate pool is ever built; the
#//           attack-target pool itself is untouched by this card) ·
#//           decline=N/A (no "you may" and no cost — both halves are mandatory while the gate holds) ·
#//           control=StolenSnowtrooper_BuffAndOverwhelmFollowTheController (owner ≠ controller: the
#//           gate reads the CONTROLLER's enemy, and the Overwhelm excess spills to the controller's
#//           opponent's base while the body goes to the owner's discard) ·
#//           boundary pair=VsUndamaged_NoBuffNoOverwhelm (0 damage → printed 2, no spill) vs
#//           Boundary_OneDamageArmsBothHalves (1 damage → 4 and the excess spills); scope exclusions in
#//           WhileDefending_NoBuff (attacking only) and VsDamagedBase_NoBuff / VsUndamagedBase_
#//           DealsPrintedTwo (a damaged BASE is not a damaged unit) ·
#//           reqboundary=HealedToUndamaged_NoBuff (the gate is re-read at attack time across three
#//           separate serialized actions — heal play, heal target, attack — never cached from setup)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_130:1:0      # First Legion Snowtrooper (2/3)
WithP2GroundArena: SEC_080:1:2      # Battlefield Marine (3/3) with 2 damage

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0

---

# VsDamaged_PowerBuff
#// SOR_130 First Legion Snowtrooper (2/3) — "While attacking a damaged unit, this
#// unit gets +2/+0 and gains Overwhelm." Attacking a DAMAGED high-HP enemy that
#// survives isolates the +2 power: P2's Consular Security Force (SOR_046, 3/7) starts
#// with 1 damage → Snowtrooper deals 2+2 = 4 → its damage becomes 1+4 = 5 (HP 7, lives).
#// (Snowtrooper takes the 3 counter-damage and dies; no Overwhelm since the defender lives.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_130:1:0      # First Legion Snowtrooper (2/3)
WithP2GroundArena: SOR_046:1:1      # Consular Security Force (3/7) with 1 damage

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:0

---

# VsUndamaged_NoBuffNoOverwhelm
#// SOR_130 First Legion Snowtrooper (2/3) — the NEGATIVE that makes both halves of the gate
#// load-bearing. The same 3/3 Imperial Dark Trooper as VsDamaged_Overwhelm, but UNDAMAGED: the
#// Snowtrooper attacks at its printed 2 (not 4) and has no Overwhelm, so the Trooper survives at 2
#// damage and P2's base takes nothing. The Snowtrooper still eats the 3 counter-damage and dies.
#// Without this control, an engine that always granted +2/+0 and Overwhelm would pass both existing
#// sections.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_130:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:0

---

# Boundary_OneDamageArmsBothHalves
#// SOR_130 First Legion Snowtrooper — the N-vs-N-1 boundary of "a DAMAGED unit": exactly ONE damage
#// counter is enough. Same 3/3 Trooper as VsUndamaged_NoBuffNoOverwhelm with a single point on it, so
#// the Snowtrooper swings for 2+2 = 4 into 2 remaining HP — the Trooper dies and the 2 excess spills to
#// P2's base through the granted Overwhelm. The pair (0 damage → 2 power / no spill, 1 damage →
#// 4 power / 2 spilled) pins the threshold at one, not two.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_130:1:0
WithP2GroundArena: SEC_080:1:1

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0

---

# VsDamagedBase_NoBuff
#// SOR_130 First Legion Snowtrooper — "while attacking a damaged UNIT". A damaged BASE is not a damaged
#// unit: P2's base already carries 5 damage and there is nothing on P2's board, so the Snowtrooper
#// attacks the base directly and must deal exactly its printed 2 (5 → 7), never 4. This is the scope
#// exclusion a condition written as "while attacking something damaged" would get wrong.
#// ⚠ CURRENTLY RED — candidate engine bug (see VsUndamagedBase_DealsPrintedTwo, the passing control
#// on the identical board with a clean base): the base takes 9, i.e. the +2 is applied to a base
#// attack. Left red deliberately; the assertion states the intended rule.

## GIVEN
CommonSetup: rrk/rrk/{theirBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SOR_130:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# WhileDefending_NoBuff
#// SOR_130 First Legion Snowtrooper — "while ATTACKING a damaged unit". The condition names the
#// Snowtrooper's own attack, so it grants nothing when the Snowtrooper is the DEFENDER, even against an
#// attacker that is itself damaged. P2's Consular Security Force (3/7) carries 2 damage and attacks
#// into the Snowtrooper: the Snowtrooper deals back its printed 2 (2+2 = 4 on the Force, not 6) and
#// dies to the 3. An implementation that read "a damaged unit is involved in this combat" would put 6
#// on the Force here.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1GroundArena: SOR_130:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:0
P1BASEDMG:0

---

# HealedToUndamaged_NoBuff
#// SOR_130 First Legion Snowtrooper — the gate is re-read at attack time, not remembered from earlier in
#// the phase. P2's Consular Security Force (3/7) starts on 3 damage, then P2 plays Repair (SOR_074) on
#// it to bring it back to 0. P1's follow-up attack therefore sees an UNDAMAGED unit and deals only its
#// printed 2. The three steps — heal target choice, heal, attack — are separate serialized requests, so
#// this is also the request-boundary reading of the condition.

## GIVEN
CommonSetup: rrk/bbw/{theirResources:1;theirhandCardIds:SOR_074}
WithActivePlayer: 2
WithP1GroundArena: SOR_130:1:0
WithP2GroundArena: SOR_046:1:3

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0
P1GROUNDARENACOUNT:0

---

# StolenSnowtrooper_BuffAndOverwhelmFollowTheController
#// SOR_130 First Legion Snowtrooper — the CONTROL axis, both readings at once. The Snowtrooper is OWNED
#// by P1 but CONTROLLED by P2 (the end state after a take-control effect). "a damaged unit" is read
#// from the attacker's CONTROLLER, so P1's own damaged Dark Trooper now arms the buff, and the reminder
#// text's "the opponent's base" is the opponent of the CONTROLLER — P1's base — which takes the excess.
#// 3/3 Trooper on 2 damage, hit for 2+2 = 4: 1 remaining HP absorbed, 3 spilled to P1's base. The
#// Snowtrooper dies to the 3 counter-damage and goes to its OWNER's (P1's) discard alongside it.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:2
WithP2GroundArenaControlled: SOR_130:1

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1BASEDMG:3
P2BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# VsUndamagedBase_DealsPrintedTwo
#// SOR_130 First Legion Snowtrooper — the PASSING CONTROL for VsDamagedBase_NoBuff. Identical board and
#// identical action, with the only difference being that P2's base is clean: the Snowtrooper deals its
#// printed 2. The pair isolates the base's damage as the single variable, so the sibling section's
#// result cannot be blamed on the fixture, the arena, or the attack-target form.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_130:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:DAMAGE:0
