# Deployed_WhenDeployed_DeclineNoUpgrade
#// LOF_001 Kylo Ren — When Deployed is a "may" loop; declining ('-') the first offer plays nothing.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:LOF_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Discard: SOR_120

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1

---

# Deployed_WhenDeployed_PlayUpgradeFromDiscard
#// LOF_001 Kylo Ren — When Deployed: play any number of upgrades from your discard on this unit,
#// paying their costs. Kylo deploys (7 resources), then plays Academy Training (SOR_120, cost 2)
#// from the discard onto himself → 1 upgrade on Kylo, discard empty, 5 resources left.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:LOF_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Discard: SOR_120

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1DISCARDCOUNT:0
P1RESAVAILABLE:5

---

# DiscardUpgradeDraw
#// LOF_001 Kylo Ren — Action [Exhaust]: Discard a card from your hand. If you discarded an upgrade this way,
#// draw a card. P1 discards SOR_053 (an upgrade) and draws SOR_059; the leader exhausts.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:LOF_001;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_053
WithP1Deck: SOR_059

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1LEADER:EXHAUSTED


---

# DiscardPilotingUnit_NoDraw
#// LOF_001 Kylo Ren draws only if an UPGRADE was discarded. A Piloting unit is a Unit (type 'Unit', not
#// 'Upgrade') even though it can be played as a pilot upgrade — so discarding JTL_034 (Piloting) draws
#// nothing. (Pilot-as-upgrade family: the draw keys on printed CardType, not playable-as.)

## GIVEN
CommonSetup: bbk/bbk/{ myLeader:LOF_001; myBase:SOR_021; theirBase:SOR_021 }
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: JTL_034
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P1DISCARDCOUNT:1

---

# DiscardUpgrade_Draws
#// LOF_001 Kylo Ren — discarding a real Upgrade (SOR_053, type 'Upgrade') draws a card: hand nets back to
#// 1 (discarded 1, drew SOR_095), deck -1, discard +1.

## GIVEN
CommonSetup: bbk/bbk/{ myLeader:LOF_001; myBase:SOR_021; theirBase:SOR_021 }
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: SOR_053
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:1

---

# DiscardEvent_NoDraw
#// LOF_001 Kylo Ren draws only if an UPGRADE was discarded. Discarding an Event (Force Throw, SOR_167) draws
#// nothing: hand empties, deck untouched, discard +1, leader exhausts.

## GIVEN
CommonSetup: bbk/bbk/{ myLeader:LOF_001; myBase:SOR_021; theirBase:SOR_021 }
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: SOR_167
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P1DISCARDCOUNT:1
P1LEADER:EXHAUSTED

---

# NoCardsInHand_NoEffect
#// LOF_001 Kylo Ren — with an EMPTY hand the Action has no effect: nothing is discarded or drawn, but the
#// leader still exhausts (it was used).

## GIVEN
CommonSetup: bbk/bbk/{ myLeader:LOF_001; myBase:SOR_021; theirBase:SOR_021 }
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:0
P1LEADER:EXHAUSTED

---

# DiscardUpgrade_EmptyDeck_BaseDamage
#// LOF_001 Kylo Ren — discarding an Upgrade triggers the draw even with an EMPTY deck: the failed draw deals
#// 3 damage to P1's own base instead. Discards Fallen Lightsaber (SOR_137), deck empty → base takes 3.

## GIVEN
CommonSetup: bbk/bbk/{ myLeader:LOF_001; myBase:SOR_021; theirBase:SOR_021 }
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: SOR_137

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1BASEDMG:3
P1LEADER:EXHAUSTED

---

# Deployed_NoAttachableUpgrades_DoesNothing
#// LOF_001 Kylo Ren (deployed) — When Deployed offers only playable upgrades attachable to Kylo. A discard of
#// only an Event (Drain Essence LOF_041), a Vehicle-only upgrade (Dorsal Turret JTL_120), and a Piloting unit
#// (JTL_034) has NO valid target → no prompt, Kylo deploys with 0 upgrades, discard unchanged.

## GIVEN
CommonSetup: gbk/brk/{ myLeader:LOF_001 }
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Discard: LOF_041, JTL_120, JTL_034

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:3

---

# Deployed_UpgradeTriggerResolves
#// LOF_001 Kylo Ren (deployed, power 7) — When Deployed resolves each upgrade's own When Played trigger as it
#// is played. Playing Craving Power (LOF_091, "When Played: deal damage to an enemy unit equal to attached
#// unit's power") onto Kylo deals 7 to the lone enemy Death Star Stormtrooper (3/1) → defeated. Kylo keeps
#// Craving Power attached.

## GIVEN
CommonSetup: gbk/brk/{ myLeader:LOF_001 }
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Discard: LOF_091
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1LEADER:DEPLOYED
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LOF_091

---

# Deployed_PlayTwoUpgradesFromDiscard
#// LOF_001 Kylo DEPLOYED "play any number of upgrades from discard, one at a time, paying costs". Two
#// attachable affordable upgrades in discard (SOR_069 c1 + SOR_072 c2). Deploy → play both → Kylo has 2.
## GIVEN
CommonSetup: bbk/ggw/{myLeader:LOF_001;myBase:SOR_021;theirBase:SOR_021;discardCardIds:SOR_069,SOR_072}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myDiscard-0
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:LOF_001
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
