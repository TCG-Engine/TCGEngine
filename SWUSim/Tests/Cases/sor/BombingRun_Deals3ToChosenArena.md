# SOR_173 Bombing Run (Event, cost 5) — "Choose an arena. Deal 3 damage to each
# unit in that arena." P1 chooses Ground (YES): both ground units (friendly + enemy
# Consular Security Force, 3/7) take 3 and survive at 3 damage. The friendly Space
# unit (Restored ARC-170, 2/3) is in the other arena → untouched (0 damage).

## GIVEN
CommonSetup: rrk/rrk/{myResources:5;handCardIds:SOR_173}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # friendly ground (3/7)
WithP2GroundArena: SOR_046:1:0    # enemy ground (3/7)
WithP1SpaceArena: SOR_044:1:0     # friendly space (2/3) — different arena

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENACOUNT:1
