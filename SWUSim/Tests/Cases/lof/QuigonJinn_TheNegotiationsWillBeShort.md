# PutUnitOnDeck
#// LOF_200 Qui-Gon Jinn (7/5) — Ambush + When Defeated: may choose a non-leader ground unit; its owner
#// puts it on the top or bottom of their deck. Pre-damaged Qui-Gon attacks and defeats the enemy 3/1,
#// dying to the counter; on death P1 chooses the surviving enemy 3/7, whose owner (P2) puts it on top of
#// their deck.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:4
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:Top

## EXPECT
P2GROUNDARENACOUNT:0
P2DECKCOUNT:1

---

# PutUnitOnBottomOfDeck
#// LOF_200 Qui-Gon Jinn — the owner may instead put the chosen unit on the BOTTOM of their deck. Same trade
#// as the top-of-deck case (pre-damaged Qui-Gon defeats the enemy 3/1 and dies to the counter), but P2 sends
#// the surviving 3/7 to the bottom: the seeded SOR_111 stays on top and the deck grows to 2. (FT: "...put it
#// on bottom of their deck".)

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:4
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_111

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:Bottom

## EXPECT
P2GROUNDARENACOUNT:0
P2DECKCOUNT:2
P2DECKTOPCARD:SOR_111

---

# ChooseFriendlyUnit_OwnerP1Chooses
#// LOF_200 Qui-Gon Jinn — "its owner" can be P1. Pre-damaged Qui-Gon defeats an enemy 3/3 and dies to the
#// counter; on death P1 targets their OWN Battlefield Marine (SOR_095), and because P1 owns it P1 is the one
#// prompted for top/bottom. P1 puts it on top of P1's own deck. (FT: mirror of the "choose a non-leader ground
#// unit and put it on top/bottom" cases with a friendly target.)

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_200:1:4
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: SOR_111

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Top

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095
