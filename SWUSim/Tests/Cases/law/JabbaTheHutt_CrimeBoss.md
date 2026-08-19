# Deployed_CreditDefeated_GrantsAmbush
#// LAW_015 Jabba (deployed) — Action: Play an Underworld unit; if you defeated a Credit while paying its
#// cost, that unit gains Ambush this phase. Jabba plays SOR_247 (cost 2); the player defeats a Credit to
#// pay 1 less (1 resource), so SOR_247 enters with Ambush and immediately attacks P2's SOR_247 for 2.
#// (WHEN sequence refined via live TestSchemaStep probing.)

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LAW_015:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Credits: 1
WithP1Hand: SOR_247
WithP2GroundArena: SOR_247:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P2GROUNDARENAUNIT:0:DAMAGE:2
P1CREDITCOUNT:0
P1RESAVAILABLE:1

---

# Deployed_PlayNoCredit_NoAmbush
#// LAW_015 Jabba (deployed leader unit) — Action: Play an Underworld unit from your hand. With NO
#// Credit defeated while paying (the player has no Credit tokens), the played unit does NOT gain Ambush,
#// so it just enters play and makes no entry attack.
#// Jabba (ground idx 0) plays SOR_247 (cost 2, vanilla Underworld) at full cost. P2's SOR_247 is untouched.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LAW_015:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_247
WithP2GroundArena: SOR_247:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:NOTKEYWORD:Ambush
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0

---

# Front_NoUnderworldUnit_NoOp
#// LAW_015 Jabba (undeployed) — the "return a friendly Underworld unit" additional cost is unpayable
#// when no friendly Underworld unit is in play, so the action is a full no-op: the leader stays ready,
#// the resource is kept, no Credit is created, and the player keeps their action.
#// P1's only unit is SEC_080 (Imperial/Droid/Trooper — NOT Underworld).

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LAW_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility:0

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:1
P1CREDITCOUNT:0
P1GROUNDARENACOUNT:1
P1NODECISION

---

# Front_ReturnUnderworldCreateCredit
#// LAW_015 Jabba the Hutt (undeployed leader) — Action [1 resource, Exhaust, return a friendly
#// Underworld unit to its owner's hand]: Create a Credit token.
#// P1 has one friendly Underworld unit (SOR_247) — the return target auto-resolves. After the action:
#// the unit is back in hand, a Credit token exists, the leader is exhausted, and the resource is spent.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LAW_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_247:1:0

## WHEN
- P1>UseLeaderAbility:0

## EXPECT
P1CREDITCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# Front_ReturnUnitThatGainedUnderworldTrait
#// LAW_015 Jabba (undeployed) — the "return a friendly Underworld unit" cost accepts a unit that GAINED
#// the Underworld trait, not just printed Underworld units. SEC_163 Outer Rim Constable (Fringe/Official,
#// NOT natively Underworld) carries LAW_111 Leia's Disguise, which grants it the Underworld trait, so it
#// is a legal return target. Jabba returns it (its upgrade falls off), exhausts, pays 1, and creates a Credit.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LAW_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1GroundArena: SEC_163:1:0
WithP1GroundArenaUpgrade: 0:LAW_111

## WHEN
- P1>UseLeaderAbility:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1CREDITCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:4

---

# Deployed_NoUnderworldInHand_PlayUnavailable_CanStillAttack
#// LAW_015 Jabba (deployed) — the unit-side "Play an Underworld unit from your hand" action is unavailable
#// when the hand holds no Underworld units (SOR_176 ISB Agent = Imperial, SOR_095 Battlefield Marine =
#// Rebel/Trooper): using the ability is a no-op that plays nothing and leaves the hand intact. Jabba's only
#// available play is to attack, dealing his 3 power to P2's base.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LAW_015:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_176 SOR_095]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:2
P1GROUNDARENACOUNT:1
P2BASEDMG:3

---

# Front_PayCostWithCreditToken_NetZero
#// LAW_015 Jabba (front) — the [1 resource] cost may be paid by defeating a Credit token (CR 3.13: "while
#// paying resources you may defeat this token to pay 1 less"). P1 has 3 resources + 1 Credit; it defeats
#// the Credit to pay the cost (no resources spent), returns LAW_124, and creates a Credit — a net-zero
#// Credit swap (1 → defeat → 0 → create → 1).

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_015;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Credits: 1
WithP1GroundArena: LAW_124:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1CREDITCOUNT:1
P1RESAVAILABLE:3
P1GROUNDARENACOUNT:0

---

# Front_PayCostWithCreditToken_ZeroResources
#// LAW_015 Jabba (front) — with ZERO ready resources but 1 Credit, the action is still usable: the Credit
#// covers the [1 resource] cost. P1 defeats its only Credit, returns LAW_124, and creates a Credit (ends
#// with 1 Credit again).

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_015;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1Credits: 1
WithP1GroundArena: LAW_124:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1CREDITCOUNT:1
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0

---

# Front_ReturnCost_StolenUnitGoesToOwnersHand
#// LAW_015 Jabba (undeployed) — the additional cost is "return a FRIENDLY Underworld unit to ITS OWNER'S
#// hand". Friendly means CONTROLLED, not owned: a stolen enemy unit is a legal way to pay, and it goes
#// back to the OPPONENT's hand, not the paying player's. P1 controls JTL_221 Stolen AT-Hauler (Underworld)
#// but P2 owns it; after the action P1's hand is still empty and P2 holds the card.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_015;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1SpaceArenaControlled: JTL_221:2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:1
P1CREDITCOUNT:1
P1RESAVAILABLE:2
P1LEADER:EXHAUSTED

---

