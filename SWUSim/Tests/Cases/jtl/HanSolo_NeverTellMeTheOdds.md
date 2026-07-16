# DeployAsPilot_ReadyOddCostResources
#// JTL_017 Han Solo (leader) — "When deployed as an upgrade: For each friendly unit or upgrade that has
#// an odd cost, ready a resource." Han deploys as a Pilot onto SOR_237 (cost 2, even). Odd-cost friendly
#// permanents = SOR_063 Cloud City Wing Guard (cost 3) + Han himself as a pilot upgrade (cost 5) = 2, so
#// 2 of P1's 5 exhausted resources ready.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_017;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5:SOR_095:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot

## EXPECT
P1LEADER:DEPLOYED
P1RESAVAILABLE:2
P1SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# LeaderAction_DifferentOddCosts_PlusOne
#// JTL_017 Han Solo (leader) — Action [Exhaust]: Reveal the top card of your deck, then attack with a
#// unit. If the revealed card and that unit have DIFFERENT odd costs, that unit gets +1/+0 for this
#// attack. Revealed SOR_225 (cost 1, odd); attacker JTL_069 (cost 5, odd) — different odd costs → +1/+0,
#// so it deals 4+1=5 to P2's base, then is back to power 4.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_017;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1Deck: SOR_225

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:5
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:POWER:4
P1LEADER:EXHAUSTED

---

# LeaderAction_EvenCost_NoBuff
#// JTL_017 Han Solo (leader) — the +1/+0 requires BOTH costs to be odd. The attacker SOR_095 has an even
#// cost (2), so even though the revealed SOR_225 is odd (1) the condition fails and no buff is granted:
#// SOR_095 deals its base 3 to P2's base.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_017;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_225

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED

---

# LeaderAction_SameOddCost_NoBuff
#// JTL_017 Han Solo (leader) — the +1/+0 requires the revealed card and the unit to have DIFFERENT odd
#// costs. Both the revealed card and the attacker are JTL_069 (cost 5, odd) — same odd cost, so no buff:
#// the attacker deals its base 4 to P2's base.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_017;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1Deck: JTL_069

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:POWER:4
P1LEADER:EXHAUSTED
