# Deployed_Action_PlayUnitReady
#// ASH_002 Fennec Shand (deployed) — Action [1 resource, exhaust a friendly unit]: play a unit from
#// your hand (paying its cost). It enters play ready. Fennec exhausts the Dark Trooper (cost), plays
#// SOR_128 (3/1) which enters ready; Fennec herself does NOT exhaust (no self-Exhaust on the deployed side).

## GIVEN
CommonSetup: brw/brk/{
  myLeader:ASH_002:1:1:1;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_128
WithP1Resources: 6

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:2:CARDID:SOR_128
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY

---

# PlayUnitReady
#// ASH_002 Fennec Shand — Leader Action [1 resource, Exhaust, exhaust a friendly unit]: play a unit from
#// your hand (paying its cost); it enters play ready. P1 exhausts SEC_135 (the cost, auto-chosen) and plays
#// SOR_095 (named: play-from-hand is a MAY, owner 2026-11-04), which enters the ground arena READY; Fennec exhausts.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_002
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1LEADER:EXHAUSTED

---

# SelfExhaustAsCost
#// ASH_002 Fennec Shand (deployed) — the "exhaust a friendly unit" cost may be paid by exhausting Fennec
#// herself. With Battlefield Marine (SOR_095) also on board, P1 chooses Fennec (leader unit, index 1) as the
#// cost; the Marine stays ready and Wampa (SOR_164) enters play ready.
## GIVEN
CommonSetup: rrw/rrk/{myLeader:ASH_002:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_164
## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_002
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:2:CARDID:SOR_164
P1GROUNDARENAUNIT:2:READY

---

# Saboteur_WhenDeployed
#// ASH_002 Fennec Shand — the deployed leader unit has Saboteur, so it ignores enemy Sentinels. With P2's
#// Echo Base Defender (SOR_098, Sentinel) present, Fennec can attack the enemy base directly for 3.
## GIVEN
CommonSetup: rrw/rrk/{myLeader:ASH_002:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_098:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P2BASEDMG:3
P2GROUNDARENACOUNT:1

---

# NoReadyFriendlyUnit_ActionUnavailable_LeaderStaysReady
#// ASH_002 Fennec Shand — "Action [1 resource, Exhaust, exhaust a friendly unit]: Play a unit from your
#// hand (paying its cost). It enters play ready."
#// "Exhaust a friendly unit" is a mandatory COST, and only a READY unit can be exhausted. With no ready
#// friendly unit the cost cannot be paid, so the action is unavailable.
#// ⚠ USER RULING (2026-08-17, given for HMW_010 Tarfful and general to the family): a player may NOT
#// SOFT PASS with a leader Action whose costs cannot all be paid. An unpayable cost makes the action
#// UNAVAILABLE — the leader must stay READY rather than exhausting for no effect. The engine's own
#// SWULeaderActionAffordable doc-comment already stated this ("an unaffordable action must be a no-op,
#// not a soft pass that spends the leader"); these cards simply were not gated.
#// ⚠ RED before the fix: the leader exhausted and the resource was spent for nothing.
#// ⚠ SCOPE: this gates the COST only. "Play a unit from your hand" is the EFFECT and is deliberately NOT
#// gated (CR 6.4.587.c — the same reasoning the codebase already applies to SHD_017 Lando): exhausting a
#// friendly unit changes game state, so the action stays legal with an empty hand.

## GIVEN
CommonSetup: yyk/bgw/{myLeader:ASH_002;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:3
P1NODECISION

---

# OnlyEXHAUSTEDFriendlyUnits_StillUnavailable
#// ASH_002 Fennec Shand — a friendly unit exists but is already EXHAUSTED, so it cannot pay the
#// "exhaust a friendly unit" cost. The action is still unavailable.
#// This is the sharper half: a gate that merely checks "do I control a unit" passes the section above
#// and fails here.

## GIVEN
CommonSetup: yyk/bgw/{myLeader:ASH_002;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1GroundArena: SOR_128:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:3
P1NODECISION

---

# EmptyHand_Front_StillUsable_PaysEveryCost
#// CR 6.4.587.c (owner go 2026-11-04, "do as much as you can" — an Action like this is a legitimate soft pass): the hand
#// unit is the EFFECT, not a cost. With an empty hand the front Action is still usable and ALL THREE costs are paid —
#// 1 resource, the leader's Exhaust, and the friendly unit's exhaust — then nothing is played.
#// RED before the fix: the closure bailed on the empty hand BEFORE paying, so the resource stayed ready and the
#// Stormtrooper stayed ready (only the leader, exhausted by the framework, paid).
## GIVEN
CommonSetup: yyk/bgw/{myLeader:ASH_002;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
P1RESAVAILABLE:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:1
P1NODECISION

---

# EmptyHand_Deployed_StillUsable_PaysEveryCost
#// Deployed side, same rule: [1 resource, exhaust a friendly unit] are paid with an empty hand. Two ready friendly units
#// (the Dark Trooper and Fennec herself), so the cost pick is a real choice; Fennec has no self-[Exhaust] and stays ready.
## GIVEN
CommonSetup: brw/brk/{myLeader:ASH_002:1:1:1;myBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Resources: 6
## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:ASH_002
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENACOUNT:2
P1NODECISION

---

# DeclineHandUnit_CostsPaid_UnitStaysInHand
#// Play-from-HAND is always declinable (the hand is hidden): with exactly ONE playable unit the pick is a MAY, not an
#// auto-resolve. Declining keeps the Marine in hand, and every cost is still paid.
#// RED before the fix: the lone hand unit auto-played (PASSPARAMETER), so "-" had no decision to answer.
## GIVEN
CommonSetup: yyk/bgw/{myLeader:ASH_002;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:2
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# HandOffer_IsAMay_LeftPending
#// The OFFER itself: after the costs, the lone hand unit is offered as a MAY-choose (pending, not auto-played).
## GIVEN
CommonSetup: yyk/bgw/{myLeader:ASH_002;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1DECISIONTYPE:MZMAYCHOOSE
P1SELECTABLEEXACT:myHand-0
P1GROUNDARENACOUNT:1

---

# ZeroResources_Front_Unavailable_LeaderStaysReady
#// The [1 resource] is a COST, so with nothing to pay it the front Action is unavailable — the leader must stay READY
#// (owner ruling 2026-08-17: no soft pass on an unpayable cost). The resource is now paid centrally
#// ($leaderActionResourceCosts), so SWULeaderActionAffordable refuses it.
#// RED before the fix: the front gate never checked resources; the leader exhausted and nothing else happened.
## GIVEN
CommonSetup: yyk/bgw/{myLeader:ASH_002;myResources:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1
P1NODECISION

---

# ZeroResources_Deployed_Unavailable
#// Deployed side: the [1 resource] cost is unpayable → the Action is unavailable; nothing exhausts.
## GIVEN
CommonSetup: brw/brk/{myLeader:ASH_002:1:1:1;myBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_128
WithP1Resources: 0
## WHEN
- P1>UseUnitAbility:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1HANDCOUNT:1
P1NODECISION

---

# CreditPaysTheResourceCost_Front
#// CR 3.13: a Credit token pays a COST. With 0 ready resources and 1 Credit the front Action is usable and the Credit
#// pays the [1 resource]. RED before the fix: the closure paid with a bare SWUExhaustResources, which never spends a
#// Credit, so the action fizzled after exhausting the leader. Empty hand so the payment is the only thing observed.
## GIVEN
CommonSetup: yyk/bgw/{myLeader:ASH_002;myResources:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Credits: 1
WithP1GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1CREDITCOUNT:0
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# PlayUnitReady_AcrossRequestBoundary
#// Request-boundary cell: the hand pick is answered in a fresh request; the unit still enters READY ($gForceEnterReady
#// is set inside ASH_002#1, the same request as the play). Same fixture as PlayUnitReady: the Marine pays +2 for
#// Heroism (off-aspect under this leader), so 6 − 1 Action cost − 4 Marine = 1.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_002}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1HANDCOUNT:0
P1RESAVAILABLE:1
