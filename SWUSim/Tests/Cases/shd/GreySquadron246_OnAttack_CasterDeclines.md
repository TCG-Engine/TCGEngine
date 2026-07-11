# SHD_246 Grey Squadron Y-Wing — the damage is a "may": after the opponent chooses its unit, P1 declines
# (NO), so no damage is dealt to it (only the 1 base combat damage from the attack itself).

## GIVEN
CommonSetup: rrw/rrw
WithActivePlayer: 1
WithP1SpaceArena: SHD_246:1:0
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:NO

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_046
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:1
