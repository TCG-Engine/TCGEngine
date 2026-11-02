# SearchEventResolvesBeforeAhsokaIsOffered
#// Bug report P-8 (Twin Suns game 957): "when I play an event (usually a tutor event), it asks if I want to use
#// the Ahsoka ability to look at the top card, shows me the top card, and if I choose to discard that card, I think
#// it discards whatever is next, and then the tutor event will still show that 'top card'."
#// TS26_08 Ahsoka Tano — "When you play an event: You may exhaust this leader. If you do, look at the top card of
#// your deck. You may play it (paying its cost), discard it, or leave it on top of your deck."
#// Ruling (06/04/2026): "Fully resolve the played event before resolving Ahsoka's triggered leader ability." CR: a
#// "When you play an event" trigger waits for the event's ability to resolve as completely as possible.
#// Ahsoka's offer used to be queued AHEAD of the event's own effect, so the first prompt after playing HMW_101
#// Trust Yourself was Ahsoka's, not the event's search. With no unit in play the Shield is skipped, so the first
#// prompt must be the search, and Ahsoka must still be ready.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
## EXPECT
P1DECISIONTOOLTIP:Search_top_cards
P1LEADER:READY
P1DECKCOUNT:2

---

# SearchThenAhsokaDiscard_MillsThePostSearchTopCard
#// The reported sequence, end to end, with five distinct cards so every zone is identifiable. Trust Yourself
#// searches the top 3 (SOR_128 SEC_080 SOR_046) and draws SEC_080; the other two go to the bottom, leaving
#// SOR_095 on top. ONLY THEN is Ahsoka offered: exhausting her looks at SOR_095 (the prompt names it — the old
#// order named SOR_128, a card the search had already taken), and Discard mills SOR_095 — not the card below it.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:YES
## EXPECT
P1DECISIONPARAM:@SOR_095&Play&Discard&Leave
P1HANDCOUNT:1
P1HANDCARD:0:SEC_080
P1DECKCOUNT:4
P1DECKTOPCARD:SOR_095
P1LEADER:EXHAUSTED

---

# SearchThenAhsokaDiscard_FinalZones
#// Same line, answered through to the end. Discard pile = the event + the milled SOR_095 (From DECK); the deck is
#// SOR_251 on top over the two searched-and-bottomed cards; the hand holds only the searched SEC_080.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:YES
- P1>AnswerDecision:Discard
## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SEC_080
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_251
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:HMW_101
P1DISCARDUNIT:1:CARDID:SOR_095
P1DISCARDUNIT:1:FROM:DECK
P1LEADER:EXHAUSTED
P1NODECISION

---

# SearchThenAhsokaPlay_PlaysThePostSearchTopCard
#// The Play branch after a search: SOR_095 Battlefield Marine (the top card once the search has resolved) is
#// played at full cost. Before the fix the prompt named the searched SOR_128 while Play took whatever was on top.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:YES
- P1>AnswerDecision:Play
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1HANDCARD:0:SEC_080
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_251
P1DISCARDCOUNT:1

---

# SearchThenAhsokaDeclined_DeckIsTheSearchsResult
#// Declining Ahsoka after the search: she stays ready and the deck is exactly what the search left (SOR_095 on top).
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:NO
## EXPECT
P1LEADER:READY
P1DECKCOUNT:4
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:1
P1NODECISION

---

# AlongsideBossk_BothReactAfterTheEvent
#// Ahsoka and SOR_182 Bossk both trigger on "When you play an event", so both wait for the event: the Shield
#// pick and the search come first, then P1 orders the two reactions — Bossk first here, so Bossk's damage, then
#// Ahsoka's offer (CR 7.6.9). Ahsoka then looks at the post-search top card.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_101
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:SEC_080
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P1DECISIONPARAM:@SOR_095&Play&Discard&Leave
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED
P1DECKTOPCARD:SOR_095

---

# AltCostEvent_AhsokaStillTriggersAfterTheEvent
#// SOR_199 Bamboozle can be played by discarding a Cunning card (an alternate cost), which takes a different
#// branch of the event play. Ahsoka must still trigger there, after the event: decline the alternate cost and
#// pay normally, Bamboozle exhausts the enemy unit, and only then is Ahsoka offered.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_199 SOR_199]
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP1Deck: [SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Discard
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_251
P1DISCARDUNIT:1:CARDID:SOR_095

---

# TwinSuns_Seat4_SearchResolvesBeforeAhsoka
#// The reporter's seat: P4 in a four-seat Twin Suns game, Ahsoka in the first leader slot beside TS26_06 Rex (the
#// reported pairing). Same line as SearchThenAhsokaDiscard_FinalZones, driven from seat 4.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 4
WithInitiativePlayer: 4
WithP4Leader: TS26_08:1:0
WithP4Leader2: TS26_06:1:0
WithP4Resources: 6
WithP4Hand: HMW_101
WithP4Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P4>PlayHand:0
- P4>AnswerDecision:SEC_080
- P4>AnswerDecision:YES
- P4>AnswerDecision:Discard
## EXPECT
P4HANDCOUNT:1
P4HANDCARD:0:SEC_080
P4DECKCOUNT:3
P4DECKTOPCARD:SOR_251
P4DISCARDCOUNT:2
P4DISCARDUNIT:1:CARDID:SOR_095
P4LEADER:EXHAUSTED
P4NODECISION

---

# TwinSuns_Seat4_FirstPromptIsTheSearch
#// Seat 4: the first prompt after playing the search event is the search, not Ahsoka.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 4
WithInitiativePlayer: 4
WithP4Leader: TS26_08:1:0
WithP4Leader2: TS26_06:1:0
WithP4Resources: 6
WithP4Hand: HMW_101
WithP4Deck: [SOR_128 SEC_080 SOR_046 SOR_095 SOR_251]
## WHEN
- P4>PlayHand:0
## EXPECT
P4DECISIONTOOLTIP:Search_top_cards
P4LEADER:READY

---

# AltCostPaid_AhsokaStillTriggersAfterTheEvent
#// Bamboozle played FOR its alternate cost (discarding the second Bamboozle): the event still resolves first, then
#// Ahsoka is offered. Discard pile = the discarded copy, the played copy, then the milled SOR_095.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:TS26_08;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_199 SOR_199]
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP1Deck: [SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Discard
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
P1DECKTOPCARD:SOR_251
P1DISCARDCOUNT:3
P1DISCARDUNIT:2:CARDID:SOR_095

---

# AltCostEvent_BosskStillReacts
#// The same alternate-cost branch skipped the whole own-play reaction collector, so SOR_182 Bossk ("When you play
#// an event: you may deal 2 damage to a unit") never reacted to a Bamboozle that COULD have been played for its
#// alternate cost, whichever way it was paid. Pay normally, exhaust Consular Security Force, then Bossk deals 2 to it.
## GIVEN
CommonSetup: bbk/bbk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_199 SOR_199]
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_251]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
