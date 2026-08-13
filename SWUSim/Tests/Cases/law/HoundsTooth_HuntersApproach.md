# AttackEndDefeatLowerPower
#// LAW_033 Hound's Tooth (8/8, space) — When Attack Ends: if this unit survived, you may defeat a unit
#// with less power than this unit. Attacks the base and survives; defeat the enemy SOR_046 (power 3 < 8).
#// COVERAGE: offer=AttackEnd_OfferIncludesFriendlyAndExcludesEqualPower (P1SELECTABLEEXACT over both
#//           players' arenas) · decline=AttackEnd_MayDeclineDefeat · control=AttackEnd_DefeatedAndReplayed
#//           ViaBounty_NewCopyUntouched (the attacker itself changes control mid-resolution) · boundary pair=
#//           AttackEnd_OfferIncludesFriendlyAndExcludesEqualPower (power 1/2 in, power 4 = own power out)
#//           + AttackEnd_DefeatThreshold_PowerFromUpgrades (raised threshold) · reqboundary=N/A (the
#//           may-defeat resolves inside the single attack flow; no state is read across a later request)

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# AttackEnd_MayDeclineDefeat
#// LAW_033 Hound's Tooth (4/8, space) — the after-attack defeat is optional ("you may"). Hound's Tooth
#// attacks the enemy base and survives; the player DECLINES the defeat, so every lower-power unit stays.
#// Board: friendly SOR_141 Green Squadron A-Wing (1) + Hound's Tooth in space, SOR_164 Wampa (4) on ground;
#// enemy SOR_232 AT-ST (6) / SOR_045 Yoda (2) ground, SEC_213 A-Wing (1) / SOR_040 Avenger (8) / SOR_185
#// Chimaera space. Nothing is defeated.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SOR_185:1:0

## WHEN
- P1>AttackSpaceArena:1:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2BASEDMG:4
P1SPACEARENACOUNT:2
P2SPACEARENACOUNT:3
P2GROUNDARENACOUNT:2

---

# AttackEnd_DefeatLowerPower_TargetUnit
#// LAW_033 Hound's Tooth (4/8) — attacks an enemy UNIT (SEC_213 A-Wing, 1/2), survives (takes 1), and its
#// after-attack ability defeats a unit with less power than its 4: SOR_045 Yoda (2). The combat-defeated
#// A-Wing and Yoda both leave; Hound's Tooth remains. (SOR_164 Wampa at power 4 is NOT eligible — 4 is not
#// less than 4 — nor are AT-ST/Avenger/Chimaera.)

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SOR_185:1:0

## WHEN
- P1>AttackSpaceArena:1:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1SPACEARENACOUNT:2
P2SPACEARENACOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232

---

# AttackEnd_NoDefeatIfKilledWhileAttacking
#// LAW_033 Hound's Tooth (4/8) — the ability requires the attacker to survive. Hound's Tooth attacks
#// SOR_040 Avenger (8/8): it deals 4 (Avenger survives) but takes 8 and is defeated, so no after-attack
#// defeat happens and no decision is offered.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SOR_185:1:0

## WHEN
- P1>AttackSpaceArena:1:1

## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:3
P2GROUNDARENACOUNT:2

---

# AttackEnd_NoDefeatIfKilledEvenAfterKillingDefender
#// LAW_033 Hound's Tooth (4/8) — even when Hound's Tooth defeats its target in combat, it does NOT get the
#// after-attack defeat if it is itself defeated. It attacks a pre-damaged SOR_185 Chimaera (8/7 with 6
#// damage = 1 HP left): Hound's Tooth deals 4 (Chimaera defeated) and takes 8 (Hound's Tooth defeated).
#// Both leave; no after-attack defeat, no decision.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SOR_185:1:6

## WHEN
- P1>AttackSpaceArena:1:2

## EXPECT
P1NODECISION
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:2

---

# AttackEnd_DefeatThreshold_PowerFromUpgrades
#// LAW_033 Hound's Tooth (4/8) — the "less power than this unit" threshold uses its LIVE power. With two
#// TWI_155 Twice the Pride (+4/+0 each), Hound's Tooth is power 12, so it can now defeat SOR_040 Avenger
#// (power 8 < 12) after attacking the base and surviving. (SEC_213 A-Wing at power 1 is also eligible.)

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP1SpaceArenaUpgrade: 0:TWI_155
WithP1SpaceArenaUpgrade: 0:TWI_155
WithP2SpaceArena: SOR_040:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:12
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SEC_213

---

# AttackEnd_DefeatThreshold_PowerFromAbilityThisAttack
#// LAW_033 Hound's Tooth (4/8) — a temporary "+2/+0 for this attack" boost (TWI_012 Anakin Skywalker's
#// front Action sends it at SEC_213 A-Wing at 6 power) has EXPIRED by the time the When Attack Ends
#// ability resolves: the attack is over, so the threshold is back at the printed 4. The offer is exactly
#// SOR_095 Battlefield Marine (3 < 4) — SOR_050 The Ghost (5) is NOT selectable even though the boosted
#// attack itself dealt 6 (killing the A-Wing). The offer is asserted while pending.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_012}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_050:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirSpaceArena-1

