# AmbushWhenBaseAttacked
#// TS26_75 Jango Fett — "While an enemy unit has attacked your base this phase, this unit gains Ambush."
#// After P2's SEC_080 attacks P1's base, Jango gains Ambush.
## GIVEN
CommonSetup: yyk/rrk
WithP1GroundArena: TS26_75:1:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# NoAmbushByDefault
#// TS26_75 Jango Fett — with no enemy having attacked your base this phase, Jango does NOT have Ambush.
## GIVEN
CommonSetup: yyk/rrk
WithP1GroundArena: TS26_75:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# OnAttackEnemyMinus3
#// TS26_75 Jango Fett (Unit 5/5, cost 5) — On Attack: give an enemy unit -3/-0 for this phase. Jango
#// attacks LAW_124 (4/7); the On-Attack debuff drops its power to 1, so it counters for only 1.
## GIVEN
CommonSetup: yyk/rrk
WithP1GroundArena: TS26_75:1:0
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:1
