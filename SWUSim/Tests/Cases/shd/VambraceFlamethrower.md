# VambraceFlamethrower_OnAttack_SplitDamage
#// SHD_177 Vambrace Flamethrower — attached unit gains "On Attack: You may deal 3 damage divided as you
#// choose among enemy ground units." Host (SOR_046 + SHD_177 +1/+1 = 4 power) attacks the base; its On
#// Attack deals all 3 to the lone enemy ground unit (SOR_046, 7 HP → 3 damage). Base still takes 4.
#// COVERAGE: offer=VambraceFlamethrower_OnAttack_AllOnOneTarget_Overkill (the split is assigned out of a
#//           TWO-unit enemy ground pool, so the pool contents are load-bearing) · decline=KNOWN-OPEN
#//           (both sections answer YES to the "you may"; the pass branch and the assign-zero branch are
#//           not asserted in this file) · control=N/A (the granted On Attack is scoped to the
#//           host's controller and damages enemy ground units only) ·
#//           boundary=VambraceFlamethrower_OnAttack_SplitDamage (3 onto a 7-HP unit → survives) vs
#//           VambraceFlamethrower_OnAttack_AllOnOneTarget_Overkill (3 onto a 1-HP unit → defeated, the
#//           surplus is legal and lost) · reqboundary=N/A (the divide is one MZSPLITASSIGN answer)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:4

---

# VambraceFlamethrower_OnAttack_AllOnOneTarget_Overkill
#// SHD_177 Vambrace Flamethrower — the 3 damage may be piled entirely onto ONE enemy ground unit even
#// when that exceeds its remaining HP. Two enemy ground units are available; all 3 go to the 1-HP
#// SOR_128, which is defeated (the surplus is simply lost), and the other enemy takes nothing.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0:3

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:4
P2DISCARDCOUNT:1
