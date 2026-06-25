# JTL_100 Poe Dameron — played as a PILOT: no X-Wing token, no pending decision.
#
# This guards the no-op WhenPlayedAsUpgrade suppressor:
# HasWhenPlayedAsUpgradeAbility(JTL_100)=true fires the no-op stub, which prevents
# the WhenPlayedAsUpgrade->WhenPlayed fallback from ever running the token logic.
#
# JTL_100: unit cost 4, piloting cost 2, aspects Command+Heroism.
# Leader SOR_009 Leia (Command+Heroism) + Base SOR_024 (Command) → 0 aspect penalty.
# With exactly 2 resources: canUnit=false (2 < 4), canPilot=true (2 >= 2, SOR_237 present).
# → Pilot-only short-circuit: auto-attaches to the only Vehicle (SOR_237) immediately.
#
# WhenPlayedAsUpgrade fires: no-op stub → returns without action.
# WhenPlayed does NOT fire (JTL_100 entered as Upgrade, not Unit).
#
# Final state:
#   Space arena count: 1 (SOR_237 only — no X-Wing token spawned).
#   SOR_237 upgradeCount: 1 (JTL_100 as Pilot, IsPilot=true).
#   SOR_237 power: 2 (base) + 2 (JTL_100 upgradePower) = 4.
#   SOR_237 hp:    3 (base) + 3 (JTL_100 upgradeHp)    = 6.
#   No pending decision (P1NODECISION).
#   JTL_100 is an upgrade on SOR_237, NOT a unit in any arena.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_100
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:6
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1NODECISION
