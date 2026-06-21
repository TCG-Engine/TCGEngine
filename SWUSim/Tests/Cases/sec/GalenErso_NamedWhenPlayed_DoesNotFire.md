# SEC_046 Galen Erso — naming a card denies its When Played ability. SEC_097 Beloved Orator's "When
# Played: Create a Spy token" should not fire when P2 plays it after Galen named "Beloved Orator". So
# P2 ends with only Beloved Orator in play (no Spy token).

## GIVEN
CommonSetup: bbw/ggw
WithActivePlayer: 1
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2Resources: 6
WithP2Hand: SEC_097

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Beloved Orator
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_097
