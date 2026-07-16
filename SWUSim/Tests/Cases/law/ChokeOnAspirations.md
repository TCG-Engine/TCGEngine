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
