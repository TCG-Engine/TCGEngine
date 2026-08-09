# DeclineNoToken
#// TS26_78 Barriss Offee (Unit 5/6) — "When an enemy unit attacks: you may give an Experience token to
#// that unit." DECLINE branch: P1 declines, so SEC_080 stays 3 power and deals 3 to the base.
## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_78:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P1BASEDMG:3

---

# ExpToAttackingEnemy
#// TS26_78 Barriss Offee (Unit 5/6) — Hidden + "When an enemy unit attacks: you may give an Experience
#// token to that unit." When P2's SEC_080 attacks P1's base, Barriss's controller (P1) gives SEC_080 an
#// Experience token (3 → 4 power), so it deals 4 to the base.
## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_78:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P1BASEDMG:4

---

# TriggersWhenTheEnemyAttacksAFRIENDLYUNITToo
#// TS26_78 Barriss Offee — "when an enemy unit ATTACKS", not "attacks your base". P2's SEC_080 attacking
#// P1's SOR_046 still opens the window: the attacker takes the Experience (3 -> 4 power) and deals 4.

## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: [TS26_78:1:0 SOR_046:1:0]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:DAMAGE:4

---

# AFRIENDLYUnitAttackingDoesNotTrigger
#// TS26_78 Barriss Offee — "when an ENEMY unit attacks". P1's own SEC_080 attacking P2's base raises no
#// offer and gains nothing: it stays at 3 power and deals 3.

## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TS26_78:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
P2BASEDMG:3
P1NODECISION

---

# TriggersAgainForASecondEnemyAttackInTheSamePhase
#// TS26_78 Barriss Offee — the ability is not once-per-phase. Two different enemy units attack P1's base
#// in the same phase and each is offered the Experience: both end at 4 power, and the base takes 4 + 4.

## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TS26_78:1:0
WithP2GroundArena: [SEC_080:1:0 SOR_095:1:0]

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:1:POWER:4
P1BASEDMG:8
