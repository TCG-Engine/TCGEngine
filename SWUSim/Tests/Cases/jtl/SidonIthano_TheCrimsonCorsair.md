# AttachToEnemyVehicle
#// JTL_213 Sidon Ithano — "When played as a unit: You may attach this unit as an upgrade to an enemy
#// Vehicle unit without a Pilot on it." Played as a unit (no friendly Vehicle → no Pilot option), Sidon
#// attaches onto the enemy SOR_237 (2/3 X-Wing). As a Pilot he is −2/−2, so the enemy ship drops to 0/1.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:JTL_213}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_213
P2SPACEARENAUNIT:0:POWER:0
P2SPACEARENAUNIT:0:HP:1

---

# PlayedAsUnit_NoEnemyVehicle
#// JTL_213 Sidon Ithano — the "attach to an enemy Vehicle without a Pilot" is a WHEN-PLAYED "may". With no
#// eligible enemy Vehicle (the opponent has only a ground non-Vehicle unit), Sidon simply enters as a normal
#// ground unit.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:JTL_213}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_213

---

# AttachToEnemyHost_FiresHostPilotAttachReaction
#// JTL_213 Sidon Ithano attaches as a Pilot onto an ENEMY Red Leader (JTL_101), whose "When a Pilot upgrade
#// attaches to this unit: Create an X-Wing token" reaction must fire for the HOST'S controller (P2), not the
#// player who played Sidon. P2 should gain a JTL_T02 X-Wing token in its space arena.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:JTL_213}
P1OnlyActions: true
WithP2SpaceArena: JTL_101:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_101
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_213
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:1:CARDID:JTL_T02

---

# Offer_EnemyVehicleWithoutPilot
#// JTL_213 Sidon Ithano — "You may attach this unit as an upgrade to an ENEMY VEHICLE unit WITHOUT A PILOT on
#// it." Three independent filters, one excluded unit each, and the pool spans BOTH arenas:
#//   IN  theirSpaceArena-0  SOR_237 Alliance X-Wing — enemy Vehicle, no Pilot
#//   IN  theirGroundArena-1 JTL_220 Skyway Cloud Car — enemy Vehicle in the GROUND arena, no Pilot
#//   OUT theirSpaceArena-1  JTL_101 Red Leader — enemy Vehicle but already carries the Pilot JTL_046
#//   OUT theirGroundArena-0 SOR_046 Consular Security Force — enemy, but a Trooper, not a Vehicle
#//   OUT mySpaceArena-0     SOR_237 — a Vehicle without a Pilot, but FRIENDLY
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:JTL_213}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_101:1:0
WithP2SpaceArenaUpgrade: 1:JTL_046
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: JTL_220:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P2SPACEARENAUNIT:1:CARDID:JTL_101
P2SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SELECTABLEEXACT:theirSpaceArena-0&theirGroundArena-1
