# ControllerPlays_OpponentGetsCredits
#// LAW_215 Vermillion (5/7 Space) — When Attack Ends (survived): reveal the top card of a deck, choose a
#// player to play it for free; a DIFFERENT player creates Credits = that card's cost. Here P1 reveals its
#// own deck (P2's is empty → auto), chooses ITSELF to play the revealed Battlefield Marine (cost 2) for
#// free, and the other player (P2) creates 2 Credits.
#// COVERAGE: offer=EmptyDeckIsNeverOfferedInTheDeckPool (added 2026-08-16 — asserts the deck step is
#//           SKIPPED, not auto-answered, when one deck is empty) + the auto-reveal outcome sections
#//           YourDeckEmpty_OppDeckAutoRevealed and OppDeckEmpty_YourOwnDeckIsAutoRevealed;
#//           Intended: same design as the LAW_018 mill choice · decline=Declined_NoPlayNoCredits +
#//           YourDeck_ChooseOpp_Decline + OppDeck_ChooseSelf_Decline + OppDeck_ChooseOpp_Decline (all
#//           four chooser/decliner quadrants) · control=RevealOpponentDeck_StealUnit (the chosen player
#//           plays a card they do not own) · boundary=RevealedCost0_NoCredits (zero-cost) +
#//           BothDecksEmpty_NoTrigger + RevealedUpgrade_NoValidHost_Fizzles +
#//           NothingIfVermillionDefeated (dead-source) · reqboundary=reveal -> choose-player ->
#//           play/decline crosses a request boundary at every answer in every section

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2CREDITCOUNT:2
P1CREDITCOUNT:0

---

# Declined_NoPlayNoCredits
#// LAW_215 Vermillion — "They MAY play the revealed card." Declining means nothing is played and NO
#// Credits are created (the Credit clause is gated on "if they do"). The revealed Battlefield Marine stays
#// on top of P1's deck (deck count unchanged), no unit enters play, and neither player gets Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# OpponentPlays_ControllerGetsCredits
#// LAW_215 Vermillion — the cross-player branch. P1 reveals its own deck-top (Battlefield Marine) but
#// chooses the OPPONENT (P2) to play it. P2 plays it for free — it enters P2's arena owned by P1 (its deck
#// owner), controlled by P2 — and the DIFFERENT player (P1) creates 2 Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:2
P2CREDITCOUNT:0

---

# RevealOpponentDeck_StealUnit
#// LAW_215 Vermillion — "reveal the top card of A deck" is the controller's choice between the two decks.
#// Both decks are non-empty here, so P1 is asked which to reveal. P1 reveals the OPPONENT's deck-top
#// (Battlefield Marine, cost 2), then chooses ITSELF to play it: P1 gets a free unit (owned by P2, its deck
#// owner; controlled by P1), and the other player (P2) creates 2 Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2CREDITCOUNT:2

---

# NothingIfVermillionDefeated
#// LAW_215 Vermillion — the When Attack Ends ability only fires if Vermillion survived combat. Here the
#// 5/7 Vermillion attacks P2's 7/7 Home One and takes 7 back → Vermillion is defeated, so the reveal/play
#// ability never triggers. No card is played and no Credits are created.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2SpaceArena: SOR_102:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0
P2CREDITCOUNT:0
P1NODECISION

---

# YourDeck_ChooseOpp_Decline
#// LAW_215 Vermillion — reveal P1's own deck-top (Battlefield Marine), choose the OPPONENT to play it,
#// but P2 declines. Nothing is played (the Credit clause is gated on "if they do"), the card stays on top
#// of P1's deck, and neither player gets Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# OppDeck_ChooseSelf_Decline
#// LAW_215 Vermillion — reveal the OPPONENT's deck-top (Battlefield Marine, only P2's deck is non-empty so
#// it is auto-selected), choose YOURSELF to play it, then decline. Nothing is played, the card stays on top
#// of P2's deck, and no Credits are created.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:0
P2DECKCOUNT:2
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# OppDeck_ChooseOpp_Play
#// LAW_215 Vermillion — reveal the OPPONENT's deck-top (Battlefield Marine, cost 2), choose the OPPONENT to
#// play it. P2 plays it for free — it enters P2's arena, owned and controlled by P2 (its own deck) — and the
#// DIFFERENT player (P1) creates 2 Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1CREDITCOUNT:2
P2CREDITCOUNT:0

---

