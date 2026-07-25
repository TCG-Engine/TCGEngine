# LeaderAction_AttackBase_PlusOne
#// JTL_015 Rio Durant (leader) — Action [1 resource, Exhaust]: Attack with a space unit. It gets +1/+0
#// and gains Saboteur for this attack. The X-Wing (SOR_237, power 2) attacks P2's base for 2+1=3. The
#// +1/+0 and Saboteur are both "for this attack" only, so afterwards the X-Wing is back to power 2 with
#// no Saboteur.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:NOTKEYWORD:Saboteur
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# LeaderAction_NoResource_NoOp
#// JTL_015 Rio Durant (leader) — the action costs 1 resource. With 0 ready resources it is a full no-op:
#// Rio stays READY, the space unit does not attack, and no decision is pending.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Resources: 0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
P2BASEDMG:0
P1SPACEARENAUNIT:0:READY

---

# LeaderAction_Saboteur_DefeatsShield
#// JTL_015 Rio Durant (leader) — the attacking space unit gains Saboteur for this attack (defeat the
#// defender's Shields). The X-Wing (SOR_237, power 2 → 3 with the +1) attacks a Shielded TIE (SOR_225,
#// 2/1 + a Shield token): Saboteur defeats the Shield, so the 3 damage goes through and the TIE is
#// defeated. The TIE's counter (power 2) damages the surviving X-Wing.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED

---

# DeployedPilot_NonTransport_GainsSaboteur
#// JTL_015 Rio Durant DEPLOYED AS A PILOT — deployed-side text: "Attached unit is a leader unit. It gains
#// Saboteur. If it's a Transport, it also gets +1/+0." On a NON-Transport host (SOR_237 Alliance X-Wing,
#// 2/3 Fighter), Rio's pilot stats (+3/+5) make it 5/8, and it gains Saboteur — but NOT the Transport
#// +1/+0. (Ports the deployed-pilot side, previously unimplemented — only the leader front action existed.)

## GIVEN
CommonSetup: yrk/bbk/{
  myLeader:JTL_015;
  myLeaderDeployedPilot:true;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:8
P1SPACEARENAUNIT:0:HASKEYWORD:Saboteur

---

# DeployedPilot_Transport_PlusOnePower
#// JTL_015 Rio Durant DEPLOYED AS A PILOT on a TRANSPORT host (JTL_258 Corellian Freighter, 4/4
#// Vehicle/Transport): Rio's pilot stats (+3/+5) make it 7/9, PLUS the deployed-side "+1/+0 if it's a
#// Transport" → power 8, hp 9, and it gains Saboteur. (The Transport clause was the missing half.)

## GIVEN
CommonSetup: yrk/bbk/{
  myLeader:JTL_015;
  myLeaderDeployedPilot:true;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_258:1:0

## WHEN

## EXPECT
P1LEADER:DEPLOYED
P1SPACEARENAUNIT:0:CARDID:JTL_258
P1SPACEARENAUNIT:0:POWER:8
P1SPACEARENAUNIT:0:HP:9
P1SPACEARENAUNIT:0:HASKEYWORD:Saboteur
