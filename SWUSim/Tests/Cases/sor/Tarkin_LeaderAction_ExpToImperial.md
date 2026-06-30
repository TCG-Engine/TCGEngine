# SOR_007 Grand Moff Tarkin (leader) — Action [1 resource, exhaust]: Give an Experience token
# to an Imperial unit. P1 uses the leader action: pays 1 resource (2 → 1 ready), the leader
# exhausts, and the only Imperial unit (SOR_229, 3/3) auto-receives +1/+1 (→ 4/4).

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SOR_229:1:0    # Imperial unit — Experience recipient

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1
