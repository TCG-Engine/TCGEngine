# Finn_Deployed_OnAttack_DefeatUpgradeShield
#// SHD_003 Finn (deployed On Attack) — "You may defeat a friendly upgrade on a unit. If you do, give a
#// Shield token to that unit." Deployed (5 resources), Finn attacks the base; his On Attack defeats
#// SOR_069 on SOR_046 and shields it.
#// COVERAGE: offer=Finn_Front_OfferIsFriendlyUnitsWearingAnUpgrade (only friendly units actually wearing an
#//           upgrade; a token upgrade counts) · reqboundary=Finn_Front_DefeatAndShield_SurviveRequestBoundary
#//           (a boundary at BOTH the host pick and the which-upgrade pick) · control=
#//           Finn_Front_ShieldGoesToTheHostP1ControlsButP2Owns (friendly is read off the CONTROLLER) plus
#//           Finn_Front_DefeatAStolenTokenUpgrade_ForAShield (a token whose control changed hands) ·
#//           boundary=Finn_Front_DefeatOpponentCreatedShield_HostGetsAFreshShield vs
#//           Finn_Front_DefeatThePrintedUpgrade_OpponentsShieldSurvives (1 shield + 0 discard vs 2 shields
#//           + 1 discard on an identical board), and the "if you do" negative in
#//           Finn_Front_NoFriendlyUpgradeAnywhere_NoDecisionNoShield ·
#//           decline=Finn_Deployed_OnAttack_Decline_NoShield (deployed side only — the front action is
#//           mandatory-shaped and simply fizzles with nothing to defeat, which the no-upgrade section
#//           asserts via P1NODECISION).

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1

---

# Finn_Front_DefeatUpgradeShield
#// SHD_003 Finn (front Action [Exhaust]) — "Defeat a friendly upgrade on a unit. If you do, give a Shield
#// token to that unit." SOR_046 wears SOR_069; using Finn's Action defeats the upgrade and shields SOR_046.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1

---

# Finn_Front_OfferIsFriendlyUnitsWearingAnUpgrade
#// SHD_003 Finn — the host offer is exactly the friendly units that are actually WEARING an upgrade. Three
#// friendly units are on the board; the middle one (SOR_095, index 1) has nothing attached and is left out,
#// while index 0 (a printed upgrade) and index 2 (an Experience TOKEN) are both offered — a token upgrade
#// is an upgrade for this purpose. Two legal hosts keep the choice pending so the offer can be read.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_046:1:0 SOR_095:1:0 SOR_034:1:0]
WithP1GroundArenaUpgrade: [0:SOR_069 2:SOR_T01]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:2:SHIELDCOUNT:0
P1DISCARDCOUNT:0

---

# Finn_Front_NoFriendlyUpgradeAnywhere_NoDecisionNoShield
#// SHD_003 Finn — "Defeat a friendly upgrade on a unit. IF YOU DO, give a Shield token to that unit." is a
#// real gate: with no upgrade on any friendly unit there is nothing to defeat, so no picker is raised and
#// NO shield appears. The action still costs Finn his exhaust.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:0
P1LEADER:EXHAUSTED

---

# Finn_Front_DefeatOpponentCreatedShield_HostGetsAFreshShield
#// SHD_003 Finn — a Shield token IS an upgrade, so Finn can consume one that the OPPONENT put on your own
#// unit. P2's LAW_091 Val ("When Defeated: Give a Shield token to an enemy unit") is killed by P1's SOR_046,
#// and P2's reaction shields that very attacker. Finn then defeats the shield and the "if you do" rider
#// immediately grants a new one, so the net shield count holds at 1 and nothing reaches the discard
#// (defeating a TOKEN is a cease, not a discard). The paired section below picks the OTHER upgrade on the
#// same board and ends on 2 shields + 1 discard — that contrast is what proves the shield really went.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: LAW_091:1:2

## WHEN
- P1>AttackGroundArena:0:0
- P2>Drain
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-1

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P1DISCARDCOUNT:0
P2GROUNDARENACOUNT:0

