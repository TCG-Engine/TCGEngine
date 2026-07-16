# DiscardTwo_ReorderRest
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
