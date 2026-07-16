# NoResourcesPaid_GetsExperience
#// LAW_231 Weequay Pirate (Ground Unit 3/2, cost 2, Cunning/Underworld) —
#// "When Played: If no resources were paid to play this unit, give an Experience token to it."
#// 0-resources-paid mechanism: SOR_235 Galactic Ambition calls ActivateCard($player, $mzID, true)
#// (ignoreCost=true), which stamps SWU_PAID_0 on the entering unit (Task 3.1 logic).
#// SWUUnitResourcesPaid returns 0 == 0, so LAW_231 gets 1 Experience token (+1/+1 → 4/3).
#// P1's base also takes 2 damage (Galactic Ambition: deal cost of played unit to your base).
#// LAW_231 is the only non-Heroism unit in hand → auto-selected by SWUQueueChooseTarget.

## GIVEN
CommonSetup: yyk/grw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_235
WithP1Hand: LAW_231

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_231
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1BASEDMG:2
P1RESAVAILABLE:0

---

# PaidResources_NoExperience
#// LAW_231 Weequay Pirate (Ground Unit 3/2, cost 2, Cunning/Underworld) —
#// "When Played: If no resources were paid to play this unit, give an Experience token to it."
#// Guard: P1 plays LAW_231 from hand paying its full cost of 2 resources → SWU_PAID_2 is stamped.
#// SWUUnitResourcesPaid returns 2 ≠ 0, so NO Experience token is granted.
#// Weequay Pirate enters as a bare 3/2 unit with no subcards.

## GIVEN
CommonSetup: yyk/grw/{myResources:2;handCardIds:LAW_231}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_231
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:0
