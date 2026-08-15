# DealSurviveHeal
#// LAW_102 Choke on Aspirations (Vigilance,Villainy event, cost 1) — "Deal up to 5 damage to a friendly
#// non-Vehicle unit. If it survives, heal damage from your base equal to the damage dealt this way."
#// Deal 5 to LAW_124 (4/7, survives) -> heal 5 from base (was at 5 -> 0).

## GIVEN
CommonSetup: brk/rrk/{myResources:1;myBaseDamage:5}
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:5
P1BASEDMG:0
P1DISCARDCOUNT:1

---

# DiesNoHeal
#// LAW_102 Choke on Aspirations — if the unit does NOT survive, no heal. Deal 5 to SEC_080 (3/3, dies);
#// base stays damaged.

## GIVEN
CommonSetup: brk/rrk/{myResources:1;myBaseDamage:5}
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:5
P1DISCARDCOUNT:2

---

# DealZeroNoHeal
#// LAW_102 Choke on Aspirations — "up to 5" allows 0. Deal 0 to LAW_124: it takes no damage and the base
#// does not heal.

## GIVEN
CommonSetup: brk/rrk/{myResources:1;myBaseDamage:5}
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:5
P1DISCARDCOUNT:1

---

# DealThreeHealThree
#// LAW_102 Choke on Aspirations — the base heals only as much damage as was dealt. Deal 3 to LAW_124 (4/7,
#// survives) -> base heals 3 (5 -> 2).

## GIVEN
CommonSetup: brk/rrk/{myResources:1;myBaseDamage:5}
WithP1GroundArena: LAW_124:1:0
WithP1Hand: LAW_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:3

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:2
P1DISCARDCOUNT:1

---

# ShieldPreventsNoHeal
#// LAW_102 Choke on Aspirations — a Shield prevents the damage, so 0 damage is dealt and the base does not
#// heal. Choose 5 against a shielded LAW_124: the Shield pops, the unit stays at 0 damage, base stays 5.

## GIVEN
CommonSetup: brk/rrk/{myResources:1;myBaseDamage:5}
WithP1GroundArena: LAW_124:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1Hand: LAW_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1BASEDMG:5

---

# TargetChoiceIsMandatory_SoftPassIsAmountZero
#// USER RULING (2026-08-14): the AMOUNT can technically be 0, but the UNIT choice is MANDATORY — a
#// player who wants no effect soft-passes by choosing a unit and dealing it 0, not by declining the
#// target. So the target stage is a mandatory MZCHOOSE whose pool is exactly the friendly non-Vehicle
#// units, with no decline among them. Two eligible units are seated so the pick stays interactive (a
#// lone target auto-resolves, which is correct for a mandatory choose but leaves no offer to assert),
#// and the decision is left PENDING here.
#// Contrast DealZeroNoHeal above, which exercises the soft pass itself: target chosen, amount 0,
#// nothing damaged and no heal.

## GIVEN
CommonSetup: brk/rrk/{myResources:1;myBaseDamage:5}
WithP1GroundArena: LAW_124:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_102

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_a_friendly_non-Vehicle_unit
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# SoftPass_ChosenUnitTakesNothing_OtherUnitUntouched
#// The soft pass with a real choice on the board: P1 must pick a unit (the ruling), picks LAW_124 and
#// deals it 0. Neither unit takes damage, the base does not heal, and the event is still spent — the
#// player has legally used the card for nothing.

## GIVEN
CommonSetup: brk/rrk/{myResources:1;myBaseDamage:5}
WithP1GroundArena: LAW_124:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P1BASEDMG:5
P1DISCARDCOUNT:1
P1NODECISION
