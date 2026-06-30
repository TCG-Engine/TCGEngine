# ASH_006 Sabine Wren — Leader Action [Exhaust]: an opponent gives 2 Advantage tokens to a unit they
# control; if they do, the next unit you play this phase gains Shielded. P2's SOR_046 (its only unit,
# auto-chosen) gets 2 Advantage; then P1 plays SOR_095, which enters with a Shield token.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED
