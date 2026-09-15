# RoseTico_DefeatsAShieldGalenNamed
#// SEC_046 Galen Erso naming "Shield": official ruling — "Until Galen leaves play, the cards with the chosen
#// name lose all abilities." A Shield token that lost its abilities is still a TOKEN UPGRADE: it no longer
#// prevents damage, but an effect that DEFEATS a Shield still defeats it (HMW_077 Boss Nass already worked
#// this way). SHD_045 Rose Tico: "On Attack: You may defeat a Shield token on a friendly unit. If you do,
#// give 2 Experience tokens to that unit." Found by the 2026-09-11 game-log pass: Rose Tico consumed the
#// Shield through the PREVENTION path, which Galen blanks — so she could not defeat it and gave no Exp.

## GIVEN
CommonSetup: ggw/bbw
WithActivePlayer: 2
WithP2Resources: 4
WithP2Hand: SEC_046
WithP1GroundArena: SHD_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Shield
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# Mandalorian_DefeatsItsGalenNamedShieldToPrevent
#// ASH_062 The Mandalorian (Devoted Rescuer): "If damage would be dealt to another friendly unit, you may
#// defeat a Shield token on this unit. If you do, prevent that damage." The prevention is the MANDALORIAN'S
#// ability; the Shield is only its cost. Galen blanking the Shield token does not stop it being defeated.
#// (Fixture from ash/TheMandalorian_DevotedRescuer.md, AbilityPrevent_Accept, plus P2's Galen.)

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;handCardIds:SOR_172}
WithActivePlayer: 2
WithP2Resources: 4
WithP2Hand: SEC_046
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Shield
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
