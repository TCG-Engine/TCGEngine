# SHD_158 Wild Rancor (6-cost 6/8 ground) — Overwhelm + "When Played: Deal 2 damage to each OTHER ground
# unit." On play it hits every other ground unit both sides: the friendly SEC_080 (2 damage, survives), the
# enemy SOR_128 (3/1 → defeated), and the enemy SOR_046 (2 damage). The Rancor itself is unaffected.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_158
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:CARDID:SHD_158
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
