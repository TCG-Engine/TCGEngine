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

---

# PlayedViaPlot_StillGivesSentinel
#// SEC_255 Remote Escort Tank carries Plot, so it can be played out of the RESOURCE row when a leader
#// deploys, and its When Played resolves exactly as from hand.
#// P1 deploys its leader and plays the tank from resources for its cost of 6. The Sentinel offer covers
#// every unit on the board — the freshly deployed leader (idx 0), the tank itself (idx 1) and the enemy
#// unit — and P1 grants it to the tank.
#// The resource row stays at 8: the played card is replaced from the top of the deck (deck 2 -> 1), and
#// 6 of the 8 resources are exhausted, leaving 2 ready.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_255:1,7:SOR_046:1
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1RESCOUNT:8
P1RESAVAILABLE:2
P1DECKCOUNT:1
P1NODECISION
