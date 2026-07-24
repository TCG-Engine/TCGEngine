# WhenPlayed_CreateSpy
#// SEC_175 Ambition's Reward (Upgrade, cost 2, Aggression) — When Played: create a Spy token.
#// Attach to the friendly SOR_095 → its When Played creates a Spy.

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

---

# WhenPlayed_AttachToEnemy_StillCreatesSpy
#// SEC_175 Ambition's Reward — printed "attach to a unit" has no friendly restriction, so it can be
#//   attached to an ENEMY unit (granting it +1/+1). The When Played still creates a Spy for the
#//   controller. With only the enemy unit in play, the attach auto-resolves onto it.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_175

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENACOUNT:1
P1NODECISION
