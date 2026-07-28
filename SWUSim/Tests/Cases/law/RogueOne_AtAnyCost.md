# FriendlyDefeatedScry
#// LAW_119 Rogue One (3/3, space) — When a friendly unit is defeated: look at the top 2 cards; put any
#// number on the bottom, rest on top. SOR_128 attacks SOR_046 and dies; put the top SOR_237 on the
#// bottom -> new top is SOR_095.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_119:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myDeck-0

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2

---

# FriendlyDefeated_DeckSizeOne_KeepOnTop
#// LAW_119 Rogue One — with only 1 card in the deck, the "look at top 2" ability shows just the single
#// card. P1 keeps it on top (chooses none to put on bottom). SOR_128 attacks SOR_046 and dies, triggering
#// Rogue One; the lone deck card SOR_237 stays on top and the deck count remains 1.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_119:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:DONE

## EXPECT
P1DECKTOPCARD:SOR_237
P1DECKCOUNT:1

---

# FriendlyLeaderUnitDefeated_Triggers
#// LAW_119 Rogue One — a friendly LEADER unit dying also counts as a friendly unit defeated. Deployed
#// Chewbacca (5/6) is seated with 5 damage (1 HP left); with no resources its optional On-Attack defeat
#// auto-skips. It attacks SOR_046 (3/7) and takes 3 combat back (8 >= 6), dying. Rogue One triggers: put
#// the top SOR_237 on the bottom, so the new top is SOR_095.

## GIVEN
CommonSetup: yrw/bgw/{
  myLeader:LAW_013:1:1:0:5;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1SpaceArena: LAW_119:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myDeck-0

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2
P1LEADER:NOTDEPLOYED
