# Front_AttacksWithADamagedHeroismUnit_GritCountsItsDamage
#// HMW_006 Omega, Close Your Eyes and Focus — Leader, [Vigilance][Heroism], Clone, cost 5 (deployed 2/7).
#//   FRONT     Action [1 resource, Exhaust]: Attack with a Heroism unit. It gains Grit for this attack.
#//             Epic Action: If you control 5 or more resources, deploy this leader.
#//   DEPLOYED  Other friendly Heroism units gains Grit.
#// Grit: "This unit gets +1/+0 for each damage on it."
#//
#// COVERAGE: offer=Front_Offer_ReadyFriendlyHeroismUnitsOnly (the deployed side selects nothing — a
#//                 continuous grant)
#//           decline=N/A (structural — no "may" on either side; the front's no-target case is the soft
#//                   pass, Front_NoHeroismUnit_SoftPass_CostStillPaid)
#//           boundary=N/A (structural — Grit scales with damage; no threshold on the card)
#//           control=N/A (structural — a leader can't be taken control of; "friendly" is read per unit, and
#//                   the Team Suns section covers a friendly unit Omega's controller does not control)
#//           reqboundary=Front_AcrossARequestBoundary + Deployed_GrantHoldsAcrossARequestBoundary
#//           modes=2P,TeamSuns (the deployed text says "other FRIENDLY Heroism units":
#//                 Deployed_TeamSuns_ATeammatesHeroismUnitGainsGrit) · TwinSuns=N/A (no player reference) ·
#//                 the FRONT is self-only in every format (you attack with a unit you control)
#//           epic-deploy=N/A (generic — SWUDeployLeader gates on the printed cost, 5)
#//
#// "Heroism" is an ASPECT, not a trait: read off the unit's aspect icons.
#// Front precedent: TWI_172 Grim Resolve ("Attack with a non-leader unit. It gains Grit for this attack.")
#// — an attack-duration GRIT grant, then the attack.
#//
#// This section: SOR_095 Battlefield Marine (3/3, Command/Heroism) carries 2 damage. With Grit it swings
#// for 3 + 2 = 5 into the base. Afterwards it has NO Grit and reads 3 again — "for this attack", and still
#// the same phase, so a phase-long grant would fail here. Poggle-style soft costs: 1 resource spent,
#// Omega exhausted.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;myLeader:HMW_006:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1NODECISION

---

# Front_Offer_ReadyFriendlyHeroismUnitsOnly
#// The attacker pool: units YOU control, READY (no "even if exhausted"), with the Heroism aspect.
#//   myGroundArena-0  SOR_095, ready, Heroism          → in
#//   myGroundArena-1  SOR_046, EXHAUSTED, Heroism      → out
#//   myGroundArena-2  SEC_080, ready, Villainy         → out
#//   mySpaceArena-0   SOR_237, ready, Heroism (space)  → in
#//   theirGroundArena-0  an ENEMY Heroism unit         → out

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;myLeader:HMW_006:1}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_046:0:0 SEC_080:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# Front_NoHeroismUnit_SoftPass_CostStillPaid
#// No ready Heroism unit: the cost (1 resource + exhaust) is state-changing, so the Action is still legal
#// and resolves to nothing — no attack, no prompt.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;myLeader:HMW_006:1}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1NODECISION

---

# Front_NoResource_CannotBeUsed
#// The [1 resource] half: with nothing to pay it, the Action is unavailable — Omega stays ready.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0;myLeader:HMW_006:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY

---

# Front_AcrossARequestBoundary
#// Two ready Heroism units, so the pick is a real prompt; the boundary sits between the Action and the
#// answer. The chosen damaged Marine still swings with Grit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;myLeader:HMW_006:1}
P1OnlyActions: true
WithP1GroundArena: [SOR_046:1:0 SOR_095:1:2]

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:READY

---

# Front_TurnPassesExactlyOnce
#// No P1OnlyActions: the turn really alternates. The attack owns the Action's close; one Action → P2's turn.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;myLeader:HMW_006:1}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:5
TURNPLAYER:2

---

# Deployed_RealDeploy_OtherFriendlyHeroismUnitGainsGrit
#// The real deploy path. Once Omega is deployed, the damaged Marine has Grit and reads 3 + 2 = 5.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myLeader:HMW_006}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:CARDID:HMW_006

---

# Deployed_Scope_NotHerself_NotNonHeroism_NotEnemies
#// Who does NOT gain it, on one board, every body carrying 2 damage so a wrong grant shows in POWER:
#//   Omega herself (Heroism, but "OTHER")         → 2, no Grit
#//   SEC_080, friendly but Villainy               → 3, no Grit
#//   an ENEMY SOR_095 (Heroism, but not friendly)  → 3, no Grit
#// …and the friendly Heroism Marine does (5, Grit). Omega is pre-deployed at index 2.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_006:1:1:1:2}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:2 SEC_080:1:2]
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:2:CARDID:HMW_006
P1GROUNDARENAUNIT:2:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:2:POWER:2
P2GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P2GROUNDARENAUNIT:0:POWER:3

---

# Deployed_GrantedGritDealsTheBonusInCombat
#// The grant is real power, not just a keyword badge: the damaged Marine attacks the base for 5.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_006:1:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# Deployed_AuraEndsWhenOmegaIsDefeated
#// A while-in-play grant must END: P2's SEC_080 attacks Omega (2/7, 6 damage) and defeats her — she
#// returns to the leader zone — and the Marine loses Grit, reading 3 again.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_006:1:1:1:6}
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:1

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:3

---

# Deployed_OmegaLostHerAbilities_NoGrant
#// The grant is Omega's ABILITY. P2 plays SOR_138 Force Lightning on her ("It loses all abilities for
#// this phase"); P2 controls no Force unit, so its second half does nothing. The Marine has no Grit.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_006:1:1:1;theirResources:1}
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:2
WithP2Hand: SOR_138

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:3

---

# Deployed_TeamSuns_ATeammatesHeroismUnitGainsGrit
#// TEAM SUNS: "friendly" is the TEAM. Omega deployed on seat 1 grants Grit to seat 3's (teammate's)
#// damaged Heroism Marine, and to neither opponent's.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:HMW_006:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_095:1:2
WithP4GroundArena: SOR_095:1:2

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:HASKEYWORD:Grit
P3GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P4GROUNDARENAUNIT:0:NOTKEYWORD:Grit

---

# Deployed_GrantHoldsAcrossARequestBoundary
#// The deploy is one action and the attack is the next, in a fresh process: the grant is recomputed from
#// the board, not cached at deploy time.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;myLeader:HMW_006}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>DeployLeader
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