---

# Finn_Front_DefeatThePrintedUpgrade_OpponentsShieldSurvives
#// SHD_003 Finn — the discriminating half of the pair above. Identical board; Finn defeats the PRINTED
#// upgrade instead of the opponent-created Shield token. The old shield is untouched and the rider adds a
#// second one (2 shields), and the printed upgrade lands in P1's discard (1) — whereas defeating the shield
#// leaves 1 shield and an empty discard.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: LAW_091:1:2

## WHEN
- P1>AttackGroundArena:0:0
- P2>Drain
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_069

---

# Finn_Front_DefeatAStolenTokenUpgrade_ForAShield
#// SHD_003 Finn — a token upgrade that P2 created for P2's OWN unit and that P1 then took control of is
#// still a friendly upgrade for Finn once it sits on a friendly unit. JTL_056 Hondo Ohnaka's On Attack
#// moves the enemy Experience token onto P1's SOR_046 (power 3 → 4); Finn then defeats it (power back to 3)
#// and shields the host. The token ceases rather than being discarded, so neither discard pile grows.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_046:1:0 JTL_056:1:0]
WithP2GroundArena: SOR_034:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0.u0
- P1>AnswerDecision:myGroundArena-0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:0
P2DISCARDCOUNT:0

---

# Finn_Front_ShieldGoesToTheHostP1ControlsButP2Owns
#// SHD_003 Finn — "friendly" is read off the host unit's CONTROLLER, not its owner. P1 controls a SOR_046
#// that P2 OWNS (the end state after a take-control effect); the upgrade on it is offered, is defeated into
#// P1's discard, and the Shield token lands on that P2-owned unit because it is the one P1 controls.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_046:2
WithP1GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1

---

# Finn_Front_DefeatAndShield_SurviveRequestBoundary
#// SHD_003 Finn — the host pick AND the which-upgrade pick are each answered in a separate request, so the
#// staged upgrade list and the "then shield that unit" continuation both have to survive a serialize /
#// read-back round trip. Same board and same outcome as
#// Finn_Front_DefeatOpponentCreatedShield_HostGetsAFreshShield, with a boundary forced at both points.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: [0:SOR_069 0:SOR_T01]

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myTempZone-1

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:3
P1DISCARDCOUNT:0

---

# Finn_Deployed_OnAttack_Decline_NoShield
#// SHD_003 Finn — the deployed side is "YOU MAY defeat a friendly upgrade", a decline branch the front's
#// mandatory wording does not have. Finn deploys, attacks the base for 4, and declines the On Attack offer:
#// the friendly upgrade stays attached, nothing is discarded, and no shield is created.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P1DISCARDCOUNT:0
P2BASEDMG:4

---

# Finn_Deployed_OnAttack_DefeatATokenUpgrade_ForAShield
#// SHD_003 Finn — the deployed On Attack reaches token upgrades just as the front action does: the friendly
#// SOR_046 wears an Experience token (power 3 → 4), Finn attacks the base, and the On Attack defeats the
#// token (power back to 3) and shields the host. The token ceases, so nothing reaches the discard.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1DISCARDCOUNT:0
P2BASEDMG:4

---

# Front_YourOwnUpgradeOnAnENEMYUnitIsAFriendlyUpgrade
#// SHD_003 — USER RULING (2026-08-15): an upgrade is OWNED AND CONTROLLED BY WHOEVER PLAYED IT, and it
#// may be attached to ANY eligible unit, including an opponent's. So "a friendly upgrade" means one YOU
#// control, wherever it sits. P1 plays SOR_071 Electrostaff onto P2's Consular Security Force (legal:
#// "attach to a non-Vehicle unit" names no controller), and Finn's offer includes that ENEMY host —
#// previously the pool scanned P1's arenas only, so P1's own upgrade was unreachable and the ability
#// fizzled entirely.

## GIVEN
CommonSetup: bbw/bbw/{myLeader:SHD_003;myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_071
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEHAS:theirGroundArena-0
