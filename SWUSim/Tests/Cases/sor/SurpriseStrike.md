# AttackPlus3
#// SOR_220 Surprise Strike (Event, cost 2) — "Attack with a unit. It gets +3/+0 for
#// this attack." P1's only ready unit (Battlefield Marine, 3/3) is auto-chosen, gets
#// +3/+0, and — with P2 having no units — attacks P2's base for 3 + 3 = 6.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:3

---

# SimulateRequestBoundary_Plus3SurvivesTheChoose
#// SOR_220 Surprise Strike — AttackPlus3 has a single ready unit, so the attacker auto-resolves and no
#// request ever ends. A second unit (SOR_046) keeps the "attack with a unit" choose interactive, and
#// the boundary goes before the answer: in production that answer arrives in a fresh process, so the
#// pending attack context AND the +3/+0-for-this-attack rider must both be serialized.
#// SEC_080 (3/3) is chosen → 3 + 3 = 6 to P2's base, and it is back to 3 power afterwards.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:SOR_220}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_046:1:0    # 2nd ready unit, keeps the attacker choose interactive

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:3
