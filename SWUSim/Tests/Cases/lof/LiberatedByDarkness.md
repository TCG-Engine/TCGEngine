# TakeControl
#// LOF_189 Liberated by Darkness — Use the Force; if you do, take control of a non-leader unit (its owner
#// takes control back at regroup). P1 uses the Force and steals SOR_046 into its own arena.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5;handCardIds:LOF_189}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENACOUNT:0
P1NOFORCE

---

# StealEnemy_RevertsAtRegroup
#// LOF_189 — P1 uses the Force to steal the enemy SOR_046 (arena flips to P1). Then both players pass into
#// the regroup phase, where the delayed effect returns the unit to its owner (P2). Arena flips back to P2.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5;handCardIds:LOF_189}
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_046,SOR_046,SOR_046
WithP2Deck: SOR_046,SOR_046,SOR_046

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENACOUNT:0
P1NOFORCE

---

# NoForce_NothingHappens
#// LOF_189 — with no Force token, the event fizzles: no unit is taken. It resolves to the discard and the
#// enemy unit stays in P2's arena.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5;handCardIds:LOF_189}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# StealOwnUnitBack_StaysWithOwnerAtRegroup
#// LOF_189 Liberated by Darkness — the delayed clause is "its OWNER takes control of it", not "the
#// previous controller". When the stolen unit is one P1 already OWNS (SOR_046 sits in P2's arena but is
#// owned by P1 — the end state after P2 took control of it earlier), P1 uses the Force to take it back
#// and the regroup revert is a no-op: P1 is the owner, so it simply STAYS in P1's arena.
#// Contrast StealEnemy_RevertsAtRegroup above, where the owner is P2 and the unit does flip back.

## GIVEN
CommonSetup: yyk/ggw/{myResources:5;handCardIds:LOF_189}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArenaControlled: SOR_046:1
WithP1Deck: [SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENACOUNT:0

---

# StolenOwnUnitDefeatedBeforeRegroup_NoRevert
#// LOF_189 Liberated by Darkness — the delayed "its owner takes control" simply finds nothing if the unit
#// has left play. P1 takes back its own SOR_046 (owned by P1, controlled by P2), then P2 defeats it with
#// SOR_050 Vanquish before the round ends. At regroup the delayed effect resolves harmlessly: the unit
#// stays in the discard (its OWNER's — P1's), no error and nothing returns to an arena.
#// ⚠ P2's base/leader must cover Vigilance for SOR_078 Vanquish (Vigilance, cost 5) — an uncovered
#// aspect adds +2 and the play silently no-ops.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5;handCardIds:LOF_189;theirResources:6}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Force: true
WithP2GroundArenaControlled: SOR_046:1
WithP2Hand: SOR_078
WithP1Deck: [SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# CannotTakeControlOfPilotedLeaderUnit
#// LOF_189 Liberated by Darkness — "take control of a NON-LEADER unit". A Vehicle carrying a deployed
#// leader as a Pilot IS a leader unit (its printed type is still Unit, so this must be decided by
#// IsLeaderUnit, not by CardType), and therefore is not a legal target. With P2's only unit being such a
#// piloted leader unit, the event has nothing to take: the Force is still spent and the card is played,
#// but no unit changes arenas and no decision is left pending.

## GIVEN
CommonSetup: yyk/ggw/{
  myResources:5;
  handCardIds:LOF_189;
  theirLeader:JTL_008;
  theirLeaderDeployedPilot:true
}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P1NODECISION

---

# StolenUnitBecomesLeaderUnit_DefeatedAtRegroup
#// LOF_189 Liberated by Darkness — "At the start of the regroup phase, its OWNER takes control of it."
#// A LEADER UNIT can never be controlled by anyone but its leader's controller, so if the stolen unit has
#// become one before the delayed effect resolves, the revert cannot happen and the unit is DEFEATED
#// instead. P1 uses the Force to steal P2's Vehicle SEC_214, then deploys its own leader JTL_008 Wedge
#// Antilles onto it as a Pilot — making it a leader unit under P1's control. At regroup the delayed
#// effect fires: SEC_214 goes to its OWNER's discard (P2's) and Wedge returns to the leader zone
#// exhausted (a leader never goes to a discard).
#// ⚠ Overriding myLeader to JTL_008 (Command/Heroism) leaves LOF_189's Villainy uncovered, so it costs
#// 5+2=7 — budget resources accordingly or the play silently no-ops.

## GIVEN
CommonSetup: yyk/ggw/{
  myResources:12;
  handCardIds:LOF_189;
  myLeader:JTL_008
}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SEC_214:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDUNIT:0:CARDID:SEC_214
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED
