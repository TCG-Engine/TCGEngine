# TWI_151 Resolute — "When Played/On Attack: Deal 2 damage to an enemy unit and each other enemy unit
# with the same name as that unit." Choosing one Munificent Frigate (JTL_069) deals 2 to it AND 2 to the
# OTHER frigate (same name); the differently-named SOR_237 is untouched.

## GIVEN
CommonSetup: rrw/bbw/{myResources:10;handCardIds:TWI_151}
P1OnlyActions: true
WithP2SpaceArena: [JTL_069:1:0 JTL_069:1:0 SOR_237:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:1:CARDID:JTL_069
P2SPACEARENAUNIT:1:DAMAGE:2
P2SPACEARENAUNIT:2:CARDID:SOR_237
P2SPACEARENAUNIT:2:DAMAGE:0
