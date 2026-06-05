# SOR_054 Jedi Lightsaber — when attached to a FORCE unit it grants:
#   "On Attack: Give the defender −2/−2 for this phase."
# Host = Mace Windu (SOR_149, Force, 5/7) + saber → 8/10 attacker.
# Defender = SOR_119 (6/9) carrying a Shield so the 8 combat damage is fully
# absorbed (shield), letting us read its post-attack stats cleanly:
#   On-Attack shrink −2/−2 → power 6−2=4, HP 9−2=7. (Shrink is not damage, so the
#   defender survives at 7 HP with 0 damage; the shield only stopped the combat hit.)

## GIVEN
P1LeaderBase: SOR_014/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
WithP1GroundArena: SOR_149:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
WithP2GroundArena: SOR_119:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_119
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
