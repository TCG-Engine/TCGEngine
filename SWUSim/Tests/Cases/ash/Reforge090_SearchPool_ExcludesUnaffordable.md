# ASH_090 Reforge — "Defeat an upgrade on a friendly unit. If you do, search the top 8 for an upgrade
# that can attach to that unit… It costs 4 resources less." Same class of bug as Kelleran Beq (LOF_100):
# the offered pool wasn't filtered by affordability (its host-target filter passed a null upgrade object,
# which skips the affordability gate). Worse, an unaffordable pick was staged into hand and then failed to
# pay — leaving the searched upgrade stuck in the player's hand. The playable set must exclude unaffordable
# upgrades.
#
# P1 has SOR_095 carrying SOR_166 (the upgrade Reforge defeats — sole host + sole upgrade → auto). P1 has 2
# resources; Reforge costs 2 (Vigilance, covered by the blue base) → 0 ready remain. Top of deck:
#   - SOR_069 Resilient — cost 1 (Vigilance, covered) → max(0, 1−4) = 0 net → affordable, MUST be offered.
#   - LOF_091 Craving Power — cost 5 (Command/Villainy, covered by Tarkin) → max(0, 5−4) = 1 net →
#     UNaffordable, must NOT be offered.
# Both can attach to the friendly SOR_095, so only affordability separates them. Decision left pending.

## GIVEN
CommonSetup: bgk/ggw/{myResources:2;handCardIds:ASH_090}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_166
WithP1Deck: SOR_069
WithP1Deck: LOF_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SEARCHPLAYABLEHAS:SOR_069
P1SEARCHPLAYABLENOT:LOF_091
