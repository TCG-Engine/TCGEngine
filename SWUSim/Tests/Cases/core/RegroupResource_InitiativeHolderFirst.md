#// CORE — CR 5.4.c: "Starting with the active player, each player may choose 1 card from their hand to put into
#// play as a resource." The order is SEQUENTIAL: the player with the initiative chooses (or declines) first, and
#// only then is the next player asked. It matters for IBH Ima-Gun-Di and anything else that reads the other
#// player's resource count before you commit your own.
#//
#// Regression (player report 2026-11-20): every seat's "Resource up to 1 card" prompt was queued at once, one per
#// seat's own decision queue, so both players were prompted simultaneously. A second, hidden defect: the order was
#// taken from GetFirstPlayer() (the seat that STARTED the game) rather than the current initiative holder, so a
#// round where the other player had taken the initiative still started with the starting player.
#//
#// CommonSetup deals no hand, so each answer is a DECLINE (`AnswerDecision:-`), same as MultiSeat_RoundTransitionResets.
#//
# InitiativeHolderIsAskedFirst_OtherPlayerWaits
#// P1 holds the initiative. Once the action phase ends, only P1 has a resource prompt; P2 has none yet.
## GIVEN
CommonSetup: grw/ggk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Pass
- P2>Pass
## EXPECT
PHASE:RES
P1HASDECISION
P1DECISIONTOOLTIP:Resource_up_to_1_card
P2NODECISION

---

# OtherPlayerIsAskedOnceTheHolderAnswers
#// After P1 declines, P2 gets its resource prompt; the action phase does not open until P2 answers.
## GIVEN
CommonSetup: grw/ggk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Pass
- P2>Pass
- P1>AnswerDecision:-
## EXPECT
PHASE:RES
P1NODECISION
P2HASDECISION
P2DECISIONTOOLTIP:Resource_up_to_1_card

---

# BothAnswered_ActionPhaseOpens
## GIVEN
CommonSetup: grw/ggk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Pass
- P2>Pass
- P1>AnswerDecision:-
- P2>AnswerDecision:-
## EXPECT
PHASE:MAIN
P1NODECISION
P2NODECISION

---

# InitiativeTakenByTheNonStartingPlayer_TheyResourceFirst
#// P1 started the game (FirstPlayer = 1) but P2 has taken the initiative this round. The resource step starts
#// with P2, the initiative holder, not with the starting player.
## GIVEN
CommonSetup: grw/ggk
P1OnlyActions: true
WithGamePhase: ActionPhase
## WHEN
- P1>Pass
## EXPECT
PHASE:RES
P2HASDECISION
P2DECISIONTOOLTIP:Resource_up_to_1_card
P1NODECISION

---

# InitiativeTakenByTheNonStartingPlayer_StartingPlayerAskedSecond
## GIVEN
CommonSetup: grw/ggk
P1OnlyActions: true
WithGamePhase: ActionPhase
## WHEN
- P1>Pass
- P2>AnswerDecision:-
## EXPECT
PHASE:RES
P2NODECISION
P1HASDECISION
P1DECISIONTOOLTIP:Resource_up_to_1_card

---

# Decline_IsLogged
#// A player who chooses NOT to resource during the regroup phase gets a log line too (owner request 2026-11-21), so the
#// opponent can see the decision was made rather than wondering whether it was skipped. One line per decline.
## GIVEN
CommonSetup: grw/ggk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
PHASE:MAIN
LOGCOUNT:1:P1 did not resource a card
LOGCOUNT:1:P2 did not resource a card

---

# Resourcing_IsNotLoggedAsADecline
## GIVEN
CommonSetup: grw/ggk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
WithP1Deck: [SOR_095 SOR_095]
## WHEN
- P1>Pass
- P2>Pass
- P1>ResourceHand:0
- P2>ResourcePass
## EXPECT
PHASE:MAIN
P1RESCOUNT:1
LOGCOUNT:1:P1 resourced a card
LOGCOUNT:0:P1 did not resource a card
LOGCOUNT:1:P2 did not resource a card

---

# Decline_AsPASS_IsLogged
#// The board's decline button answers "PASS" (UILibraries SubmitInput cardID=PASS), not "-". A PASS skips a following
#// CUSTOM step unless it is queued dontSkipOnPass — the decline must still be logged and still pass the step on.
## GIVEN
CommonSetup: grw/ggk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Pass
- P2>Pass
- P1>AnswerDecision:PASS
- P2>AnswerDecision:PASS
## EXPECT
PHASE:MAIN
LOGCOUNT:1:P1 did not resource a card
LOGCOUNT:1:P2 did not resource a card
