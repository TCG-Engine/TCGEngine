# OnAttack_Decline_NoBuff
#// SHD_118 Kihraxz Heavy Fighter — declining the optional exhaust means no +3: the base attack deals the
#// printed 3, and SOR_095 stays ready.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1SpaceArena: SHD_118:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY

---

# OnAttack_ExhaustForBuff
#// SHD_118 Kihraxz Heavy Fighter (4-cost 3/3 space) — Overwhelm + "On Attack: You may exhaust another
#// friendly unit. If you do, this unit gets +3/+0 for this attack." Kihraxz exhausts the friendly SOR_095,
#// gains +3 → 6 power → its base attack deals 6.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1SpaceArena: SHD_118:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
