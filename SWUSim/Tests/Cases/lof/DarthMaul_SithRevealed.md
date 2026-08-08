# DealOneEach
#// LOF_009 Darth Maul — Action [Exhaust, use the Force]: Deal 1 damage to a unit and 1 damage to a different
#// unit. Both enemy units take 1; P1 loses the Force.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1NOFORCE

---

# DeployedOnAttack
#// LOF_009 Darth Maul (deployed) — On Attack: deal 1 damage to a unit and 1 to a different unit. He attacks
#// the base; both enemy units take 1.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1

---

# DeployedOnAttack_MandatorySelfDamage_WhenOnlyUnit
#// LOF_009 Darth Maul (deployed) — the On-Attack damage is MANDATORY (deal 1 to min(2, units in play)
#// units). With Maul as the ONLY unit in play, he attacks the base and must deal 1 damage to HIMSELF
#// (single valid target → auto-resolved, cannot be declined). Intended: "must damage himself if there are no
#// other units in play."
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_009
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# LeaderAbility_MustPickTwoDifferent_IncludingFriendly
#// LOF_009 Darth Maul (front) — the damage is mandatory to 2 DIFFERENT units when at least 2 units are in
#// play, and friendly units are valid targets. With only 1 enemy (Battlefield Marine) and 1 friendly
#// (Guardian of the Whills) in play, both must be picked → each takes 1. Intended: "must deal damage to 2
#// different units (including friendly) if there are at least 2 units in play".

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_061:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NOFORCE
P1LEADER:EXHAUSTED

---

# LeaderAbility_SingleUnitInPlay_DamagesOnlyIt
#// LOF_009 Darth Maul (front) — with only ONE unit in play (friendly Guardian of the Whills), the "1 to a
#// unit and 1 to a different unit" resolves against just that single legal target (auto-resolved, cannot pick
#// a "different" second unit). Guardian takes 1; the Force is still spent. Intended: "can deal damage to a single
#// unit if there is only 1 unit in play".

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_061:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NOFORCE
P1LEADER:EXHAUSTED

---

# LeaderAbility_NoUnitsInPlay_SoftPass
#// LOF_009 Darth Maul (front) — with NO units in play the ability still resolves as a "soft pass": Maul
#// exhausts and the Force is spent, but no damage is dealt anywhere. Intended: "can be used to soft pass if there
#// are no units in play".

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1NOFORCE
P1LEADER:EXHAUSTED
P2BASEDMG:0
