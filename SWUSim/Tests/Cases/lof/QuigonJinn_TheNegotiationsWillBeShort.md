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
