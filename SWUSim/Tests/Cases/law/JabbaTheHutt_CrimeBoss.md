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
- P1>AnswerDecision:myResources-2
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
- P1>AnswerDecision:myResources-3
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
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1CREDITCOUNT:1
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0
