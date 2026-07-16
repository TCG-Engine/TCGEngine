# OnAttack_NoLeaderUnit_NoBuff
#// SOR_116 Steadfast Battalion — absence guard for the conditional On Attack buff.
#// P1's leader is NOT deployed (no leader unit controlled) → condition fails → NO buff.
#// Steadfast Battalion stays 5/5 and its attack on the enemy base deals 5 (printed power).

## GIVEN
CommonSetup: ggw/grw
SkipPreGame: true
WithP1GroundArena: SOR_116:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_116
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:5

---

# OnAttack_WithLeaderUnit_Buffs
#// SOR_116 Steadfast Battalion — Unit 5/5, Ground, Overwhelm.
#// "On Attack: If you control a leader unit, give a friendly unit +2/+2 for this phase."
#// P1 controls a REAL deployed leader unit (Leia @1) → condition met. "A friendly unit" includes the
#// leader unit, so the buff is a genuine 2-target choice; here it's put on Leia (myGroundArena-1).
#// SOR_116 attacks the base for 5; Leia (4 power → 6 with +2/+2) then attacks for 6 → base takes 11.

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_116:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P2BASEDMG:11
