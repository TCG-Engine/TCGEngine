# NoForce_Deals9ToOwnBase
#// LOF_137 Savage Opress (9/6) — When Played: you may use the Force. If you DON'T (here: can't, no Force
#// token), deal 9 damage to your own base.

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:LOF_137}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:9
P1GROUNDARENACOUNT:1

---

# UseForce_NoSelfDamage
#// LOF_137 Savage Opress — with the Force, P1 uses it (YES) and avoids the 9 self-damage.

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:LOF_137}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1BASEDMG:0
