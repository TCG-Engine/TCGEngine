# WhenPlayed_ReturnsFriendly
#// SOR_209 Pirated Starfighter (2/4, Space, Raid 1) — When Played: Return a friendly non-leader
#// unit to its owner's hand (mandatory). P1 has one other friendly non-leader unit (Battlefield
#// Marine) which is returned to hand. (Raid 1 is an auto keyword; this tests only the return.)
#// COVERAGE: offer=Offer_IncludesSelf_ExcludesDeployedLeader (pending SELECTABLEEXACT: the
#//           Starfighter itself + the ground unit; deployed leader unit excluded) · decline=N/A
#//           (the return is mandatory — no "you may") · control=ControlledEnemyUnit_ReturnsToOwnersHand
#//           (P1-controlled, P2-owned unit returns to the OWNER'S hand) ·
#//           boundary=NoOtherUnits_AutoReturnsItself (pool of exactly one → auto-resolve, cost
#//           still paid) vs multi-candidate prompt (WhenPlayed_ReturnsFriendly) ·
#//           reqboundary=CanReturnItself (play and pick span separate requests)

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_209
WithP1GroundArena: SEC_080:1:0    # friendly non-leader unit — returned to hand

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1HANDCOUNT:1

---

# NoOtherUnits_AutoReturnsItself
#// SOR_209 Pirated Starfighter — the return is MANDATORY and the Starfighter itself is a
#// legal target once it is in play: with no other friendly units it is the SOLE candidate,
#// so the pick auto-resolves (no prompt) and the Starfighter bounces straight back to hand.
#// The play still happened: 2 resources stay exhausted.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_209

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_209
P1RESAVAILABLE:2
P1NODECISION

---

# Offer_IncludesSelf_ExcludesDeployedLeader
#// SOR_209 Pirated Starfighter — the return pool is every friendly NON-LEADER unit,
#// including the Starfighter itself; P1's deployed leader unit is excluded. Two candidates
#// (ground unit + the Starfighter) → the pick stays pending and the offer is asserted.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4;myLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SOR_209
WithP1GroundArena: SEC_080:1:0    # friendly non-leader candidate (leader unit sits at ground index 1)

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# CanReturnItself
#// SOR_209 Pirated Starfighter — picking ITSELF returns it to hand while the other friendly
#// unit stays seated.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_209
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_209

---

# ControlledEnemyUnit_ReturnsToOwnersHand
#// SOR_209 — "to its OWNER'S hand": P1 controls a unit OWNED by P2 (post-control-change
#// state). Picking it as the return target sends it to P2's hand, not P1's.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_209
WithP1GroundArenaControlled: SEC_080:2   # P1-controlled, P2-owned

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1HANDCOUNT:0
P2HANDCOUNT:1
