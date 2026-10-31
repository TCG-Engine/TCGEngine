# Front_StartingHand_DrawsTwoMore
#// IC27_006 Luke Skywalker (Leader, cost 6, [Aggression][Heroism]). FRONT: "Draw 2 more cards in your starting
#// hand." DEPLOYED (4/6): "Overwhelm. On Attack: You may discard a card from your hand. If you do, this unit gets
#// +2/+0 for this attack." (ASH_172 Razor Crest's sentence word for word; the leader dispatches to its closure.)
#// P1 (Luke) draws 8 and resources 2 → hand 6; P2 (an ordinary leader) draws 6 → hand 4.
#// The production deal and the mulligan redraw are covered by DevTools/tdd-regression/test_ic27006_luke_starting_hand.php
#// (a real SWUSetupGame game) — this schema form exercises the harness's pregame count, which reads the same helper.
#// COVERAGE: offer=N/A (front: no choice; deployed: the hand pick is "a card from your hand", every card legal) ·
#//           decline=Deployed_OnAttack_Decline_NoDiscardNoBonus · boundary=Front_WithColossus_ModifiersAdd (the
#//           +2 is pinned against the base's -1) · control=N/A (a leader cannot change control; the hand and
#//           discard read are the leader's controller's own) ·
#//           reqboundary=Deployed_RequestBoundary_DiscardPickInAFreshRequest
#//           modes=2P only (front + deployed text name no player and say no friendly/enemy; Overwhelm's spill is
#//           the keyword's own seat logic)

## GIVEN
P1LeaderBase: IC27_006/SOR_024
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: SOR_014/SOR_024
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitChoice: 1
## WHEN
- P1>MulliganNo
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0

## EXPECT
P1HANDCOUNT:6
P2HANDCOUNT:4
P1RESCOUNT:2
P1DECKCOUNT:12
P2DECKCOUNT:14

---

# Front_WithColossus_ModifiersAdd
#// Luke's +2 and JTL_021 Colossus's -1 both apply: 6 + 2 - 1 = 7 drawn, hand 5 after resourcing 2. (Separates
#// "+2" from "set to 8" and from "the larger modifier wins".)

## GIVEN
P1LeaderBase: IC27_006/JTL_021
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: SOR_014/SOR_024
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitChoice: 1
## WHEN
- P1>MulliganNo
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0

## EXPECT
P1HANDCOUNT:5
P2HANDCOUNT:4

---

# Front_OpponentsLuke_DoesNotChangeYourHand
#// The leader modifies its OWN player's starting hand: P2 leads with Luke (hand 6 after resourcing), P1 does not
#// (hand 4).

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2LeaderBase: IC27_006/SOR_024
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
InitChoice: 1
## WHEN
- P1>MulliganNo
- P1>ResourceHand:0
- P1>ResourceHand:0
- P2>MulliganNo
- P2>ResourceHand:0
- P2>ResourceHand:0

## EXPECT
P1HANDCOUNT:4
P2HANDCOUNT:6

---

# Deployed_OnAttack_DiscardForPlusTwo
#// Deployed Luke (4/6) attacks P2's base. YES → his only hand card is discarded (a lone card auto-resolves) and he
#// gets +2/+0 for this attack: 6 damage. "for this attack": his power reads 4 again afterwards.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:IC27_006;myLeaderDeployed:true;myhandCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1NODECISION

---

# Deployed_OnAttack_Decline_NoDiscardNoBonus
#// NO with a card in hand: nothing is discarded and Luke deals his printed 4.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:IC27_006;myLeaderDeployed:true;myhandCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1NODECISION

---

# Deployed_OnAttack_EmptyHand_NoPrompt
#// Cannot pay: with an empty hand nothing is offered and Luke deals 4.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:IC27_006;myLeaderDeployed:true}
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1NODECISION

---

# Deployed_Overwhelm_SpillsTheBonusedExcess
#// Deployed Luke has Overwhelm. With the +2 he hits P2's 3/3 Battlefield Marine for 6: 3 to defeat it, 3 excess to
#// P2's base.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:IC27_006;myLeaderDeployed:true;myhandCardIds:SOR_095}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# Deployed_RealDeploy_ThenAttack
#// Through the real path: the Epic Action deploys Luke (6 resources), he enters ready and attacks; the On Attack is
#// dispatched off the deployed leader unit.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:IC27_006;myResources:6;myhandCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:DEPLOYED
P2BASEDMG:6
P1HANDCOUNT:0

---

# Deployed_RequestBoundary_DiscardPickInAFreshRequest
#// Two cards in hand, so the discard pick is a real choice; both answers cross a fresh request. The chosen card
#// (the second) is discarded and the +2 still applies.

## GIVEN
CommonSetup: rrw/rrk/{myLeader:IC27_006;myLeaderDeployed:true;myhandCardIds:SOR_095,SEC_080}
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-1

## EXPECT
P2BASEDMG:6
P1HANDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_080
