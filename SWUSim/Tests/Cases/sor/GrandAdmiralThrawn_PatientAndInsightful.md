# APS_LeaderUnit_LooksAtBothDecks
#// COVERAGE: offer=LeaderAction_OwnDeck_OfferPool + LeaderAction_OpponentDeck_OfferPool +
#//           Deployed_OnAttack_OfferPool (pending P1SELECTABLEEXACT; the deployed pool also proves
#//           Thrawn himself, c6, is excluded) · reqboundary=LeaderAction_SurvivesRequestBoundary
#//           (deck choice and exhaust target are separate decisions; the revealed cost is read after
#//           the first answer) · control=N/A (the exhaust pool is purely cost-based over ALL units in
#//           play — both controllers' units already appear in every pool section, and no per-unit
#//           state outlives the resolution) · boundary=LeaderAction_OwnDeck_OfferPool (equal-cost c4
#//           Wampa IN, c5 Freighter OUT of a c4 reveal) + the empty-deck family
#//           (LeaderAction_OpponentDeckEmpty_NoOp / _BothDecksEmpty_CostStillPaid /
#//           APS_OneDeckEmpty_PeeksOnlyNonEmpty / APS_BothDecksEmpty_NoPeek) ·
#//           decline=OnAttack_No (deployed "you may" declined; the leader-side Action has no decline —
#//           once the [1 resource, exhaust] cost is paid the reveal is mandatory, see
#//           LeaderAction_NoValidTargets / LeaderAction_Unaffordable)
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

---

# APS_OneDeckEmpty_PeeksOnlyNonEmpty
#// SOR_016 Grand Admiral Thrawn (leader side) — APS passive with ONE empty deck: only the non-empty
#// deck is peeked. P1's deck holds 3 cards (1 survives the regroup draw); P2's deck is EMPTY, so P2
#// takes 3 damage per undrawn card (+6 base) and Thrawn skips P2's deck entirely. The peek entries are
#// written P1-then-P2, so a LAST log entry that is P1's peek proves the P2 peek line was never written.

## GIVEN
CommonSetup: gyk/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
LOGCONTAINS: top of P1
LASTLOGCONTAINS: top of P1
P2BASEDMG:6
PHASE:MAIN

---

# APS_BothDecksEmpty_NoPeek
#// SOR_016 Grand Admiral Thrawn (leader side) — APS passive with BOTH decks empty: no peek at all.
#// Each player fails both regroup draws (+6 to each base). The action-phase banner is the LAST log
#// entry, proving neither "sees top of" line was written.

## GIVEN
CommonSetup: gyk/grw
SkipPreGame: true
WithActivePlayer: 1

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
LASTLOGCONTAINS: Action Phase
P1BASEDMG:6
P2BASEDMG:6
PHASE:MAIN

---

# APS_LeaderUnit_OneDeckEmpty_PeeksOnlyNonEmpty
#// SOR_016 Grand Admiral Thrawn DEPLOYED — same one-empty-deck APS behavior on the leader-unit side:
#// only P1's non-empty deck is peeked (last log entry = P1's peek), P2 takes +6 for the failed draws.

