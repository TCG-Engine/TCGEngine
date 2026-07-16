# NextUnitAmbush
#// LOF_180 Deceptive Shade (2/3) — When Defeated: the next unit you play this phase gains Ambush for this
#// phase. Deceptive Shade trades with the enemy 3/1 (both defeated), then the next unit P1 plays (SOR_095)
#// gains Ambush. (No enemy remains, so Ambush has no attack target — the keyword is just present.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: LOF_180:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
