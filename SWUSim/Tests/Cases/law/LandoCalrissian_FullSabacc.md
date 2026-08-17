# DeployedCreditEngine
#// LAW_018 Lando Calrissian (deployed) — "When Deployed: You may defeat a friendly Credit token. If you
#// do, create 3 Credit tokens." Deploy Lando with 1 existing Credit; defeat it and create 3 → net 3
#// Credits.
#// COVERAGE: offer=FrontOpponentDeckDiscardCredit (both decks stocked → the Your/Opponent's-deck choice
#//           is live and answered; the deck choice is an option prompt, not an MZ pool, so no
#//           SELECTABLEEXACT applies) · reqboundary=FrontOpponentDeckDiscardCredit (aspect and deck
#//           answered on successive requests) · control=N/A (no unit changes control; Credits are
#//           seat-bound) · boundary=FrontAspectMillCredit vs FrontNoCreditWhenAspectMismatch (aspect
#//           hit/miss); DeployedCreditEngine vs DeployedNoCreditsAutoSkips (credit present/absent);
#//           FrontEmptyDecksNoEffect (both decks empty) · decline=DeployedDeclineKeepsCredit.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES

## EXPECT
P1CREDITCOUNT:3

---

# FrontAspectMillCredit
#// LAW_018 Lando Calrissian (leader front) — "Action [1 resource, Exhaust]: Choose an aspect, then
#// discard a card from a deck. If it has the chosen aspect, create a Credit token." Choose Vigilance;
#// only P1 has a deck so it auto-discards SOR_046 (Vigilance/Heroism) → it has Vigilance → 1 Credit.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Deck: SOR_046

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Vigilance

## EXPECT
P1CREDITCOUNT:1
P1DECKCOUNT:0

---

# FrontOpponentDeckDiscardCredit
#// LAW_018 Lando Calrissian (leader front) — the discard may come from the OPPONENT's deck. With both
#// decks stocked, choose Heroism then discard from the opponent's deck: SOR_237 (Heroism) is milled -> it
#// has the chosen aspect -> 1 Credit. P1's own deck is untouched.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Deck: SOR_046
WithP2Deck: SOR_237

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Heroism
- P1>AnswerDecision:Opponent's_deck

## EXPECT
P1CREDITCOUNT:1
P2DECKCOUNT:0
P1DECKCOUNT:1

---

# FrontNoCreditWhenAspectMismatch
#// LAW_018 Lando Calrissian (leader front) — a Credit is created only if the discarded card HAS the chosen
#// aspect. Only P1 has a deck (auto-resolves to it); choose Command and discard SOR_237 (Heroism only) ->
#// aspect mismatch -> no Credit, but the resource + exhaust cost are still paid.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Deck: SOR_237

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Command

## EXPECT
P1CREDITCOUNT:0
P1DECKCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# FrontEmptyDecksNoEffect
#// LAW_018 Lando Calrissian (leader front) — with BOTH decks empty there is no card to discard, so no
#// Credit is created, but the ability still resolves and pays its costs (leader exhausted, resource spent).

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Aggression

## EXPECT
P1CREDITCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0

---

# DeployedDeclineKeepsCredit
#// LAW_018 Lando Calrissian (deployed) — the When Deployed ability is optional ("You may defeat a friendly
#// Credit token"). Deploy with 1 Credit and decline: the Credit is kept and no new Credits are created.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:NO

## EXPECT
P1CREDITCOUNT:1

---

# DeployedNoCreditsAutoSkips
#// LAW_018 Lando Calrissian (deployed) — with NO friendly Credit token in play the When Deployed ability
#// has nothing to defeat, so it is skipped automatically (no prompt) and no Credits are created.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader

## EXPECT
P1CREDITCOUNT:0
P1LEADER:DEPLOYED

---

# FrontAspectOptionPool_AllSixAspectsOfferedRegardlessOfLeaderOrDeck
#// LAW_018 Lando Calrissian (leader front) — "CHOOSE AN ASPECT, then discard a card from a deck." The
#// choice is unrestricted: it is not narrowed to Lando's own aspects (Cunning, Heroism), nor to the
#// aspects actually present in the deck about to be milled. This board makes both of those plausible
#// wrong pools observable — the only card in either deck is SOR_046 (Vigilance, Heroism) / SOR_237
#// (Heroism), yet Command, Aggression, Villainy and Vigilance must all still be offered, and the option
#// prompt is left pending so the list itself is the assertion. FrontNoCreditWhenAspectMismatch only shows
#// that a mismatched aspect CAN be answered; it cannot see an over- or under-populated option list.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Deck: SOR_046
WithP2Deck: SOR_237

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1OPTIONHAS:Vigilance
P1OPTIONHAS:Command
P1OPTIONHAS:Aggression
P1OPTIONHAS:Cunning
P1OPTIONHAS:Heroism
P1OPTIONHAS:Villainy

---

# FrontDeckOptionPool_BothDecksOfferedWhenBothStocked
#// LAW_018 Lando Calrissian (leader front) — "…then discard a card FROM A DECK." With BOTH decks stocked
#// the second prompt must offer both sides. This is the option-prompt analogue of a target pool, and it IS
#// assertable: P1OPTIONHAS reads the pending OPTIONCHOOSE's label list directly, so the choice can be
#// inspected while pending rather than merely answered. FrontOpponentDeckDiscardCredit answers
#// "Opponent's_deck" and therefore proves that label exists, but it cannot prove that "Your_deck" is still
#// offered alongside it — a prompt that had silently collapsed to a single option would pass it unchanged.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Deck: SOR_046
WithP2Deck: SOR_237

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Heroism

