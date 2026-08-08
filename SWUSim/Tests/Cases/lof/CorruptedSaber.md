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
#// defender keeps its full 4 counter-power, so the host takes the full 4 damage. Intended: "if attached unit is
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

---

# DebuffIsForThisAttackOnly
#// LOF_187 Corrupted Saber — "-2/-0 FOR THIS ATTACK", so it must not persist onto a later attack in the
#// same phase. Grogu (LOF_246, 1/6, Force) carries the saber and attacks Industrious Team (LAW_124, 4/7),
#// taking only 2 from the reduced counter. A SECOND friendly attacker with no saber (Consular Security
#// Force, 3/7) then attacks the SAME defender and takes the FULL 4 — proving the debuff ended with the
#// first attack rather than sticking to the defender. (A low-power host on purpose: a big one kills
#// LAW_124 outright and there is no defender left to attack twice.)
## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_246:1:0
WithP1GroundArenaUpgrade: 0:LOF_187
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AttackGroundArena:1:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:DAMAGE:4