# OppDeck_ChooseOpp_Decline
#// LAW_215 Vermillion — reveal the OPPONENT's deck-top, choose the OPPONENT to play it, but P2 declines.
#// Nothing is played, the card stays on top of P2's deck, and no Credits are created.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P2DECKCOUNT:2
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# YourDeckEmpty_OppDeckAutoRevealed
#// LAW_215 Vermillion — "reveal the top card of a deck": only decks with a card are offered. P1's deck is
#// empty, so the engine auto-reveals the OPPONENT's deck-top (Battlefield Marine) with no deck-choice
#// prompt. P1 chooses itself to play it for free → P1 gets a free unit and P2 creates 2 Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2CREDITCOUNT:2

---

# BothDecksEmpty_NoTrigger
#// LAW_215 Vermillion — with BOTH decks empty there is no deck with a top card to reveal, so the ability
#// never triggers. No card is played and no Credits are created.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0
P2CREDITCOUNT:0
P1NODECISION

---

# RevealedCost0_NoCredits
#// LAW_215 Vermillion — the "different player creates Credits = the card's cost" clause creates 0 Credits
#// for a cost-0 card. P1 reveals its own deck-top Porg (cost 0), plays it for free, and P2 creates 0 Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: LOF_254

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_254
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# RevealedUpgrade_NoValidHost_Fizzles
#// LAW_215 Vermillion — the revealed card is Nemik's Manifesto (attach to a non-Vehicle unit). Neither
#// player has a non-Vehicle unit in play (only the vehicle ships Vermillion and Desperado Freighter), so
#// once P1 chooses to play it the attach finds no legal host and fizzles: the upgrade stays in the deck and
#// no Credits are created.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2SpaceArena: SHD_152:1:0
WithP1Deck: SEC_156

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1DECKCOUNT:1
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# RevealedUpgrade_FriendlyRestriction_AttachesToChooserUnit
#// LAW_215 Vermillion — reveal the OPPONENT's Darth Maul's Lightsaber (cost 3, "attach to a friendly
#// non-Vehicle unit"). P1 is chosen to play it, so "friendly" is relative to P1: the only legal host is
#// P1's Battlefield Marine (auto-selected). The upgrade attaches to it and P2 creates 3 Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: LOF_254
WithP2Deck: LOF_140

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LOF_140
P2CREDITCOUNT:3

---

# RevealPilotingUnit_PlayAsPilotForFree
#// LAW_215 Vermillion — a revealed PILOTING unit may be played as a Pilot upgrade for free. P1 reveals its
#// own deck-top JTL_103 Chewbacca (a 5-cost Piloting unit), chooses itself, and plays it as a Pilot; the
#// only valid Vehicle host is Vermillion, so Chewbacca attaches to it (upgrade count 1). The play is FREE
#// (no resources spent), and the other player (P2) creates Credits equal to Chewbacca's cost (5).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: JTL_103
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_215
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2CREDITCOUNT:5

---

# RevealPilotingUnit_PlayAsUnitForFree
#// LAW_215 Vermillion — the same revealed Piloting unit may instead be played as a UNIT (the Unit branch
#// of the Unit-vs-Pilot choice). Chewbacca (JTL_103) is a ground unit, so it enters P1's GROUND arena;
#// Vermillion keeps no upgrade, and P2 still creates 5 Credits.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: JTL_103
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:Unit

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_103
P2CREDITCOUNT:5

---

# OppDeckEmpty_YourOwnDeckIsAutoRevealed
#// The mirror of YourDeckEmpty_OppDeckAutoRevealed: only decks that HAVE a top card are offered, so with
#// the OPPONENT's deck empty the reveal comes from P1's own deck with no deck-choice prompt. P1 chooses
#// itself, plays SOR_095 for FREE (all 5 resources still ready — this is what makes it a free play rather
#// than a cheap one) and P2 creates Credits equal to the revealed card's cost (2).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1DECKCOUNT:0
P2CREDITCOUNT:2
P1RESAVAILABLE:5

---

# RevealedEventThatItselfPlaysACard_ResolvesFully
#// The revealed card may be an EVENT whose own effect plays another card — the nested play has to resolve
#// inside Vermillion's free play. P1 reveals SOR_219 Sneak Attack from P2's deck and plays it for free;
#// Sneak Attack then plays SOR_046 (cost 4, on-aspect) from P1's hand at 3 less, so exactly 1 of P1's 5
#// resources is spent. Sneak Attack itself was free. The Credits are for the REVEALED card's cost
#// (Sneak Attack = 2), not the nested unit's, and the spent event goes to its OWNER's discard (P2's).

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Hand: SOR_046
WithP2Deck: SOR_219

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1RESAVAILABLE:4
P1HANDCOUNT:0
P2CREDITCOUNT:2
P2DISCARDCOUNT:1

