# CaptureCheaper_CreatesDroids
#// TWI_227 Prisoner of War (Event, cost 4, Cunning) — "A friendly unit captures an enemy non-leader,
#// non-Vehicle unit. If the enemy unit costs less than the friendly unit, create 2 Battle Droid tokens."
#// Captor = TWI_097 Captain Rex (cost 6); target = SEC_080 (cost 3, non-Vehicle Imperial). 3 < 6 →
#// SEC_080 is captured (facedown under Rex) AND 2 Battle Droids are created. Single captor + single
#// valid target → both auto-resolve.

## GIVEN
CommonSetup: yyk/grw/{myResources:4;handCardIds:TWI_227}
P1OnlyActions: true
WithP1GroundArena: TWI_097:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_097
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:2:CARDID:TWI_T01

---

# CaptureNotCheaper_NoDroids
#// TWI_227 Prisoner of War — captor SEC_080 (cost 3) captures an enemy SEC_080 (cost 3). 3 is NOT
#// less than 3 → the capture still happens but NO Battle Droids are created (the cost gate fails).

## GIVEN
CommonSetup: yyk/grw/{myResources:4;handCardIds:TWI_227}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# OnlyVehicleTarget_Fizzles
#// TWI_227 Prisoner of War — the only enemy unit in the captor's arena is a Vehicle (SOR_225 TIE),
#// which the "non-Vehicle" filter excludes. No valid target → the event fizzles cleanly (nothing
#// captured, no droids). Captor JTL_069 and target TIE are both in the space arena.

## GIVEN
CommonSetup: yyk/grw/{myResources:4;handCardIds:TWI_227}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION
