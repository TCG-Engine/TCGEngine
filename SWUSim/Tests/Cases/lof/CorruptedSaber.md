# DefenderDebuff
#// LOF_187 Corrupted Saber — if attached unit is a Force unit, it gains "On Attack: the defender gets
#// -2/-0 for this attack." Plo Koon (Force, with the saber) attacks the enemy 4/7, whose counter-power is
#// reduced from 4 to 2 → Plo Koon takes only 2.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_187
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# NonForceHost_NoDebuff
#// LOF_187 Corrupted Saber — if the attached unit is NOT a Force unit, the -2/-0 defender debuff does NOT
#// apply. SOR_046 Consular Security Force (non-Force) carries the saber and attacks LAW_124 (4/7); the
#// defender keeps its full 4 counter-power, so the host takes the full 4 damage. Ref: "if attached unit is
#// not a Force unit, it does not apply the -2/-0 effect."

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:LOF_187
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
