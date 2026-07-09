# SHD_206 Spare the Target (3-cost event, Cunning/Heroism) — "Return an enemy non-leader unit to its owner's
# hand. Collect that unit's Bounties." The enemy SHD_095 (Bounty: Draw a card) is returned to P2's hand and
# P1 collects its bounty, drawing a card.

## GIVEN
CommonSetup: yyw/yyw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_206
WithP2GroundArena: SHD_095:1:0
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1HANDCOUNT:1
