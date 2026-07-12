# TWI_036 Devastating Gunship (Unit 3/5, Space, cost 5, Vigilance/Villainy, Separatist/Droid/Vehicle/
# Transport) — Grit + "When Played: Defeat an enemy unit with 2 or less remaining HP." The only enemy
# with ≤2 remaining HP is SOR_225 (2/1); the 7-HP SOR_046 doesn't qualify, so SOR_225 auto-defeats.

## GIVEN
CommonSetup: bbk/rrw/{myResources:5;handCardIds:TWI_036}
P1OnlyActions: true
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
