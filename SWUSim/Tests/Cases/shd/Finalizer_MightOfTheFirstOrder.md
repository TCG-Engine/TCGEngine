# MultiCapture
#// SHD_092 Finalizer (Unit, cost 11, Command/Villainy, Space) — "When Played: Choose any number of friendly
#// units. Each of those units captures an enemy non-leader unit in the same arena." P1 has two ground units
#// (SOR_046, SOR_095); playing Finalizer, P1 chooses both, and each captures one of P2's two ground enemies
#// (SOR_128, SOR_160) — clearing P2's ground arena. (Finalizer itself is in space with no space enemy, so
#// it isn't offered as a captor.)

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1Resources: 15
WithP1Hand: SHD_092
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_160:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
