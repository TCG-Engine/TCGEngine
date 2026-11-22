# Leader_KeepCreditForTheUnit_PaysTheLast1
#// Bug report 2026-11-22 (Discord, no game attached): "if you have fennec shand, ready for war as leader and have 7 ressources and
#// 1 credit, and you use her ability which cost 1 ressource to play a card which cost 7 ressources, he doesnt do it, it looks like it
#// doesnt read the additional credits". ASH_002 — Action [1 resource, Exhaust, exhaust a friendly unit]: play a unit from your hand
#// (paying its cost); it enters play ready. NOT reproduced: every shape below plays TWI_166 Aurra Sing (cost 7, Aggression — no
#// aspect penalty under Fennec) with the Credit paying 1 of it (CR 3.13). Kept as guards for the reported scenario.
#// (A real game where an enemy controls LAW_117 Conveyex Security Captain — "Enemy Credit tokens lose all abilities" — WOULD
#// refuse the play, correctly.)
#// The ability's [1 resource] is paid with a resource (Credit picker declined); 6 resources + the Credit pay the 7.
## GIVEN
CommonSetup: rrw/brk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Credits: 1
WithP1Hand: TWI_166
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-
- P1>AnswerDecision:myHand-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:TWI_166
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# Leader_CreditPaysTheAbility_ResourcesPayTheUnit
#// The Credit pays the ability's [1 resource]; all 7 resources then pay the unit.
## GIVEN
CommonSetup: rrw/brk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Credits: 1
WithP1Hand: TWI_166
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myHand-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:TWI_166
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# Leader_ChoosingTheExhaustedUnit_StillPaysWithTheCredit
#// Two ready units, so the exhaust cost is a choice before the hand pick.
## GIVEN
CommonSetup: rrw/brk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Credits: 1
WithP1Hand: TWI_166
WithP1GroundArena: SEC_135:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:2:CARDID:TWI_166
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# Leader_SpareCredit_PickerDuringThePlay
#// Two Credits: 6 resources + 2 Credits is more than 7, so the Credit picker opens DURING the nested play; one is spent.
## GIVEN
CommonSetup: rrw/brk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Credits: 2
WithP1Hand: TWI_166
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myTempZone-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:TWI_166
P1CREDITCOUNT:1
P1RESAVAILABLE:0

---

# Deployed_UnitAction_CreditPaysTheLast1
#// Deployed Fennec's Action [1 resource, exhaust a friendly unit].
## GIVEN
CommonSetup: rrw/brk/{myLeader:ASH_002:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Credits: 1
WithP1Hand: TWI_166
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:-
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:2:CARDID:TWI_166
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# Leader_NoReadyFriendlyUnit_Unavailable_CreditUntouched
#// The likely cause of the report (owner, 2026-11-22: "my guess is they didnt have a friendly unit to exhaust"). The reporter's board —
#// 7 resources, 1 Credit, a 7-cost unit in hand — but NO ready friendly unit. "Exhaust a friendly unit" is part of the COST, and the
#// undeployed leader is not a unit, so the cost cannot be paid and the Action is unavailable (see FennecShand_ReadyForWar.md
#// NoReadyFriendlyUnit_*). Correct by the card text — not a Credit problem. Nothing is spent: the leader stays ready, every resource
#// stays ready, and the Credit is NOT eaten by the refused ability.
## GIVEN
CommonSetup: rrw/brk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Credits: 1
WithP1Hand: TWI_166
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1NODECISION
P1LEADER:READY
P1RESAVAILABLE:7
P1CREDITCOUNT:1
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0

---

# Deployed_Alone_ExhaustsHerself_CreditPaysTheLast1
#// Deployed Fennec with NO other unit: her leader-unit Action is [1 resource, exhaust a friendly unit], and she is a friendly unit, so
#// she pays it by exhausting HERSELF (owner, 2026-11-22: "the deployed leader unit can exhaust herself" — the undeployed leader side is
#// not a unit). 7 resources + 1 Credit: 1 resource for the ability, 6 + the Credit for TWI_166 (cost 7), which enters ready.
## GIVEN
CommonSetup: rrw/brk/{myLeader:ASH_002:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Credits: 1
WithP1Hand: TWI_166
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-
- P1>AnswerDecision:myHand-0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:ASH_002
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:TWI_166
P1GROUNDARENAUNIT:1:READY
P1CREDITCOUNT:0
P1RESAVAILABLE:0

---

# Deployed_NothingReadyToExhaust_Unavailable_NothingSpent
#// Deployed Fennec's Action is [1 resource, exhaust a friendly unit]. Fennec herself is already EXHAUSTED and the only other friendly
#// unit is exhausted too, so nothing can pay the exhaust: the Action is unavailable (SWUUnitActionAffordable, case 'ASH_002'). Nothing
#// is spent — no resource, no Credit — and no unit is played.
## GIVEN
CommonSetup: rrw/brk/{myLeader:ASH_002:0:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Credits: 1
WithP1Hand: TWI_166
WithP1GroundArena: SOR_095:0:0
## WHEN
- P1>UseUnitAbility:myGroundArena-1
## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_002
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:7
P1CREDITCOUNT:1
P1HANDCOUNT:1