# Front_ReturnCost_OnlyFriendlyUnderworldUnitsAreOffered
#// LAW_015 Jabba (undeployed) — the return-a-unit cost offers EXACTLY the friendly Underworld units in
#// play, across both arenas, and nothing else. SOR_247 (ground, Underworld) and JTL_221 (space,
#// Underworld) are offered; SOR_095 Battlefield Marine (friendly but Rebel/Trooper) is excluded by the
#// trait, and P2's LAW_124 Industrious Team (Underworld but ENEMY) is excluded by "friendly". The choice
#// is left pending so the offer itself is what's asserted.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_015;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: [SOR_247:1:0 SOR_095:1:0]
WithP1SpaceArena: JTL_221:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1DECISIONTOOLTIP:Return_a_friendly_Underworld_unit_to_its_owner's_hand
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# Deployed_PlayTargets_OnlyUnderworldUnitsInHandAreOffered
#// LAW_015 Jabba (deployed) — "Play an Underworld unit from your hand" offers EXACTLY the Underworld
#// UNITS in hand. SOR_176 ISB Agent is a unit but Imperial (wrong trait); SHD_229 Ma Klounkee carries the
#// Underworld trait but is an EVENT (wrong type) — both are excluded, leaving SOR_247 (ground) and
#// JTL_221 (space). Two candidates keep the choice pending; with one it would auto-resolve.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_015:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: [SOR_176 SOR_247 JTL_221 SHD_229]

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1DECISIONTOOLTIP:Play_an_Underworld_unit_from_your_hand
P1SELECTABLEEXACT:myHand-1&myHand-2

---

# Deployed_UsableTwiceInAPhase_EvenWhileExhausted
#// LAW_015 Jabba (deployed) — the unit-side Action costs no exhaust, so it is NOT once-per-phase and does
#// not care that Jabba is already exhausted. Jabba starts EXHAUSTED and still plays two Underworld units
#// in the same action phase, each paid partly with a Credit, so each enters with Ambush and attacks
#// immediately: SOR_247 (2/3) trades 2 damage with P2's SOR_247 in the ground arena, then JTL_221 (4/5)
#// trades 4 with P2's JTL_221 in the space arena. Jabba is still exhausted at the end (nothing readied him).
#// Resources: 8 total cost (2+3) less 2 Credits = 3 of 6 spent.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_015:0:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 2
WithP1Hand: [SOR_247 JTL_221]
WithP2GroundArena: SOR_247:1:0
WithP2SpaceArena: JTL_221:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:YES
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:1
P1HANDCOUNT:0
P1CREDITCOUNT:0
P1RESAVAILABLE:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:DAMAGE:2
P1SPACEARENAUNIT:0:CARDID:JTL_221
P1SPACEARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:DAMAGE:4

---

# Deployed_AmbushIsNotKeptWhenBouncedAndReplayed
#// LAW_015 Jabba (deployed) — the Ambush grant belongs to THAT play, not to the card. SOR_247 is played
#// by Jabba's action with a Credit defeated, so it enters with Ambush and immediately attacks P2's
#// SOR_247 (2 damage each way). LAW_246 The Axe Forgets then returns it to hand, and P1 plays it again
#// from hand — still paying with a Credit, but NOT through Jabba's action. It enters without Ambush and
#// makes no entry attack, so P2's unit stays on 2 damage and the replayed copy is undamaged.
#// (Paying with a Credit is not what grants Ambush; being played by Jabba's action is.)

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_015:1:1:1;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Credits: 2
WithP1Hand: [SOR_247 LAW_246]
WithP2GroundArena: SOR_247:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:YES
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:NOTKEYWORD:Ambush
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2
P1CREDITCOUNT:0
P1HANDCOUNT:0
P1RESAVAILABLE:4

---

# Deployed_CreditHeldButNotSpent_NoAmbush
#// COVERAGE addendum (the file's ledger lines sit in frozen sections): decline=this section (the Credit
#//           payment offer is declinable, and the Ambush condition reads "a Credit was DEFEATED", not
#//           "a Credit was available") + Deployed_AmbushOfferIsDeclinable (the granted Ambush attack is
#//           a may, and refusing it costs nothing extra).
#//
#// LAW_015 Jabba (deployed) — the Ambush grant is conditional on a Credit actually being DEFEATED while
#// paying, not on owning one. P1 has a Credit and 2 ready resources and plays SOR_247 (cost 2) through
#// Jabba's action, but declines the Credit at the payment offer and pays the full 2 from resources.
#// The unit enters WITHOUT Ambush and makes no entry attack, and the Credit is still there afterwards.
#// Deployed_PlayNoCredit_NoAmbush is the same outcome with no Credit in existence; this section is the
#// sharper one, because the Credit exists and is offered and the condition still has to read "spent".

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LAW_015:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Credits: 1
WithP1Hand: SOR_247
WithP2GroundArena: SOR_247:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:NOTKEYWORD:Ambush
P2GROUNDARENAUNIT:0:DAMAGE:0
P1CREDITCOUNT:1
P1RESAVAILABLE:0

---

# Deployed_AmbushOfferIsDeclinable
#// LAW_015 Jabba (deployed) — the granted Ambush is an offer ("this unit MAY attack"), not a forced
#// entry attack, so the player can take the keyword and refuse the attack. Same line as
#// Deployed_CreditDefeated_GrantsAmbush — SOR_247 is played through Jabba's action with a Credit
#// defeated, so it enters carrying Ambush — but the attack offer is declined: neither the newcomer nor
#// P2's SOR_247 takes a point of damage, and the action still closes cleanly (no dangling decision).
#// The Credit is spent either way; declining the attack does not refund it.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:LAW_015:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Credits: 1
WithP1Hand: SOR_247
WithP2GroundArena: SOR_247:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_247
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1CREDITCOUNT:0
P1RESAVAILABLE:1
P1NODECISION
