# SHD_220 Fennec Shand (7-cost 4/6 ground) — Ambush + "On Attack: Deal 1 damage to the defender (if it's a
# unit) for each DIFFERENT cost among cards in your discard pile." Discard holds SOR_095 (cost 2), SHD_038
# (cost 2), SHD_178 (cost 1) → 2 DISTINCT costs (not 3 cards), so the On Attack deals 2 to SOR_046; combined
# with Fennec's 4 combat power, SOR_046 (7 HP) takes 6.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_220:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Discard: [SOR_095 SHD_038 SHD_178]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6
