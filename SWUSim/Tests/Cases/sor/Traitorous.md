# NoStealUnit4PlusCost
#// SWUSim Replay Schema
Traitorous — attach to non-leader unit costing 4+, no steal trigger

## GIVEN
CommonSetup: grw/ggk
SkipPreGame: true
WithP1Hand: SOR_122
WithP2GroundArena: SOR_148:1:0
WithP1Resources: 5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_148
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# ReturnWhenUpgradeDefeated
#// SWUSim Replay Schema
Traitorous — when upgrade is defeated, unit returns to its owner

## GIVEN
CommonSetup: grw/ggk
SkipPreGame: true
WithP1Hand: SOR_122
WithP2Hand: SOR_251
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 5
WithP2Resources: 3

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2RESCOUNT:3
P2RESAVAILABLE:2

---

# StealUnit3Cost
#// SWUSim Replay Schema
Traitorous — attach to non-leader unit costing 3 or less, take control of it

## GIVEN
CommonSetup: grw/ggk
SkipPreGame: true
WithP1Hand: SOR_122
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# AttachOffer_AnyUnitIncludingLeaderAndExpensive
#// Intended: the ATTACH pool is unrestricted — "attach to a non-leader unit that costs 3 or
#// less" is the TAKE-CONTROL condition, not an attach restriction (per CR 2.e an upgrade with
#// no printed attach restriction can attach to any unit). The pool must offer: P1's own
#// Battlefield Marine, P2's Wampa (cost 4), P2's deployed leader Luke (leader unit), and P2's
#// Cartel Spacer in space. The attach choose is left PENDING so the pool itself is asserted.
#// COVERAGE: offer=this section · reqboundary=StealUnit3Cost (play and attach answer are
#//           separate serialized steps) + HostBecomesLeaderUnit_DefeatedInsteadOfRevert
#//           (multi-request flow) · control=DefeatOnNeverControlledHost_OwnerStillTakes (revert
#//           hands the host to its OWNER regardless of how control was gained) +
#//           HostBecomesLeaderUnit_DefeatedInsteadOfRevert (revert vs leader-unit replacement)
#//           · boundary pair=StealUnit3Cost vs NoStealUnit4PlusCost (cost 3/4) and
#//           DeployedLeader_AttachOnly vs StealUnit3Cost (leader/non-leader) · decline=N/A (the
#//           attach is mandatory once the upgrade is played; both halves of the text are "when"
#//           triggers with no "you may"). Unattach-by-MOVE (rather than defeat) is currently
#//           unencodable — cross-controller upgrade moves fizzle engine-side (reported).

## GIVEN
CommonSetup: grw/bbw/{theirLeaderDeployed:true}
WithP1Hand: SOR_122
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# DeployedLeader_AttachOnly_NoControlTake
#// Intended: attaching to a DEPLOYED LEADER unit does not take control — the take-control
#// trigger requires a NON-leader host. Luke (deployed leader, in P2's ground arena) simply
#// carries Traitorous: he stays P2's, stays a leader unit, and no decision is left pending.

## GIVEN
CommonSetup: grw/bbw/{theirLeaderDeployed:true}
WithP1Hand: SOR_122
WithP2GroundArena: SOR_164:1:0
WithP1Resources: 5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_122
P1GROUNDARENACOUNT:0
P1NODECISION

---

# DefeatOnNeverControlledHost_OwnerStillTakes
#// Intended: the unattach half has NO cost cap and does not care whether Traitorous itself
#// took control. P1 takes Wampa (cost 4) with Change of Heart, then attaches Traitorous to it
#// (cost > 3 → no take-control trigger). When P2's Confiscate defeats Traitorous, "that
#// unit's owner takes control of it" fires anyway: Wampa goes back to P2 immediately — even
#// though Change of Heart's own return is not due until regroup.

## GIVEN
CommonSetup: gyw/ggk
SkipPreGame: true
WithP1Hand: [SOR_224 SOR_122]
WithP2Hand: SOR_251
WithP2GroundArena: SOR_164:1:0
WithP1Resources: 11
WithP2Resources: 1

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1

---

# HostBecomesLeaderUnit_DefeatedInsteadOfRevert
#// Intended: per CR 3.4.6/3.4.7, if the unattach trigger would hand control of a unit that has
#// BECOME a leader unit to another player, the unit is DEFEATED instead. P1's Traitorous takes
#// P2's Millennium Falcon (cost 3, non-leader at that moment). P1 then deploys Darth Vader as a
#// Pilot onto the stolen Falcon, making it P1's leader unit. When P2's Confiscate defeats
#// Traitorous, "the unit's owner takes control" would move a leader unit to P2 — so the Falcon
#// is defeated instead: it goes to its owner P2's discard, and Vader returns to P1's leader
#// zone exhausted and undeployed. (Vader's deploy-as-upgrade rider made 2 TIE Fighter tokens —
#// they survive the Falcon's defeat and are all that is left in P1's space arena.)

## GIVEN
CommonSetup: grw/ggk/{myLeader:JTL_006}
WithP1Hand: SOR_122
WithP2Hand: SOR_251
WithP2SpaceArena: SOR_193:1:0
WithP1Resources: 6
WithP2Resources: 1

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P2>PlayHand:0
- P2>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T01
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:2
P1DISCARDCOUNT:1
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED
P2NODECISION
P1NODECISION
