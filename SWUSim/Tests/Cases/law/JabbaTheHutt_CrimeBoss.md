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
