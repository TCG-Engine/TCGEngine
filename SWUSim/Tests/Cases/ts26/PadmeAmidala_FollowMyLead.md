# DeployedAttackEndChain
#// TS26_04 Padmé Amidala (leader deployed, 5/6) — When Attack Ends: you may attack with another friendly
#// unit that entered play this phase (can't attack bases). After playing SEC_080 this phase, deployed Padmé
#// attacks LAW_124 (5 damage), then SEC_080 attacks it too (3 more) → LAW_124 (7 HP) is defeated.
## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_04:1:1;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:0

---

# FrontAttackWithEntered
#// TS26_04 Padmé Amidala (leader front) — Action [Exhaust]: if 2+ friendly units entered play this phase,
#// attack with 1 of them (can't attack bases). After playing 2 units, SEC_080 attacks the enemy LAW_124
#// (the only non-base target) for 3.
## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_04;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SEC_080 SOR_095]
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:EXHAUSTED

---

# Front_AttacksWithAnEnteredExhaustedUnit_AndCANNOTAttackTheBase
#// TS26_04 Padmé (front) — "attack with 1 of them, EVEN IF IT'S EXHAUSTED. It can't attack bases for this
#// attack." A unit played this phase enters EXHAUSTED, so the fact that SOR_095 can attack at all is the
#// proof of the first clause. The second clause is proved by the enemy base taking ZERO: with bases
#// excluded, the only legal target is LAW_124, so the attack auto-resolves onto it for 3.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED

---

# Front_ATOKENUnitCountsAndCanBeTheAttacker
#// TS26_04 Padmé (front) — the gate counts tokens. SEC_097 Beloved Orator puts TWO units into play from
#// one card (itself + a Spy token), which satisfies the 2-unit gate alone, and BOTH are offered as the
#// attacker — the token is not excluded.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_097
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Front_OnlyOneUnitEntered_NoAttack_PadmeStillExhausts
#// TS26_04 Padmé (front) — the "2 OR MORE" gate boundary. One unit entered, so no attack happens and the
#// enemy takes nothing. Per the soft-pass ruling the Action is still usable and Padmé still exhausts.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# Front_TwoEnteredOneDefeated_TheSURVIVORStillAttacks
#// TS26_04 Padmé (front) — the gate is a fact about the past. SOR_128 (3/1) and SOR_095 (3/3) both enter,
#// then TWI_173 Blood Sport ("2 damage to each ground unit") kills SOR_128 and leaves SOR_095 at 2 damage
#// and LAW_124 at 2. The gate still holds, so SOR_095 attacks for 3 more (LAW_124 -> 5 damage) and dies to
#// the 4-power counter, emptying P1's arena.
#// Discriminating: without the historical gate the Action would soft-pass and LAW_124 would sit at 2.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_128 SOR_095 TWI_173]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5
P1LEADER:EXHAUSTED

---

# Front_TwoEnteredBOTHDefeated_NoAttack_NotEvenByALongStandingUnit
#// TS26_04 Padmé (front) — the gate is met but there is nobody left who ENTERED this phase to attack with.
#// SOR_046 has been in play since before this phase: friendly, alive, and NOT eligible. Two SOR_128 enter
#// and Blood Sport kills both (SOR_046 survives at 2). LAW_124 ends at 2 damage — from Blood Sport only,
#// never from an attack — and Padmé still exhausts.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: [SOR_128 SOR_128 TWI_173]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED
P1NODECISION

---

# Front_EnteredUnderFRIENDLYControlStillCounts_EvenAfterBeingSTOLEN
#// TS26_04 Padmé (front) — the GATE reads control at ENTRY, the ATTACKER POOL reads control NOW. P1 plays
#// SOR_095 and SEC_080; P2 steals SOR_095 with SOR_224 Change of Heart. Two units still entered under P1's
#// control, so the gate holds and the remaining SEC_080 attacks LAW_124 for 3 (dying to the counter).
#// Without the historical gate this soft-passes and LAW_124 takes nothing.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
WithActivePlayer: 1
WithP2Resources: 10
WithP1Hand: [SOR_095 SEC_080]
WithP2Hand: SOR_224
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P2>Pass
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:EXHAUSTED