## GIVEN
CommonSetup: gyk/grw/{
  myLeader:SOR_016:1:1:1
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
LOGCONTAINS: top of P1
LASTLOGCONTAINS: top of P1
P2BASEDMG:6
PHASE:MAIN

---

# LeaderAction_OwnDeck_OfferPool
#// SOR_016 Grand Admiral Thrawn — Leader Action, OWN deck revealed (SOR_077 Takedown, cost 4): the
#// exhaust pool is EXACTLY the units costing 4 or less, across BOTH players — P1's Battlefield Marine
#// (c2) and P2's Wampa (c4) — and NOT P1's Desperado Freighter (c5). The choice is left PENDING so the
#// pool itself is the assertion; resolution is covered by LeaderAction_OwnDeck.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_077
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SHD_152:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# LeaderAction_OpponentDeck_OfferPool
#// SOR_016 Grand Admiral Thrawn — Leader Action, OPPONENT's deck revealed (SOR_116 Steadfast
#// Battalion, cost 5): now the c5 Desperado Freighter also qualifies, so the pool is exactly all
#// three units. Left pending; resolution covered by LeaderAction_OpponentDeck.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP2Deck: SOR_116
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SHD_152:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# LeaderAction_OpponentDeckEmpty_NoOp
#// SOR_016 Grand Admiral Thrawn — Leader Action choosing the opponent's EMPTY deck: nothing is
#// revealed and nothing is exhausted, but the action's cost was already paid — the leader stays
#// exhausted and the resource stays spent.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# LeaderAction_BothDecksEmpty_CostStillPaid
#// SOR_016 Grand Admiral Thrawn — Leader Action with BOTH decks empty: the ability does nothing,
#// but the [1 resource, exhaust] cost is still paid (it was paid to initiate the action).

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0
P1NODECISION

---

# Deployed_OnAttack_OpponentDeck
#// SOR_016 Grand Admiral Thrawn DEPLOYED — On Attack choosing the OPPONENT's deck (top SOR_116,
#// cost 5): pool = P1's Dark Trooper (c2) and P2's Wampa (c4); Thrawn himself (c6) is excluded.
#// P1 picks the Wampa, which exhausts; base still takes Thrawn's 3.

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true
WithP2Deck: SOR_116
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY
P1LEADER:DEPLOYED

---

# Deployed_OnAttack_OfferPool
#// SOR_016 Grand Admiral Thrawn DEPLOYED — On Attack, own deck revealed (SOR_077 Takedown, c4):
#// the pool is exactly the c4-or-less units — P1's Battlefield Marine (c2) and P2's Wampa (c4).
#// Thrawn himself (c6 leader unit) and the c5 Desperado Freighter are excluded. Left pending;
#// resolution covered by OnAttack_Yes and Deployed_OnAttack_OpponentDeck.

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true
WithP1Deck: SOR_077
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SHD_152:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Deployed_OnAttack_OpponentDeckEmpty_NoExhaust
#// SOR_016 Grand Admiral Thrawn DEPLOYED — On Attack choosing the opponent's EMPTY deck: nothing is
#// revealed or exhausted; the attack itself still lands for 3.

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:READY
P1NODECISION

---

# LeaderAction_SurvivesRequestBoundary
#// SOR_016 Grand Admiral Thrawn — the Leader Action spans two decisions (deck choice, then the
#// exhaust target); the revealed top card's cost is read AFTER the deck-choice answer, so the whole
#// flow must survive a serialize/decode round-trip at each decision boundary. Same fixture as
#// LeaderAction_OwnDeck_OfferPool, resolved onto the Wampa.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP1Deck: SOR_077
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# Deployed_OnAttack_BothDecksEmpty_NoEffect
#// SOR_016 Grand Admiral Thrawn DEPLOYED — On Attack with BOTH decks empty: accepting the ability
#// and picking a deck does nothing (no reveal, no exhaust); the attack still lands for 3.

## GIVEN
CommonSetup: yyk/grw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:READY
P1NODECISION

---

# TwinSuns_APS_LooksAtEVERYSeatsDeck
#// ⚠ THE SEAT-COUNT CELL for the passive half. "Look at the top card of EACH PLAYER'S deck" was written
#// `for ($deckOwner = 1; $deckOwner <= 2; …)`, so a Thrawn in Twin Suns saw seats 1 and 2 only and was
#// blind to half the table — on a card whose entire purpose is information.
#// A four-seat loop back into a fresh action phase must log a peek for all four decks.
#// ⚠ A 2-player version of this cannot fail: the old literal and GetLiveSeatsArray() are the same list
#//   at two seats.

## GIVEN
CommonSetup: gyk/grw
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_128 SOR_128 SOR_128]
WithP3Deck: [SOR_046 SOR_046 SOR_046]
WithP4Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>Pass
- P2>Pass
- P3>Pass
- P4>Pass
- P1>ResourcePass
- P2>ResourcePass
- P3>ResourcePass
- P4>ResourcePass

## EXPECT
SEATCOUNT:4
LOGCONTAINS: top of P1
LOGCONTAINS: top of P2
LOGCONTAINS: top of P3
LOGCONTAINS: top of P4

---

# TwinSuns_LeaderAction_PicksWHICHPlayersDeck
#// ⚠ THE SEAT-COUNT CELL for the ACTION half. "Reveal the top card of ANY player's deck" — you are a
#// legal pick, so this is an $includeSelf player choice. At two seats it stays the original
#// "own deck or opponent?" YES/NO (invariant I1: a conversion must not change Premier, and with two
#// seats that prompt says it better than a two-name menu would). The picker appears only at 3+ seats,
#// where the YES/NO physically cannot express "seat 3's deck".
#// Left pending so the MENU is the assertion: every live seat, INCLUDING P1's own.

## GIVEN
CommonSetup: gyk/grw/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_128 SOR_128]
WithP3Deck: [SOR_046 SOR_046]
WithP4Deck: [SEC_080 SEC_080]
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P1
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4

---

# TwinSuns_LeaderAction_RevealsSeatThreesDeck
#// The pick driven through: P1 names SEAT THREE's deck, whose top card is SOR_046 Consular Security
#// Force (cost 4). Anything costing 4 or less may then be exhausted — P1 exhausts its own Battlefield
#// Marine (cost 2), since "a unit" is unqualified and includes your own board.
#// ⚠ Seat 3's deck is unreachable for the old YES/NO by construction: it could only ever say "mine" or
#//   "the one opponent's".

## GIVEN
CommonSetup: gyk/grw/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_128 SOR_128]
WithP3Deck: [SOR_046 SOR_046]
WithP4Deck: [SEC_080 SEC_080]
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility:0
- P1>AnswerDecision:P3
- P1>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:EXHAUSTED
LOGCONTAINS: Thrawn reveals
