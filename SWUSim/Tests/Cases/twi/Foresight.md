# NameMatchesTop_MayDraw
#// TWI_068 Foresight (Upgrade) — grants "When the regroup phase starts (before drawing cards): Name a
#// card, then look at the top card of your deck. If it's the named card, you may reveal and draw it." The
#// deck top is SOR_046 (Consular Security Force); at regroup the controller names it and draws it.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_068
P1Deck: [SOR_046 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>AnswerDecision:Consular Security Force
- P1>AnswerDecision:YES
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:3

---

# NameMatches_DeclineDraw
#// TWI_068 Foresight — the reveal/draw is optional ("you may"). The top IS the named card (SOR_046), but
#// P1 DECLINES, so no extra card is drawn: P1 ends with only the two normal regroup draws (hand 2, deck 4),
#// the same as if the ability had done nothing.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_068
P1Deck: [SOR_046 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>AnswerDecision:Consular Security Force
- P1>AnswerDecision:NO
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:4

---

# NameWrong_NoDraw
#// TWI_068 Foresight — if the top card is NOT the named card, nothing is revealed or drawn. The top is
#// SOR_046 but P1 names a different card, so there is no reveal/draw prompt and the hand stays empty.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_068
P1Deck: [SOR_046 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>AnswerDecision:Battlefield Marine
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:4

---

# RegroupStart_NamesBeforeTheRegroupDraw
#// Reported 2026-09-30: "When the regroup phase starts (BEFORE DRAWING CARDS)". The name-a-card prompt must
#// be the first thing the regroup phase asks — no regroup draw yet (hand still 0, deck still 6). It was
#// queued from the regroup READY step instead, so it came after both draws and the resource step, and the
#// card it peeked was no longer the one that started the phase on top.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_068
P1Deck: [SOR_046 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Name_a_card_(Foresight)
P1HANDCOUNT:0
P1DECKCOUNT:6

---

# RegroupStart_PeeksTheCardOnTopBeforeTheDraw
#// The peek must see the card on top WHEN THE REGROUP PHASE STARTS. SOR_046 starts on top with Battlefield
#// Marines below: naming it hits, P1 reveals and draws it, THEN the two regroup draws take two Marines —
#// hand 3 (SOR_046 among them), deck 3. Peeking after the regroup draw (the bug) would find a Marine on top,
#// miss, and leave hand 2. (The sections above use `P1Deck:`, which until 2026-10-01 built count(list) copies
#// of its FIRST card — they ran on six SOR_046s and could not tell the two timings apart. It is now the literal
#// deck under SkipPreGame/CommonSetup: core/Framework_PDeckIsLiteral.md.)
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_068
WithP1Deck: [SOR_046 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Pass
- P1>AnswerDecision:Consular Security Force
- P1>AnswerDecision:YES
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P1HANDCOUNT:3
P1DECKCOUNT:3
P1HANDCARD:0:SOR_046
P1NODECISION
