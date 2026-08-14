# Ambush_OnAttackExhaustsEnemyVehicle
#// SOR_244 Snowspeeder (3/6, Heroism ground Vehicle) — "Ambush / On Attack: Exhaust an enemy
#// Vehicle ground unit." Played with Ambush (unconditional): YES, attack target chosen = SOR_128
#// Death Star Stormtrooper (3/1). The Ambush attack IS an attack, so On Attack fires: the only
#// enemy ground Vehicle (SOR_232 AT-ST) is picked for the mandatory exhaust (the pick stays
#// interactive even with one candidate). Combat then kills the Stormtrooper; the Snowspeeder
#// takes 3.
#//
#// COVERAGE: offer=OnAttackOffer_EnemyGroundVehiclesOnly (pool asserted pending) · reqboundary=
#//           this section (PlayHand → YES → attack-target answers cross serialized request
#//           boundaries before the On Attack resolves) · control=EnemyControlledFriendlyOwned_
#//           StillInPool ("enemy" reads controller, not owner) · boundary pair=OnAttack_
#//           SingleVehicle_StillPicked vs OnAttack_NoEnemyGroundVehicle_NoPrompt · decline=
#//           AmbushDeclined_NoAttackNoOnAttack (the exhaust itself is mandatory — no decline
#//           branch exists on the On Attack clause)

## GIVEN
CommonSetup: bbw/ggk/{myResources:5;handCardIds:SOR_244}
P1OnlyActions: true
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_244
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:EXHAUSTED
P2DISCARDCOUNT:1

---

# AmbushDeclined_NoAttackNoOnAttack
#// Ambush is "may": P1 answers NO — no attack happens, so On Attack never fires either. The
#// Snowspeeder enters exhausted; the enemy AT-ST stays READY and undamaged.

## GIVEN
CommonSetup: bbw/ggk/{myResources:5;handCardIds:SOR_244}
P1OnlyActions: true
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_244
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OnAttackOffer_EnemyGroundVehiclesOnly
#// OFFER assert: attacking the base with a seated Snowspeeder, the On Attack pool is EXACTLY the
#// two enemy GROUND Vehicles — the enemy ground Trooper is excluded by trait, the enemy space
#// Vehicle by arena, and P1's own ground Vehicle by side. The decision is left pending.

## GIVEN
CommonSetup: bbw/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_244:1:0
WithP1GroundArena: SOR_249:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_165:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_111:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# OnAttack_ChosenVehicleExhausts_AttackContinues
#// Two enemy ground Vehicles → real choice. P1 picks the Occupier Siege Tank (index 1); it
#// exhausts, the AT-ST stays ready, and the attack still lands 3 on the base.

## GIVEN
CommonSetup: bbw/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_244:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_165:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SOR_165
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:READY
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# OnAttack_SingleVehicle_StillPicked
#// With exactly ONE enemy ground Vehicle the mandatory exhaust still raises its picker (this
#// pick stays interactive rather than auto-resolving); answering it exhausts the AT-ST and the
#// attack lands 3 on the base.

## GIVEN
CommonSetup: bbw/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_244:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# OnAttack_NoEnemyGroundVehicle_NoPrompt
#// Empty pool → the ability resolves to nothing: enemy has only a ground Trooper and a space
#// Vehicle. No prompt appears; the attack lands 3 on the base; neither enemy unit is exhausted.

## GIVEN
CommonSetup: bbw/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_244:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_111:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:3
P2GROUNDARENAUNIT:0:READY
P2SPACEARENAUNIT:0:READY

---

# OnAttack_CanExhaustTheDefenderItself
#// The pool is unrestricted among enemy ground Vehicles — including the DEFENDER. Snowspeeder
#// (3/6) attacks the AT-ST (6/7) and exhausts the AT-ST itself; exhausting the defender does NOT
#// prevent its combat damage (CR: exhausted defenders still deal combat damage). The Snowspeeder
#// dies to the 6 power; the AT-ST ends with 3 damage, exhausted.

## GIVEN
CommonSetup: bbw/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_244:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_165:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# EnemyControlledFriendlyOwned_StillInPool
#// "Enemy" reads CONTROLLER: a Frontier AT-RT owned by P1 but controlled by P2 (post-control-
#// change state) is an enemy ground Vehicle for the On Attack — it is the only Vehicle P2
#// controls and appears in the pool; picking it exhausts it when the Snowspeeder attacks the base.

## GIVEN
CommonSetup: bbw/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_244:1:0
WithP2GroundArenaControlled: SOR_249:1

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:CARDID:SOR_249
P2GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:3
