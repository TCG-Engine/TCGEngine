# NamedCantBePlayed
#// LAW_243 Transmission Jamming (Cunning event, cost 1) — "Name a card. Cards with that name can't be
#// played this phase." P1 names Battlefield Marine; P2's attempt to play SOR_095 is blocked (stays in hand).

## GIVEN
CommonSetup: yyw/ggw/{myResources:1;theirResources:3}
WithActivePlayer: 1
WithP1Hand: LAW_243
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# OtherCardsStillPlayable
#// LAW_243 Transmission Jamming — only cards with the NAMED title are locked. P1 names Wampa; P2 can still
#// play a differently-named card (SOR_095 Battlefield Marine) normally this phase.

## GIVEN
CommonSetup: yyw/ggw/{myResources:1;theirResources:3}
WithActivePlayer: 1
WithP1Hand: LAW_243
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Wampa
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2HANDCOUNT:0

---

# BlockExpiresNextPhase
#// LAW_243 Transmission Jamming — the name lock lasts only THIS phase. P1 names Battlefield Marine; after
#// the action phase ends and the next begins, P2 can play SOR_095 Battlefield Marine again.

## GIVEN
CommonSetup: yyw/ggw/{myResources:1;theirResources:3}
WithActivePlayer: 1
WithP1Deck: SOR_237
WithP1Deck: SOR_095
WithP2Deck: SOR_237
WithP2Deck: SOR_095
WithP1Hand: LAW_243
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1

---

# NamedCardCantBePlayedAsPilot
#// LAW_243 Transmission Jamming — the name-lock is enforced at the play-from-hand entry, BEFORE the
#// unit-vs-pilot branch, so a named Piloting card can't be played as a pilot either. P1 names "Dagger
#// Squadron Pilot" (JTL_196); attempting to play it (which would otherwise offer a Unit/Pilot choice onto
#// the friendly AT-ST) is blocked — it stays in hand and the AT-ST gains no upgrade.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1Hand: LAW_243
WithP1Hand: JTL_196

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Dagger Squadron Pilot
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# UnnamedPilotStillPlayable
#// LAW_243 Transmission Jamming — control: with no name-lock on it, JTL_196 Dagger Squadron Pilot plays
#// normally as a pilot onto the friendly AT-ST (upgrade count 1). Confirms the block above is real, not a
#// pilot-play limitation.

## GIVEN
CommonSetup: yyw/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1Hand: JTL_196

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NamedCantBePlayed_SurvivesTheRequestBoundary
#// LAW_243 Transmission Jamming — request-boundary guard. Identical to NamedCantBePlayed except the game
#// round-trips through serialization (SimulateRequestBoundary) while the "name a card" prompt is still
#// pending. This is the crux for this card: the event's resolution continuation (the thing that turns the
#// answer into a phase-scoped play-lock) sits across an interactive decision that, in a real game, is
#// answered by a fresh process — and the lock it writes is then read by a LATER request still. Naming
#// Battlefield Marine after the boundary must still stop P2 playing SOR_095 this phase.

## GIVEN
CommonSetup: yyw/ggw/{myResources:1;theirResources:3}
WithActivePlayer: 1
WithP1Hand: LAW_243
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# NameMatchesByTitle_EveryVersionIsBlocked
#// COVERAGE (this file; appended because the earlier sections are frozen): offer=the name is free text,
#//           so the "pool" is the TITLE space — NameMatchesByTitle_EveryVersionIsBlocked pins that a
#//           title matches every card printing that carries it, not one card ID · decline=N/A (naming is
#//           mandatory once the event resolves) · control=NamedTitleNotChosen_BothVersionsPlayable (same
#//           board, a different name) + OtherCardsStillPlayable · boundary=BlockExpiresNextPhase (phase
#//           edge) + NameLocksTheNamingPlayerToo (the block is symmetric, not opponent-only) ·
#//           reqboundary=NamedCantBePlayed_SurvivesTheRequestBoundary.
#//
#// LAW_243 Transmission Jamming — "Name a card. Cards with that name can't be played this phase." A
#// card's NAME is its title; the subtitle is not part of it, so naming "Millennium Falcon" locks every
#// printing that shares that title. P2 holds two different Millennium Falcon cards — SOR_193 (cost 3)
#// and SHD_204 (cost 6), different subtitles, different stats, different card IDs — plus SOR_095
#// Battlefield Marine. With 15 resources P2 can afford all three, yet BOTH Falcons are stuck in hand and
#// only the Battlefield Marine reaches the board. A block keyed on the card ID of a single printing
#// would let the other Falcon through.

