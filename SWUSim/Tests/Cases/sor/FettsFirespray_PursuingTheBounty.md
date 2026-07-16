# Action_ExhaustNonUniqueUnit
#// SOR_184 Fett's Firespray — Action [2 resources]: Exhaust a non-unique unit. Firespray (in play, no
#// self-exhaust cost) pays 2 resources to exhaust the non-unique enemy SOR_046; Firespray stays READY.

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
