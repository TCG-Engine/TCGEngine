# TWI_235 Battle Droid Legion (Unit 6/5, Ground, cost 9, Villainy) — "Exploit 2. When Defeated: Create
# 3 Battle Droid tokens." (Exploit is a generic keyword.) TWI_235 (pre-damaged to 3, so 2 remaining HP)
# attacks SOR_046 (3/7); the 3-power counter kills TWI_235 → its When Defeated creates 3 Battle Droids.
# TWI_235 deals 6 to SOR_046 (survives at 6 damage). Driven as attacker self-defeat so it resolves inline.

## GIVEN
CommonSetup: gyk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_235:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:6
