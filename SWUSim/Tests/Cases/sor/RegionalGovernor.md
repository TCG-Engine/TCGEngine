# BlockEndsWhenDefeated
#// SOR_062 Regional Governor — "While THIS UNIT is in play …". The block ends when Governor leaves
#// play. P1 plays Governor and names "Battlefield Marine". P2 attacks Governor (1/4) with SOR_210
#// (4/3) and defeats it. P1 passes. Now P2 can play their Battlefield Marine (SOR_095) — the block is
#// gone because Governor is no longer in play.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:2}
WithP1Hand: SOR_062
WithP2Hand: SOR_095
WithP2GroundArena: SOR_210:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>AttackGroundArena:0:0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2HANDCOUNT:0

---

# BlocksNamedCard
#// SOR_062 Regional Governor (Unit 1/4, cost 2, Vigilance) — "When Played: Name a card. While this
#// unit is in play, opponents can't play the named card." P1 plays Governor and names "Battlefield
#// Marine". On P2's turn, P2 tries to play their Battlefield Marine (SOR_095) — it is BLOCKED: the
#// card stays in hand, no resources spent.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:2}
WithP1Hand: SOR_062
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2RESAVAILABLE:2

---

# BlocksTopDeckPlay
#// SOR_062 Regional Governor — the "can't play the named card" block also covers cards-played-by-
#// effects (not just from hand). P1 plays Governor and names "Battlefield Marine". On P2's turn, P2
#// plays U-Wing Reinforcement (SOR_104), which searches the top 10 and plays up to 3 units for free.
#// P2's deck top has two Battlefield Marines (SOR_095) — both are BLOCKED, so neither enters play;
#// they go back to the deck. (The U-Wing event still resolves and goes to P2's discard.)

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:7}
WithP1Hand: SOR_062
WithP2Hand: SOR_104
WithP2Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171
WithP2Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0
- P2>AnswerDecision:SOR_095,SOR_095

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2DECKCOUNT:10
P2DISCARDCOUNT:1

---

# NonNamedCardAllowed
#// SOR_062 Regional Governor — the block is name-specific. P1 names "Death Star Stormtrooper"
#// (SOR_128, which P2 doesn't have). On P2's turn, P2 plays a DIFFERENT card — Battlefield Marine
#// (SOR_095) — which is NOT the named card, so it plays normally.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2;theirResources:2}
WithP1Hand: SOR_062
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Death Star Stormtrooper
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2HANDCOUNT:0
P2RESAVAILABLE:0
