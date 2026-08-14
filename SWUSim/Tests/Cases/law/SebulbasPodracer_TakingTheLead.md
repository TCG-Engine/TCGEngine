# DeckDiscardDecline
#// LAW_176 Sebulba's Podracer — "may" decline branch: same setup, but P1 declines the ready (NO), so
#// the Podracer stays EXHAUSTED.
#// COVERAGE: offer=DeckDiscardMayReady + DeckDiscardDecline (the YES/NO "you may ready" offer is the
#//           only decision this card raises; no target picker exists) · decline=DeckDiscardDecline +
#//           DeclineDoesNotSpendTheOncePerRound (a pass does NOT consume the round limit) ·
#//           control=NewControllerGetsAFreshUsePerRound (once-each-round is tracked per controlling
#//           player, not welded to the unit) · boundary pair=DeckDiscardMayReady vs EmptyDeck_NoTrigger
#//           + OpponentDeckDiscard_NoTrigger + FriendlyMillOfEnemyDeck_NoTrigger + HandDiscard_NoTrigger
#//           (fires only on an actual discard from YOUR deck) · request boundary=EnemyEffectDiscard_Readies
#//           and NewControllerGetsAFreshUsePerRound (trigger crosses opponent-action requests and is
#//           answered on a later request)

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
#// LAW_176 Sebulba's Podracer — "Use this ability only once each round." BT-1 (LAW_173, On Attack:
#// discard a card from YOUR deck) causes the first P1-deck discard, which readies the exhausted
#// Podracer (P1 answers YES); P1 then attacks with the Podracer to exhaust it again; LAW_203 Daring
#// Delve then discards 2 more cards from P1's deck — a genuine second P1-deck discard the SAME round —
#// and does NOT re-ready it (no decision offered), so it stays EXHAUSTED. The milled cards are
#// non-Aggression, so Daring Delve's own return-offer never prompts. (An earlier board used SOR_188
#// Chopper as the second attacker, but Chopper mills the DEFENDER's deck, so the limit was never
#// actually re-tested; a second BT-1 is impossible — it is unique.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: [LAW_176:0:0 LAW_173:1:0]
WithP1Hand: LAW_203
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

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

---

# EnemyEffectDiscard_Readies
#// LAW_176 Sebulba's Podracer — the trigger keys on WHOSE DECK loses the card, not whose effect caused
#// it. P2's Chopper (SOR_188, "On Attack: discard a card from the defending player's deck") attacks
#// P1's base, discarding from P1's deck. That is a discard from P1's deck, so P1's exhausted Podracer
#// offers its ready; P1 answers YES and it readies. (Deck card is a unit, so Chopper's "if it's an
#// event" rider adds no extra decision.)

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_176:0:0
WithP2GroundArena: SOR_188:1:0
WithP1Deck: SOR_046 SOR_095

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:1

---

# FriendlyMillOfEnemyDeck_NoTrigger
#// LAW_176 Sebulba's Podracer — a FRIENDLY effect that discards from the OPPONENT's deck is not "you
#// discard a card from your deck". P1's own Chopper (SOR_188) attacks P2's base and discards from
#// P2's (defending player's) deck; P1's Podracer stays EXHAUSTED with no ready offer, even though the
#// discard was caused by P1's card. (P2DISCARDCOUNT proves the mill actually happened.)

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_176:0:0 SOR_188:1:0]
WithP2Deck: SOR_046 SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
EFFECTSTACKCOUNT:0
P2DISCARDCOUNT:1

---

# HandDiscard_NoTrigger
#// LAW_176 Sebulba's Podracer — a discard from HAND is not a discard from your deck. P2 plays LAW_193
#// Mid Rim Sharpshooter and pays 1, forcing P1 to discard a card from hand. P1's deck is untouched
#// (deck count stays 1) and the exhausted Podracer offers no ready.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:4}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_176:0:0
WithP1Hand: SOR_046
WithP1Deck: SOR_095
WithP2Hand: LAW_193

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DECKCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
EFFECTSTACKCOUNT:0

---

# DeclineDoesNotSpendTheOncePerRound
#// LAW_176 Sebulba's Podracer — passing on the offer does NOT consume "use this ability only once
#// each round": declined, the ability may still be used on a later deck discard in the same round.
#// P1's BT-1 (LAW_173) mills P1's deck; P1 answers NO (still exhausted). Then P2's Chopper (SOR_188)
#// attacks P1's base and mills P1's deck again; the offer comes back and P1 answers YES — readied.

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 1
WithP1GroundArena: [LAW_176:0:0 LAW_173:1:0]
WithP2GroundArena: SOR_188:1:0
WithP1Deck: SOR_046 SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:NO
- P2>AttackGroundArena:0:BASE
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:2

---

# UsedOnce_SecondMillSameRound_NoOffer
#// LAW_176 Sebulba's Podracer — once the ready has actually been used this round, a SECOND discard
#// from P1's deck in the same round offers nothing. BT-1 mills (YES -> readied); the Podracer attacks
#// and exhausts itself; then P2's Chopper mills P1's deck again — no offer, still EXHAUSTED.
#// (P1DISCARDCOUNT:2 proves the second mill really hit P1's deck.)

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 1
WithP1GroundArena: [LAW_176:0:0 LAW_173:1:0]
WithP2GroundArena: SOR_188:1:0
WithP1Deck: SOR_046 SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P2>Pass
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
EFFECTSTACKCOUNT:0
P1DISCARDCOUNT:2

---

# NewControllerGetsAFreshUsePerRound
#// LAW_176 Sebulba's Podracer — "use this ability only once each round" is tracked per CONTROLLER: after
#// P1 uses the ready, a player who takes control of the Podracer the same round can use it again on a
#// discard from THEIR deck. P1's BT-1 mills P1 (YES -> Podracer readies; P1's use is spent). P2 plays
#// SOR_122 Traitorous on the cost-3 Podracer and takes control, attacks with it (exhausting it), then
#// P2's own BT-1 mills P2's deck — the offer fires for P2, who answers YES: the Podracer readies in
#// P2's arena.

## GIVEN
CommonSetup: rrk/ggk/{theirResources:6}
WithActivePlayer: 1
WithP1GroundArena: [LAW_176:0:0 LAW_173:1:0]
WithP2GroundArena: LAW_173:1:0
WithP2Hand: SOR_122
WithP1Deck: SOR_046 SOR_095
WithP2Deck: SOR_046 SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>AttackGroundArena:1:BASE
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:LAW_176
P2GROUNDARENAUNIT:1:READY
P2DISCARDCOUNT:1
