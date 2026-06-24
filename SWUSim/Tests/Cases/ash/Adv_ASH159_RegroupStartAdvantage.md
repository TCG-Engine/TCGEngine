# ASH_159 Alphabet Squadron U-Wing (Space, 5/6, Overwhelm, cost 5) — When the regroup phase starts: give an
# Advantage token to a unit. With only the U-Wing in play, the choice auto-resolves onto itself, so passing
# to the regroup phase grants it 1 Advantage token.
## GIVEN
CommonSetup: rrw/rrk
WithP1SpaceArena: ASH_159:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_159
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
