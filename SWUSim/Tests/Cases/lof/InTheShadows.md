# HiddenExp
#// LOF_241 In the Shadows — Give an Experience token to each of up to 3 friendly units with Hidden. Both
#// Hidden LOF_132 units (3/4) are chosen and become 4/5.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_241}
P1OnlyActions: true
WithP1GroundArena: LOF_132:1:0
WithP1GroundArena: LOF_132:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:5

---

# ChooseNothing_NoExperience
#// LOF_241 In the Shadows — the "up to 3" lets P1 choose nothing; no Experience tokens are given and both
#// Hidden LOF_132 units stay 3/4 with no upgrades. Intended: "should allow you to choose nothing."

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_241}
P1OnlyActions: true
WithP1GroundArena: LOF_132:1:0
WithP1GroundArena: LOF_132:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# SpaceHiddenEligible_NonHiddenExcluded
#// LOF_241 In the Shadows — eligible targets are any friendly unit WITH Hidden, in either arena; a non-Hidden
#// friendly is not eligible. Grand Inquisitor (LOF_132, innate Hidden) grants Hidden to the other friendly
#// Inquisitor Scythe (LOF_135, space). Jedi Knight (LOF_145, ground, not Inquisitor) has no Hidden. P1 gives
#// Experience to both Hidden units incl. the space one; the Jedi Knight is excluded. Intended: "should give
#// experience token to up to 3 friendly hidden unit" (a space Hidden unit is selectable, non-Hidden is not).

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_241}
P1OnlyActions: true
WithP1GroundArena: LOF_132:1:0
WithP1GroundArena: LOF_145:1:0
WithP1SpaceArena: LOF_135:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
