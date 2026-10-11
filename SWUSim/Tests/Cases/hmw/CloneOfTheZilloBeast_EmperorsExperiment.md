# Aura_OtherFriendlyUnitsGetMinusTwo_BothArenas_NotItself
#// HMW_065 Clone of the Zillo Beast, Emperor's Experiment (Vigilance/Villainy, Clone/Creature, 4-cost
#// 6/6 Ground, unique) — "Other friendly units get -2/-2. / On Attack: You may give a Weakness token to
#// a unit."
#// COVERAGE: offer=OnAttack_Offer_EveryUnitIncludingItself_NoBases ·
#//           decline=OnAttack_Decline_NoTokenAnywhere · boundary=PlayedZillo_DefeatsFriendliesAtTwoOrLess
#//           Remaining (2 remaining dies / 3 remaining survives on 1, one board) ·
#//           control=ChangeOfHeart_AuraFollowsTheController (a LIVE steal: the thief's units shrink, the
#//           owner's recover) · reqboundary=OnAttack_RequestBoundaryBeforeTheAnswer (the aura itself is
#//           recomputed on every read and writes nothing; the On Attack's pick is the decision) ·
#//           modes=2P,TeamSuns ("Other FRIENDLY units" — a teammate's units are friendly) · TwinSuns=N/A
#//           (no player reference; "a unit" is unqualified and the pool comes from the shared collector)
#// ⚠ PREVIEW-SET ASSUMPTIONS (HMW is absent from card-specific-rulings.md):
#//   • "Other" excludes THIS unit by identity (UniqueID), not by name — so under a teammate's Zillo, your
#//     own Zillo is an "other friendly unit" and shrinks (TeamSuns_TwoZillos_…).
#//   • "Friendly" = the TEAM in Team Suns (the HMW_006 Omega reading); self only everywhere else.
#//   • The aura is HP reduction, not damage (the SHD_037 Snoke shape): shields don't stop it and a unit
#//     reduced to 0 remaining HP is defeated by the state check.
#// Here: SOR_046 3/7 → 1/5 on the ground, JTL_069 4/7 → 2/5 in SPACE (both arenas), SHD_028 0/5 → 0/3
#// (power floors at 0, never -2), Zillo itself stays 6/6, and P2's SOR_046 is untouched.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: [HMW_065:1:0 SOR_046:1:0 SHD_028:1:0]
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_065
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:2:POWER:0
P1GROUNDARENAUNIT:2:HP:3
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:5
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7

---

# PlayedZillo_DefeatsFriendliesAtTwoOrLessRemaining
#// HMW_065 — the BOUNDARY PAIR on one board, and the real entry path (played from hand, not seeded).
#// SOR_095 3/3 on 1 damage has exactly 2 remaining → 0 under the aura → defeated; SEC_080 3/3 undamaged has
#// 3 remaining → survives on 1. Zillo enters after both, and the defeat compacts the arena, so Zillo ends
#// at index 1. The discard holds only the Marine (Zillo is a unit, not an event).

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:1 SEC_080:1:0]
WithP1Hand: HMW_065

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:HMW_065
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095

---

# UnitsPlayedLater_ShrinkOnEntry_OrDie
#// HMW_065 — the aura is continuous, so a unit that arrives AFTER Zillo meets it on entry: SOR_128 3/1 →
#// 1/-1 is defeated at once, SEC_080 3/3 → 1/1 survives. Costs under bbk: SOR_128 1 + 2 (Aggression
#// off-aspect) = 3; SEC_080 2 + 2 (Command off-aspect) = 4 → 7 resources.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7}
P1OnlyActions: true
WithP1GroundArena: HMW_065:1:0
WithP1Hand: [SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_128

---

# TokensCreatedUnderZillo_AreDefeatedAtOnce
#// HMW_065 — a CREATED token never passes through the play ceremony, so it is a separate route into play
#// (the "enter play is entry-wide" rule). TWI_237 Droid Deployment (event, 2 Villainy) makes two 1/1 Battle
#// Droids; under Zillo each is -1/-1 and ceases at once. Only Zillo remains.

## GIVEN
CommonSetup: bbk/bbk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: HMW_065:1:0
WithP1Hand: TWI_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_065

---

# FriendlyLeaderUnit_AlsoShrinks
#// HMW_065 — value class: a deployed LEADER unit is a friendly unit too ("other friendly units" has no
#// non-leader qualifier — contrast Snoke's "each enemy NON-LEADER unit"). HMW_003 Doctor Hemlock deployed
#// is 3/6 → 1/4. The deployed leader seeds after the plain units, at index 1.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:HMW_003:1:1}
P1OnlyActions: true
WithP1GroundArena: HMW_065:1:0

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:1:HP:4

---

# AuraEnds_WhenZilloLeavesPlay
#// HMW_065 — the aura must be RECOMPUTED, not stamped: when Zillo is defeated, the survivor's HP comes
#// back. Zillo on 5 damage attacks SOR_095 (3/3): each kills the other. The friendly SOR_046 carries 4
#// damage — alive on 1 under the aura (HP 5), and on 3 of 7 afterwards. The On Attack prompt (three
#// legal units) is declined.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: [HMW_065:1:5 SOR_046:1:4]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7

---

# BlankedZillo_NoAura
#// HMW_065 — the aura is one of Zillo's abilities, so a Zillo that has lost all abilities (the SOR_138
#// Force Lightning marker) shrinks nothing: SOR_046 stays 3/7.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: [HMW_065:1:0:SOR_138 SOR_046:1:0]

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:7

---

# ChangeOfHeart_AuraFollowsTheController
#// HMW_065 — "friendly" is relative to Zillo's CONTROLLER, read live. P2 owns Zillo and a SOR_046 (1/5
#// under it). P1 plays SOR_224 Change of Heart (Cunning, 6 — yyk covers it) and takes Zillo:
#//   • P2's SOR_046 recovers to 3/7 (no longer friendly to Zillo);
#//   • P1's SEC_080 3/3 shrinks to 1/1;
#//   • P1's LAW_180 3/1 is defeated by the take-control sweep.
#// The pool for Change of Heart spans both sides, so the pick is explicit. After LAW_180 leaves, P1's arena
#// is SEC_080 then Zillo. Discard: Change of Heart + LAW_180.

## GIVEN
CommonSetup: yyk/bbk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 LAW_180:1:0]
WithP2GroundArena: [HMW_065:1:0 SOR_046:1:0]
WithP1Hand: SOR_224

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:1:CARDID:HMW_065
P1DISCARDCOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7

