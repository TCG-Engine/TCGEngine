# TWI_217 Tri-Droid Suppressor (Unit 4/7, Ground, cost 7) — "Exploit 2. When Played: Exhaust an enemy
# ground unit." Played with no friendly units (Exploit auto-skips); the only enemy ground unit SOR_046
# (ready) is exhausted (single target → auto-resolves).

## GIVEN
CommonSetup: yyk/grw/{myResources:7;handCardIds:TWI_217}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
