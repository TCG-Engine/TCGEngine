# Uniqueness rule for UPGRADES (CR 8.19.1.b / 29.3): a player may control only one copy of a unique
# upgrade at a time. Unlike the unit case (which prompts the player to choose a copy to defeat), a
# newly-played unique upgrade AUTO-defeats the player's pre-existing copy of the same card — no prompt —
# keeping the just-played one.
#
# SOR_053 Luke's Lightsaber (unique, +3/+1, cost 2, Vigilance/Heroism, attach to non-Vehicle). Its
# When-Played only does something if the attached unit IS Luke Skywalker, so attaching to a plain
# Battlefield Marine (SOR_095, non-Vehicle, non-unique) is a clean no-op — isolating the uniqueness rule.
#
# P1 controls two Battlefield Marines; unit 0 already carries a Luke's Lightsaber. P1 plays a SECOND
# Luke's Lightsaber and attaches it to unit 1. The older copy on unit 0 is immediately defeated to the
# discard; the freshly-played copy on unit 1 remains. No decision is presented.
#
# Base blue (b = Vigilance) + Sabine (rw = Aggression/Heroism) together cover SOR_053's Vigilance+Heroism
# → no aspect penalty; cost 2 = 2 resources. (Deliberately NOT Luke's leader, to avoid any Luke interaction.)

## GIVEN
CommonSetup: brw/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_053
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_053

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:1

## EXPECT
# Both host units survive; only the duplicate upgrade was defeated.
P1GROUNDARENACOUNT:2
# Older copy on unit 0 auto-defeated — unit 0 now has no upgrades.
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
# Freshly-played copy stays attached to unit 1.
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_053
# The defeated copy is in the discard, and nothing was asked of the player.
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_053
P1NODECISION
