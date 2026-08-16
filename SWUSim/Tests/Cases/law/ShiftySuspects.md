# OnAttackNoBaseHeal
#// LAW_197 Shifty Suspects (4/5) — On Attack: bases can't be healed for this phase. Shifty attacks the
#// base (setting the lock); then Tantive IV (Restore 2) attacks the base but its Restore is blocked, so
#// P1's base (pre-damaged 3) stays at 3.

## GIVEN
CommonSetup: rrw/bgw/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: LAW_197:1:0
WithP1SpaceArena: LAW_109:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3

---

# NoBaseHealLock_SurvivesTheRequestBoundary
#// LAW_197 Shifty Suspects — request-boundary guard on the "bases can't be healed for this phase" lock, which
#// by definition has to outlive the action that set it. Shifty attacks the base (setting the lock); SOR_142
#// Sabine then attacks SEC_081 Major Partagaz, leaving a REAL pending choose for her On Attack damage
#// (MZMAYCHOOSE over theirGroundArena-0 & theirBase-0 & myBase-0); a serialize round-trip is inserted before
#// that answer. Only then does LAW_109 Tantive IV (Restore 2) attack the base — its Restore is still blocked,
#// so P1's pre-damaged base stays at 3. Control run (identical fixture without Shifty's attack): the base
#// heals to 1, so the 3 here is load-bearing.

## GIVEN
CommonSetup: rrw/bgw/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: [LAW_197:1:0 SOR_142:1:0]
WithP1SpaceArena: LAW_109:1:0
WithP2GroundArena: SEC_081:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
