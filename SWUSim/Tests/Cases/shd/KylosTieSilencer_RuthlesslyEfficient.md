# DiscardedFromHand_PlayableFromDiscardAtCost
#// SHD_135 Kylo's TIE Silencer, Ruthlessly Efficient — unique Space unit, cost 2, 3/2,
#// [Villainy][Aggression], traits First Order / Vehicle / Fighter.
#// "Action: If this unit was discarded from your hand or deck this phase, play it from your discard pile
#// (paying its cost)."
#// P1's SHD_181 Pillage makes P2 discard 2 cards from hand; SHD_135 is one of them, so it becomes
#// replayable this phase. P2 then plays it out of its own discard, PAYING the full cost of 2 (2 ready
#// resources -> 0) and it enters P2's SPACE arena. Only the other discarded card (SOR_095) is left in
#// P2's discard.
#// COVERAGE: offer=N/A — the replay is a direct board action on a discard-pile card, not a target choice,
#//           so there is no decision pool for SELECTABLE to address; the permission's contents are
#//           instead pinned end-to-end by the two positives against the three negatives (wrong source
#//           zone / wrong phase / unaffordable) · reqboundary=DiscardedFromDeck_PlayableFromDiscardAtCost
#//           (the stamp is written during P2's attack and read back by P1 on a later action across a turn
#//           swap) + StampExpiresNextPhase (read back across a regroup crossing) ·
#//           control=OpponentCannotPlayItFromYourDiscard (the replay permission belongs to the discard's
#//           OWNER — an opponent may not spend it; the card itself never changes controller in these
#//           flows) · boundary pair=this section vs Unaffordable_NoOp (full cost payable vs one resource
#//           short), plus DiscardedFromPlay_NotPlayable (source zone PLAY, not hand/deck) and
#//           StampExpiresNextPhase (this phase vs next) · decline=N/A — an "Action:" replay is an
#//           optional board action with no prompt, so declining it is simply not taking it, which every
#//           negative section here does.

## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:SHD_135,SOR_095;theirResources:2}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>Pass
- P2>PlayFromDiscard:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_135
P2RESAVAILABLE:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095

---

# Unaffordable_NoOp
#// SHD_135 — "(paying its cost)": the replay is NOT free. Same forced discard as
#// DiscardedFromHand_PlayableFromDiscardAtCost, but P2 holds only 1 ready resource against the Silencer's
#// cost of 2. The play attempt is a silent no-op: SHD_135 stays in P2's discard, P2's space arena is
#// empty and the lone resource is never spent.

## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:SHD_135,SOR_095;theirResources:1}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>Pass
- P2>PlayFromDiscard:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:2
P2DISCARDUNIT:0:CARDID:SHD_135
P2RESAVAILABLE:1

---

# DiscardedFromDeck_PlayableFromDiscardAtCost
#// SHD_135 — "discarded from your hand OR DECK this phase" also covers a deck mill. P2's SOR_047 Kanan
#// Jarrus mills the top of P1's deck on attack, putting SHD_135 into P1's discard tagged replayable. P1
#// then plays it from the discard for its full cost of 2 (Villainy/Aggression, on-aspect for P1's rrk
#// pairing) and it enters P1's SPACE arena, emptying P1's discard.

## GIVEN
CommonSetup: rrk/grw/{theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_047:1:0
WithP1Resources: 2
WithP1Deck: SHD_135
WithP1Deck: SOR_095

## WHEN
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P1>PlayFromDiscard:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_135
P1DISCARDCOUNT:0
P1RESAVAILABLE:0

---

# DiscardedFromPlay_NotPlayable
#// SHD_135 — the permission is scoped to a discard FROM HAND OR DECK. A Silencer that reaches the discard
#// FROM PLAY is not stamped and cannot be replayed. P1's SHD_135 (3/2) attacks P2's SOR_237 Alliance
#// X-Wing (2/3): the X-Wing dies to 3 damage and the counter of 2 kills the Silencer, so it lands in P1's
#// discard from PLAY. P1's replay attempt is a silent no-op. The trailing P1>PlayHand (SOR_128, cost 1
#// on-aspect, 4 -> 3 ready resources) is the positive control that P1 really had the action.

## GIVEN
CommonSetup: rrk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_128
WithP1SpaceArena: SHD_135:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayFromDiscard:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_135
P2DISCARDCOUNT:1
P1RESAVAILABLE:3

---

# StampExpiresNextPhase
#// SHD_135 — "discarded ... THIS PHASE" is a per-phase permission. Same forced discard as
#// DiscardedFromHand_PlayableFromDiscardAtCost, but both players pass through regroup into the next
#// action phase before P2 tries the replay: the attempt is a silent no-op, SHD_135 stays in P2's discard
#// and P2's 2 resources are untouched. Both decks are seeded so the regroup draws do not hit an empty
#// deck (which would add base damage and muddy the state).

## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:SHD_135,SOR_095;theirResources:2}
WithP1Deck: [SOR_128 SOR_128]
WithP2Deck: [SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>PlayFromDiscard:0

## EXPECT
PHASE:MAIN
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:2
P2DISCARDUNIT:0:CARDID:SHD_135
P2RESAVAILABLE:2

---

# OpponentCannotPlayItFromYourDiscard
#// SHD_135 — "play it from YOUR discard pile" belongs to the card's owner. After P1's Pillage puts P2's
#// Silencer into P2's discard tagged replayable, P1 tries to play it out of P2's discard: the attempt is
#// a silent no-op. SHD_135 stays in P2's discard, P1's arenas stay empty and P1 has spent nothing beyond
#// Pillage's own 4 resources.

## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:SHD_135,SOR_095;theirResources:2}

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>PlayFromOpponentDiscard:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P2DISCARDCOUNT:2
P2DISCARDUNIT:0:CARDID:SHD_135
P1RESAVAILABLE:0
