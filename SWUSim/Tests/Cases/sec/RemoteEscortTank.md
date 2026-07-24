# WhenPlayed_GiveSentinel
#// SEC_255 Remote Escort Tank (Ground, 5/5, cost 6) — When Played: give a unit Sentinel for this phase.
#//   P1 plays it and grants Sentinel to SEC_041.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_255

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenPlayed_TargetsAnyUnit
#// SEC_255 Remote Escort Tank — "Give a unit Sentinel for this phase" can target ANY unit. On being
#//   played, the selectable set is exactly SEC_255 itself, friendly SEC_041, the enemy ground
#//   Battlefield Marine, and the enemy space Cartel Spacer — no unit is excluded.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_095:1
WithP2SpaceArena: SOR_178:1
WithP1Hand: SEC_255

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirSpaceArena-0

---

# WhenPlayed_GrantSentinelToEnemy
#// SEC_255 Remote Escort Tank — the grant may land on an ENEMY unit (a legal, if unusual, choice).
#//   P1 gives Sentinel to the enemy Battlefield Marine.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_095:1
WithP2SpaceArena: SOR_178:1
WithP1Hand: SEC_255

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
