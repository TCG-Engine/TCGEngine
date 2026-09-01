# DiscardTwo_ReorderRest
#// COVERAGE: offer=FourSeats_EnemyBaseOffer_TeammateAndSelfAreNotOnTheMenu (the "an ENEMY base" picker
#//           left PENDING and its label set read exactly: P2 and P4 offered, the TEAMMATE P3 and the
#//           caster's own seat both absent — the two seats a naive "every other base" pool would add;
#//           at 1v1 the same pick auto-resolves, so four seats is the only board that can show it) plus
#//           the reveal keep/discard split prompt exercised with real picks in DiscardTwo_ReorderRest +
#//           FourHeroism_FourDamage (keep-order + discard destinations both asserted)
#//           · reqboundary=N/A (single-resolver event; reveal, damage and split resolve in one
#//           uninterrupted resolution — no decision is answered after reading pre-decision state)
#//           · control=CrossPlayer_RevealsTHEIROwnDeck_AndHitsOURBase (the who-resolves-it reading:
#//           P2 casts it with P1 also holding a stocked deck, so "your deck", "an enemy base" and the
#//           discard the rejected reveals fall into must all resolve from the CASTER's seat — P1's deck
#//           and discard end untouched. The owner≠controller reading is N/A: this is an event that puts
#//           no permanent into play and moves only the caster's own cards)
#//           · boundary=EmptyDeck_NoEffect + TwoCardDeck_RevealsRemaining (deck-size edges) and
#//           LethalToOpponentBase_EndsGame / SawGerreraSurcharge_SelfLethal (win/loss edges)
#//           · decline=NoHeroism_NoBaseDamage + TwoHeroism_KeepAll (discard NONE of the reveals)
#// SOR_152 For a Cause I Believe In — same reveal of 4 (2 Heroism → P2 base takes 2, dealt before
#// the discard step). This time the player discards the two Heroism cards (SOR_095, SOR_189) and
#// keeps the two non-Heroism cards on top, reordered SOR_111 then SOR_128. Deck 4 → 2 (top SOR_111);
#// discard = the event (SOR_152) + the two discarded reveals = 3 (discarded reveals are From DECK).

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: SOR_095
WithP1Deck: SOR_189
WithP1Deck: SOR_128
WithP1Deck: SOR_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_111,SOR_128|SOR_095,SOR_189

## EXPECT
P2BASEDMG:2
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_111
P1DISCARDCOUNT:3

---

# NoHeroism_NoBaseDamage
#// SOR_152 For a Cause I Believe In — absence guard. Top 4 are all non-[Heroism] (SOR_128 Villainy,
#// SOR_111 Command, SOR_171 Aggression, SOR_226 Villainy) → no [Heroism] revealed → P2 base takes 0.
#// Player keeps all four (discards none). Deck stays 4; only the event is in discard.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: SOR_128
WithP1Deck: SOR_111
WithP1Deck: SOR_171
WithP1Deck: SOR_226

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_128,SOR_111,SOR_171,SOR_226|

## EXPECT
P2BASEDMG:0
P1DECKCOUNT:4
P1DECKTOPCARD:SOR_128
P1DISCARDCOUNT:1

---

# TwoHeroism_KeepAll
#// SOR_152 For a Cause I Believe In (Event, cost 3, Aggression/Heroism) — Reveal the top 4 cards;
#// for each [Heroism] card revealed, deal 1 damage to an enemy base; then you may discard any of the
#// revealed cards and put the rest back on top in any order. Top 4 = SOR_095 (Heroism), SOR_189
#// (Heroism), SOR_128 (Villainy), SOR_111 (Command) → 2 Heroism → P2 base takes 2. Player keeps all
#// four in the original order (discards none). Deck stays 4; only the event itself is in discard.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: SOR_095
WithP1Deck: SOR_189
WithP1Deck: SOR_128
WithP1Deck: SOR_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095,SOR_189,SOR_128,SOR_111|

## EXPECT
P2BASEDMG:2
P1DECKCOUNT:4
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:1

---

# FourHeroism_FourDamage
#// SOR_152 For a Cause I Believe In — all four revealed cards are [Heroism] (SOR_095 Battlefield
#// Marine, SOR_189 Leia Organa, SOR_236 R2-D2, SOR_237 Alliance X-Wing) → 4 damage to P2's base,
#// dealt before the discard step. Player then keeps R2-D2 and the X-Wing on top (new order: SOR_236
#// then SOR_237) and discards the two 2-cost units. Deck 4 → 2; discard = event + 2 reveals = 3.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: SOR_095
WithP1Deck: SOR_189
WithP1Deck: SOR_236
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_236,SOR_237|SOR_095,SOR_189

## EXPECT
P2BASEDMG:4
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_236
P1DISCARDCOUNT:3

---

# TwoCardDeck_RevealsRemaining
#// SOR_152 For a Cause I Believe In — "top 4" with only 2 cards left reveals just those 2. One of
#// them (SOR_237 Alliance X-Wing) is [Heroism] → exactly 1 damage to P2's base. The player keeps the
#// X-Wing on top and discards the non-Heroism SOR_111 → deck 2 → 1, discard = event + 1 reveal = 2.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: SOR_111
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_237|SOR_111

## EXPECT
P2BASEDMG:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_237
P1DISCARDCOUNT:2

---

