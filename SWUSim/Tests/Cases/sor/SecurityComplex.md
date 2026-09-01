# SecurityComplexEpicAction
#// SOR_019 Security Complex — Base, [Vigilance], HP 25, trait Scarif.
#// "Epic Action: Give a Shield token to a non-leader unit."
#// COVERAGE: offer=ShieldOffer_EveryNonLeaderUnitBothSides_LeaderUnitExcluded (pending
#//           SELECTABLEEXACT over BOTH players' non-leader units; the deployed leader unit is the
#//           excluded target) · reqboundary=RequestBoundary_ShieldLandsOnTheEnemyUnitAcrossTheBoundary ·
#//           control=ControlChange_ShieldsAUnitYouControlButDoNotOwn (owner P2 / controller P1 — the
#//           unqualified "a non-leader unit" reads controller-agnostic) · boundary pair=zero vs one
#//           legal target: NoNonLeaderUnitInPlay_EpicActionFizzlesWithNoPrompt (0 → no prompt) vs
#//           SecurityComplexEpicAction (1 → shielded); the once-per-game use boundary is
#//           EpicActionIsOncePerGame_SecondUseGivesNoSecondShield · decline=N/A — the printed text
#//           carries no "you may" and the handler queues a mandatory MZCHOOSE, so there is no
#//           decline branch to exercise.
#// Note: the handler queues its OWN MZCHOOSE, so even a single candidate stays interactive (no
#// auto-resolve) — every section answers the pick explicitly.
## GIVEN
CommonSetup: brw/grw/{
  myBase:SOR_019
}
SkipPreGame: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1BASE:EPICUSED

---

# TokenUnitIsALegalShieldTarget
#// "Give a Shield token to a NON-LEADER unit" — non-leader excludes leaders, not TOKENS. A bare
#// ["Unit"] filter dropped both (the Open Fire family sweep); the Clone Trooper token must be in the
#// pool beside the real unit, and shielding it sticks.

## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_019}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TWI_T02:1:0 SOR_095:1:0]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# ShieldOffer_EveryNonLeaderUnitBothSides_LeaderUnitExcluded
#// Intended: "Give a Shield token to a NON-LEADER unit" — unqualified by controller, so the pool is
#// every non-leader unit on the table (P1's Marine AND P2's Dark Trooper), while the deployed leader
#// unit is excluded by "non-leader". Three units are seated so the pick cannot auto-resolve; the
#// decision is left PENDING so the offer itself is the assertion.

## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_019; myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# RequestBoundary_ShieldLandsOnTheEnemyUnitAcrossTheBoundary
#// SOR_019 — in production the target prompt ends the request and the answer arrives in a fresh
#// process. The Shield still lands on the chosen ENEMY unit (proving the pool is not friendly-only),
#// the unchosen friendly Marine keeps no Shield, and the Epic Action is spent exactly once.

## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_019; myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseBaseAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1BASE:EPICUSED

---

# ControlChange_ShieldsAUnitYouControlButDoNotOwn
#// SOR_019 — "a non-leader unit" names no owner, so a unit P1 CONTROLS but P2 OWNS (the end state
#// after a take-control effect) is a legal target and the Shield sticks on it. The card's handler
#// queues its own MZCHOOSE, so the single candidate stays interactive rather than auto-resolving —
#// the offer is asserted first, then answered.

## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_019}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SEC_080:2

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1BASE:EPICUSED

---

# NoNonLeaderUnitInPlay_EpicActionFizzlesWithNoPrompt
#// SOR_019 — boundary at zero targets. The only unit on the table is P1's own DEPLOYED LEADER unit,
#// which "non-leader" excludes, so there is nothing to shield: no decision is raised and the leader
#// unit carries no Shield token.

## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_019; myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# EpicActionIsOncePerGame_SecondUseGivesNoSecondShield
#// SOR_019 — "Epic Action" is once per game. After the first use shields the Marine, the base reads
#// EPICUSED and a second UseBaseAbility is a full no-op: the second unit never gets a Shield and no
#// new decision is raised.

## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_019}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myGroundArena-0
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1BASE:EPICUSED
P1NODECISION
