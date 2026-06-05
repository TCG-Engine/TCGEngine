# SOR_092 Overwhelming Barrage (Event, cost 5) — give a friendly unit +2/+2 this phase, then it
# deals damage equal to its (BUFFED) power divided among any number of OTHER units. P1's only
# friendly is a 3/3 → buffed to 5/5 → deals 5, split 3 to one enemy + 2 to another. Proves the
# buff is applied BEFORE power is read (total dealt = 5, not 3). Dealer auto-picked (only friendly).

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # 3/3 dealer → buffed to 5/5
WithP2GroundArena: SOR_046:1:0    # 3/7 — takes 3
WithP2GroundArena: SOR_095:1:0    # 3/3 — takes 2, survives

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:3
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:0
