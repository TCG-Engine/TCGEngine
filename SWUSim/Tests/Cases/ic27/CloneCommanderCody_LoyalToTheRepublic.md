# PlayARepublicUnit_ItGetsAnExperience
#// IC27_076 Clone Commander Cody - Loyal to the Republic (Unit, Ground 4/4, cost 4, [Command,Heroism]) — "When another
#// friendly Republic unit enters play: Give an Experience token to it."
#//
#// COVERAGE: positive=PlayARepublicUnit_ItGetsAnExperience · negative (trait)=ANonRepublicUnit_GetsNothing ·
#//   "another"=CodyEntering_GivesNothing (nor to a Republic unit already in play) · "friendly"=AnEnemyRepublicUnit_GetsNothing ·
#//   dispatch paths (every way to ENTER play): played (above) · created tokens=DropIn_EachCloneTrooperGetsOne ·
#//   played + its own token=BatchBrothers_TheUnitAndItsTokenEachGetOne · deployed leader=DeployARepublicLeader_ItGetsOne ·
#//   rescued (CR 8.34.3 "considered to enter play")=ARescuedRepublicUnit_GetsOne ·
#//   blank=CodyBlanked_GivesNothing · control=AStolenCody_GivesToItsController (owner ≠ controller) ·
#//   reqboundary=RequestBoundary_TheExperienceSurvivesTheEnteringUnitsPrompt ·
#//   modes=2P,TeamSuns,TwinSuns ("friendly" spans the team — TeamSuns_ATeammatesCodyGivesToMine /
#//   TwinSuns_AnOpponentsCodyGivesNothing)
#//
#// TWI_093 Advanced Recon Commando (Republic 4/3) is played next to Cody and enters with an Experience token: 5/4.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: TWI_093
WithP1GroundArena: IC27_076:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_093
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# ANonRepublicUnit_GetsNothing
#// The trait gate: SOR_095 Battlefield Marine is a Rebel, not Republic.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1GroundArena: IC27_076:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# CodyEntering_GivesNothing
#// "another": Cody entering play is not another unit, and a Republic unit ALREADY in play did not enter — nobody gets one.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: IC27_076
WithP1GroundArena: TWI_093:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:IC27_076
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# AnEnemyRepublicUnit_GetsNothing
#// "friendly": P2 plays a Republic unit on P2's turn — Cody gives it nothing.
## GIVEN
CommonSetup: ggw/ggw/{theirResources:3}
WithP1GroundArena: IC27_076:1:0
WithP2Hand: TWI_093
## WHEN
- P1>Pass
- P2>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:TWI_093
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# DropIn_EachCloneTrooperGetsOne
#// Created token units ENTER play too: TWI_251 Drop In creates two Republic Clone Trooper tokens, and each gets one.
## GIVEN
CommonSetup: ggw/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: TWI_251
WithP1GroundArena: IC27_076:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:CARDID:TWI_T02
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1

---

# BatchBrothers_TheUnitAndItsTokenEachGetOne
#// TWI_144 Batch Brothers (Republic) enters — one Experience — and its When Played creates a Clone Trooper token — another
#// entry, another Experience. Aggression is off-aspect under Leia: 3 + 2.
## GIVEN
CommonSetup: ggw/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: TWI_144
WithP1GroundArena: IC27_076:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:TWI_144
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:CARDID:TWI_T02
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1

---

# DeployARepublicLeader_ItGetsOne
#// A deployed leader is not PLAYED, but it ENTERS play: TWI_011 Ahsoka Tano (Republic) deploys and gets one.
## GIVEN
CommonSetup: ggw/rrk/{myResources:5;myLeader:TWI_011}
P1OnlyActions: true
WithP1GroundArena: IC27_076:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_011
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# ARescuedRepublicUnit_GetsOne
#// CR 8.34.3: a rescued unit "is considered to enter play" (it is not played). Cody defeats P2's Death Star Stormtrooper,
#// which was guarding P1's captured Advanced Recon Commando: it returns to P1's ground arena with an Experience token.
## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_076:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaCaptive: 0:TWI_093
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_093
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# CodyBlanked_GivesNothing
#// A Cody that has lost its abilities (SOR_138 Force Lightning's blank) gives nothing.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: TWI_093
WithP1GroundArena: IC27_076:1:0:SOR_138
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_093
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# AStolenCody_GivesToItsController
#// P1 controls a Cody that P2 OWNS: "friendly" is judged from the controller, so P1's Republic unit gets the Experience.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: TWI_093
WithP1GroundArenaControlled: IC27_076:2
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_093
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# RequestBoundary_TheExperienceSurvivesTheEnteringUnitsPrompt
#// TWI_091 Republic Tactical Officer gets its Experience on entry, then its When Played ("you may attack with a Republic
#// unit") ends the request. Declined in the next one, the token is still there: 2/5.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: TWI_091
WithP1GroundArena: IC27_076:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_091
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:2

---

# TeamSuns_ATeammatesCodyGivesToMine
#// "friendly" spans the TEAM (user ruling 2026-08-26): seat 3's Cody gives P1's entering Republic unit an Experience.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: TWI_093
WithP3GroundArena: IC27_076:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:TWI_093
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# TwinSuns_AnOpponentsCodyGivesNothing
#// THE CONTROL — the same table without teams: seat 3 is an opponent, so its Cody gives nothing.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: TWI_093
WithP3GroundArena: IC27_076:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:CARDID:TWI_093
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
