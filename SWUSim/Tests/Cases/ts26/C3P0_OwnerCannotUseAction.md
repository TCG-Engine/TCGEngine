# TS26_015 C-3P0 — "Only opponents may use this ability." The owner-gate blocks the OWNER from activating
# the action even when they control a ready C-3P0. P1 (owner) tries to use it: nothing happens — C-3P0
# stays ready (the exhaust cost is never paid) and the enemy SOR_095 takes no damage.
## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: TS26_015:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:0
