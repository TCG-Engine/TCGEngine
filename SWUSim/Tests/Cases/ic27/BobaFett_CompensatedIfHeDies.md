# DefenderDefeated_ReadiesTwoResources
#// IC27_146 Boba Fett (Compensated If He Dies) — 5 cost, 4/7, Cunning+Villainy, Ground,
#//   Underworld/Bounty Hunter (unique).
#// Text: "When Attack Ends: If the defending unit was defeated, you may ready 2 resources."
#// Boba (4 power) kills a 3/3 and takes 3 back on his 7 HP. Starting 1 ready + 3 exhausted, taking
#// the offer readies exactly 2 -> 3 ready.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Resources: 1:SOR_046:1,3:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:IC27_146
P1GROUNDARENAUNIT:0:DAMAGE:3
P1RESCOUNT:4
P1RESAVAILABLE:3

---

# DefenderDefeated_Decline_NothingReadies
#// TAKE/DECLINE: "you may" must be declinable, and declining must ready nothing.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Resources: 1:SOR_046:1,3:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:4
P1RESAVAILABLE:1

---

# DefenderSurvived_NoTriggerNoPrompt
#// THE LOAD-BEARING NEGATIVE: the "if the defending unit was defeated" gate. Boba's 4 power does not
#// kill a 3/7, so nothing triggers and no prompt appears.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_146:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Resources: 1:SOR_046:1,3:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION
P1RESAVAILABLE:1

---

# AttackedBase_NoDefendingUnit_NoTrigger
#// SCOPE: an attack on the BASE has no defending unit at all, so the condition can never be met.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_146:1:0
WithP1Resources: 1:SOR_046:1,3:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1NODECISION
P1RESAVAILABLE:1

---

# BobaDiesInTheTrade_StillTriggers
#// THE CARD'S OWN SUBTITLE — "Compensated If He Dies". There is no "if this unit survived" clause,
#// so the attack ending + the defender dying is enough; the trigger must sit ABOVE the attacker-
#// survival gate in the combat-hit collection (the LAW_252 Fett's Firespray shape). Boba is seeded at
#// 6 damage on 7 HP, so the 3-power counter finishes him after he kills the 3/3.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_146:1:6
WithP2GroundArena: SEC_080:1:0
WithP1Resources: 1:SOR_046:1,3:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:3

---

# NoExhaustedResources_NoPrompt
#// A "may" with nothing to gain must not raise a prompt at all (the SEC_186 "pointless prompt"
#// family). All four resources are already ready, so readying 2 would be a no-op.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Resources: 4:SOR_046:1

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1NODECISION
P1RESAVAILABLE:4

---

# OnlyOneExhausted_ReadiesJustThatOne
#// QUANTITY: "ready 2" readies as many as are available (1 here) rather than erroring or over-readying.

## GIVEN
CommonSetup: yyk/yyk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_146:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Resources: 2:SOR_046:1,1:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:3
P1RESAVAILABLE:3
