# DSL parser: multi-value arena directives accept a bracketed, whitespace-separated array on one
# line — WithP{n}GroundArena/SpaceArena: [CID:ready:dmg ...] and the ArenaUpgrade form. Each token
# expands to its own entry (same as one-per-line). Bracketed hand/discard/deck already worked; this
# guards the arena/upgrade extension.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SEC_098:0:3]
WithP1GroundArenaUpgrade: [1:SEC_054]
WithP2SpaceArena: [JTL_033:0:1 JTL_140:1:0]
WithP2Hand: [LAW_050 ASH_052]

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:SEC_098
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:3
P2SPACEARENACOUNT:2
P2SPACEARENAUNIT:0:CARDID:JTL_033
P2SPACEARENAUNIT:1:CARDID:JTL_140
P2HANDCOUNT:2
