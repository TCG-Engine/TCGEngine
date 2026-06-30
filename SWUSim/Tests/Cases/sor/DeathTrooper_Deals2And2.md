# SOR_033 Death Trooper (3/3) — When Played: deal 2 to a friendly ground unit AND 2
# to an enemy ground unit. P1 already has Battlefield Marine (SOR_095); chosen as the
# friendly target it takes 2. P2's Consular Security Force (SOR_046) is the only enemy
# ground unit → auto-takes 2.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3;handCardIds:SOR_033}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # friendly target — index 0
WithP2GroundArena: SOR_046:1:0    # enemy target

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:2
