# WhenPlayed_GiveOfficialSentinel
#// SEC_031 Nute Gunray (Ground, 3/4) — Grit + When Played/On Attack: may give another friendly Official
#//   unit Sentinel for this phase. SEC_041 (Official) gains Sentinel.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_031

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_041
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NODECISION

---

# OnAttack_GiveOfficialSentinel
#// SEC_031 Nute Gunray (Ground, 3/4) — the SAME "may give another friendly Official unit Sentinel for this
#//   phase" fires On Attack (not only When Played). Nute is already in play and attacks P2's base; his On
#//   Attack trigger lets P1 give the other friendly Official (SEC_041) Sentinel.

## GIVEN
CommonSetup: bbk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_031:1:0
WithP1GroundArena: SEC_041:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_041
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P2BASEDMG:3

---

# WhenPlayed_DeclineSentinel
#// SEC_031 — the ability is a "may": P1 can decline. Playing Nute with another friendly Official present,
#//   P1 passes the optional grant, so SEC_041 does NOT gain Sentinel.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_031

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_041
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1NODECISION
