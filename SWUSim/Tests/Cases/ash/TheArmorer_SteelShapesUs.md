# Deployed_AttackEnd_PlayUpgradeFromResources
#// ASH_001 The Armorer (deployed) — When Attack Ends: you may play an upgrade from your resources on
#// a friendly unit; if you do, resource the top card of your deck. The Armorer attacks the base
#// (survives), then plays Academy Training (SOR_120, cost 2) from resources onto the Dark Trooper,
#// and resources the deck's top card.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Resources: 3:SOR_046:1,1:SOR_120:1
WithP1Deck: SOR_237

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-3
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1DECKCOUNT:0

---

# PlayUpgradeFromResources
#// ASH_001 The Armorer — Leader Action [Exhaust]: play an upgrade from your resources on a unit that entered
#// play this phase (paying its cost). P1 plays SOR_095 (so it "entered this phase"), then uses The Armorer to
#// play SOR_120 (an upgrade in the resource zone) onto it, raising it to 5 power.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_120:1,7:SOR_095:1
WithP1Hand: SOR_095
WithP1Deck: [SOR_063]
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1LEADER:EXHAUSTED
