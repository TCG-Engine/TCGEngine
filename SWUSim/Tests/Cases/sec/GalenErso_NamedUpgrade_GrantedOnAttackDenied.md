# SEC_046 Galen Erso — naming an UPGRADE denies the On Attack ability it grants its host. SOR_137 Fallen
# Lightsaber grants "On Attack: if the attached unit is a Force unit, deal 1 to each enemy ground unit".
# P2's Force unit (LOF_231) wears it. P1 names "Fallen Lightsaber"; when LOF_231 attacks, the granted On
# Attack does NOT fire — P1's ground unit (SOR_046) takes no damage.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_231:1:0
WithP2GroundArenaUpgrade: 0:SOR_137

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Fallen Lightsaber
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:7
