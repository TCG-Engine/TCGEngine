# ExhaustedDefender_GetsMinusFour
#// IC27_157 The Mandalorian — From the Shadows (5/3 Ground, Cunning/Heroism, Mandalorian·Bounty Hunter, unique)
#// "Shielded. On Attack: If the defending unit is exhausted, it gets -4/-0 for this attack."
#// Word for word SEC_208 Hunter: the -4/-0 is a one-shot SWU_DEF_DEBUFF_4 marker set synchronously in
#// ExecuteSWUAttack (the deferred On Attack trigger would be too late — SWUCombatDamage reads the marker first);
#// the On Attack stub is empty and registered in $swuLogEffectAppliedElsewhere.
#//
#// COVERAGE: offer=N/A (no choice — the defender is the attack's target) ·
#//           decline=N/A (no "you may") ·
#//           boundary=ExhaustedDefender_GetsMinusFour (a 5-power defender counters for exactly 1, which pins
#//           the 4) paired with ReadyDefender_FullCounter ·
#//           control=N/A (no owner-scoped zone, no "your") ·
#//           reqboundary=N/A (nothing is stored across a decision — the marker is set and consumed inside one
#//           attack with no decision in between) ·
#//           modes=2P only (no player reference, no friendly/enemy wording)
#//
#// Mando (seeded, so no Shield) attacks an EXHAUSTED Captain Phasma (5/6): she counters for 5 - 4 = 1.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_157:1:0
WithP2GroundArena: JTL_088:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# ReadyDefender_FullCounter
#// The gate's FALSE case: a READY Phasma counters for the full 5 and Mando (3 HP) is defeated.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_157:1:0
WithP2GroundArena: JTL_088:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# ForThisAttackOnly_NextAttackerTakesFullCounter
#// Duration: "-4/-0 for this attack". A second attacker in the SAME phase (Consular Security Force 3/7) takes
#// Phasma's full 5 counter — so it is not a phase debuff on her. (Her 5 + 3 damage then defeats her.)

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_157:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: JTL_088:0:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:5
P2GROUNDARENACOUNT:0

---

# Blanked_NoDebuff
#// A Mandalorian that has lost all abilities (SOR_138 marker) gets no On Attack: the exhausted Phasma counters
#// for 5 and he is defeated.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_157:1:0:SOR_138
WithP2GroundArena: JTL_088:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0

