# WhenDefeated_MayGiveWeaknessToEnemyUnit
#// HMW_059 Clone X Assassin (1/3) — "When Defeated: You may give a Weakness token to a unit." It attacks
#// a 3/3 (SEC_080) and dies to the counter (attacker self-defeat resolves inline), then gives a Weakness
#// (-1/-1) to that enemy unit — proving "a unit" allows an ENEMY target. SEC_080 becomes 2/2 with 1 combat
#// damage (survives at 1 remaining HP).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_059:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# WhenDefeated_WeaknessDefeatsAOneHpUnit
#// The -1 HP can be lethal: given to a unit at 1 remaining HP (SOR_128, a 3/1), it drops to 0 and is
#// defeated by the shrink sweep. HMW_059 dies attacking the 3/3, then targets the separate 1-HP unit.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_059:1:0
WithP2GroundArena: [SEC_080:0:0 SOR_128:0:0]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# WhenDefeated_Decline_NoToken
#// The "may" decline: no Weakness token is attached.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_059:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
