# Deployed_OnAttack_GivesExperience
#// COVERAGE: offer=Deployed_OnAttack_OfferPool (pending P1SELECTABLEEXACT — exactly the OTHER unique
#//           units on both sides, incl. the enemy deployed leader unit; excludes Hera herself and all
#//           non-uniques) · reqboundary=Deployed_OnAttack_SurvivesRequestBoundary · control=N/A (the
#//           waiver keys off "cards YOU play" — the payer is always the controller of the play, and
#//           the Experience pool already spans both controllers; no per-unit marker outlives a
#//           resolution) · boundary=exact-resource plays on both sides of the waiver:
#//           IgnoresAspectPenalty_OnSpectre (waived, exactly cost) vs NonSpectreUnit_PenaltyApplies /
#//           Deployed_NonSpectreUnit_PenaltyApplies (unwaived, exactly cost+2) — a 1-resource-short
#//           unwaived play no-ops per NonHeraLeader_PenaltyApplies · decline=Deployed_OnAttack_Decline
#// SOR_008 Hera (deployed Leader Unit, 4/6) — "On Attack: You may give an Experience token to another
#// unique unit." P1 deploys Hera (6 resources) and attacks the base; On Attack, she gives an Experience
#// token to the other unique unit (Zeb, in space → UPGRADECOUNT 1). Her 4 power hits the base.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: SOR_146:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:4

---

# IgnoresAspectPenalty_OnSpectre
#// SOR_008 Hera Syndulla (leader) — "Ignore the aspect penalty on SPECTRE cards you play." P1's leader is
#// Hera (Command/Heroism). SOR_146 Zeb (Spectre, Aggression/Heroism, cost 5) would normally cost 7 (the
#// Aggression pip is off-aspect, +2), but Hera waives it — so with exactly 5 resources Zeb enters play.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_146
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0

---

# NonHeraLeader_PenaltyApplies
#// SOR_008 Hera — control: with a non-Hera leader that has the SAME aspects (SOR_009, Command/Heroism),
#// Zeb's off-aspect Aggression pip still adds +2 → cost 7. With only 5 resources the play is a silent
#// no-op (Zeb stays in hand), proving the waiver is Hera-specific, not just the shared Heroism aspect.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_146
WithP1Resources: 5

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# IgnoresAspectPenalty_SpectreEvent
#// SOR_008 Hera (undeployed) — the waiver covers SPECTRE EVENTS too. SOR_151 Karabast (Aggression/
#// Heroism, cost 2, trait Spectre) is off-aspect on Aggression (+2 → 4 normally); Hera waives it, so
#// with EXACTLY 2 resources it resolves: the damaged (2) Battlefield Marine deals 2+1=3 to the Pyke
#// Sentinel (2/3), defeating it. Both picks are single-candidate and auto-resolve.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_151
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# NonSpectreUnit_PenaltyApplies
#// SOR_008 Hera (undeployed) — the waiver is SPECTRE-only: SOR_164 Wampa (Aggression, cost 4, no
#// Spectre trait) still pays the +2 Aggression penalty → 6. With exactly 6 resources the play lands
#// and leaves 0 available, proving the full 6 was charged.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_164

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# IgnoresAspectPenalty_SpectrePilotUpgrade
#// SOR_008 Hera (undeployed) — the waiver also covers a SPECTRE unit played WITH PILOTING as an
#// upgrade. JTL_045 Hera Syndulla (Piloting [2, Vigilance/Heroism], trait Spectre) is off-aspect on
#// Vigilance (+2 → 4 normally); the leader waives it, so with exactly 2 resources she attaches to the
#// friendly Vehicle SHD_042 Concord Dawn Interceptors.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: JTL_045
WithP1SpaceArena: SHD_042:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# SpectreEvent_CostIncreaseStillApplies
#// SOR_008 Hera (undeployed) — the waiver removes only the ASPECT penalty, not other cost increases.
#// P2's SOR_034 Del Meeko makes each event an opponent plays cost 1 more, so Karabast costs 2+1=3
#// (Aggression penalty still waived). With exactly 3 resources it resolves: the damaged (2) marine
#// auto-picks, then P1 chooses the Pyke Sentinel from the two enemies and defeats it (3 damage).

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_151
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SHD_029:1:0
WithP2GroundArena: SOR_034:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_034
P1RESAVAILABLE:0

---

# Deployed_IgnoresAspectPenalty_SpectreUnit
#// SOR_008 Hera DEPLOYED — the waiver persists on the leader-unit side. SOR_142 Sabine Wren
#// (Aggression/Heroism, cost 2, trait Spectre) is off-aspect on Aggression (+2 → 4 normally); with
#// exactly 2 resources she enters play beside the deployed Hera.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_142

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Deployed_IgnoresAspectPenalty_SpectreEvent
#// SOR_008 Hera DEPLOYED — Spectre EVENT waiver on the leader-unit side. Karabast (cost 2 after the
#// waiver) resolves with exactly 2 resources: the undamaged deployed Hera (only friendly unit) deals
#// 0+1=1 to the Pyke Sentinel. Both picks auto-resolve (single candidates).

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_151
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# Deployed_NonSpectreUnit_PenaltyApplies
#// SOR_008 Hera DEPLOYED — the waiver stays Spectre-only on the leader-unit side: Wampa still costs
#// 4+2=6. With exactly 6 resources the play lands and leaves 0 available.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_164

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Deployed_OnAttack_OfferPool
#// SOR_008 Hera DEPLOYED — On Attack: "You may give an Experience token to ANOTHER UNIQUE unit."
#// The pool is exactly the OTHER unique units on either side — P1's Yoda (myGroundArena-0) and P2's
#// deployed leader unit Rey (theirGroundArena-1) — excluding Hera herself (the attacker) and both
#// non-unique units (P1's marine at myGroundArena-1, P2's Wampa at theirGroundArena-0). Left pending
#// so the pool is the assertion; resolution covered by Deployed_OnAttack_GivesExperience.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirLeader:SHD_004:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:2:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-1

---

# Deployed_OnAttack_Decline
#// SOR_008 Hera DEPLOYED — On Attack is "you may": declining ('-') gives no Experience token to the
#// lone unique candidate (Yoda), and the attack still lands for Hera's 4.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_045:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Deployed_OnAttack_SurvivesRequestBoundary
#// SOR_008 Hera DEPLOYED — the On Attack Experience grant is answered across a decision boundary; the
#// pending choice and its pool must survive a serialize/decode round-trip before the answer lands.
#// Yoda (only other unique unit) gets the Experience token and the attack still hits for 4.

## GIVEN
CommonSetup: ggw/brw/{
  myLeader:SOR_008;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_045:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
