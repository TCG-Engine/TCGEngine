# FewerResources_Buff
#// SEC_151 Kazuda Xiono (Ground, 2/6) — Raid 2 + "While you control fewer resources than an opponent,
#//   this unit gets +2/+0." P1 controls 1 resource vs P2's 5 → +2 → power 4 (passive only; Raid is
#//   attack-time and not reflected in the static power readout).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
WithActivePlayer: 1
WithP2Resources: 5
WithP1GroundArena: SEC_151:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4

---

# NotFewerResources_NoBuff
#// SEC_151 Kazuda Xiono — when you do NOT control fewer resources than the opponent, the +2/+0 is off.
#//   P1 controls 5 resources vs P2's 1 → power stays at base 2.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5}
WithActivePlayer: 1
WithP2Resources: 1
WithP1GroundArena: SEC_151:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