---

# Front_EnteredUnderENEMYControlDoesNOTCount_EvenAfterComingAcross
#// TS26_04 Padmé (front) — the mirror. P1 plays ONE unit; P2 plays LAW_233 Galen Erso and hands it over.
#// P1 now CONTROLS two units but only one ENTERED under P1's control, so the gate fails: no attack, the
#// enemy takes nothing, and Padmé still exhausts. A gate counting currently-controlled units would fire.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
WithActivePlayer: 1
WithP2Resources: 10
WithP1Hand: SOR_095
WithP2Hand: LAW_233
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# Deployed_NothingEnteredThisPhase_NoChainOffer
#// TS26_04 Padmé (deployed) — "When Attack Ends: you may attack with ANOTHER friendly unit that entered
#// play this phase." With only a pre-seated SEC_080 on board, nothing entered this phase, so her attack
#// resolves alone (LAW_124 takes her 5) and no chain offer is raised.

## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_04:1:1;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1NODECISION

---

# Deployed_EnteredUnderENEMYControlButNowFRIENDLY_IsAValidChainAttacker
#// TS26_04 Padmé (deployed) — the deployed side has no historical gate: it simply offers a unit that is
#// friendly NOW and entered play this phase, whoever controlled it at entry. P2 plays LAW_233 Galen Erso
#// and hands it to P1; when Padmé's attack ends the chain offer is exactly that Galen.
#// Guard for the any-seat fix: the entry flag is stamped under the ENTRY controller, so reading only
#// $player's flags silently skipped a unit that came across.
#// Note this is the OPPOSITE of her front side, which counts entries under YOUR control.

## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_04:1:1;myResources:14}
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

---

# Front_NothingEnteredAtAll_NoAttack_PadmeStillExhausts
#// TS26_04 Padmé (front) — the zero case beneath the "2 or more" boundary. SOR_046 has been in play since
#// before this phase and nothing new arrives, so the Action finds no eligible attacker at all. Per the
#// soft-pass ruling it is still a legal action: Padmé exhausts, LAW_124 takes nothing, no decision opens.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# Deployed_ATOKENUnitIsAValidChainAttacker
#// TS26_04 Padmé (deployed) — the chain offer counts tokens, matching her front side. SEC_097 Beloved
#// Orator puts two units into play from one card (itself plus a Spy token); when deployed Padmé's attack
#// on LAW_124 ends, BOTH are offered as the chained attacker — the token is not excluded.

## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_04:1:1;myResources:18}
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

# Deployed_TheOnlyEnteredUnitWasDefeated_NoChainOffer
#// TS26_04 Padmé (deployed) — unlike her front side, the deployed chain has no historical gate: it needs a
#// unit that is alive and friendly NOW. SOR_128 (3/1) enters, then TWI_173 Blood Sport deals 2 to each
#// ground unit and kills it (Padmé survives at 2, LAW_124 sits at 2). Padmé attacks the base for 5 and the
#// chain offer never appears — only she is left in the arena.

## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_04:1:1;myResources:18}
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
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Deployed_EnteredUnderFRIENDLYControlButNowENEMY_IsNotAChainAttacker
#// TS26_04 Padmé (deployed) — the mirror of Deployed_EnteredUnderENEMYControlButNowFRIENDLY. P1 plays
#// SOR_095, P2 steals it with SOR_224 Change of Heart. It entered under P1's control, but the deployed
#// chain reads control NOW, so it is not offered: Padmé's attack on LAW_124 (5 damage) resolves alone.
#// Her FRONT side takes the opposite view and would still count that entry toward its 2-unit gate.

## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_04:1:1;myResources:18}
SkipPreGame: true
WithActivePlayer: 1
WithP2Resources: 10
WithP1Hand: SOR_095
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
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# Deployed_ChainStillOffered_WhenPADMEHerselfDiesInTheAttack
#// TS26_04 Padmé (deployed) — CR 16.c: a "When Attack Ends" ability triggers even when its own unit is
#// defeated by combat damage. SOR_128 is played (so it entered this phase), then Padmé (5/6) attacks Army
#// of the Dead (7/6): she deals 5 and takes 7, dying. Her attack-end chain STILL fires, and SOR_128 —
#// exhausted from having just been played, which the chain explicitly allows — swings for 3, finishing
#// LOF_236 off at 8 total and dying to its 7 counter. Both arenas end empty.
#// Discriminating: while the survival gate applied to every attack-end card, this offer never appeared and
#// LOF_236 survived on 5. Padmé's handler uses its mzID only to exclude HERSELF from "another friendly
#// unit" — a condition a dead attacker satisfies for free, which is why she is safe on the no-mzID path.

## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_04:1:1;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_128
WithP2GroundArena: LOF_236:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# TeamSuns_Front_ATeammatesEntrantCountsTowardTheTwo_ButOnlyYourOwnUnitAttacks
#// TS26_04 Padmé (front) in Team Suns (seats 1+3 vs 2+4). The GATE says "friendly", which spans the team
#// (owner ruling, IBH_095): P1's SOR_095 plus teammate P3's SOR_095 are two friendly entrants. The
#// ATTACKER is a controller-only verb ("attack with") so only P1's own entrant can swing. It attacks
#// enemy P2's LAW_124 (the only non-base target) for 3 and dies to the counter.
#// BUG THIS PINS: the gate counted only P1's own flags (1 here), so the Action soft-passed.
## GIVEN
CommonSetup4P: ggw/ggw/ggw/ggw/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1Hand: SOR_095
WithP2GroundArena: LAW_124:1:0
WithP3Resources: 10
WithP3Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P2>Pass
- P3>PlayHand:0
- P4>Pass
- P1>UseLeaderAbility
## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED

---

# TeamSuns_Front_ATeammatesUnitCannotBeTheAttacker
#// TS26_04 Padmé (front) in Team Suns — the attacker pool stays P1's own. Teammate P3 plays SEC_097 Beloved
#// Orator, which puts TWO units into play (itself + a Spy token): the team-wide gate is met, but P1 has no
#// entrant of its own to attack with, so the Action resolves with no attack (soft pass): no decision,
#// LAW_124 untouched, Padmé exhausted. Catches an attacker pool widened to the team.
## GIVEN
CommonSetup4P: ggw/ggw/ggw/ggw/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 3
WithP2GroundArena: LAW_124:1:0
WithP3Resources: 10
WithP3Hand: SEC_097
## WHEN
- P3>PlayHand:0
- P4>Pass
- P1>UseLeaderAbility
## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# TwinSuns3P_Front_OPPONENTEntrantsDoNotCount
#// TS26_04 Padmé (front), free-for-all Twin Suns (no teams). P1 plays one unit and each opponent plays one.
#// With no teammates the gate sees one friendly entrant and fails: no attack, LAW_124 untouched, Padmé
#// exhausts. Confirms the team widening leaves free-for-all untouched.
## GIVEN
CommonSetup3P: ggw/ggw/ggw/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_095
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 10
WithP2Hand: SOR_095
WithP3Resources: 10
WithP3Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P3>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
SEATCOUNT:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION

---

# TwinSuns4P_Front_OwnTwoEntrantsCount_OpponentsAreNotOffered
#// TS26_04 Padmé (front), free-for-all Twin Suns at 4P. P1 plays two units across two turns while all three
#// opponents play one each. The gate is met by P1's own two, and the attacker offer is EXACTLY those two.
## GIVEN
CommonSetup4P: ggw/ggw/ggw/ggw/{myLeader:TS26_04;myResources:18}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 10
WithP2Hand: SOR_095
WithP3Resources: 10
WithP3Hand: SOR_095
WithP4Resources: 10
WithP4Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P3>PlayHand:0
- P4>PlayHand:0
- P1>PlayHand:0
- P2>Pass
- P3>Pass
- P4>Pass
- P1>UseLeaderAbility
## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
