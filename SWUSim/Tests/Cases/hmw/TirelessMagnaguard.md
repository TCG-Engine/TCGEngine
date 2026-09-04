# DiesAtPrintedFivePower_MayReplayFreeWithTwoWeakness
#// HMW_109 Tireless Magnaguard (Ground 5/3, cost 4, [Command,Villainy], Separatist/Droid)
#// "When Defeated: If this unit had 5 or more power, for this phase you may play this unit from your
#// discard pile for free and give 2 Weakness tokens to it."
#//
#// COVERAGE: offer=N/A (the permission is a discard MODIFIER, not a target pool — the player's choice
#//                      IS the PlayFromDiscard action, exercised by Decline_PermissionIsOptional)
#//           decline=Decline_PermissionIsOptional (the "may" is never using the permission)
#//           boundary=ExactlyFourPower_NoPermission vs this section (5) — N-1/N pair on "5 or more"
#//           control=StolenMagnaguard_LandsInTheOWNERSDiscard_NoPermission
#//           reqboundary=RequestBoundary_PermissionSurvives
#//           modes=2P only (no player reference; "your discard pile" is owner/controller-scoped, not
#//                 friendly/enemy — TwinSuns=N/A, TeamSuns=N/A)
#//
#// The plain positive. The Magnaguard attacks a 3/7 Consular Security Force and trades: it deals 5,
#// takes 3 on 3 HP and is defeated at its printed 5 power. It comes back for FREE carrying two Weakness
#// tokens (-1/-1 each), so the replayed copy is a 3/1.
## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_109:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayFromDiscard:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_109
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:1
P1DISCARDCOUNT:0
P1RESAVAILABLE:6

---

# TwoWeakness_ThreePower_NoPermission
#// The load-bearing negative. Two Weakness tokens make it a 3/1, so its power AT DEFEAT is 3 — under
#// the threshold, no permission is granted, and the PlayFromDiscard action is refused. Proves the
#// condition is read from the unit's CURRENT power and not from its printed 5.
## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_109:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayFromDiscard:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_109

---

# ExactlyFourPower_NoPermission
#// The N-1 half of the boundary pair: ONE Weakness token leaves it a 4/2, so it is defeated at exactly
#// 4 power — one short of "5 or more". Without this the positive alone passes for any threshold <= 5.
## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_109:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayFromDiscard:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# RaidCountsTowardPowerAtDefeat
#// ★ USER RULING (CR 8.8.c — "an attacker gets bonus power from Raid DURING an attack"). A debuffed
#// Magnaguard that is separately given Raid 2 dies in a trade at 3 + 2 = 5 power, so it DOES qualify
#// and re-animates in its weakened state. Two Weakness tokens take it to 3/1; Raid 2 (a phase-duration
#// grant) restores the two power WHILE ATTACKING, which is the moment it is defeated.
#//
#// This is the section that forces the snapshot to read the ATTACK power rather than ObjectCurrentPower:
#// Raid is not a stat modifier, it is added to the attack power inside SWUCombatDamage, so a defeat
#// snapshot taken from the live object alone answers 3 and refuses the permission.
## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_109:1:0:RAID-2
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayFromDiscard:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_109
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:0

---

# TwoWeaknessAndTwoAdvantage_StillFivePower
#// ★ USER RULING. Two Weakness (-1/-1 each) and two Advantage (+1/+0 each) net to the printed 5 power,
#// so the Magnaguard qualifies. The load-bearing half is the TIMING: an Advantage token reads "When
#// attached unit's attack or defense ends: Defeat this upgrade", and the When Defeated ability resolves
#// BEFORE that attack-ends window — so both Advantage tokens are still attached when the power is
#// measured. Shedding them first would answer 3 and refuse the permission.
#// (HP is 3 - 2 = 1, so the 3-power counter is lethal.)
## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_109:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayFromDiscard:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_109
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:0

---

