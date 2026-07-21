# DealRemainingHP
#// LOF_128 Protect the Pod — A friendly non-Vehicle unit deals damage equal to its REMAINING HP to an enemy
#// unit. Plo Koon (8 HP, already 3 damage → 5 remaining) deals 5 to SOR_046 (3/7), which survives with 5.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:LOF_128}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# SelectsNonVehicleOnly
#// LOF_128 Protect the Pod — the acting unit must be a friendly NON-Vehicle unit, in either arena. With a
#// non-Vehicle ground unit (SOR_095), a Vehicle ground unit (SOR_232 AT-ST), and a non-Vehicle space unit
#// (LOF_071 Grappling Guardian), only the two non-Vehicles are offered — exactly the Battlefield Marine and
#// the Grappling Guardian, excluding the AT-ST Vehicle.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:LOF_128}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_232:1:0
WithP1SpaceArena: LOF_071:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# SpaceNonVehicleDealsRemainingHP
#// LOF_128 Protect the Pod — a non-Vehicle SPACE unit is eligible and deals damage equal to its REMAINING HP.
#// Grappling Guardian (LOF_071, 3/9) with 3 damage → 6 remaining deals 6 to the enemy SOR_046 (3/7), which
#// survives with 6 damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:LOF_128}
P1OnlyActions: true
WithP1SpaceArena: LOF_071:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
