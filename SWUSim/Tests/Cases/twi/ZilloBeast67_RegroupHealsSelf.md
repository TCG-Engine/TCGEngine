# TWI_067 The Zillo Beast — "When the regroup phase starts: Heal 5 damage from this unit." Zillo with 5
# damage heals to 0 at regroup. (Deck seeded so the regroup draw deals no deck-out damage.)

## GIVEN
CommonSetup: bbw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_067:1:5
WithP1Deck: [SOR_095 SOR_046 SOR_128]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
