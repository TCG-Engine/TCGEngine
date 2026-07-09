# SHD_039 Calculated Lethality (4-cost event) — "Defeat a non-leader unit that costs 3 or less. For each
# upgrade that was on that unit, give an Experience token to a friendly unit." The enemy SEC_080 (cost 3)
# carries 2 upgrades; it's defeated and P1's only friendly unit SOR_046 receives 2 Experience tokens (→ 5/9).

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_039
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