---

# TeamSuns_TeammatesUnitsShrink_EnemiesDoNot
#// HMW_065 — Team Suns: "friendly" is the TEAM (seats 1+3 vs 2+4). P1's Zillo shrinks teammate P3's
#// SOR_046 to 1/5 while both enemies' SOR_046 stay 3/7. A controller-only read leaves P3 at 3/7; a
#// whole-table read shrinks P2 and P4.

## GIVEN
CommonSetup: bbk/bbk/{}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: HMW_065:1:0
WithP3GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>Drain

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:POWER:1
P3GROUNDARENAUNIT:0:HP:5
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P4GROUNDARENAUNIT:0:POWER:3
P4GROUNDARENAUNIT:0:HP:7

---

# TeamSuns_TwoZillos_Stack_AndEachShrinksTheOther
#// HMW_065 — two auras on one team (P1 and teammate P3 each control a copy — uniqueness is per player).
#// They STACK: P1's SOR_046 3/7 → -1/3 → power floors at 0, HP 3. And "other" is by IDENTITY: each Zillo
#// is an "other friendly unit" to the teammate's copy, so both read 4/4. A name-based "other" (skip any
#// Zillo) leaves them 6/6; a boolean "a Zillo exists" gives SOR_046 1/5.

## GIVEN
CommonSetup: bbk/bbk/{}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: [HMW_065:1:0 SOR_046:1:0]
WithP3GroundArena: HMW_065:1:0

## WHEN
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:POWER:0
P1GROUNDARENAUNIT:1:HP:3
P3GROUNDARENAUNIT:0:POWER:4
P3GROUNDARENAUNIT:0:HP:4

---

# OnAttack_Offer_EveryUnitIncludingItself_NoBases
#// HMW_065 — the OFFER: "a unit" is unqualified, so the pool is every unit on both sides INCLUDING Zillo
#// itself, and no base. Left pending (a base attack, so the defender is not a unit).

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: [HMW_065:1:0 SOR_046:1:0]
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirGroundArena-1

---

# OnAttack_GivesWeaknessToAnEnemyUnit
#// HMW_065 — the positive: Weakness (HMW_T02, -1/-1) on P2's SEC_080 → 2/2. Zillo hits the base for 6.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_065:1:0
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:1:POWER:2
P2GROUNDARENAUNIT:1:HP:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# OnAttack_RequestBoundaryBeforeTheAnswer
#// HMW_065 — the same pick with a request boundary before the answer: the offer and its GIVE_WEAKNESS
#// continuation must survive a fresh process.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_065:1:0
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:POWER:2

---

# OnAttack_Decline_NoTokenAnywhere
#// HMW_065 — "you may": declining (MZMAYCHOOSE → `-`) gives no token to anything, and the attack still
#// resolves in full.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: [HMW_065:1:0 SOR_046:1:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# OnAttack_FriendlyTarget_StacksWithTheAura
#// HMW_065 — a FRIENDLY unit is a legal target, and the token stacks with the aura: SOR_046 3/7 → -2/-2
#// (aura) → -1/-1 (Weakness) = 0/4. Attacking a UNIT this time (the other dispatch path): Zillo 6 kills
#// P2's SOR_095 and takes 3.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: [HMW_065:1:0 SOR_046:1:0]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:0
P1GROUNDARENAUNIT:1:HP:4

---

# OnAttack_SelfTarget_WeakensThisVeryAttack
#// HMW_065 — Zillo is "a unit" too, and the only one here. A MAY-choose never auto-resolves, so the lone
#// target is still offered; taking it makes Zillo 5/5 BEFORE damage (On Attack resolves in the attack's
#// first step), so the base takes 5, not 6.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_065:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