# EmptyDeck_NoEffect
#// SOR_152 For a Cause I Believe In — with an EMPTY deck nothing is revealed: no damage, no
#// keep/discard prompt. The event still resolves to the discard (count 1) and no decision is left
#// pending.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_152

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# SawGerreraSurcharge_SelfLethal
#// SOR_152 For a Cause I Believe In vs SOR_153 Saw Gerrera — Saw makes each opponent's event cost an
#// ADDITIONAL 2 damage to their own base. With P1's base already at 28, paying the surcharge puts it
#// at 30: P1 loses the game on its own event's cost. Intended: the game ends at that moment, so the
#// reveal should never resolve (post-win resolution is tracked separately; only the decided winner
#// and the lethal self-damage are asserted here).

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;myBaseDamage:28}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP2GroundArena: SOR_153:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_189
WithP1Deck: SOR_236
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P2WIN
P1BASEDMG:30

---

# LethalToOpponentBase_EndsGame
#// SOR_152 For a Cause I Believe In — the reveal damage can be lethal. P2's base is at 26; all four
#// revealed cards are [Heroism] → 4 damage → 30 → P1 wins the game on the event's own resolution.

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;theirBaseDamage:26}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: SOR_095
WithP1Deck: SOR_189
WithP1Deck: SOR_236
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1WIN
P2BASEDMG:30

---

# LethalToOpponentBase_NoArrangePromptAfterTheWin
#// Post-win resolution halt, the non-trigger shape. Same board as LethalToOpponentBase_EndsGame: all
#// four reveals are [Heroism], so P2's base goes 26 → 30 and P1 wins DURING the event. The reveal's
#// "you may discard any of the revealed cards and put the rest back on top" prompt is queued AFTER
#// that damage, so it must never appear — the game is already over. (Contrast the sections above,
#// where the same prompt is the whole point: it is suppressed only because the game ended.)

## GIVEN
CommonSetup: rrw/rrw/{myResources:3;theirBaseDamage:26}
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: SOR_095
WithP1Deck: SOR_189
WithP1Deck: SOR_236
WithP1Deck: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1WIN
P1NODECISION
P2NODECISION

---

# FourSeats_ChoosesWHICHEnemyBaseTakesTheDamage
#// SOR_152 at FOUR seats — "deal 1 damage to AN enemy base" per Heroism card revealed. The lump lands on
#// ONE base the caster picks, and above two seats that pick is a real prompt (SWUQueueChooseOpponent →
#// SOR_152#BASE → SWUPickedOpponent). This section is the guard for that: P1 names P4, so the damage must
#// be on P4's base and NOT on P2's — the seat a legacy OtherPlayer()/auto-pick would have hit. The pick
#// comes BEFORE the arrange answer, which also pins the queue order (damage first, arrange after).

## GIVEN
CommonSetup: rrw/rrw
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 3
WithP1Hand: SOR_152
WithP1Deck: SOR_095
WithP1Deck: SOR_189
WithP1Deck: SOR_128
WithP1Deck: SOR_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P4
- P1>AnswerDecision:SOR_111,SOR_128|SOR_095,SOR_189

## EXPECT
SEATCOUNT:4
P4BASEDMG:2
P2BASEDMG:0
P1DECKCOUNT:2

---

# FourSeats_EnemyBaseOffer_TeammateAndSelfAreNotOnTheMenu
#// SOR_152 For a Cause I Believe In — OFFER axis for "deal 1 damage to AN ENEMY BASE". Answering P4, as
#// FourSeats_ChoosesWHICHEnemyBaseTakesTheDamage does, proves the answer was honoured; it cannot prove
#// WHO ELSE was on the menu, and the two seats that must NOT be there are exactly the ones a naive
#// "every other base" pool would include. Teams are seat parity (1,3 vs 2,4), so P1's enemies are P2 and
#// P4 and P3 is P1's TEAMMATE. The picker is left PENDING and its label set is read directly: P2 and P4
#// offered, P3 (teammate — not an ENEMY base) and P1 (self) both absent. Nothing has resolved yet, so
#// every base is still undamaged and the reveal's arrange prompt has not been queued.

## GIVEN
CommonSetup: rrw/rrw
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 3
WithP1Hand: SOR_152
WithP1Deck: SOR_095
WithP1Deck: SOR_189
WithP1Deck: SOR_128
WithP1Deck: SOR_111

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1OPTIONHAS:P2
P1OPTIONHAS:P4
P1OPTIONNOT:P3
P1OPTIONNOT:P1
P1BASEDMG:0
P2BASEDMG:0
P3BASEDMG:0
P4BASEDMG:0
P1DECKCOUNT:4

---

# CrossPlayer_RevealsTHEIROwnDeck_AndHitsOURBase
#// SOR_152 For a Cause I Believe In — CONTROL axis, the "who resolves it" reading. Every zone reference
#// on this card is seat-relative — "the top 4 cards of YOUR deck", "AN ENEMY base", and the discard the
#// rejected reveals fall into — and all three are resolved from the player who PLAYED the event, not
#// from seat 1. Here P2 casts it while P1 also holds a stocked deck: the reveal must come off P2's deck
#// (4 → 2, top SOR_111 after the reorder), the 2 [Heroism] damage must land on P1's base, the two
#// discarded reveals plus the event must be in P2's discard, and P1's deck and discard must be
#// untouched. A hardcoded seat-1 read would have milled P1's four Battlefield Marines and reported zero
#// Heroism.

## GIVEN
CommonSetup: rrw/rrw/{theirResources:3}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Hand: SOR_152
WithP2Deck: SOR_095
WithP2Deck: SOR_189
WithP2Deck: SOR_128
WithP2Deck: SOR_111
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:SOR_111,SOR_128|SOR_095,SOR_189

## EXPECT
P1BASEDMG:2
P2BASEDMG:0
P2DECKCOUNT:2
P2DECKTOPCARD:SOR_111
P2DISCARDCOUNT:3
P1DECKCOUNT:4
P1DISCARDCOUNT:0
