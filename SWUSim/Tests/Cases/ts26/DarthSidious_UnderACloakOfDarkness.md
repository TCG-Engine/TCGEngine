# BuffsOtherSeparatists
#// TS26_013 Darth Sidious (Unit 4/6, cost 6) — Hidden. Each OTHER friendly Separatist unit gets +1/+0.
#// The friendly Battle Droid (TS26_T01, Separatist) gets +1 power; the Imperial SEC_080 is unaffected;
#// Sidious himself is not buffed (the grant is to OTHER units).
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: [TS26_013:1:0 TS26_T01:1:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:2:POWER:3

---

# DroidOnNonTokenDefeat
#// TS26_013 Darth Sidious — "When a non-token unit is defeated: create a Battle Droid token." LAW_124
#// attacks and defeats the enemy SOR_128 (a non-token unit); Sidious's controller creates a Battle Droid,
#// so P1's ground goes from 2 units (Sidious + LAW_124) to 3.
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: [TS26_013:1:0 LAW_124:1:0]
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:TS26_T01