---

# ChosenPlayerCannotPlayARestrictedCard_NoPlayAndNoCredits
#// "They MAY play the revealed card for free" is still a PLAY, so a play-restriction blocks it. P2 plays
#// SOR_062 Regional Governor naming Battlefield Marine; P1 then reveals its own Battlefield Marine with
#// Vermillion and chooses itself. The play step is skipped entirely — no offer is raised, the card stays
#// on top of P1's deck, and because nothing was played NO Credits are created either (the Credit clause
#// is gated on "if they do").

## GIVEN
CommonSetup: bbk/bbw/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:5
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_095
WithP2Hand: SOR_062
WithP2Resources: 5

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Battlefield Marine
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:You

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:1
P2CREDITCOUNT:0

---

# EmptyDeckIsNeverOfferedInTheDeckPool
#// LAW_215 Vermillion — RE-VERIFICATION of the one residual scenario ("choose an EMPTY deck, the ability
#// fizzles"), which was closed earlier as unreachable. Re-read against the current code: STILL VALID and
#// unchanged — the trigger builds its deck list from decks whose top-card index is not -1, and it only
#// raises the deck-choice prompt when TWO decks survive that filter, so an empty deck can never be
#// picked and the fizzle state has no path.
#// What WAS missing is an explicit assertion of that premise, which the ledger had been claiming without
#// a section behind it. This is it. P1's deck is EMPTY and P2's is stocked: if empty decks were in the
#// pool the trigger would raise the two-option "Reveal the top card of which deck?" prompt. Instead the
#// pending decision after the attack is already the NEXT step, the choose-a-player OPTIONCHOOSE — the
#// tooltip names the card revealed from P2's deck, so the deck step was skipped entirely rather than
#// auto-answered to "Yours". The two option labels are asserted so the section fails loudly if the
#// pending decision is ever a different prompt that merely happens to carry this tooltip.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1DECISIONTOOLTIP:Choose_a_player_to_play_Battlefield_Marine_for_free
P1OPTIONHAS:You
P1OPTIONHAS:Opponent
P1DECKCOUNT:0
P2DECKCOUNT:2

---

# FourSeats_CreditChooserIsVermillionsController
#// LAW_215 at FOUR seats — "If they do, A DIFFERENT PLAYER creates Credit tokens equal to that card's
#// cost." Nothing in the text hands that choice to anyone but the ability's controller, so the prompt
#// belongs to P1 (the Vermillion's controller), not to the revealed deck's owner. It used to be queued
#// onto $D, the DECK OWNER — indistinguishable while you reveal your own deck, but reveal an opponent's
#// and the prompt landed on THEM; because that seat is idle at that moment its queue never drained in the
#// request, so the Credits simply never appeared. Here P1 reveals P2's deck, plays the card itself, and
#// P1 names P4 — a seat neither $D nor a "first live opponent" auto-pick would produce.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1SpaceArena: LAW_215:1:0
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackSpaceArena:0:P2B
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:P4

## EXPECT
SEATCOUNT:4
P2BASEDMG:5
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P4CREDITCOUNT:2
P2CREDITCOUNT:0
P3CREDITCOUNT:0
P1CREDITCOUNT:0

---

# FourSeats_TeammatesDeckIsInThePoolAndMayTakeTheCredits
#// LAW_215 — "reveal the top card of A DECK … a DIFFERENT player creates Credits". Both references are
#// UNQUALIFIED, so both span every live seat, a TEAMMATE included. The deck pool used to be built from
#// OpponentsOf($V), which in Team Suns dropped P1's partner: with only P3's deck stocked the pool came
#// back empty and the whole When-Attack-Ends trigger fizzled silently (no reveal, no play, no Credits).
#// The credits pool is "any player except the one who PLAYED it", so P3 is eligible there too — P1 plays
#// the card itself and hands its own partner the 2 Credits.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1SpaceArena: LAW_215:1:0
WithP3Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackSpaceArena:0:P2B
- P1>AnswerDecision:You
- P1>AnswerDecision:YES
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P3DECKCOUNT:1
P3CREDITCOUNT:2
P2CREDITCOUNT:0
P4CREDITCOUNT:0
P1CREDITCOUNT:0
