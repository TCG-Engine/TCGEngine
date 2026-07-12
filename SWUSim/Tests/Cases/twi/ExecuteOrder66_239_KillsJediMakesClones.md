# TWI_239 Execute Order 66 (Event, cost 4, Villainy, Plan) — "Deal 6 damage to each Jedi unit. For each
# unit defeated this way, its controller creates a Clone Trooper token." Both TWI_048 (4/6 Jedi) — one P1,
# one P2 — die to the 6, so each controller creates a Clone Trooper (TWI_T02). The non-Jedi SOR_095 is
# untouched. Leader rk covers the Villainy pip.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4;handCardIds:TWI_239}
P1OnlyActions: true
WithP1SpaceArena: TWI_048:1:0
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: TWI_048:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_T02
