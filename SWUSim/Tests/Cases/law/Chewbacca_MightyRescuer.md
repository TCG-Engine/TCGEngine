# AttackEndExpHeal
#// LAW_034 Chewbacca (4/4, Overwhelm) — When Attack Ends: if the defending unit was defeated, give it an
#// Experience token and heal 3 from him. Attacks SOR_128 (3/1, dies); Chewbacca takes 3 then heals 3
#// (DAMAGE:0) and gains Experience (5/5).
#// COVERAGE: offer=N/A (the heal/Experience is mandatory and self-targeted — no target-choice ever
#//           exists; the only prompt in the family is the trigger-order MZCHOOSE asserted via
#//           ResolveTrigger in AttackEnd_DefeatedByGrantedAbilityFirst_NoTrigger) · decline=N/A (no
#//           "you may") · boundary=survives vs defeated pair: AttackEndExpHeal +
#//           AttackEnd_NoTriggerWhenDefeatedByCombatDamage (combat death) +
#//           AttackEnd_DefeatedByGrantedAbilityFirst_NoTrigger (ability death in the same window) ·
#//           control=N/A (self-referential effect) · reqboundary=trigger-order choice held between
#//           combat resolution and trigger resolution in the granted-ability section

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_034:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_034
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:5

---

# AttackEnd_NoTriggerWhenDefeatedByCombatDamage
#// Intended: the heal/Experience does NOT happen if Chewbacca is defeated during the attack. He starts
#// at 1 damage and trades with SEC_080 (3/3): his 4 kills it (Overwhelm spills 1 to base), its 3 back
#// brings him to 4/4 — both are defeated and he stays in the discard with no trigger.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_034:1:1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LAW_034
P2BASEDMG:1
P1NODECISION

---

# AttackEnd_DefeatedByGrantedAbilityFirst_NoTrigger
#// Intended: SOR_150 Heroic Sacrifice attacks with Chewbacca (+2/+0 and "when this unit deals combat
#// damage: defeat it"). He kills SOR_128 (3/1) and survives the counter, so BOTH the granted defeat and
#// his attack-end heal/Experience collect in the same window; resolving the granted defeat first puts
#// him in the discard and the attack-end trigger finds him gone — no heal, no Experience, he stays dead.

## GIVEN
CommonSetup: rrw/bgw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LAW_034:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SOR_150
WithP1Deck: [SOR_237 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>ResolveTrigger:SOR_150

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P1HANDCOUNT:1
P2BASEDMG:5
P1NODECISION

---

# AttackingABASE_NoTriggerAtAll
#// LAW_034 Chewbacca — "When Attack Ends: If the DEFENDING UNIT was defeated…". An attack on a base has no
#// defending unit at all, so the condition can never be met: Chewbacca attacks the enemy base, deals his
#// damage and receives no Experience token and no heal. The existing negatives both involve a defending
#// unit; this one removes the defender entirely, which is the case a condition implemented as "if anything
#// was defeated" would get wrong.

## GIVEN
CommonSetup: bgw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_034:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:LAW_034
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
