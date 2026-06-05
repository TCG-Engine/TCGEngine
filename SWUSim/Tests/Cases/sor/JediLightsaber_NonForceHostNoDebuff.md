# SOR_054 Jedi Lightsaber — the On-Attack shrink is granted ONLY to FORCE hosts.
# Host = SOR_046 (Rebel/Trooper, non-Force, 3/7) + saber → 6/10 attacker.
# Defender = SOR_119 (6/9) carrying a Shield (combat damage absorbed).
# Host is not a Force unit, so no grant fires → defender keeps its printed 6/9.

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
WithP2GroundArena: SOR_119:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_119
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:9
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
