# TWI_247 AT-TE Vanguard (Unit 6/9, Ground, cost 8, Heroism) — "Restore 3. When Defeated: Create 2
# Clone Trooper tokens." (Restore is a generic keyword.) AT-TE (pre-damaged to 7, so 2 remaining HP)
# attacks SOR_046 (3/7); the 3-power counter kills it → When Defeated creates 2 Clone Troopers.
# AT-TE deals 6 to SOR_046 (survives). Driven as attacker self-defeat so it resolves inline.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_247:1:7
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:6
