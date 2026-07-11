# SHD_202 Qi'ra (Unit, When Played) — "Look at an opponent's hand, then name a card. While this unit is in
# play, each card with that name costs 3 resources more for your opponents to play." P1 plays Qi'ra and
# names P2's SOR_063 (mono-Vigilance, cost 3). On P2's turn, SOR_063 now costs 3 + 3 = 6, so P2 (with
# exactly 6 resources) spends all of them to play it (only 3 without the surcharge).

## GIVEN
CommonSetup: yyk/bbk
WithActivePlayer: 1
WithP1Resources: 8
WithP1Hand: SHD_202
WithP2Resources: 6
WithP2Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2RESAVAILABLE:0
