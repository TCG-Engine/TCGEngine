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
