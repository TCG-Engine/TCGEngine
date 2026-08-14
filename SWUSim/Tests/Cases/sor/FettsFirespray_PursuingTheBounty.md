# Action_ExhaustNonUniqueUnit
#// SOR_184 Fett's Firespray — Action [2 resources]: Exhaust a non-unique unit. Firespray (in play, no
#// self-exhaust cost) pays 2 resources to exhaust the non-unique enemy SOR_046; Firespray stays READY.
#// COVERAGE: offer=Action_OfferPool_NonUniquesBothSides (target pool asserted pending: uniques and
#//           the Firespray itself excluded, friendly + enemy non-uniques in) · reqboundary=
#//           Action_UsableWhileExhausted_Repeatable (two uses across separate serialized actions) ·
#//           control=N/A (both the WhenPlayed title check and the action pool already scan BOTH
#//           players' cards; no seat-bound per-unit marker involved) · boundary pair=
#//           Action_ExhaustNonUniqueUnit vs Action_Unaffordable_NoOp (2 vs 1 ready resources) +
#//           WhenPlayed_BobaLeader_EntersReady vs WhenPlayed_NoFett_EntersExhausted ·
#//           decline=N/A (neither ability has a "you may"; the action's target choose is mandatory
#//           once paid, and the WhenPlayed ready is automatic)

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_184:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Resources: 3

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:READY
P1RESAVAILABLE:1

---

# Action_Unaffordable_NoOp
#// SOR_184 Fett's Firespray — the Action costs 2 resources; with only 1 ready resource it's a full
#// no-op: the enemy unit stays READY and resources are unchanged.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_184:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Resources: 1

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:1

---

# WhenPlayed_BobaLeader_EntersReady
#// SOR_184 Fett's Firespray — When Played: if you control Boba Fett or Jango Fett, ready this unit.
#// P1's leader IS Boba Fett (SOR_015) → Firespray (Space) enters READY instead of the default exhausted.

## GIVEN
CommonSetup: ryk/brw/{
  myLeader:SOR_015;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_184
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:READY

---

# WhenPlayed_BobaUnit_EntersReady
#// SOR_184 Fett's Firespray — the "control Boba Fett" check also sees a Boba Fett UNIT in play (not
#// just the leader). P1's leader is Thrawn (not Boba), but a Boba Fett unit (SOR_179) is in play →
#// Firespray enters READY.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Hand: SOR_184
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:READY

---

# WhenPlayed_NoFett_EntersExhausted
#// SOR_184 Fett's Firespray — without a Boba/Jango Fett you control, the WhenPlayed does nothing and
#// Firespray enters EXHAUSTED (CR default). Thrawn (SOR_016, Cunning/Villainy) covers the cost but is
#// not Boba/Jango.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_184
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:EXHAUSTED

---

# WhenPlayed_ShdBobaLeader_EntersReady
#// SOR_184 Fett's Firespray — the "control Boba Fett" check matches by TITLE, so the SHD_008 Boba Fett
#// leader (undeployed, a different printing than SOR_015) also satisfies it: Firespray enters READY.
#// SHD_008's own "when you play a unit with 1+ keywords" trigger stays silent (Firespray has none).

## GIVEN
CommonSetup: yyk/brw/{
  myLeader:SHD_008;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_184
WithP1Resources: 10

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:READY

---

# WhenPlayed_ShdBobaLeaderDeployed_EntersReady
#// SOR_184 Fett's Firespray — the SHD_008 Boba Fett leader DEPLOYED as a ground unit still counts for
#// the title check (leader-unit form). Firespray enters READY.

## GIVEN
CommonSetup: yyk/brw/{
  myLeader:SHD_008:1:1;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_184
WithP1Resources: 10

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:READY

---

# WhenPlayed_BobaLeaderDeployed_EntersReady
#// SOR_184 Fett's Firespray — SOR_015 Boba Fett deployed as a leader UNIT (not just an undeployed
#// leader) satisfies "you control Boba Fett": Firespray enters READY.

## GIVEN
CommonSetup: ryk/brw/{
  myLeader:SOR_015:1:1;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_184
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:READY

---

# WhenPlayed_JangoUnit_EntersReady
#// SOR_184 Fett's Firespray — the check is "Boba Fett OR Jango Fett". A Jango Fett unit in play
#// (SHD_138, no Fett leader) is enough: Firespray enters READY.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_138:1:0
WithP1Hand: SOR_184
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_184
P1SPACEARENAUNIT:0:READY

---

# WhenPlayed_BobaPilotUpgrade_EntersReady
#// SOR_184 Fett's Firespray — "as a leader OR unit" includes a Boba Fett attached as a Pilot UPGRADE:
#// JTL_189 Boba Fett sits on the friendly A-Wing (SOR_141) as an upgrade, no Fett leader and no Fett
#// unit in an arena slot. Firespray (seated second in space) still enters READY.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:JTL_189
WithP1Hand: SOR_184
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:1:CARDID:SOR_184
P1SPACEARENAUNIT:1:READY

---

# Action_OfferPool_NonUniquesBothSides
#// SOR_184 Fett's Firespray — the action's target pool is every NON-unique unit in play on BOTH
#// sides: friendly SOR_032 and both enemy troopers are offered; the unique Jango (SHD_138) and the
#// unique Firespray itself are excluded. The choose is left pending so the offer itself is the
#// assertion.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SHD_138:1:0 SOR_032:1:0]
WithP1SpaceArena: SOR_184:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_098:1:0]
WithP1Resources: 3

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0&theirGroundArena-1

---

# Action_UsableWhileExhausted_Repeatable
#// SOR_184 Fett's Firespray — the action has NO exhaust in its cost, so an EXHAUSTED Firespray can
#// still use it, and it repeats as long as resources last: two uses in the same phase exhaust both
#// enemy troopers for 2 resources each (5 → 1 ready left). Firespray stays exhausted throughout.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_184:0:0
WithP2GroundArena: [SOR_095:1:0 SOR_098:1:0]
WithP1Resources: 5

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1SPACEARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:1

---

# Action_ExhaustedUnitStillLegalTarget
#// SOR_184 Fett's Firespray — per CR an already-exhausted non-unique unit is still a legal choice for
#// "exhaust a non-unique unit"; the exhaust simply changes nothing, and the [2 resources] cost is paid
#// regardless. With every non-unique already exhausted the choose still resolves (single candidate
#// auto-resolution or an explicit pick — here two exhausted candidates, explicit pick).

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_184:1:0
WithP2GroundArena: [SOR_095:0:0 SOR_098:0:0]
WithP1Resources: 3

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1RESAVAILABLE:1
P1SPACEARENAUNIT:0:READY
