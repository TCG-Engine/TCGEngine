# GrantedOnAttack_BuffAnother
#// SHD_175 Armed to the Teeth (+2/+0) — attached unit gains "On Attack: Give another friendly unit
#// +2/+0 for this phase." The wearing marine attacks the base (3+2 = 5); the granted On Attack fires
#// via the host-upgrade scan and buffs the OTHER friendly (Consular 3/7 → 5/7; the host itself is
#// excluded).

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_175

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:7
