# DiscardTwo_ReorderRest
#// COVERAGE: offer=the reveal keep/discard split prompt is exercised with real picks in
#//           DiscardTwo_ReorderRest + FourHeroism_FourDamage (keep-order + discard destinations both
#//           asserted); the target of the damage is always "an enemy base" (1v1: auto — no offer)
#//           · reqboundary=N/A (single-resolver event; reveal, damage and split resolve in one
#//           uninterrupted resolution — no decision is answered after reading pre-decision state)
#//           · control=N/A (nothing changes control; damage keys on "enemy base", asserted throughout)
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
