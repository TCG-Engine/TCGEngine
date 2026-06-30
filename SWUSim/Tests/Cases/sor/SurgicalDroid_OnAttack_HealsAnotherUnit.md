# SOR_059 2-1B Surgical Droid (1/3) — On Attack: You may heal 2 damage from another unit.
# The Droid attacks the enemy base; the trigger offers to heal another unit. Battlefield
# Marine (the only other unit, pre-damaged 2) auto-resolves and is healed to 0 damage.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0    # 2-1B Surgical Droid (ready) — attacker, idx 0
WithP1GroundArena: SOR_095:1:2    # Battlefield Marine with 2 damage — idx 1, the heal target

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:1
