# AttackDefeats_Captures
#// SEC_209 The Mandalorian (Ground, 6/8, Cunning/Heroism) — Ambush + when this unit attacks and defeats
#//   a unit, may capture an enemy non-leader. Attacks SOR_095 (idx1) and defeats it, then captures SOR_046 (idx0).

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_209:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_209
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# AttackDoesNotDefeat_NoCapture
#// SEC_209 The Mandalorian (6/8) — the capture only triggers when he attacks AND defeats a unit. He attacks
#// SOR_232 AT-ST (6/7): deals 6 (survives at 6 damage) and takes 6 back. No defeat → no capture offer; the
#// other enemy (SHD_258) is untouched.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_209:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SHD_258:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:CARDID:SEC_209
P1GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# EnemyAttacksMandoAndDies_NoCapture
#// SEC_209 The Mandalorian — the trigger requires MANDO to be the attacker. Here the enemy SHD_258
#// Mandalorian Warrior (3/3) attacks into Mando (6/8) and is defeated by his 6 counter, but Mando was the
#// DEFENDER → no capture is offered; the surviving enemy SOR_232 stays uncaptured.

## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 2
WithP1GroundArena: SEC_209:1:0
WithP2GroundArena: SHD_258:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_209
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# MandoDefeatedWhileAttacking_Fizzles
#// SEC_209 The Mandalorian — if Mando is defeated by the same combat, the capture fizzles (he is no longer
#// in play to capture). He attacks a pre-damaged SOR_039 AT-AT Suppressor (8/8, 2 damage → 6 HP): Mando's 6
#// defeats it, but its 8 counter defeats Mando (8 HP). Both die; the other enemy SHD_258 is NOT captured.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_209:1:0
WithP2GroundArena: SOR_039:1:2
WithP2GroundArena: SHD_258:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_258
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
P2NODECISION
