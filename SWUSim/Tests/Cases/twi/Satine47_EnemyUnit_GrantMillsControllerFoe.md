# TWI_047 Satine Kryze — the grant applies to ENEMY units too, and each unit's controller uses it to mill
# THEIR opponent's deck. P1 controls Satine; P2 controls SOR_095 (3/3). On P2's turn, P2 activates their
# SOR_095's granted Action → discards ceil(3/2) = 2 from P1's deck (P2's opponent). Proves enemy units
# receive the grant and target the granting player's own deck.
## GIVEN
CommonSetup: bbw/rrk/{}
WithActivePlayer: 2
WithP1GroundArena: TWI_047:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P2>UseUnitAbility:myGroundArena-0
## EXPECT
P1DISCARDCOUNT:2
P1DECKCOUNT:2
P2GROUNDARENAUNIT:0:EXHAUSTED
