# InspiringMentor_OnAttack_Exp
#// SHD_104 Inspiring Mentor — attached unit gains "On Attack: Give an Experience token to another
#// friendly unit." The host (SOR_046 + SHD_104 = 4 power) attacks the base; its On Attack gives an
#// Experience token to the only other friendly unit (SOR_095 3/3 → 4/4).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_104
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:POWER:4

---

# InspiringMentor_WhenDefeated_Exp
#// SHD_104 Inspiring Mentor — the granted "When Defeated" half (also exercises On Attack in the same
#// combat). The host (SOR_046 + SHD_104 = 4/8, pre-damaged 5 → 3 effective HP) attacks a Wampa (SOR_164
#// 4/5): its On Attack gives SOR_095 one Experience (→4/4), then it deals 4 (Wampa survives), counters 4
#// → the host dies, and its When Defeated gives SOR_095 a SECOND Experience (→5/5). Power 5 confirms the
#// When Defeated half fired (On Attack alone would leave it at 4). SOR_095 is now the sole ground unit.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:5
WithP1GroundArenaUpgrade: 0:SHD_104
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
