# Guard: the upgrade uniqueness auto-defeat fires ONLY for unique upgrades. SOR_069 Resilient is a
# NON-unique upgrade (+0/3, cost 1, Vigilance, no abilities). P1 controls two Battlefield Marines; unit
# 0 already carries a Resilient. P1 plays a SECOND Resilient onto unit 1 — both copies coexist, nothing
# is defeated, no prompt. Guards _SWUEnforceUpgradeUniqueness against over-firing on non-unique upgrades.
#
# Base blue (b = Vigilance) covers SOR_069's lone Vigilance aspect → no penalty; cost 1 = 1 resource.

## GIVEN
CommonSetup: bbk/grw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_069
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_069
P1DISCARDCOUNT:0
P1NODECISION
