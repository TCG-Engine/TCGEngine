# TransportHost_CreateSpy
#// SEC_227 Special Modifications (Upgrade, cost 2) — Attach to a Vehicle unit. When Played: if the
#//   attached unit is a Transport, you may create a Spy token. Host JTL_069 (Capital Ship) is a Vehicle
#//   but NOT a Transport... so use a Transport host. SOR_237 Alliance X-Wing is a Fighter (not Transport);
#//   instead use a Transport vehicle. Here the host is a Transport → may create a Spy.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SEC_083:1:0
WithP1Hand: SEC_227

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION

---

# NonTransportVehicle_NoSpy
#// SEC_227 Special Modifications — attaches to a Vehicle, but the "create a Spy" clause only fires if the
#//   attached unit is a Transport. Host SOR_232 AT-ST is a Vehicle but NOT a Transport → no Spy token,
#//   no prompt. The upgrade still attaches.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1Hand: SEC_227

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENACOUNT:1
P1NODECISION

---

# AttachRestriction_NonVehicleUnitsAreNotOfferedAsHosts
#// SEC_227 Special Modifications — "Attach to a Vehicle unit" is an attach RESTRICTION, so a non-Vehicle
#// friendly (SOR_095 Battlefield Marine) must never be offered as a host. Two Vehicles are in play so a
#// real choice is presented: the offer is exactly those two, with the Marine absent.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SEC_083:1:0
WithP1GroundArena: LAW_158:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_227

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0