## GIVEN
CommonSetup: yyw/ggw/{myResources:2;theirResources:15}
WithActivePlayer: 1
WithP1Hand: LAW_243
WithP2Hand: [SOR_193 SHD_204 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Millennium Falcon
- P2>PlayHand:0
- P2>PlayHand:1
- P2>PlayHand:2

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2HANDCOUNT:2

---

# NamedTitleNotChosen_BothVersionsPlayable
#// LAW_243 Transmission Jamming — the control for the section above. Identical board, but P1 names
#// "Wampa" (a card nobody holds), so nothing is locked: P2 plays BOTH Millennium Falcon printings into
#// the space arena in the same phase. This is what proves the previous section's empty space arena is
#// the name-block and not a resource, aspect, uniqueness or turn-order artifact — the two Falcons carry
#// different subtitles, so the unique rule does not collide either.

## GIVEN
CommonSetup: yyw/ggw/{myResources:2;theirResources:15}
WithActivePlayer: 1
WithP1Hand: LAW_243
WithP2Hand: [SOR_193 SHD_204 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Wampa
- P2>PlayHand:0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:2
P2HANDCOUNT:1

---

# NameLocksTheNamingPlayerToo
#// LAW_243 Transmission Jamming — "Cards with that name can't be played this phase" names no player, so
#// the lock is symmetric: it binds the player who cast it exactly as hard as the opponent. P1 names
#// Battlefield Marine while holding one, and then cannot play it — while SOR_046 Consular Security
#// Force, sitting in the same hand and paid from the same resources, plays normally. The in-section
#// control matters here: it rules out "P1 simply had no action left" as the reason nothing happened.

## GIVEN
CommonSetup: yyw/ggw/{myResources:12}
P1OnlyActions: true
WithP1Hand: [LAW_243 SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P1>PlayHand:0
- P1>PlayHand:1

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046

---

# NamedCardCantBeSmuggledFromResources
#// LAW_243 Transmission Jamming — "can't be PLAYED" covers every way of playing the card, not just the
#// play-from-hand action. Smuggle is a play made from the resource row, so a named card sitting face-up
#// as a resource is just as locked. P1 names SHD_111 Collections Starhopper (Smuggle 3, Command — its
#// own aspect is covered by the base, so the cost is genuinely payable) and then tries to smuggle it:
#// nothing enters the space arena and the resource row is untouched at 7 cards.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 6:SOR_095:1,1:SHD_111:1
WithP1Hand: LAW_243
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Collections Starhopper
- P1>SmuggleResource:6

## EXPECT
P1SPACEARENACOUNT:0
P1RESCOUNT:7

---

# UnnamedCardStillSmugglable
#// LAW_243 Transmission Jamming — the control for the section above: same board, same resources, same
#// Smuggle, but the named card is "Wampa". SHD_111 Collections Starhopper smuggles into the space arena
#// normally, which is what proves the empty arena above is the name-block and not an unpayable Smuggle
#// cost, a wrong resource index, or a spent action.

## GIVEN
CommonSetup: ggw/rrk/{}
P1OnlyActions: true
WithP1Resources: 6:SOR_095:1,1:SHD_111:1
WithP1Hand: LAW_243
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Wampa
- P1>SmuggleResource:6

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111

---

# NamedCardCantBePlayedFromTheDiscardPile
#// LAW_243 Transmission Jamming — the third play-from-elsewhere path (hand · resources · discard). A card
#// whose own text lets it be played out of the discard pile is still "played", so the name-block reaches
#// it there. P1's SHD_181 Pillage makes P2 discard LAW_200 Salvaged Blaster (which stamps it "discarded
#// from hand this phase" and so grants its own "Action: play it from your discard pile") plus a filler;
#// P1 then names Salvaged Blaster. P2's replay attempt does nothing: SEC_080 stays a bare 3-power unit
#// and BOTH discarded cards are still in the discard pile.

## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SHD_181,LAW_243;myResources:8;theirHandCardIds:LAW_200,SOR_095;theirResources:2}
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>PlayHand:0
- P1>AnswerDecision:Salvaged Blaster
- P2>PlayFromDiscard:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2DISCARDCOUNT:2

---

# UnnamedCardStillPlayableFromTheDiscardPile
#// LAW_243 Transmission Jamming — the control for the section above. Same Pillage, same discard, same
#// replay attempt, but P1 names "Wampa": LAW_200 Salvaged Blaster comes back out of the discard pile and
#// attaches to SEC_080 (+2/+0 → power 5), leaving one card in the discard. Without this, the blocked
#// section could equally be an expired replay stamp, an unpayable cost or a bad discard index.

## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SHD_181,LAW_243;myResources:8;theirHandCardIds:LAW_200,SOR_095;theirResources:2}
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>PlayHand:0
- P1>AnswerDecision:Wampa
- P2>PlayFromDiscard:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:5
P2DISCARDCOUNT:1
