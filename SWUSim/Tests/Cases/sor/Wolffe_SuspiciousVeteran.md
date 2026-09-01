# NoLock_RestoreHeals
#// SOR_160 Wolffe — control test: WITHOUT Wolffe's lock, the Restore 1 unit heals P1's base normally
#// (3 → 2), proving the lock (not a broken Restore) is what blocks it in the other test.
#// COVERAGE: offer=N/A (neither clause targets — Saboteur resolves automatically during the attack and
#//           the heal lock is a global phase flag with no chooser; the Repair sections below answer
#//           Repair's OWN picker, not Wolffe's) ·
#//           decline=N/A (no "you may" anywhere on the card; both triggers are mandatory) ·
#//           control=Lock_IsGlobal_TheOPPONENTSBaseCannotBeHealedEither — the lock is not seat-scoped,
#//           so it survives any change of who controls Wolffe; its passing control is
#//           Control_OpponentsRestoreHealsTheirOwnBaseWhenNoLockIsSet ·
#//           boundary pair=WhenPlayed_LocksBaseHeal (this phase, no heal) +
#//           WhenPlayedLock_ExpiresWithThePhase_RestoreHealsNextRound (next phase, heals) ·
#//           reqboundary=SimulateRequestBoundary_PhaseHealLockSurvives

## GIVEN
CommonSetup: rrw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:2

---

# OnAttack_LocksBaseHeal
#// SOR_160 Wolffe — the lock also triggers On Attack. Wolffe (in play) attacks the enemy base, setting
#// the lock; then the Restore 1 unit (SOR_044) attacks and its Restore heal is blocked (base stays 3).
#// Base takes Wolffe's 3 + SOR_044's 2 = 5.

## GIVEN
CommonSetup: rrw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_160:1:0
WithP1SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:5

---

# WhenPlayed_LocksBaseHeal
#// SOR_160 Wolffe (Aggression unit, cost 2, 3/2, Fringe/Clone) — "Saboteur. When Played/On Attack:
#// Bases can't be healed for this phase." P1's base is at 3 damage. Playing Wolffe locks base healing,
#// so when the Restore 1 unit (SOR_044) attacks, its Restore heal is blocked and the base stays at 3.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;myBaseDamage:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0
WithP1Hand: SOR_160

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:2

---

# SimulateRequestBoundary_PhaseHealLockSurvives
#// SOR_160 Wolffe — the "bases can't be healed for this phase" lock is written when Wolffe is played
#// and read much later, when the Restore unit attacks. In production those are two separate requests,
#// so the lock must live in the serialized gamestate, not in an in-memory global. Mirrors
#// WhenPlayed_LocksBaseHeal with the boundary inserted between the play and the Restore attack.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;myBaseDamage:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_044:1:0
WithP1Hand: SOR_160

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:2

---

# Saboteur_IgnoresEnemySentinel_ReachesTheBase
#// Intended: "Saboteur (When this unit attacks, ignore Sentinel …)" — the keyword clause, which none of
#// the heal-lock sections touch. Wolffe (3/2) declares an attack on P2's base while P2's Echo Base
#// Defender (SOR_098, Sentinel, 4/3) is in the same arena. The Sentinel is ignored: the base takes
#// Wolffe's full 3, the Defender is never damaged, and Wolffe takes no counter-damage at all (a base
#// does not fight back) — so his 2 HP survive the swing.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_160:1:0    # Wolffe (Saboteur)
WithP2GroundArena: SOR_098:1:0    # Echo Base Defender (Sentinel, 4/3)

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Control_WithoutSaboteur_TheSentinelTakesTheAttackInstead
#// The load-bearing negative for the section above. Same enemy Echo Base Defender (Sentinel, 4/3), same
#// declared target (the base), but the attacker is a Consular Security Force (3/7) with NO Saboteur:
#// the Sentinel cannot be ignored, so the base takes NOTHING and the Defender eats the 3 and dies,
#// while the attacker comes back with 4 damage. Without this control, "the base took 3" in
#// Saboteur_IgnoresEnemySentinel_ReachesTheBase could just mean Sentinel is unimplemented.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # Consular Security Force 3/7 — no Saboteur
WithP2GroundArena: SOR_098:1:0    # Echo Base Defender (Sentinel, 4/3)

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# Saboteur_DefeatsTheDefendersShieldBeforeCombatDamage
#// Intended: the second half of Saboteur — "…and defeat the defender's Shields." Wolffe attacks a
#// SHIELDED Industrious Team (LAW_124, 4/7). The Shield is defeated during the attack rather than
#// spent absorbing the hit, so all 3 of Wolffe's damage lands and the defender ends with 3 damage and
#// zero Shield tokens. Wolffe takes the 4 counter-damage and dies on his 2 HP.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_160:1:0        # Wolffe (Saboteur)
WithP2GroundArena: LAW_124:1:0        # Industrious Team 4/7
WithP2GroundArenaUpgrade: 0:SOR_T02   # ...with a Shield

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# Control_WithoutSaboteur_TheShieldPreventsTheDamage
#// The load-bearing negative for the Shield half. Same shielded Industrious Team, same 3-power
#// attacker — but a Consular Security Force (3/7) with no Saboteur. Now the Shield does its normal job:
#// it PREVENTS the whole 3 and is then defeated, so the defender ends on 0 damage with 0 Shields. The
#// Shield count is identical to the Saboteur case; only the damage tells the two apart, which is why
#// both assertions are needed on both sides.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0        # Consular Security Force 3/7 — no Saboteur
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# Lock_IsGlobal_TheOPPONENTSBaseCannotBeHealedEither
#// Intended: the printed text is "BASES can't be healed for this phase" — unqualified, so it names both
#// players' bases, not just the opponent's. P1's Wolffe attacks (setting the lock and putting P2's base
#// on 3+3=6); P2 then attacks with a Restored ARC-170 (Restore 1) and P2's OWN base does not heal — it
#// stays at 6 while P1's base takes the ARC-170's 2. An implementation that locked only the enemy's
#// base would leave P2 on 5 here.

