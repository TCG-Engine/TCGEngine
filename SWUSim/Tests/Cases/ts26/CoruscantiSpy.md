# Heal2FromChosenBases
#// TS26_53 Coruscanti Spy (Unit 0/2, cost 1) — Raid 2 + When Played: heal 2 damage from each of any
#// number of bases. Choosing both bases heals 2 from each (P1 5 → 3, P2 5 → 3).
## GIVEN
CommonSetup: ggk/rrk/{myResources:1;handCardIds:TS26_53;myBaseDamage:5;theirBaseDamage:5}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0&theirBase-0
## EXPECT
P1BASEDMG:3
P2BASEDMG:3

---

# HealsJustTheChosenBase
#// TS26_53 Coruscanti Spy — "each of ANY NUMBER of bases" means the choice is a subset, not all-or-
#// nothing. Choosing only P1's base heals it 5 -> 3 and leaves P2's at 5.

## GIVEN
CommonSetup: ggk/rrk/{myResources:1;handCardIds:TS26_53;myBaseDamage:5;theirBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:3
P2BASEDMG:5

---

# ChoosingNoBaseHealsNothing
#// TS26_53 Coruscanti Spy — "any number" includes zero. Declining leaves both bases at 5.

## GIVEN
CommonSetup: ggk/rrk/{myResources:1;handCardIds:TS26_53;myBaseDamage:5;theirBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1BASEDMG:5
P2BASEDMG:5

---

# UndamagedBasesAreStillLegalChoices
#// TS26_53 Coruscanti Spy — healing a base that has no damage is a legal no-op, not an error: both bases
#// stay at 0 and the Spy is in play as normal.

## GIVEN
CommonSetup: ggk/rrk/{myResources:1;handCardIds:TS26_53}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0&theirBase-0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1GROUNDARENACOUNT:1

---

# HealsJustTheENEMYBaseWhenThatIsTheOnlyChoice
#// TS26_53 Coruscanti Spy — the subset may be the opponent's base alone. P2's base heals 5 -> 3 while
#// P1's keeps its 5. Mirror of HealsJustTheChosenBase.

## GIVEN
CommonSetup: ggk/rrk/{myResources:1;handCardIds:TS26_53;myBaseDamage:5;theirBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1BASEDMG:5
P2BASEDMG:3
