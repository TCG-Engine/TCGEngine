# Debuffs4Power
#// SOR_028 Jedha City (Base) — "Epic Action: Give a non-leader unit -4/-0 for this
#// phase." P1's base is Jedha City; P2's only non-leader unit is Consular Security
#// Force (SOR_046, 3/7). It's the sole target → auto −4/−0: power 3 → floored at 0,
#// HP unchanged at 7.
#// COVERAGE: offer=DebuffOffer_EveryNonLeaderUnitBothSides_LeaderUnitExcluded (pending
#//           SELECTABLEEXACT over BOTH players' non-leader units; the deployed leader unit is the
#//           excluded target) · reqboundary=SimulateRequestBoundary_Debuff4PowerAcrossBoundary ·
#//           control=ControlChange_DebuffsAUnitYouControlButDoNotOwn (owner P2 / controller P1 — the
#//           unqualified "a non-leader unit" reads controller-agnostic) · boundary pair=Debuffs4Power
#//           (power 3 → clamped at 0) vs HighPowerUnit_LosesExactlyFour_NoFloorInvolved (power 9 → 5),
#//           plus the zero-target edge NoNonLeaderUnitInPlay_EpicActionFizzlesWithNoPrompt and the
#//           duration edge DebuffExpiresAtEndOfPhase · decline=N/A — no "you may" on the printed
#//           text, the Epic Action's pick is a mandatory MZCHOOSE with no pass option.

## GIVEN
CommonSetup: yrw/grw/{
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:7
P1BASE:EPICUSED

---

# SimulateRequestBoundary_Debuff4PowerAcrossBoundary
#// SOR_028 Jedha City — with TWO enemy non-leader units the Epic Action's target pick stays a real
#// prompt (Debuffs4Power's lone target auto-resolves), and in production that prompt ends the request:
#// the answer arrives in a fresh process. The chosen SOR_046 still takes -4/-0 for this phase (power
#// 3 → floored at 0, HP 7), the unchosen SOR_095 is untouched, and the Epic Action is still spent.

## GIVEN
CommonSetup: yrw/grw/{
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:1:HP:3
P1BASE:EPICUSED

---

# DebuffOffer_EveryNonLeaderUnitBothSides_LeaderUnitExcluded
#// Intended: "Give a NON-LEADER unit -4/-0" names no controller, so the pool spans BOTH players —
#// P1's own Marine and P2's Security Force — while P1's deployed LEADER unit is excluded by
#// "non-leader". Three units are on the table so the pick cannot auto-resolve; the decision is left
#// PENDING so the offer itself is the assertion.

## GIVEN
CommonSetup: yrw/grw/{
  myBase:SOR_028;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# HighPowerUnit_LosesExactlyFour_NoFloorInvolved
#// SOR_028 — boundary pair against Debuffs4Power (power 3 → floored at 0). Here the sole target is a
#// Blizzard Assault AT-AT (9/9): -4/-0 lands in full → power 5, HP untouched at 9. The floor is only a
#// clamp, not the subtraction.

## GIVEN
CommonSetup: yrw/grw/{
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:HP:9
P1BASE:EPICUSED

---

# ControlChange_DebuffsAUnitYouControlButDoNotOwn
#// SOR_028 — "a non-leader unit" is controller-agnostic, so a unit P1 CONTROLS but P2 OWNS (the end
#// state after a take-control effect) is a legal and reachable target. It is the only non-leader unit
#// on the table, so the pick auto-resolves (no prompt) and the AT-AT reads 5/9 under P1's control.

## GIVEN
CommonSetup: yrw/grw/{
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_088:2

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_088
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
P1NODECISION
P1BASE:EPICUSED

---

# DebuffExpiresAtEndOfPhase
#// SOR_028 — the debuff lasts "for THIS PHASE". After the action phase ends and the game crosses
#// regroup, the AT-AT is back to its printed 9/9. Both decks are seeded so the regroup draws do not
#// ping the bases; the Epic Action stays spent.

## GIVEN
CommonSetup: yrw/grw/{
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_088:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:POWER:9
P2GROUNDARENAUNIT:0:HP:9
P1BASEDMG:0
P2BASEDMG:0
P1BASE:EPICUSED

---

# NoNonLeaderUnitInPlay_EpicActionFizzlesWithNoPrompt
#// SOR_028 — boundary at zero targets. The only unit on the table is P1's own DEPLOYED LEADER unit,
#// which "non-leader" excludes, so there is nothing to debuff: no decision is raised and the leader
#// unit keeps its printed power.

## GIVEN
CommonSetup: yrw/grw/{
  myBase:SOR_028;
  myLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1NODECISION
