# OppUpgrade_GiveExp
#// SEC_095 Theed Security (Ground, 2/3) — When Played: if an opponent controls an upgrade, give an
#//   Experience token to a unit. Enemy SOR_095 bears SOR_120 → give Experience to a friendly.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SEC_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# NoOppUpgrade_NoExperience
#// SEC_095 Theed Security — the When Played Experience grant is gated on an opponent controlling an
#//   upgrade. With no enemy upgrade anywhere, SEC_095 just enters play; no Experience token is granted and
#//   there is no target decision. P2's SOR_095 has no upgrade.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# OppUpgradeOnAFRIENDLYUnit_StillCounts
#// SEC_095 Theed Security — the gate is "if an opponent CONTROLS an upgrade", and per CR 2.e a player who
#// plays an upgrade onto an ENEMY unit REMAINS its controller. So an upgrade P2 played onto P1's OWN unit
#// still counts as an opponent-controlled upgrade. P2 plays Ambition's Reward (SEC_175, enemy-attachable)
#// onto P1's Battlefield Marine; P1 then plays Theed Security and the Experience grant IS offered.
#// This is the case a "scan the opponent's units for upgrades" implementation would miss.
## GIVEN
CommonSetup: ggw/rrk/{theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP2Hand: SEC_175
WithP1Hand: SEC_095
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# OppLeaderPilotUpgrade_Counts
#// SEC_095 Theed Security — a leader deployed AS A PILOT is an upgrade its controller controls, so an
#// opponent's leader-pilot satisfies the gate. P2 has JTL_001 Asajj Ventress deployed as a Pilot on their
#// Vehicle; P1 plays Theed Security and the Experience grant is offered.
## GIVEN
CommonSetup: ggw/rrk/{theirLeader:JTL_001;theirLeaderDeployedPilot:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP2GroundArena: SOR_183:1:0
WithP1Hand: SEC_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# OurOwnUpgradeOnAnEnemyUnit_DoesNotCount
#// SEC_095 Theed Security — the mirror of the controller rule, and the negative that makes the fix
#// load-bearing: an upgrade WE control that we played onto an ENEMY unit is still OURS (CR 2.e), so it
#// does NOT satisfy "an opponent controls an upgrade". P1 plays Ambition's Reward (SEC_175) onto P2's
#// Battlefield Marine, then plays Theed Security — no Experience is offered. A naive "is there an upgrade
#// on an enemy unit?" scan would wrongly fire here. (Ambition'''s Reward also creates a Spy token, which
#// occupies P1'''s ground index 0, so Theed Security lands at index 1.)
## GIVEN
CommonSetup: ggw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_175
WithP1Hand: SEC_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SEC_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
