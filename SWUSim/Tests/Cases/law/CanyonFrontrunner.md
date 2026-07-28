# OnAttackDebuffIfFirst
#// LAW_228 Canyon Frontrunner (3/2) — On Attack: if no other units have attacked this phase (including
#// enemy units), you may give a unit -2/-0 for this phase. It's the only attacker -> debuff SOR_046
#// (3/7 -> 1/7).

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_228:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1

---

# NoDebuffIfFriendlyAlreadyAttacked
#// LAW_228 Canyon Frontrunner (3/2) — On Attack debuff only applies if NO other unit has attacked this
#// phase. SOR_164 Wampa attacks first, then Canyon attacks; because a friendly unit already attacked
#// this phase the ability does not trigger and no target is offered (SOR_046 keeps its 3 power).

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_228:1:0 SOR_164:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:POWER:3

---

# NoDebuffIfEnemyAlreadyAttacked
#// LAW_228 Canyon Frontrunner — an ENEMY attack this phase also disables the debuff. P2's SEC_213 A-Wing
#// attacks P1's base first, then P1 attacks with Canyon. Because a unit already attacked this phase
#// Canyon's On Attack does not trigger and no target is offered (SOR_046 keeps its 3 power).

## GIVEN
CommonSetup: yyk/bgw/{}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LAW_228:1:0
WithP2SpaceArena: SEC_213:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:POWER:3

---

# DebuffAppliesEvenIfAttackedPreviousPhase
#// LAW_228 Canyon Frontrunner — the "no other units have attacked" check is per-phase. SOR_164 Wampa
#// attacks in the first action phase; after advancing to the next action phase Canyon attacks as the
#// first attacker of THAT phase, so the debuff still applies (SOR_046 3 power -> 1).

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_228:1:0 SOR_164:1:0]
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
