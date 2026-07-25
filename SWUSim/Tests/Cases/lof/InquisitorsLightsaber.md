# VsForceBuff
#// LOF_090 Inquisitor's Lightsaber (+1/+3) — attached gains "While attacking a Force unit, this unit gets
#// +2/+0." SOR_095 (3 base + 1 = 4) attacks the Force unit Plo Koon, getting +2 → deals 6.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_090
WithP2GroundArena: LOF_050:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# VsNonForce_NoBuff
#// LOF_090 Inquisitor's Lightsaber — negative: the +2/+0 only applies "while attacking a Force unit." SOR_095
#// (3 base + 1 from the upgrade = 4) attacks the NON-Force Consular Security Force (SOR_046, Rebel/Trooper),
#// so it gets NO +2 and deals only 4. Ref: Yoda attacks Consular Security Force for 3 (no Force bonus).

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_090
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
