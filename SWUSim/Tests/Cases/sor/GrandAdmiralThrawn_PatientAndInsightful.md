# APS_LeaderUnit_LooksAtBothDecks
#// SOR_016 Grand Admiral Thrawn — APS passive fires when Thrawn is deployed as a leader unit.
#// Thrawn deployed (leader zone Deployed=true, linked ground-arena leader unit). Same as the leader-side
#// test: PASS both players into a regroup and loop back to a NEW action phase (READY -> APS) to fire
#// ActionPhaseStart → the deck peek logs private REVEAL entries. Decks hold 3 cards so one survives the draw.

## GIVEN
CommonSetup: gyk/grw/{
  myLeader:SOR_016:1:1:1
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SOR_128
WithP2Deck: SOR_128
WithP2Deck: SOR_128

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
LOGCONTAINS: top of P1
LOGCONTAINS: top of P2
PHASE:MAIN

---

# APS_LooksAtBothDecks
#// SOR_016 Grand Admiral Thrawn — APS passive: looks at top of both decks at the start of the action phase.
#// The harness loads directly into MAIN, so ActionPhaseStart never fires on load. To exercise the
#// start-of-action-phase passive we PASS both players into a regroup and loop back to a NEW action
#// phase (READY -> APS), which fires ActionPhaseStart with Thrawn as leader → private REVEAL entries.
#// Decks hold 3 cards each so one card remains after the regroup's 2-card draw for the peek to see.

## GIVEN
CommonSetup: gyk/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SOR_128
WithP2Deck: SOR_128
WithP2Deck: SOR_128

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
LOGCONTAINS: top of P1
LOGCONTAINS: top of P2
PHASE:MAIN

---

# Deploy
#// SOR_016 Grand Admiral Thrawn — Deploy: leader becomes 3/9 ground unit. Deploy is free (6 resources stay available).

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_016
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:9
P1RESCOUNT:6
P1RESAVAILABLE:6

---

# LeaderAction_ChooseTarget
#// SOR_016 Grand Admiral Thrawn — Leader Action: two valid targets → MZCHOOSE → player picks opponent's unit.
#// Top of P1 deck = SOR_095 (cost 2). Both P1 and P2 have a SOR_095 (cost 2 <= 2).

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0

---

# LeaderAction_NoValidTargets
#// SOR_016 Grand Admiral Thrawn — Leader Action: top deck card cost 1 (SOR_128), only unit in play costs 2 → no valid exhaust targets.
#// Leader still exhausts and resource is spent; opponent's unit remains ready.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_128
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0

---

# LeaderAction_OpponentDeck
#// SOR_016 Grand Admiral Thrawn — Leader Action: choose opponent's deck (top = SOR_095, cost 2).
#// Same effect as own deck but cost derived from opponent's top card.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP2Deck: SOR_095
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0

---

# LeaderAction_OwnDeck
#// SOR_016 Grand Admiral Thrawn — Leader Action: choose own deck (top = SOR_095, cost 2).
#// Only one valid target (theirGroundArena-0, cost 2 <= 2) → auto-exhausted via PASSPARAMETER.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0

---

# LeaderAction_Unaffordable
#// SOR_016 Grand Admiral Thrawn — Leader Action costs [1 resource, exhaust]. With 0 ready
#// resources the cost cannot be paid, so the action is a no-op: the leader stays ready,
#// nothing is queued, and the player keeps their action.

## GIVEN
CommonSetup: gyk/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESCOUNT:0
P1NODECISION

---

# OnAttack_No
#// SOR_016 Grand Admiral Thrawn Deployed — OnAttack NO: declines ability. Friendly unit stays ready.

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED
P1LEADER:DEPLOYED

---

# OnAttack_Yes
#// SOR_016 Grand Admiral Thrawn Deployed — OnAttack YES: reveal own deck top (SOR_095, cost 2), exhaust cost-2 friendly unit.
#// Thrawn (index 1 after SOR_095 placed at index 0) attacks P2 base. P1's SOR_095 gets auto-exhausted (only target).

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1LEADER:DEPLOYED
P1LEADER:EPICUSED
