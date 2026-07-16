# AmbushAttack_DefenderDebuff
#// SHD_219 Enfys Nest (6-cost 5/4 ground) — Ambush + "While a friendly unit (including this one) is attacking
#// using Ambush, the defender gets -3/-0." Playing Enfys uses its Ambush to attack SOR_046; because it is
#// attacking using Ambush and controls Enfys Nest, the defender's counter-power drops by 3 (3 → 0), so Enfys
#// takes no damage.

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_219
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_219
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
