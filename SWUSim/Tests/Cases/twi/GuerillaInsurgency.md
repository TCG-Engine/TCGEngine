# SymmetricAttritionAndWipe
#// TWI_177 Guerilla Insurgency (Event, cost 8, Aggression, Tactic) — "Each player defeats a resource they
#// control and discards 2 cards from their hand. Deal 4 damage to each ground unit." Both players lose a
#// resource and their whole (2-card) hands; every ground unit takes 4 (SOR_046 3/7 survives at 4, SOR_128
#// 3/1 dies). Hands are seeded to exactly 2 so the discards auto-resolve.

## GIVEN
CommonSetup: rrk/bbw/{myResources:9;theirResources:3}
P1OnlyActions: true
WithP1Hand: [TWI_177 SOR_095 SOR_128]
WithP2Hand: [SOR_095 SOR_128]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1RESCOUNT:8
P2RESCOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:0
