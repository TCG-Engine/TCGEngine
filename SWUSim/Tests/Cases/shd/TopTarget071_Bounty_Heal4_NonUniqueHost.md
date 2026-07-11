# SHD_071 Top Target — "Bounty — Heal 4 damage from a unit or base. If this unit is unique, heal 6
# instead." Host is the NON-unique Battlefield Marine → heal 4. P1's base starts at 6 damage; after
# collecting and choosing their own base, exactly 4 heals (6 → 2) — the value distinguishes the
# 4-vs-6 formula.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_071

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:2
