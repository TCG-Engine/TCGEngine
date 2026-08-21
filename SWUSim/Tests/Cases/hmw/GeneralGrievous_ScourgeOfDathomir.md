# WhenPlayed_FourDamageToTheENEMYBase
#// HMW_159 General Grievous - Scourge of Dathomir (Aggression/Villainy, cost 7, 8/5 Ground, unique,
#// Separatist/Official, Legendary) —
#//   "Bases can't be healed.
#//    When Played: Deal 4 damage to a base."
#// COVERAGE: offer=WhenPlayed_OfferIsBOTHBases (left pending; two bases are ALWAYS legal so this choice
#//           can never auto-resolve) · negative=NoHeal_WithoutGrievous_RestoreHeals (the lock's own
#//           control) + NoHeal_GrievousBlanked_RestoreHeals (blanked ⇒ no lock) ·
#//           boundary=Lethal_TwentySixPlusFour_Wins vs Boundary_TwentyFivePlusFour_Survives ·
#//           control=NoHeal_OPPONENTSGrievous_LocksYourBaseToo (the passive is unqualified — it is not
#//           scoped to a controller at all, so the assertable axis is "whose Grievous" × "whose base") ·
#//           reqboundary=RequestBoundary_AcrossTheBaseChoice ·
#//           decline=N/A — neither clause is optional: no "may", no "up to". The passive is continuous
#//           and the When Played is a mandatory MZCHOOSE between two always-legal targets.
#// ⚠ "a base" carries NO controller word, so BOTH bases are legal — including your OWN. That is the
#//   unqualified-target family (LAW_058 / IBH_006 / HMW_177), and WhenPlayed_FourDamageToYourOWNBase
#//   is what pins it; auto-resolving to the enemy base would hide the violation completely.
#// ⚠ "Bases can't be healed" is likewise unqualified — it is a GLOBAL lock, not "your bases" or
#//   "enemy bases". One Grievous on either side stops every base heal in the game, including his own
#//   controller's Restore.
#// Restore fixture: SOR_243 (Restore 2, keyword-only text, 3/4 Ground) — Restore fires on EVERY attack,
#// so attacking the base both heals P1 by 2 and damages P2, which keeps the two halves independently
#// assertable.

## GIVEN
CommonSetup: rrk/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_159

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_159
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:5
P2BASEDMG:4
P1BASEDMG:0
P1NODECISION

---

# WhenPlayed_FourDamageToYourOWNBase
#// HMW_159 — the unqualified-target cell. "Deal 4 damage to a base" names no controller, so a player
#// may aim it at their own base (relevant for the base-damage-threshold family, e.g. HMW_074's "a base
#// has 15 or more damage"). If the implementation hard-codes the enemy base this section is the only
#// thing that fails.

## GIVEN
CommonSetup: rrk/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_159

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:4
P2BASEDMG:0
P1NODECISION

---

# WhenPlayed_OfferIsBOTHBases
#// HMW_159 — the offer, left PENDING. Answering a target proves the branch, never the pool; a handler
#// that offered only the enemy base would pass both sections above (the own-base one would fail, but a
#// handler offering only YOUR base would pass the first). Asserted exactly.

## GIVEN
CommonSetup: rrk/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_159

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myBase-0&theirBase-0
P1DECISIONTOOLTIP:Deal_4_damage_to_a_base
P1BASEDMG:0
P2BASEDMG:0

---

# RequestBoundary_AcrossTheBaseChoice
#// HMW_159 — the request-boundary cell. The base choice ends the request, so the amount (4) and the
#// chosen side must both survive into a fresh process. Identical to
#// WhenPlayed_FourDamageToTheENEMYBase plus one SimulateRequestBoundary line before the answer.

## GIVEN
CommonSetup: rrk/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: HMW_159

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1BASEDMG:0
P1NODECISION

---

# Lethal_TwentySixPlusFour_Wins
#// HMW_159 — the boundary's upper half AND the game-ends-mid-effect cell. A base at 26 damage is at
#// exactly 30 after the 4, so P1 wins; nothing may be left pending on either seat once the game is
#// decided (AddDecision is suppressed once SWUGetGameWinner() != 0).

