# DeckDiscardDecline
#// LAW_176 Sebulba's Podracer — "may" decline branch: same setup, but P1 declines the ready (NO), so
#// the Podracer stays EXHAUSTED.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_176:0:0
WithP1GroundArena: LAW_173:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# DeckDiscardMayReady
#// LAW_176 Sebulba's Podracer (3/3 Vehicle/Speeder) — "When you discard a card from your deck: You may
#// ready this unit. Use this ability only once each round." LAW_173 BT-1 (index 1) attacks the base; its
#// On Attack mills the top of P1's deck (a NON-Aggression card, so BT-1's own "if Aggression" rider adds
#// no decision), which fires LAW_176's trigger. P1 answers YES and the exhausted Podracer readies.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_176:0:0
WithP1GroundArena: LAW_173:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:READY

---

# OncePerRound
#// LAW_176 Sebulba's Podracer — "Use this ability only once each round." Two friendly units that mill P1's
#// deck on attack (LAW_173 BT-1 and SOR_188 Chopper) each cause a deck discard. The first mill readies the
#// exhausted Podracer (P1 answers YES); P1 then attacks with the Podracer to exhaust it again; the second
#// mill does NOT re-ready it (no decision offered), so it stays EXHAUSTED.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_176:0:0 LAW_173:1:0 SOR_188:1:0]
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:2:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# EmptyDeck_NoTrigger
#// LAW_176 Sebulba's Podracer — the ability keys off actually discarding a card from your deck. With an
#// empty P1 deck, BT-1's On Attack "discard a card from your deck" discards nothing, so the Podracer's
#// trigger never fires and it stays EXHAUSTED (no ready decision is offered).

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_176:0:0 LAW_173:1:0]

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# OpponentDeckDiscard_NoTrigger
#// LAW_176 Sebulba's Podracer — the trigger is "when YOU discard a card from YOUR deck." An enemy BT-1
#// (LAW_173) milling the OPPONENT's own deck does not touch P1's deck, so P1's exhausted Podracer does not
#// ready and no ready decision is offered.

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_176:0:0
WithP2GroundArena: LAW_173:1:0
WithP2Deck: SOR_046 SOR_095

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
