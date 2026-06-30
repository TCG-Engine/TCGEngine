# SOR_142 Sabine Wren — "While there are at least 3 aspects among other friendly units, this unit
# can't be attacked." Sabine is alone in the ground arena; 3 friendly space units (Heroism + Villainy
# + Vigilance = 3 aspects) protect her. P2's ground attacker has no legal unit target → its attack
# auto-redirects to P1's base; Sabine is untouched.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 2
WithP1GroundArena: SOR_142:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_142
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