## GIVEN
CommonSetup: rrk/bbw/{myResources:7;theirBaseDamage:26}
P1OnlyActions: true
WithP1Hand: HMW_159

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:30
P1WIN
P1NODECISION
P2NODECISION

---

# Boundary_TwentyFivePlusFour_Survives
#// HMW_159 — the boundary's lower half. 25 + 4 = 29, one short of the 30-HP base, so the game is NOT
#// decided. Without this partner the lethal section above proves nothing about the number: it would
#// pass for any amount of 4 or more.

## GIVEN
CommonSetup: rrk/bbw/{myResources:7;theirBaseDamage:25}
P1OnlyActions: true
WithP1Hand: HMW_159

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:29
P1NODECISION

---

# NoHeal_GrievousLocksYourOwnRestore
#// HMW_159 clause 1 — the passive, in its least intuitive direction: Grievous locks the base heal of
#// the player who CONTROLS him. P1's SOR_243 has Restore 2 and attacks, which normally heals P1's base
#// from 6 to 4; with Grievous on P1's own board it stays at 6.
#// Grievous is seeded rather than played so this section tests ONLY the passive.

## GIVEN
CommonSetup: rrk/bbw/{myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_159:1:0 SOR_243:1:0]

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1BASEDMG:6
P2BASEDMG:3
P1NODECISION

---

# NoHeal_WithoutGrievous_RestoreHeals
#// HMW_159 — the load-bearing NEGATIVE for the passive, and the control that makes every other
#// no-heal section mean something. Byte-identical board with a vanilla 3/3 standing in for Grievous:
#// Restore 2 heals P1's base 6 -> 4. If this and the section above ever agree, the lock is not the
#// card's.

## GIVEN
CommonSetup: rrk/bbw/{myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_243:1:0]

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1BASEDMG:4
P2BASEDMG:3
P1NODECISION

---

# NoHeal_OPPONENTSGrievous_LocksYourBaseToo
#// HMW_159 — "Bases can't be healed" names no controller, so an ENEMY Grievous locks YOUR base too.
#// Same board as NoHeal_GrievousLocksYourOwnRestore with Grievous moved to P2. A lock implemented as
#// "the controller's bases" or "the opponent's bases" fails exactly one of these two sections.

## GIVEN
CommonSetup: rrk/bbw/{myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: SOR_243:1:0
WithP2GroundArena: HMW_159:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:6
P1NODECISION

---

# NoHeal_GrievousBlanked_RestoreHeals
#// HMW_159 — a Grievous who has lost his abilities grants no lock. SHD_072 Imprisoned ("attached unit
#// loses its current abilities and can't gain abilities") is seeded onto him, and Restore 2 heals
#// normally. This is what the "active" in _SWUCountActiveUnitsWithCardID buys; a raw in-play scan
#// passes every other section in this file and fails here.

## GIVEN
CommonSetup: rrk/bbw/{myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: [HMW_159:1:0 SOR_243:1:0]
WithP1GroundArenaUpgrade: 0:SHD_072

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_072
P1BASEDMG:4
P1NODECISION

---

# NoHeal_EndsWhenGrievousLeavesPlay
#// HMW_159 — the aura must RECOMPUTE, not stamp. Grievous sits at 4 damage (1 remaining HP), P2 kills
#// him in combat (trading, since his 8 power counters), and P1's Restore 2 then heals normally. A lock
#// written once onto the players (rather than derived live) passes every positive and fails here.
#// After Grievous is defeated the survivor shifts down, so SOR_243 attacks from index 0.

## GIVEN
CommonSetup: rrk/bbw/{myBaseDamage:6}
WithActivePlayer: 2
WithP1GroundArena: [HMW_159:1:4 SOR_243:1:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_243
P1BASEDMG:4

---

# BothClauses_PlayedGrievousDamagesThenLocksTheEnemyBase
#// HMW_159 — the two clauses together, which is how the card is actually played: the When Played puts
#// the enemy base to 4, and P2's own Restore 2 then cannot heal any of it back. Neither clause alone
#// produces this end state.
#// P2 must ACT, so initiative is left unclaimed and the turn alternates.

## GIVEN
CommonSetup: rrk/bbw/{myResources:7}
WithActivePlayer: 1
WithP1Hand: HMW_159
WithP2GroundArena: SOR_243:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P2>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:HMW_159
