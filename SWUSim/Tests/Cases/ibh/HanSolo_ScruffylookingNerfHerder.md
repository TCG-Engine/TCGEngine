# OnAttack_DefenderMinusTwo
#// IBH_010 Han Solo (Ground, 4/6, Cunning/Heroism) — Raid 2 (auto) + On Attack: the defender gets -2/-0
#//   for this attack. Han attacks a 4/7 wall: deals 4+2(Raid)=6 (defender survives at 6 damage), and the
#//   defender's counter power is reduced 4→2, so Han takes 2 (a missing -2/-0 would show 4).

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: IBH_010:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# Reprint042
#// IBH_042 Han Solo (reprint of IBH_010) — Raid 2 + On Attack defender -2/-0. Confirms the duplicate.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: IBH_042:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
