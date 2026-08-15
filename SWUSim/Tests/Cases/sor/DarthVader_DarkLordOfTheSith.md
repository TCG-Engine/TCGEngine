# Deploy_OnAttack_DealDamage
#// SOR_010 Darth Vader — Deployed: OnAttack YES → deal 2 damage to a unit.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EPICUSED

---

# Deploy_OnAttack_Decline
#// SOR_010 Darth Vader — Deployed: OnAttack NO → no extra damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EPICUSED

---

# LeaderAction_NoVillainyCard
#// SOR_010 Darth Vader — Leader Action: No Villainy card played → exhaust + spend resource, no damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:1}
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0

---

# LeaderAction_VillainyPlayed
#// SOR_010 Darth Vader — Leader Action: Villainy card played → deal 1 to unit + 1 to base.
#// SOR_128 (Death Star Stormtrooper) is Villainy, cost 1.

## GIVEN
CommonSetup: rrk/grw/{myResources:2;handCardIds:SOR_128}
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:1
P1LEADER:EXHAUSTED
P1RESCOUNT:2
P1RESAVAILABLE:0

---

# SimulateRequestBoundary_MidAttackTriggerTarget
#// SOR_010 Darth Vader — the deployed OnAttack "deal 2 damage to a unit" choose ends the request in
#// production: the attack is mid-resolution (base damage still owed) when the prompt goes out, so the
#// answer arrives in a fresh process that must rebuild the attack context from the serialized
#// gamestate. Mirrors Deploy_OnAttack_DealDamage with the boundary inserted before the answer.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EPICUSED
