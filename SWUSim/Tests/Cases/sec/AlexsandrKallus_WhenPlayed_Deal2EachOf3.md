# SEC_155 Alexsandr Kallus (Unit, cost 7) — When Played: deal 2 to each of up to 3 ground units.

## GIVEN
CommonSetup: rrw/rrk/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_155

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:2:DAMAGE:2
P1NODECISION
