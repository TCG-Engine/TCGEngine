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


---

# DeployAsPilot_OddCount_ExcludesEnemyAndFriendlyEven
#// JTL_017 Han Solo — "When deployed as an upgrade: for each friendly unit or upgrade that has an odd cost,
#// ready a resource." Verifies the exclusions: only FRIENDLY, only ODD. Board: host SOR_237 (cost 2, even),
#// friendly SOR_063 (cost 3, odd → counts), friendly SOR_046 (cost 4, even → excluded), enemy SOR_108
#// (cost 1, odd → excluded because enemy). Odd-cost friendly permanents = SOR_063 + Han-as-pilot (cost 5)
#// = 2, so 2 of P1's 5 exhausted resources ready.

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
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_108:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot

## EXPECT
P1LEADER:DEPLOYED
P1RESAVAILABLE:2

---

# LeaderAction_NoFriendlyUnits_RevealOnly_TurnPasses
#// JTL_017 Han Solo (leader) — Action: "Reveal the top card of your deck, then attack with a unit." With
#// ZERO friendly units in play, the reveal still resolves (top card SOR_225 is shown) but there is no unit
#// to attack, so the attack step is simply skipped. Han exhausts and, being a normal action, the turn
#// passes to P2. No damage is dealt.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_017;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Deck: SOR_225

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P2BASEDMG:0
P1NODECISION
TURNPLAYER:2

---

# LeaderAction_EmptyDeck_AttackNoBuff
#// JTL_017 Han Solo (leader) — with an EMPTY deck there is no card to reveal, so the +1/+0 condition can
#// never be met. The attack still proceeds: the lone odd-cost attacker JTL_069 (cost 5, odd, power 4)
#// deals its BASE 4 to P2's base — proving no buff was granted because nothing was revealed. Han exhausts.
#// Intended: empty deck → attack only, at base power.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_017;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:POWER:4
P1LEADER:EXHAUSTED

---

# DeployedAsUnit_NoLeaderAction
#// JTL_017 Han Solo (leader) — the reveal-then-attack ability is a LEADER action. Deployed as a normal
#// ground UNIT (3/7), Han has no such ability: he simply attacks as a 3/7, dealing his base 3 to P2's base
#// with no reveal and no +1/+0 decision pending. Intended: does nothing if deployed as a unit.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:JTL_017;
  myLeaderDeployed:true;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_225

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_017
P1GROUNDARENAUNIT:0:POWER:3
P2BASEDMG:3
P1NODECISION

---

# SimulateRequestBoundary_PilotDeployModeSurvivesRoundTrip
#// JTL_017 Han Solo — the Pilot-vs-Unit deploy choice ends the request in production, so the answer arrives
#// in a fresh process and the pending deploy (and the host it attaches to) must be serialized. Mirrors
#// DeployAsPilot_ReadyOddCostResources with the boundary inserted before the Pilot answer.

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
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Pilot

## EXPECT
P1LEADER:DEPLOYED
P1RESAVAILABLE:2
P1SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# ControlChange_StolenUnitAttacks_RevealComesFromHansOwnDeck
#// JTL_017 Han Solo (leader) — Action: "Reveal the top card of YOUR deck, then attack with a unit."
#// Control-change axis: P1 first steals P2's SOR_063 Cloud City Wing Guard (cost 3, odd) with SOR_224
#// Change of Heart, so the only unit Han can attack with is a unit P1 CONTROLS but does not OWN. Two
#// things must resolve off the right seat: (a) the attacker offer is control-based, so the stolen unit
#// is eligible, and (b) "your deck" is HAN'S CONTROLLER'S deck, not the attacking unit's owner's.
#// The decks are deliberately discriminating — P1's top card is SOR_225 (cost 1, odd → DIFFERENT odd
#// cost from the attacker's 3 → +1/+0 → 2+1 = 3 damage), while P2's top card is SOR_063 (cost 3, the
#// SAME odd cost → would give NO buff → 2 damage). 3 vs 2 on P2's base separates the two readings.
#// Both sides asserted: P1 controls the unit, P2's arena is empty, and neither deck lost a card.

## GIVEN
CommonSetup: yyw/ggk/{
  myLeader:JTL_017
}
SkipPreGame: true
WithP2GroundArena: SOR_063:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: SOR_224
WithP1Resources: 6
WithP1Deck: SOR_225
WithP2Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1DECKCOUNT:1
P2DECKCOUNT:1
P1LEADER:EXHAUSTED
