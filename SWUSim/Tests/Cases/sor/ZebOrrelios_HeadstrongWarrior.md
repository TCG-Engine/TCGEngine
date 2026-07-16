# AttackDefeats_DealsToGroundUnit
#// SOR_146 Zeb Orrelios (5/5) — "When this unit completes an attack: If the defender was defeated,
#// you may deal 4 damage to a ground unit." Zeb (5 power) attacks a 3/3 (SEC_080), defeats it, takes 3
#// back. The defender died → the may-choose fires; deal 4 to the opponent's other ground unit (SOR_046
#// 3/7, which reindexes to idx 0 after SEC_080 is cleaned up, and survives at 4 damage).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackDefeats_DeclineDeal
#// SOR_146 Zeb Orrelios — the deal-4 is optional ("you may"). Zeb defeats the defender, then DECLINES
#// the may-choose (AnswerDecision:-), so the surviving ground unit takes no extra damage. Zeb still has
#// the 3 combat damage from the defender.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackNoDefeat_NoTrigger
#// SOR_146 Zeb Orrelios — the ability is conditional on the defender being defeated. Zeb (5 power)
#// attacks a 3/7 (SOR_046) that survives, so the defender was NOT defeated: no may-choose is queued
#// (P1NODECISION) and the defender takes only the 5 combat damage (not 5 + 4). Proves the gate.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
