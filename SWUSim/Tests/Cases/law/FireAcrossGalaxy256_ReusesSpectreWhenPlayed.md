# LAW_256 Fire Across the Galaxy (Event, Heroism) — "Use any number of 'When Played' abilities on
# friendly Spectre units." P1 controls LAW_055 (1/2 Spectre, "When Played: give it an Experience token").
# Playing Fire Across the Galaxy and choosing LAW_055 re-resolves its When-Played → it gains an Experience
# token (no Cunning/Vigilance unit controlled, so 1 token) → 2/3.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: LAW_055:1:0
WithP1Hand: LAW_256

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_055
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
