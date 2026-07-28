# TakeControlAndDebuff
#// ASH_200 Rehabilitation (Event, cost 5) — Choose a non-leader unit; give it -3/-0 for this phase, then
#// take control of it (until regroup). P1 takes control of the enemy SEC_135 (4/3 → 1 power), moving it into
#// P1's ground arena.
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:ASH_200}
WithP2GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:1

---

# ReturnsToOwnerAtRegroup
#// ASH_200 Rehabilitation — the control-take is temporary: "At the start of the regroup phase, its owner
#// takes control of it." P1 takes SEC_135 (4/3), then passes to the regroup phase; SEC_135 returns to P2's
#// arena and the -3/-0 (which lasted only the phase) is gone, so its power is back to 4.
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:ASH_200}
WithP2GroundArena: SEC_135:1:0
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_135
P2GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENACOUNT:0

---

# TakeTokenUnit_ReturnsToOwnerAtRegroup
#// ASH_200 Rehabilitation — the chosen non-leader unit may be a token. P1 takes control of P2's Clone Trooper
#// token (TWI_T02, 2/2), giving it -3/-0 (power to 0) and moving it into P1's arena. A token does not cease
#// when its control changes, so at the regroup phase its owner P2 reclaims it, restored to power 2.
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:ASH_200}
WithP2GroundArena: TWI_T02:1:0
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENACOUNT:0

---

# OpponentTakesItBack_StaysWithOwner
#// ASH_200 Rehabilitation — if the owner reclaims the unit before regroup, Rehabilitation's own regroup
#// return has nothing to do. P1 takes SOR_095, then P2 immediately plays SOR_224 Change of Heart to take it
#// back. At regroup it simply stays with its owner P2 (power restored to 3).
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:ASH_200;theirResources:8;theirhandCardIds:SOR_224}
WithP2GroundArena: SOR_095:1:0
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENACOUNT:0
