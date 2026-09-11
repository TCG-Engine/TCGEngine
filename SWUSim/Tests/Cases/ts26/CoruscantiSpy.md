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

---

# TwinSuns_AnyNumberOfBasesOffersEveryBase
#// "Any number of bases" is UNQUALIFIED — every seat's base, the teammate's included. The offer was the
#// hand-built literal "myBase-0&theirBase-0", so at four seats (teams 1+3 vs 2+4) the teammate's and
#// seat 4's bases were never offered, and the pick was capped at 2. Left pending to read the pool.
#// (Found 2026-09-12 — the sweep's scan looked for a quoted 'theirBase-0', not an &-joined literal.)
## GIVEN
CommonSetup: ggk/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 1
WithP1Hand: TS26_53
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:myBase-0&p2Base-0&p3Base-0&p4Base-0

---

# TwinSuns_HealsAFarSeatsBaseAndMoreThanTwo
#// The applier half: three bases chosen at once (the old cap was 2), one of them seat 4's.
## GIVEN
CommonSetup: ggk/rrk/{myBaseDamage:5;theirBaseDamage:5}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:5
WithP4Base: SOR_019:5
WithP1Resources: 1
WithP1Hand: TS26_53
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0&p3Base-0&p4Base-0
## EXPECT
SEATCOUNT:4
P1BASEDMG:3
P3BASEDMG:3
P4BASEDMG:3
P2BASEDMG:5
