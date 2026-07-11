# SHD_035 Clan Saxon Gauntlet (6-cost 4/5 space) — Sentinel + "When this unit is attacked: You may give an
# Experience token to a unit (before damage is dealt)." SOR_237 attacks the Gauntlet; the On Defense window
# (combat-pause) lets P1 give an Experience token to the Gauntlet itself (→ 5/6) before the 2 combat damage.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP1SpaceArena: SHD_035:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_035
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENACOUNT:0
