# ASH_204 Blade Three (Space, 2/4) — When your base is dealt damage: give an Advantage token to this
# unit. P2 attacks P1's base with SEC_080 (3 power); P1's base takes 3, so P1's ASH_204 gains 1
# Advantage token (the reaction fires inline during the damage event, even on the opponent's turn).
## GIVEN
CommonSetup: yyw/grk
WithP1SpaceArena: ASH_204:1:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1SPACEARENAUNIT:0:CARDID:ASH_204
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
