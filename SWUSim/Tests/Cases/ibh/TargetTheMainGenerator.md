# DealsTwoToABase
#// IBH_059 Target the Main Generator (Event, cost 2, Aggression) — Deal 2 damage to a base. Player
#//   chooses the enemy base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_059

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1NODECISION

---

# Reprint071
#// IBH_071 Target the Main Generator (reprint of IBH_059) — deal 2 to a base. Confirms the duplicate.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_071

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1NODECISION

---

# CanDealTwoToYourOWNBase_BothBasesAreOffered
#// IBH_059 Target the Main Generator — "deal 2 damage to a base" carries no "enemy" qualifier, so EITHER
#// base is a legal target. The offer is asserted directly (both bases selectable); the companion section
#// below takes the friendly branch.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_059

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myBase-0&theirBase-0

---

# DealsTwoToTheFRIENDLYBase
#// IBH_059 Target the Main Generator — choosing your OWN base damages it for 2 and leaves the enemy base
#// untouched. The enemy staying at 0 is what discriminates: an "enemy base only" implementation would
#// have ignored the choice and hit the opponent.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: IBH_059

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P2BASEDMG:0
P1NODECISION
