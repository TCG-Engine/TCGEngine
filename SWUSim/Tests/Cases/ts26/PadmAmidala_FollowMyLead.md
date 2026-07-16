# DeployedAttackEndChain
#// TS26_004 Padmé Amidala (leader deployed, 5/6) — When Attack Ends: you may attack with another friendly
#// unit that entered play this phase (can't attack bases). After playing SEC_080 this phase, deployed Padmé
#// attacks LAW_124 (5 damage), then SEC_080 attacks it too (3 more) → LAW_124 (7 HP) is defeated.
## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_004:1:1;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SEC_080
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:0

---

# FrontAttackWithEntered
#// TS26_004 Padmé Amidala (leader front) — Action [Exhaust]: if 2+ friendly units entered play this phase,
#// attack with 1 of them (can't attack bases). After playing 2 units, SEC_080 attacks the enemy LAW_124
#// (the only non-base target) for 3.
## GIVEN
CommonSetup: bgw/rrk/{myLeader:TS26_004;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SEC_080 SOR_095]
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:EXHAUSTED
