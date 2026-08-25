# NameCard_ExpPerCopy
#// SEC_260 Inspector's Shuttle (Space, 1/3, cost 2) — When Played: name a card; for each copy of it in
#//   an opponent's hand, give an Experience token to this unit. P2 hand has 2 Battlefield Marines → +2/+2 → 3 power.
#// ⚠ ANSWER UPDATED 2026-08-24 — a trailing OK, and the EXPECTATIONS ARE OTHERWISE UNCHANGED. The card's
#//   printed text (confirmed by its ERRATA, 07/20/2026: "Name a card, then an opponent REVEALS THEIR
#//   HAND") is a real, observable event, but this handler used to count the copies SILENTLY — the
#//   opponent's hand was inspected and nothing was shown, so the naming player could not verify the count
#//   and the revealing player never saw it happen. The reveal now puts the cards on screen behind an OK
#//   acknowledgement (a game-log line alone is too easy to miss for something a player is entitled to see),
#//   which is the extra decision answered here.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_095
WithP2Hand: SOR_095
WithP1Hand: SEC_260

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P1>AnswerDecision:OK

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_260
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# NameCard_SameTitleDifferentSubtitle
#// SEC_260 Inspector's Shuttle — the count is by TITLE, so two different Millennium Falcon printings
#//   (SOR_193 and SHD_204) both count as copies of "Millennium Falcon". P2 hand also holds an unrelated
#//   Wampa. Naming Millennium Falcon reveals P2's hand and grants 2 Experience → 1 power becomes 3.
#// ⚠ ANSWER UPDATED 2026-08-24 — a trailing OK, and the EXPECTATIONS ARE OTHERWISE UNCHANGED. The card's
#//   printed text (confirmed by its ERRATA, 07/20/2026: "Name a card, then an opponent REVEALS THEIR
#//   HAND") is a real, observable event, but this handler used to count the copies SILENTLY — the
#//   opponent's hand was inspected and nothing was shown, so the naming player could not verify the count
#//   and the revealing player never saw it happen. The reveal now puts the cards on screen behind an OK
#//   acknowledgement (a game-log line alone is too easy to miss for something a player is entitled to see),
#//   which is the extra decision answered here.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_193
WithP2Hand: SHD_204
WithP2Hand: SOR_164
WithP1Hand: SEC_260

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Millennium Falcon
- P1>AnswerDecision:OK

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_260
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# WhenPlayed_OpponentHandEmpty_NoPrompt
#// SEC_260 Inspector's Shuttle — with the opponent's hand empty there is nothing to reveal or count, so
#//   the naming is skipped entirely (no NAMECARD prompt) and no Experience is granted. Enters as 1/3.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_260

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_260
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# TwinSuns_NamesTheCardThenREVEALSTheChosenSeatsHand
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. Two defects, one of which had nothing to do with seats.
#//
#// (1) SEAT: "…then AN OPPONENT reveals their hand." OFFICIAL RULING (10/31/2025): "If there are multiple
#//     opponents, the controlling player chooses which one will be 'an opponent.'" OtherPlayer() picked
#//     one silently, so above two seats the shuttle counted copies in a hand the caster never chose.
#//     FILTER to opponents holding a card — an empty hand can reveal nothing and grants nothing (this is
#//     now the zero-eligible case that preserves WhenPlayed_OpponentHandEmpty_NoPrompt).
#//
#// (2) THE REVEAL WAS INVISIBLE, at every seat count. The handler counted the copies SILENTLY: the
#//     opponent's hand was inspected and nothing was shown or logged, so the naming player could not
#//     verify the count and the revealing player never knew it happened. The card's ERRATA (07/20/2026)
#//     spells the clause out — "Name a card, then an opponent REVEALS THEIR HAND" — so it is real text,
#//     not flavour. Now SWULookAtOpponentHand logs it (scoped to the two seats involved) and
#//     SWUQueueShowOpponentHand puts the cards on screen behind an OK.
#//
#// SEAT 3 holds two Battlefield Marines and is chosen; seat 2 also holds cards and must be untouched.
#// Naming "Battlefield Marine" grants 2 Experience (1 power → 3), and the OK acknowledgement must be
#// raised — that pending prompt IS the assertion that the reveal happened at all.
#// ⚠ A 2-player version cannot pin the SEAT half (one opponent is forced), but it does pin the reveal —
#//   which is why the two 2-player sections above also gained the OK.
#// Mutation check: revert to OtherPlayer() and this reds; drop SWUQueueShowOpponentHand and the OK
#// answer has nothing to answer.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: SEC_260
WithP2Hand: [SOR_095 SOR_164]
WithP3Hand: [SOR_095 SOR_095]
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P1>AnswerDecision:Battlefield Marine
- P1>AnswerDecision:OK

## EXPECT
SEATCOUNT:4
P1SPACEARENAUNIT:0:CARDID:SEC_260
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P2HANDCOUNT:2
P3HANDCOUNT:2

---

# TheRevealPromptIsACTUALLYRaised
#// ⚠ THE REVEAL-EXISTS CELL — added 2026-08-24, and it exists because ANSWERING a prompt does not prove
#// the prompt was ever raised. A spare `AnswerDecision:OK` is silently ABSORBED by the harness when there
#// is nothing pending (the known auto-resolve-artifact trap), so the sections that answer OK stayed GREEN
#// with the reveal deleted — verified by mutation. Only an assertion on the PENDING DECISION catches it.
#//
#// This section plays the shuttle and names the card, then STOPS: the OK acknowledgement must be sitting
#// there unanswered. That pending OPTIONCHOOSE is the proof that the opponent's hand was actually shown
#// rather than silently counted.
#// ⚠ Generalises: whenever an effect's only observable output is a prompt, assert the prompt. Answering it
#//   tests nothing.
#// Mutation check: delete SWUQueueShowOpponentHand and this reds.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2Hand: SOR_095
WithP2Hand: SOR_095
WithP1Hand: SEC_260

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P1HASDECISION
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
