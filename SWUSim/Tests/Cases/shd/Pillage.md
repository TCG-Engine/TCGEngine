# DiscardFromHandGetsTpp
## GIVEN
#// SHD_181 Pillage: Aggression aspect, cost 4
#// SOR_014 (Sabine) provides Aggression — no penalty, plays at 4
#// SHD_135 Kylo's TIE Silencer: Villainy+Aggression; covered by SOR_010 (Darth Vader)
CommonSetup: grw/grk/{myResources:4;handCardIds:SHD_181;theirHandCardIds:SHD_135,SOR_095}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0

## EXPECT
P2DISCARDUNIT:0:CARDID:SHD_135
P2DISCARDUNIT:0:MODIFIER:TPP

---

# PlayBackFromDiscard
## GIVEN
#// SHD_181 Pillage: Aggression aspect, cost 4; P1 covered by SOR_014 (Aggression)
#// SHD_135 Kylo's TIE Silencer: Villainy+Aggression, cost 2; P2 covered by SOR_010 (Aggression+Villainy)
CommonSetup: grw/grk/{myResources:4;theirResources:2;handCardIds:SHD_181;theirHandCardIds:SHD_135,SOR_095}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>Pass
- P2>PlayFromDiscard:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_135
P2DISCARDCOUNT:1
P2RESAVAILABLE:0

---

# AutoResolvesOnCard
## GIVEN
CommonSetup: grw/grw/{myResources:4;handCardIds:SHD_181;theirHandCardIds:SOR_095}

## WHEN
- P1>PlayHand:0

## EXPECT
P2DISCARDCOUNT:1
P2HANDCOUNT:0
P1DISCARDCOUNT:1

---

# ForcesDiscard
## GIVEN
CommonSetup: grw/grw/{myResources:4;handCardIds:SHD_181;theirHandCardIds:SHD_135,SOR_095}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0

## EXPECT
P2DISCARDCOUNT:2
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# ChooseYOURSELF_YouDiscardTwo
#// ⚠ THE CAPABILITY THIS CARD WAS MISSING ENTIRELY (fixed 2026-08-21). "Choose a PLAYER" — not "an
#// opponent" — so you are a legal pick, and the old implementation (a bare SWUDiscardCards, i.e. always
#// the opponent) could never express it. That was wrong in PREMIER too, not just Twin Suns.
#// P1 keeps two cards in hand after playing Pillage, so both seats are eligible and the picker appears.
#// P1 chooses THEMSELF and discards two of their own — P2's hand is untouched.
#// ⚠ This is the one card in the sweep permitted to add a 2-player prompt; see the $includeSelf note on
#//   SWUQueueChooseOpponent. Every other conversion must stay silent at two seats.

## GIVEN
CommonSetup: grw/grw/{myResources:4}
SkipPreGame: true
WithP1Hand: [SHD_181 SOR_095 SOR_046]
WithP2Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P1
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:2
P1DISCARDCOUNT:3

---

# TheOfferIsBOTHPlayers
#// THE OFFER CELL. Left pending: with both seats holding cards the menu must contain the caster's OWN
#// seat as well as the opponent's — the half a "choose an opponent" picker cannot express.
#// ⚠ The UI renders these tokens as usernames (or "Player N"); the SUBMITTED value stays "P1"/"P2",
#//   which is what these assertions read. See Tests/Visual/ChooseOpponent_PickerShowsPlayerNames.md.

## GIVEN
CommonSetup: grw/grw/{myResources:4}
SkipPreGame: true
WithP1Hand: [SHD_181 SOR_095 SOR_046]
WithP2Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:P1
P1OPTIONHAS:P2

---

# TheInFlightPillageDoesNotMakeYouEligible
#// ⚠ An event is ALREADY OUT OF HAND (removed, sitting in the discard) when its own When Played resolves,
#// but the removed entry lingers in the hand zone until a cleanup compacts it. A naive count sees the
#// caster holding one card and offers them as an eligible target for their own Pillage.
#// P1's hand is Pillage and nothing else: after the play they hold zero, so P1 is NOT eligible, only P2
#// is, and the pick auto-resolves with NO prompt — the same shape as every pre-existing section here,
#// pinned explicitly so it stays true.

## GIVEN
CommonSetup: grw/grw/{myResources:4}
SkipPreGame: true
WithP1Hand: SHD_181
WithP2Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:2

---

# NobodyHoldsACard_NothingHappens
#// The empty case: no player has a card to discard, so there is no menu and no prompt at all — rather
#// than a picker whose every answer does nothing.

## GIVEN
CommonSetup: grw/grw/{myResources:4}
SkipPreGame: true
WithP1Hand: SHD_181
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2NODECISION
P1DISCARDCOUNT:1

---

# TwinSuns_ChooseSeatThree
#// ⚠ THE SEAT-COUNT CELL. "A player" at four seats is FOUR answers, and the old code could only ever
#// reach OtherPlayer() — seat 2. P1 picks seat 3; only seat 3 discards.

## GIVEN
CommonSetup: grw/grw/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SHD_181
WithP2Hand: [SOR_095 SOR_046]
WithP3Hand: [SOR_095 SOR_046]
WithP4Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P3>AnswerDecision:myHand-0
- P3>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:4
P3HANDCOUNT:0
P2HANDCOUNT:2
P4HANDCOUNT:2

---

# TwinSuns_OnlyOneSeatHoldsCards_AutoResolvesWithNoPrompt
#// ⚠ INVARIANT I2 — auto-target when the choice is DEGENERATE, including at four seats. Early Twin Suns
#// boards are mostly empty: seat 4 alone holds a card, so there is exactly one eligible answer and the
#// player must NOT be asked "which player?" when two of the three answers would do nothing.
#// The eligibility list is what makes this work; without it the picker offers every live seat.

## GIVEN
CommonSetup: grw/grw/{myResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SHD_181
WithP4Hand: [SOR_095 SOR_046]
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P4>AnswerDecision:myHand-0
- P4>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:4
P1NODECISION
P4HANDCOUNT:0
P4DISCARDCOUNT:2
