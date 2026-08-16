# DefeatChosenUnit
#// SHD_079 Rival's Fall (6-cost event, Vigilance) — "Defeat a unit." Any unit is a valid target; with a
#// friendly and an enemy unit present it's a real choice. P1 chooses the enemy SOR_128 → defeated; the
#// friendly SEC_080 survives.
#// COVERAGE: offer=DefeatChosenUnit (answered out of a two-sided pool — a friendly and an enemy unit) +
#//           DefeatDeployedLeader (answered out of a pool that also contains a deployed leader unit) ·
#//           decline=N/A (mandatory "Defeat a unit", no "you may") · control=N/A (the event defeats a
#//           unit outright; nothing changes controller) · boundary=DefeatChosenUnit /
#//           DefeatDeployedLeader (a unit exists → defeated) vs NoUnits_Fizzle (no unit → clean fizzle,
#//           no decision) · reqboundary=N/A (a single pick with no state read after it)

## GIVEN
CommonSetup: bbw/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1DISCARDCOUNT:1

---

# NoUnits_Fizzle
#// SHD_079 Rival's Fall — with no units in play the defeat has no target and fizzles cleanly; the event
#// still lands in the discard.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_079

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION

---

# DefeatDeployedLeader
#// SHD_079 Rival's Fall — "Defeat a unit" includes a deployed LEADER unit. P2 has a stormtrooper and a
#// deployed leader on the ground; P1 targets the leader → the leader unit leaves play and P2's leader
#// reverts to its undeployed side, while the stormtrooper is untouched.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6; theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_079
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2LEADER:NOTDEPLOYED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:1
