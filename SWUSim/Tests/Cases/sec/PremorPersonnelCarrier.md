# WhenPlayed_ExpPerGround
#// SEC_089 PreMor Personnel Carrier (Ground, 6/6) — Overwhelm + When Played: give itself an Experience
#//   token for each ground unit you control (including itself). With 1 other ground unit → 2 ground → 2 Exp → 8/8.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_089

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_089
P1SPACEARENAUNIT:0:POWER:7
P1NODECISION

---

# WhenPlayed_TwoGround_TwoExp
#// SEC_089 PreMor Personnel Carrier (Space, 6/6) — With TWO friendly ground units in play, When Played
#//   gives itself 2 Experience tokens (it is a Space unit, so it does not count itself) → 8/8.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_164:1:0
WithP1Hand: SEC_089

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_089
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:8
P1NODECISION

---

# WhenPlayed_NoGround_NoExp
#// SEC_089 — With NO ground units in play, When Played gives 0 Experience tokens → stays 6/6, unupgraded.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SEC_089

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_089
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:6
P1NODECISION
