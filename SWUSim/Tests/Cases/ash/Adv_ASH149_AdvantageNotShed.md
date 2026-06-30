# ASH_149 Eviscerator — "Advantage tokens on friendly units aren't defeated after combat." Playing the
# Eviscerator gives SOR_095 2 Advantage; SOR_095 then attacks P2's base, and (unlike normal) its Advantage
# tokens are NOT shed when the attack ends, so it keeps both.
## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:ASH_149}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
