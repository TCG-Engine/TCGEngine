# WhenDefeated_ReadyUnit
#// SEC_154 Inner Rim Coalition (Ground, 6/5) — When Defeated: you may ready a unit that costs 5 or less.
#//   SEC_154 (pre-damaged to 1 HP) attacks SOR_046 and dies to the counter; on defeat P1 readies the
#//   exhausted SEC_041 (cost 1).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_154:1:4
WithP1GroundArena: SEC_041:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_041
P1GROUNDARENAUNIT:0:READY

---

# NGOR_ReadyForCaster
#// SEC_154 Inner Rim Coalition — When Defeated: you may ready a unit that costs 5 or less. P1 plays
#//   No Glory, Only Results (JTL_043) to take control of the ENEMY Inner Rim Coalition and defeat it.
#//   Control transfers to P1 first, so the ready choice belongs to P1, who readies its own exhausted
#//   SEC_041 (cost 1).

## GIVEN
CommonSetup: rrw/rrk/{myResources:13}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SEC_154:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_041
P1GROUNDARENAUNIT:0:READY
P1NODECISION