## GIVEN
CommonSetup: rrw/bbw/{theirBaseDamage:3}
WithP1GroundArena: SOR_160:1:0    # Wolffe
WithP2SpaceArena: SOR_044:1:0     # Restored ARC-170 (Restore 1)

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:6
P1BASEDMG:2

---

# Control_OpponentsRestoreHealsTheirOwnBaseWhenNoLockIsSet
#// The passing control for the section above, on the OPPONENT's side of the board (the existing
#// NoLock_RestoreHeals only proves it for P1). Identical fixture, but Wolffe never attacks — P1 simply
#// passes — so no lock exists and P2's Restore 1 heals P2's own base from 3 damage to 2 while P1's base
#// takes the ARC-170's 2.

## GIVEN
CommonSetup: rrw/bbw/{theirBaseDamage:3}
WithP1GroundArena: SOR_160:1:0    # Wolffe — present but never attacks, so no lock
WithP2SpaceArena: SOR_044:1:0     # Restored ARC-170 (Restore 1)

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P1BASEDMG:2

---

# Lock_BlocksAnABILITYBaseHeal_NotJustRestore
#// Intended: "bases can't be HEALED" is a blanket restriction on the heal itself, not a Restore-keyword
#// rule — every existing lock section happens to use Restore. Wolffe attacks (setting the lock), then
#// P1 plays Repair (SOR_074, "Heal 3 damage from a unit or base") and aims it at P1's OWN base, which
#// is sitting on 3 damage. The heal is blocked outright: the base stays at 3 even though the event was
#// paid for and resolved. Repair is off-aspect for this leader/base pair, hence the 6 resources.

## GIVEN
CommonSetup: rrw/rrk/{myResources:6;myBaseDamage:3;myhandCardIds:SOR_074}
P1OnlyActions: true
WithP1GroundArena: SOR_160:1:0    # Wolffe
WithP1GroundArena: SOR_046:1:5    # damaged Consular Security Force — the other legal Repair target

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:1:DAMAGE:5
P2BASEDMG:3
P1HANDCOUNT:0

---

# Lock_DoesNOTBlockUNITHealing
#// Intended scope check: the lock names BASES only, so unit healing is untouched. Identical fixture and
#// identical Repair play to the section above, but aimed at the damaged Consular Security Force (5
#// damage): the heal goes through in full, 5 → 2. Paired with Lock_BlocksAnABILITYBaseHeal_NotJustRestore
#// this separates "Repair is broken while a lock is up" from "the lock applies to bases only" — the two
#// sections differ by nothing but the answered target.

## GIVEN
CommonSetup: rrw/rrk/{myResources:6;myBaseDamage:3;myhandCardIds:SOR_074}
P1OnlyActions: true
WithP1GroundArena: SOR_160:1:0    # Wolffe
WithP1GroundArena: SOR_046:1:5    # damaged Consular Security Force

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
P1BASEDMG:3
P2BASEDMG:3

---

# OnAttack_FiresWhenAttackingAUNIT_AndOutlivesWolffe
#// Intended: "On Attack" is not "on attacking a base" — the existing OnAttack_LocksBaseHeal only ever
#// has Wolffe swing at the base. Here Wolffe attacks a UNIT (Industrious Team, 4/7) and dies to its 4
#// counter-damage, yet the lock he set still stands for the rest of the phase: the Restored ARC-170
#// then attacks and its Restore 1 heals nothing, leaving P1's base on 3. That the lock survives its
#// source's defeat is the point — it is a phase-duration effect, not an aura.

## GIVEN
CommonSetup: rrw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_160:1:0    # Wolffe
WithP1SpaceArena: SOR_044:1:0     # Restored ARC-170 (Restore 1)
WithP2GroundArena: LAW_124:1:0    # Industrious Team 4/7 — kills Wolffe on the counter-swing

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:2

---

# WhenPlayedLock_ExpiresWithThePhase_RestoreHealsNextRound
#// Intended: "for THIS PHASE" — the boundary that pairs with WhenPlayed_LocksBaseHeal. Identical
#// fixture, but instead of attacking with the Restored ARC-170 in the same action phase, both players
#// pass out to regroup and the ARC-170 attacks in the NEXT action phase. Now the Restore 1 heals
#// normally, taking P1's base from 3 damage to 2 — the very heal the lock swallowed one phase earlier.
#// Both decks are seeded so the regroup draws add no empty-deck base damage on top of the numbers here.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;myBaseDamage:3;myhandCardIds:SOR_160}
WithP1SpaceArena: SOR_044:1:0     # Restored ARC-170 (Restore 1)
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:2
