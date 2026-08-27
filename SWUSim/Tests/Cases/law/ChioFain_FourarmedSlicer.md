# OnAttackBothDraw
#// LAW_048 Chio Fain (2/4) — On Attack: you may choose 2 players. If you do, they each draw a card.
#// (2-player: both players draw.)
#// COVERAGE: offer=N/A (YES/NO "you may" prompt, not a target-choice; in a 2-player game "choose 2
#//           players" has exactly one selection) · decline=OnAttack_DeclineNoDraws · boundary=deck
#//           non-empty vs empty pair: OnAttackBothDraw + OnAttack_EmptyOpponentDeck_ThreeDamageToTheirBase
#//           (failed draw = 3 to that player's own base) · control=N/A (players, not units, are chosen)
#//           · reqboundary=N/A (single YES/NO decision; draws resolve in the same window)

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_048:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1

---

# OnAttack_DeclineNoDraws
#// Intended: the draw is "you may" — declining leaves both hands untouched; the attack itself still
#// deals Chio's 2 to the base.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_048:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1DECKCOUNT:1
P2DECKCOUNT:1
P2BASEDMG:2

---

# OnAttack_EmptyOpponentDeck_ThreeDamageToTheirBase
#// Intended: with P2's deck empty, accepting the draw still draws P1's card, while P2 cannot draw and
#// instead takes 3 damage to their base (CR: a player who cannot draw deals 3 damage to their own base
#// per undrawn card). P2 base ends at 5: 2 from Chio's attack + 3 from the failed draw.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_048:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_237
P2HANDCOUNT:0
P2BASEDMG:5

---

# EmptyOWNDeck_TheCasterTakesTheEmptyDeckDamage
#// LAW_048 Chio Fain — "they EACH draw a card" applies to both chosen players symmetrically, so an empty
#// deck punishes whoever owns it. The mirror of
#// OnAttack_EmptyOpponentDeck_ThreeDamageToTheirBase: here it is P1's deck that is empty and P2's that has
#// a card, so P1 draws nothing and takes the CR 6.1 empty-deck damage on its OWN base while P2 draws
#// normally. Without this half the empty-deck path is only ever exercised against the opponent.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_048:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:1
P1BASEDMG:3

---

# TwinSuns_ChooseTwoPLAYERS_TeammateIsLegal
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — "You may choose 2 PLAYERS. If you do, they each draw a card."
#// At two seats that is forced (both) and the old inline pair was right; above two it is a real pick of
#// 2 out of N, and it drew for the caster + OtherPlayer() regardless of what the player wanted.
#//
#// ⚠ The pool is every LIVE SEAT, not OpponentsOf() — the text says PLAYERS, so the caster's own TEAMMATE
#// is a legal pick. This section proves exactly that: P1 picks SEAT 3 (its teammate) and SEAT 4, so P3 and
#// P4 draw while P1 and P2 draw NOTHING. An opponent-scoped picker could not even offer P3.
## GIVEN
CommonSetup: brk/bgw
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: LAW_048:1:0
WithP1Deck: SOR_237
WithP2Deck: SOR_095
WithP3Deck: SOR_095
WithP4Deck: SOR_095
## WHEN
- P1>AttackGroundArena:0:P2B
- P1>AnswerDecision:YES
- P1>AnswerDecision:P3
- P1>AnswerDecision:P4
## EXPECT
SEATCOUNT:4
P3HANDCOUNT:1
P4HANDCOUNT:1
P1HANDCOUNT:0
P2HANDCOUNT:0
