# SEC_175 Ambition's Reward (Upgrade, cost 2, Aggression) — When Played: create a Spy token.
# Attach to the friendly SOR_095 → its When Played creates a Spy.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_175

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
