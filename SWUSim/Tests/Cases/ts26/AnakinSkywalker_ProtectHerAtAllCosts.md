# DeployedOnAttackShieldEntered
#// TS26_02 Anakin Skywalker (leader deployed, 4/5) — Sentinel + On Attack: give a Shield token to another
#// friendly unit that entered play this phase. After playing SEC_080 this phase, deployed Anakin attacks
#// LAW_124 and shields the entered SEC_080.
## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02:1:1;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# FrontShieldEnteredUnit
#// TS26_02 Anakin Skywalker (leader front) — Action [Exhaust]: if 2+ friendly units entered play this
#// phase, give a Shield token to 1 of them. After playing 2 units this phase, shield SEC_080.
## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SEC_080 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED

---

# Front_OnlyOneUnitEntered_NoShield
#// TS26_02 Anakin (front) — the gate is "2 OR MORE friendly units entered play this phase". With exactly
#// one unit played this phase the action is still usable and still exhausts the leader, but no Shield is
#// given. Boundary partner of FrontShieldEnteredUnit (which plays two).
#// RULING (2026-08-09): this Action costs nothing but [Exhaust], so it may always be taken as a SOFT
#// PASS — exhausting a leader is itself a change to the gamestate. The action is therefore always
#// available, the leader DOES exhaust, and the ability simply does nothing. It must not be gated in
#// SWULeaderActionAffordable.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1LEADER:EXHAUSTED

---

# Front_NoUnitsEnteredThisPhase_NoShield
#// TS26_02 Anakin (front) — a unit that was ALREADY on the board did not "enter play this phase", so a
#// pre-seated SEC_080 does not count and no Shield is given. Distinguishes "entered this phase" from
#// "is in play".
#// RULING (2026-08-09): this Action costs nothing but [Exhaust], so it may always be taken as a SOFT
#// PASS — exhausting a leader is itself a change to the gamestate. The action is therefore always
#// available, the leader DOES exhaust, and the ability simply does nothing. It must not be gated in
#// SWULeaderActionAffordable.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1LEADER:EXHAUSTED

---

# Front_ATOKENUnitCountsTowardTheTwo_AndIsItselfATarget
#// TS26_02 Anakin (front) — the gate explicitly counts tokens ("including tokens and leaders"). Playing
#// SEC_097 Beloved Orator ("When Played: Create a Spy token") puts TWO units into play from one card: the
#// Orator and the token. That satisfies the 2-unit gate on its own, and BOTH are offered as Shield
#// targets — the token is not excluded.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_097

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1DECISIONTOOLTIP:Give_a_Shield_to_a_unit_that_entered_this_phase

---

# Front_TwoEnteredOneDefeated_TheSURVIVORStillGetsTheShield
#// TS26_02 Anakin (front) — "2 or more friendly units ENTERED play this phase" is a fact about the past.
#// SEC_080 and SOR_095 both enter, then LOF_264 It's Worse defeats SEC_080. The gate is still satisfied
#// (two units did enter) and the survivor SOR_095 receives the Shield — the pick auto-resolves because it
#// is the only remaining legal target.
#// BUG THIS PINS (two layers, both silent):
#//   1. SWULeaderActionAffordable refused the whole Action once fewer than 2 flagged units were still IN
#//      PLAY, so the leader stayed ready and the ability closure never ran — with no error at all. Per the
#//      soft-pass ruling that clause is gone: an exhaust-only Action is always available.
#//   2. The closure then counted the LIVE list for its own gate, so a defeated entrant stopped counting.
#//      The gate now tallies the historical entry flags while the target pool stays live and friendly.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02;myResources:18}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SEC_080 SOR_095 LOF_264]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED

---

# Front_EnteredUnderFRIENDLYControlStillCounts_EvenAfterBeingSTOLEN
#// TS26_02 Anakin (front) — the gate counts units that entered play this phase under YOUR control, and a
#// later control change does not rewrite that. P1 plays SEC_080 and SOR_095 (two friendly entrants), and
#// P2 steals SEC_080 with SOR_224 Change of Heart. The gate is still met, so the Action resolves — but
#// the Shield can only go to a unit that is friendly NOW, so it lands on SOR_095 while the stolen SEC_080
#// gets nothing.
#// This is the pair to Front_EnteredUnderENEMYControlDoesNOTCount below: the GATE reads control at ENTRY,
#// the TARGET POOL reads control NOW.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02;myResources:18}
SkipPreGame: true
WithActivePlayer: 1
WithP2Resources: 10
WithP1Hand: [SEC_080 SOR_095]
WithP2Hand: SOR_224
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P2>Pass
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1LEADER:EXHAUSTED

---

# Front_EnteredUnderENEMYControlDoesNOTCount_EvenAfterComingAcross
#// TS26_02 Anakin (front) — the mirror. P1 plays ONE unit, then P2 plays LAW_233 Galen Erso and hands it
#// to P1 ("When Played: You may have an opponent take control of this unit"). P1 now CONTROLS two units,
#// but only one ENTERED play under P1's control, so the "2 or more friendly units entered" gate is not
#// met and no Shield is given to either.
#// The leader still exhausts: per the soft-pass ruling an exhaust-only Action is always usable.
#// A gate that counted currently-controlled units would wrongly fire here, which is what makes this the
#// discriminating half of the pair.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02;myResources:18}
SkipPreGame: true
WithActivePlayer: 1
WithP2Resources: 10
WithP1Hand: SEC_080
WithP2Hand: LAW_233
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1LEADER:EXHAUSTED

---

# Deployed_NothingEnteredThisPhase_NoOffer
#// TS26_02 Anakin (deployed) — "On Attack: Give a Shield token to ANOTHER friendly unit that entered play
#// this phase." With only a pre-seated SEC_080 on board, nothing entered this phase, so attacking raises
#// no offer at all and no Shield is given. (Anakin himself is excluded by "another" regardless.)

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02:1:1;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# Deployed_ATOKENThatEnteredIsAValidTarget_AnakinHimselfIsNot
#// TS26_02 Anakin (deployed) — playing SEC_097 Beloved Orator puts TWO units into play (itself + a Spy
#// token) and both are legal On Attack targets. The offer is exactly those two: Anakin is excluded by
#// "ANOTHER friendly unit" even though he is friendly and on the board.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02:1:1;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_097
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2

---

# Deployed_EnteredUnderENEMYControlButNowFRIENDLY_IsAValidTarget
#// TS26_02 Anakin (deployed) — the deployed side has no historical gate: it simply targets a unit that is
#// friendly NOW and entered play this phase, whoever controlled it at entry. P2 plays LAW_233 Galen Erso
#// and hands it to P1; attacking with Anakin offers exactly that Galen.
#// BUG THIS PINS: the entry flag is stamped under the ENTRY controller, and the target scan only read
#// $player's own flags — so a unit that entered under the opponent's control and has since come across
#// was silently skipped. The scan now checks every seat's flag.
#// Note this is the OPPOSITE of the front side, which counts entries under YOUR control; the deployed
#// side has no count at all.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02:1:1;myResources:14}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 10
WithP2Hand: LAW_233
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P1>AttackGroundArena:0:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1
P1GROUNDARENACOUNT:2

---

# Deployed_EnteredUnderFRIENDLYControlButNowENEMY_IsNotATarget
#// TS26_02 Anakin (deployed) — the mirror of the section above, and the pair that pins "friendly" to
#// CURRENT control on this side. P1 plays SEC_080, P2 steals it with SOR_224 Change of Heart, and
#// attacking with Anakin raises no offer: the only unit that entered this phase is no longer friendly.
#// Contrast the FRONT side, where that same stolen unit still counts toward the 2-unit gate.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02:1:1;myResources:18}
SkipPreGame: true
WithActivePlayer: 1
WithP2Resources: 10
WithP1Hand: SEC_080
WithP2Hand: SOR_224
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P1NODECISION

---

# Front_TwoEnteredBOTHDefeated_NobodyGetsAShield_AnakinStillExhausts
#// TS26_02 Anakin (front) — the gate is met (two units DID enter play this phase) but there is nobody
#// left to receive the Shield, so the Action resolves with no effect and Anakin still exhausts.
#// The board is built to make the negative sharp. SOR_046 has been in play since BEFORE this phase; it is
#// friendly, alive, and undamaged-by-the-gate — and it must NOT be shielded, because it did not ENTER
#// play this phase. Only the two SOR_128 (3/1) that entered were ever eligible, and TWI_173 Blood Sport
#// ("deal 2 damage to each ground unit") kills both while SOR_046 survives at 2 damage.
#// So: gate satisfied by history, target pool empty, zero Shields anywhere, leader exhausted (soft pass).

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02;myResources:18}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: [SOR_128 SOR_128 TWI_173]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
P1NODECISION

---

# Deployed_TheOnlyEnteredUnitWasDefeated_NoOffer
#// TS26_02 Anakin (deployed) — unlike his front side, the deployed On Attack has no historical gate: it
#// needs a unit that is alive and friendly NOW. SOR_128 (3/1) enters, then TWI_173 Blood Sport deals 2 to
#// each ground unit and kills it (Anakin survives at 2, LAW_124 sits at 2). Anakin then attacks P2's base
#// for 4 and no Shield offer appears — he is the only friendly unit left, and "another" excludes him.
#// Mirrors Padmé's Deployed_TheOnlyEnteredUnitWasDefeated_NoChainOffer; the two leaders share the
#// entered-this-phase helper, so both sides of it stay pinned.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_02:1:1;myResources:18}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_128 TWI_173]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P1NODECISION
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:2
