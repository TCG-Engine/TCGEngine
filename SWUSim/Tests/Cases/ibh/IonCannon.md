# Action_DealThreeToSpace
#// IBH_016 Ion Cannon (Ground, 0/5, Cunning) — Action [Exhaust]: deal 3 damage to a space unit. P1 uses
#//   the action; the only space unit (enemy 4/7) takes 3; Ion Cannon exhausts.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: IBH_016:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Reprint027
#// IBH_027 Ion Cannon (reprint of IBH_016) — Action [Exhaust]: deal 3 to a space unit. Confirms duplicate.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: IBH_027:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:EXHAUSTED
