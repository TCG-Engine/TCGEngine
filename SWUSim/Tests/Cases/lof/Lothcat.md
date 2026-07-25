# WhenPlayed_ExhaustGround
#// LOF_207 Loth-Cat — When Played: may exhaust a ground unit. P1 plays it and exhausts the enemy 3/7.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_207}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# WhenDefeated_ExhaustGround
#// LOF_207 Loth-Cat — the SAME ability also fires When Defeated: "you may exhaust a ground unit." Loth-Cat
#// (2/1) attacks the enemy 3/7 (SOR_046) and dies to the 3 counter-damage; on defeat P1 exhausts that enemy
#// unit. Ref: Loth-Cat may exhaust a ground unit when defeated.

## GIVEN
CommonSetup: yyk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_207:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
