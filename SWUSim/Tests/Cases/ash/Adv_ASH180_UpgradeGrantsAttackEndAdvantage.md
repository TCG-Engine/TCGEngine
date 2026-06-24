# ASH_180 Bokken Saber (Upgrade +1/+1, non-Vehicle) — Attached unit gains: "When Attack Ends: Give an
# Advantage token to this unit." Host SOR_095 (3/3 → 4/4 with the saber) attacks P2's base; after the
# attack ends, the host gains 1 Advantage token.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_180
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