## EXPECT
P1HASDECISION
P1OPTIONHAS:Your_deck
P1OPTIONHAS:Opponent's_deck
P1DECKCOUNT:1
P2DECKCOUNT:1

---

# FrontDeckChoiceAutoResolvesToTheOnlyStockedDeck
#// COVERAGE (corrects the ledger in DeployedCreditEngine, which recorded "the deck choice is an option
#//           prompt, not an MZ pool, so no SELECTABLEEXACT applies" — SELECTABLEEXACT indeed does not
#//           apply, but P1OPTIONHAS/OPTIONNOT do, and the deck choice is now asserted while pending):
#//           offer=FrontAspectOptionPool_AllSixAspectsOfferedRegardlessOfLeaderOrDeck (aspect list) +
#//           FrontDeckOptionPool_BothDecksOfferedWhenBothStocked (both decks offered) +
#//           FrontDeckChoiceAutoResolvesToTheOnlyStockedDeck (the pool narrows to one, so auto-resolution
#//           IS the assertion) · reqboundary=FrontOpponentDeckDiscardCredit · control=N/A (no unit changes
#//           control; Credits are seat-bound) · boundary=FrontAspectMillCredit vs
#//           FrontNoCreditWhenAspectMismatch; DeployedCreditEngine vs DeployedNoCreditsAutoSkips;
#//           FrontEmptyDecksNoEffect · decline=DeployedDeclineKeepsCredit.
#// LAW_018 — the deck prompt is affordance-gated: an empty deck is not a legal source, so when only ONE
#// deck holds a card the choice narrows to a single option and resolves without prompting. The sharp case
#// is the one that discriminates against a hardcoded "your deck" default: P1's OWN deck is EMPTY and only
#// the OPPONENT's deck is stocked. The ability must still mill — from P2's deck — rather than prompting or
#// fizzling. SOR_237 Alliance X-Wing (Heroism) matches the chosen aspect, so P1 gets the Credit, P2's deck
#// empties, and no decision is left pending. FrontAspectMillCredit is the mirror (only P1's deck stocked)
#// and FrontEmptyDecksNoEffect is the both-empty floor.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP2Deck: SOR_237

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Heroism

## EXPECT
P1NODECISION
P1CREDITCOUNT:1
P2DECKCOUNT:0
P1DECKCOUNT:0
P1LEADER:EXHAUSTED

---

# P2Seat_YourDeckIsTheDeckOfWhoeverUSEDTheAction
#// COVERAGE (corrects the ledger recorded in DeployedCreditEngine and in
#//           FrontDeckChoiceAutoResolvesToTheOnlyStockedDeck, both of which recorded
#//           "control=N/A (no unit changes control; Credits are seat-bound)" — true of UNIT control, but
#//           it left the seat-resolution half of the axis untested. Every other section in this file
#//           drives the ability from seat 1, so a "Your_deck"/"Opponent's_deck" mapping or a Credit
#//           payout wired to a hardcoded P1 would pass the entire file. control=
#//           P2Seat_YourDeckIsTheDeckOfWhoeverUSEDTheAction + P2Seat_OpponentsDeckIsSeatOnesDeck.)
#// LAW_018 (leader front) — "discard a card from a deck ... create a Credit token." Both the deck labels
#// and the Credit resolve from whoever USES the action. Here the LAW_018 leader belongs to seat 2, both
#// decks are stocked with DIFFERENT cards (P1: SOR_046 Vigilance/Heroism, P2: SOR_237 Heroism), and P2
#// chooses Heroism then "Your_deck": P2's own deck is the one that empties, P1's is untouched, and the
#// Credit lands on P2's side with P1 on zero.

## GIVEN
CommonSetup: bbw/yyw/{theirLeader:LAW_018; myBase:SOR_028; theirBase:SOR_028}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 1
WithP1Deck: SOR_046
WithP2Deck: SOR_237

## WHEN
- P2>UseLeaderAbility
- P2>AnswerDecision:Heroism
- P2>AnswerDecision:Your_deck

## EXPECT
P2CREDITCOUNT:1
P1CREDITCOUNT:0
P2DECKCOUNT:0
P1DECKCOUNT:1
P2LEADER:EXHAUSTED

---

# P2Seat_OpponentsDeckIsSeatOnesDeck
#// LAW_018 (leader front) — the mirror of the seat check: from seat 2, "Opponent's_deck" must mean P1's
#// deck. P2 chooses Vigilance and then the opponent's deck; P1's SOR_046 (Vigilance, Heroism) is milled,
#// it carries the chosen aspect, and the Credit is still created for P2 — the player who used the action,
#// not the player whose deck was milled — while P2's own deck keeps its card. Together with
#// P2Seat_YourDeckIsTheDeckOfWhoeverUSEDTheAction this pins both labels to the acting seat rather than to
#// seat 1.

## GIVEN
CommonSetup: bbw/yyw/{theirLeader:LAW_018; myBase:SOR_028; theirBase:SOR_028}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 1
WithP1Deck: SOR_046
WithP2Deck: SOR_237

## WHEN
- P2>UseLeaderAbility
- P2>AnswerDecision:Vigilance
- P2>AnswerDecision:Opponent's_deck

## EXPECT
P2CREDITCOUNT:1
P1CREDITCOUNT:0
P1DECKCOUNT:0
P2DECKCOUNT:1