## EXPECT
P1BASEDMG:2
P1SELECTABLEEXACT:theirGroundArena-0
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:1
P2GROUNDARENACOUNT:1

---

# AttackEnd_OfferIncludesFriendlyAndExcludesEqualPower
#// LAW_033 Hound's Tooth (4/8) — "defeat a unit with less power" may target ANY unit, including FRIENDLY
#// ones, and "less" is strict. After attacking the base and surviving, the offer is exactly: friendly
#// SOR_141 Green Squadron A-Wing (1), enemy SOR_045 Yoda (2), enemy SEC_213 A-Wing (1). Excluded: friendly
#// SOR_164 Wampa (power 4 — equal to Hound's Tooth's own 4, not LESS), enemy SOR_232 AT-ST (6), enemy
#// SOR_040 Avenger (8), and Hound's Tooth itself. The pick is left pending to assert the offer.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0

## WHEN
- P1>AttackSpaceArena:1:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-1&theirSpaceArena-0

---

# AttackEnd_DefeatFriendlyUnit
#// LAW_033 Hound's Tooth (4/8) — the after-attack defeat can be aimed at your OWN unit. Same board as the
#// offer section; the player picks the friendly SOR_141 Green Squadron A-Wing (power 1 < 4), which is
#// defeated into P1's discard while every enemy unit stays.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArena: LAW_033:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_045:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2SpaceArena: SOR_040:1:0

## WHEN
- P1>AttackSpaceArena:1:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:LAW_033
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_141
P2GROUNDARENACOUNT:2
P2SPACEARENACOUNT:2

---

# AttackEnd_SimultaneousWithWhenDefeated_AttackerResolvesFirst
#// LAW_033 Hound's Tooth (4/8) attacks JTL_104 Raddus (8/6) wearing TWI_070 Perilous Position (-2/-2 →
#// 6/4): Hound's Tooth deals 4 (Raddus defeated) and takes 6 (survives at 8 HP). Two triggers arise from
#// the same attack — Hound's Tooth's When Attack Ends (P1) and Raddus's When Defeated "deal damage equal
#// to this unit's power (6) to an enemy unit" (P2, surfaced from its effect stack via Drain). The active
#// player's may-defeat resolves first while Hound's Tooth is still alive, defeating SOR_095 Battlefield
#// Marine (3 < 4); THEN Raddus's When Defeated auto-targets the lone enemy unit Hound's Tooth for 6
#// (6+6=12 ≥ 8, defeated). End: both space arenas empty, marine gone.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_104:1:0
WithP2SpaceArenaUpgrade: 0:TWI_070

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:You
- P1>AnswerDecision:theirGroundArena-0
- P2>Drain

## EXPECT
P1NODECISION
P2NODECISION
P2GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0

---

# AttackEnd_DefeatedAndReplayedViaBounty_NewCopyUntouched
#// LAW_033 Hound's Tooth carrying SHD_226 Unrefusable Offer (Bounty: play this unit for free under the
#// collector's control, entering ready) attacks Raddus (6/4 with TWI_070) and trades with it: Raddus's
#// When Defeated (the active player picks Opponent-first, so it resolves before the may-defeat) deals 6,
#// defeating Hound's Tooth (6 combat + 6 = 12 >= 8). P2 collects the bounty and replays Hound's Tooth
#// under P2's control, entering READY in P2's space arena. The original did NOT survive, so its
#// survival-gated may-defeat is dropped (no leftover prompt); the replayed copy is a NEW unit — it
#// stays in play and SOR_095 Battlefield Marine survives.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP1SpaceArenaUpgrade: 0:SHD_226
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_104:1:0
WithP2SpaceArenaUpgrade: 0:TWI_070

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:Opponent
- P2>Drain
- P2>AnswerDecision:YES

## EXPECT
P1NODECISION
P2NODECISION
P2GROUNDARENACOUNT:1
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:LAW_033
P2SPACEARENAUNIT:0:READY

---

# AttackEnd_KilledByWhenDefeatedFirst_NoDefeat
#// The other branch of the order choice: same board as AttackerResolvesFirst, but the active player
#// picks Opponent-first. Raddus's When Defeated resolves first (6 combat + 6 = 12 >= 8 defeats
#// Hound's Tooth), so its "if this unit survived" fails and the may-defeat never surfaces — SOR_095
#// Battlefield Marine survives and no prompt is pending for either player.

## GIVEN
CommonSetup: grk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_033:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_104:1:0
WithP2SpaceArenaUpgrade: 0:TWI_070

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:Opponent
- P2>Drain

## EXPECT
P1NODECISION
P2NODECISION
P2GROUNDARENACOUNT:1
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