# DefeatedAsTheDEFENDER_AlsoQualifies
#// The ability is not attack-scoped — "if this unit HAD 5 or more power" is measured whenever it is
#// defeated. Here P2 attacks INTO the Magnaguard and kills it with a 5/5, so it is defeated as the
#// defender at its printed 5 power and the permission is still granted.
#//
#// ⚠ P1>Drain is REQUIRED: the Magnaguard dies on P2's action, so P1's When Defeated is queued on P1's
#// queue and is not processed during P2's action cycle. Without the drain the trigger is still pending
#// at assertion time and this reads exactly like the ability never firing.
## GIVEN
CommonSetup: ggk/rrk/{myResources:6;theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_109:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>PlayFromDiscard:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_109
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:0

---

# Decline_PermissionIsOptional
#// "you MAY play this unit" — the permission is an option, never a compulsion. The same board as the
#// positive, with P1 simply not taking the PlayFromDiscard action: the Magnaguard stays in the discard,
#// no Weakness tokens are created, and nothing else changed.
## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_109:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_109
P1DISCARDUNIT:0:MODIFIER:TPF

---

# StolenMagnaguard_LandsInTheOWNERSDiscard_NoPermission
#// OWNERSHIP vs CONTROL. The ability says "play this unit from YOUR discard pile", and it resolves for
#// the unit's CONTROLLER — but a defeated unit goes to its OWNER's discard. P1 controls a Magnaguard
#// P2 owns; when it trades, the card lands in P2's discard, so P1's own discard has no Magnaguard and
#// the permission has nothing to grant. P1's PlayFromDiscard is refused.
## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: HMW_109:2
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayFromDiscard:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:HMW_109

---

# RequestBoundary_PermissionSurvives
#// The permission is written by the defeat (one player action) and consumed by the PlayFromDiscard
#// (the next one), so in production the two are different PHP processes. Identical to the positive with
#// a boundary between them: the TPF modifier lives on the discard entry and the "give 2 Weakness"
#// rider must ride serialized state too, not an in-memory global.
## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_109:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>PlayFromDiscard:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_109
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:0

---

# PlayedFromHandWhileArmed_GetsNoWeakness
#// The 2 Weakness tokens ride the PERMISSION, not the card. One Magnaguard trades at 5 power, arming
#// the permission; P1 then plays a SECOND copy out of HAND (paying its 4) while that charge is live.
#// The hand copy is not the card the permission named, so it enters clean — and the dead one is still
#// sitting in the discard with its TPF intact, waiting to be used.
## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_109:1:0
WithP1Hand: HMW_109
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_109
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:5
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:MODIFIER:TPF
P1RESAVAILABLE:4

---

# Defending_TwoWeaknessAndTwoAdvantage_AdvantageStillAttachedAtDefeat
#// ★ USER RULING, and the section that actually DISCRIMINATES it. The sibling attacking version cannot:
#// an attacker's power is stashed at damage time, before anything is shed, so it answers 5 even if the
#// Advantage tokens were removed first. A DEFENDER has no such stash, so its power at defeat is read
#// from the live object — and that read only answers 5 while both Advantage tokens are still attached.
#//
#// 2 Weakness + 2 Advantage on the printed 5/3 = 5 power / 1 HP. P2 attacks with a 3/7: 3 damage kills
#// the Magnaguard, which is defeated at exactly 5 power and grants the permission.
#//
#// ⚠ What actually GUARDS this: making an Advantage subcard contribute 0 power reds this section and its
#// attacking sibling and nothing else (2 Weakness alone is 3 power and correctly refuses — see
#// TwoWeakness_ThreePower_NoPermission). The ordering itself cannot be mutated from the shed side:
#// _SWUDefeatAllAdvantageTokens no-ops on an already-removed unit, so a defeated host's Advantage
#// tokens are never shed by the attack-ends path at all. The ruling holds STRUCTURALLY here rather than
#// by a guard, which is worth knowing before anyone "tidies" the shed to run earlier.
#//
#// ⚠ USER CONFIRMATION 2026-09-04, and the reason this is CORRECT rather than merely convenient: the
#// two are SEQUENTIAL WINDOWS, not one simultaneous window. When Defeated resolves BEFORE the
#// attack-ends window — if they shared a window the player would be owed a "choose which trigger to
#// resolve first" ordering prompt, and the Advantage tokens' presence at the power read would become
#// the player's choice instead of a fact. Do NOT merge them.
## GIVEN
CommonSetup: ggk/rrk/{myResources:6;theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_109:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain
- P1>PlayFromDiscard:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_109
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:0
