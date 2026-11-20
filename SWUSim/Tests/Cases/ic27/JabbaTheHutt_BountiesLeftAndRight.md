# Action_AttackerIntoTheChosenUnitGetsPlusFour
#// IC27_060 Jabba the Hutt, Bounties Left and Right — Unit (Ground) 0/8, cost 3, [Command][Villainy], Underworld/Hutt,
#// unique.
#// Text: Action [Exhaust]: Choose an enemy unit. For this phase, while that unit is defending, the attacker gets +4/+0.
#// COVERAGE: offer=Offer_EnemyUnitsBothArenas (friendly unit excluded) · decline=N/A — "choose", no "may" ·
#//           no target=NoEnemyUnit_StillExhausts (CR 6.4.587.c) · quantity=this (a 1-power Battle Droid deals exactly 5) +
#//           TeamSuns_TwoJabbas_Stack (+8) · scope=OtherEnemyUnit_NoBonus + ChosenUnitAttacking_NoBonus (only while it
#//           DEFENDS) · duration=SecondAttackSamePhase_AlsoBonus (phase, not one attack) +
#//           NextPhase_BonusGone (expiry) · control=Control_ChosenUnitChangesSides_AnyAttackerGetsIt (the marker follows
#//           the unit; "the attacker" is whoever attacks it) · turn=ChosenUnitAttacking_NoBonus (no P1OnlyActions:
#//           TURNPLAYER after the Action) · blanked=Blanked_NoAction · reqboundary=RequestBoundary_BeforeTheChoice ·
#//           modes=2P,TeamSuns ("enemy" is team-relative) · TwinSuns covered by the same pool helper.
#//
#// ⚠ PREVIEW SET — IC27 is absent from card-specific-rulings.md. Modelled on TWI_072 I Have the High Ground (a phase
#// marker on the DEFENDING unit that changes the attacker's power). Read as a lasting effect on the chosen unit (CR 7.4):
#// it is not tied to Jabba staying in play, and it applies to ANY attacker of that unit.
#//
#// THE POSITIVE. Jabba marks Consular Security Force (3/7). A Battle Droid token (1/1) attacks it for 1 + 4 = 5.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_060:1:0 TWI_T01:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
- P1>AttackGroundArena:1:1
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:DAMAGE:5
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Offer_EnemyUnitsBothArenas
#// "an ENEMY unit" in either arena: P2's ground and space units are offered; P1's own Battle Droid is not.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_060:1:0 TWI_T01:1:0]
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# NoEnemyUnit_StillExhausts
#// No enemy unit: the Action is still usable (its [Exhaust] cost changes game state, CR 6.4.587.c) and resolves to
#// nothing — no dangling decision.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_060:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# OtherEnemyUnit_NoBonus
#// Only THAT unit: marking Security Force #0, then attacking Security Force #1 deals the Droid's plain 1.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_060:1:0 TWI_T01:1:0]
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:1:1
## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ChosenUnitAttacking_NoBonus
#// Only while it is DEFENDING: the marked Battlefield Marine attacks P1's base for its plain 3. Run without
#// P1OnlyActions, so the Action's single close hands the turn to P2.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: IC27_060:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
TURNPLAYER:2
NOEXTRAACTION

---

# ChosenUnitAttacking_BaseTakesPlainPower
#// Same board, P2 then attacks with the marked unit: P1's base takes 3, not 7.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: IC27_060:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3

---

# SecondAttackSamePhase_AlsoBonus
#// "For this phase", not "for this attack": two Battle Droids attack the marked Security Force (3/7) one after the
#// other, 5 + 5 = 10, and it is defeated. A one-attack duration would leave it on 5 + 1 = 6 damage.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_060:1:0 TWI_T01:1:0 TWI_T01:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
- P1>AttackGroundArena:1:1
- P1>AttackGroundArena:1:1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095

---

# NextPhase_BonusGone
#// The duration ENDS: after the regroup the Droid (readied) attacks the same Security Force for its plain 1.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP1GroundArena: [IC27_060:1:0 TWI_T01:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:1:1
## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:1

---

# Control_ChosenUnitChangesSides_AnyAttackerGetsIt
#// The effect is on the chosen UNIT, and "the attacker" is whoever attacks it. P1 marks P2's Hunter For Hire (4/4), then
#// takes control of it with its own Action (defeating P1's Credit). P2's Battlefield Marine attacks it: 3 + 4 = 7 kills
#// it (a plain 3 would leave it on 3 damage).
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
WithActivePlayer: 1
WithP1Credits: 1
WithP1GroundArena: IC27_060:1:0
WithP2GroundArena: [LAW_156:1:0 SOR_095:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>UseUnitAbility:theirGroundArena-0
- P2>AttackGroundArena:0:1
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_060

---

# Blanked_NoAction
#// A Jabba that has lost its abilities (SOR_138 marker) has no Action: nothing happens and he stays ready.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_060:1:0:SOR_138
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:READY

---

# RequestBoundary_BeforeTheChoice
#// The choice is answered in a later request; the marker still lands and the Droid deals 5.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [IC27_060:1:0 TWI_T01:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1
- P1>AttackGroundArena:1:1
## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:5

---

# TeamSuns_OffersBothEnemySeats_NotTheTeammate
#// TEAM SUNS (1+3 vs 2+4): "enemy" is team-relative. Seat 2's and seat 4's units are offered; teammate P3's is not.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: IC27_060:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_046:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1SELECTABLEEXACT:p2GroundArena-0&p4GroundArena-0

---

# TeamSuns_TwoJabbas_Stack
#// Uniqueness is per player: P1 and teammate P3 each control a Jabba and both mark seat 2's Security Force (3/7). Two
#// separate effects: P1's Battle Droid attacks it for 1 + 4 + 4 = 9 and defeats it. One effect would leave it on 5.
#// Turn order: P1 → P2 → P3 → P4 → P1.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: [IC27_060:1:0 TWI_T01:1:0]
WithP3GroundArena: IC27_060:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:p2GroundArena-1
- P2>Pass
- P3>UseUnitAbility:myGroundArena-0
- P3>AnswerDecision:p2GroundArena-1
- P4>Pass
- P1>AttackGroundArena:1:p2GroundArena-1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
