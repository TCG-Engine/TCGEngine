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

---

# LeaderAction_SoftPass_NoUpgradeInResources
#// ASH_001 The Armorer (leader Action) — with a unit that entered this phase but NO upgrade in resources, the
#// ability may still be activated as a soft pass: The Armorer exhausts, nothing is played, and the deck top is
#// not resourced.
## GIVEN
CommonSetup: gbw/brk/{myLeader:ASH_001}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8:SOR_046:1
WithP1Hand: SOR_095
WithP1Deck: [SOR_063]
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:EXHAUSTED
P1DECKCOUNT:1

---

# LeaderAction_SoftPass_NoUnitEnteredThisPhase
#// ASH_001 The Armorer (leader Action) — with an upgrade in resources but no unit that entered play this phase
#// (the seated SEC_080 arrived earlier), the ability is a soft pass: The Armorer exhausts, the upgrade is not
#// played, and the deck top is not resourced.
## GIVEN
CommonSetup: gbw/brk/{myLeader:ASH_001}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_120:1,7:SOR_046:1
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_063]
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:EXHAUSTED
P1DECKCOUNT:1

---

# Deployed_AttackEnd_Decline
#// ASH_001 The Armorer (deployed) — the When Attack Ends upgrade play is optional. The Armorer attacks the
#// base and P1 declines: no upgrade is played and the deck top is NOT resourced (SOR_237 stays in the deck).
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
- P1>AnswerDecision:PASS
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DECKCOUNT:1

---

# Deployed_AttackEnd_AttachToSelf
#// ASH_001 The Armorer (deployed) — with no other friendly units, the When Attack Ends upgrade can be played
#// onto The Armorer itself. It attacks the base, then plays SOR_120 from resources onto itself and resources
#// the top card of the deck.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_120:1,7:SOR_046:1
WithP1Deck: SOR_237
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-0
## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1DECKCOUNT:0

---

# LeaderAction_ResourcesTopCardOnSuccess
#// ASH_001 The Armorer (leader action) — "If you do, resource the top card of your deck." After playing SOR_120
#// from resources onto the just-played SOR_095, the top deck card (SOR_063) is moved to resources (deck 2→1,
#// SOR_046 now on top). (Previously the ramp was wrongly gated on the attach's trigger-count return.)
## GIVEN
CommonSetup: gbw/brk/{myLeader:ASH_001}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_120:1,7:SOR_095:1
WithP1Hand: SOR_095
WithP1Deck: [SOR_063 SOR_046]
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_046

---

# LeaderAction_ExhaustedUpgradeResource_StillPlayable
#// Bug #955 (game 3324): an EXHAUSTED upgrade in the resource zone was wrongly filtered by the
#// affordability gate. The gate required cost < ready capacity ("need OTHER resources to pay"), but an
#// exhausted resource never contributed to that capacity, so removing it from the zone costs nothing —
#// cost <= ready is enough. Here P1 plays LAW_070 (cost 2), which exhausts ASH_084 Arcana Star Map and
#// one SOR_046 (payment exhausts in zone order), leaving 1 ready resource. The Armorer must still be
#// able to play the cost-1 map from resources onto the just-played unit and ramp the deck top.
## GIVEN
CommonSetup: ybw/brk/{myLeader:ASH_001}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:ASH_084:1,2:SOR_046:1
WithP1Hand: LAW_070
WithP1Deck: [SOR_063]
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_070
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:ASH_084
P1LEADER:EXHAUSTED
P1DECKCOUNT:0

---

# Deployed_AttackEnd_ExhaustedUpgradeResource_StillPlayable
#// Same gate on the deployed side (When Attack Ends): the upgrade sitting in resources is EXHAUSTED
#// (status 0), one other resource is ready — cost 1 equals the ready capacity, so the play is legal and
#// must be offered. The Armorer attacks the base, plays the exhausted Arcana Star Map onto SEC_080,
#// and resources the deck top.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Resources: 1:ASH_084:0,1:SOR_046:1
WithP1Deck: SOR_237
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:ASH_084
P1DECKCOUNT:0

---

# LeaderAction_SoftPass_UpgradesInHandDiscardNotValidSources
#// ASH_001 The Armorer (leader Action) — the ONLY valid upgrade source is the resource zone. With a unit that
#// entered play this phase, upgrades sitting in HAND and DISCARD, but only a unit in resources, the ability has
#// no legal source and is a soft pass: The Armorer exhausts, nothing is attached to the unit, the hand upgrade
#// stays in hand, and the deck top is not resourced.
## GIVEN
CommonSetup: gbw/brk/{myLeader:ASH_001}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8:SOR_046:1
WithP1Hand: [SOR_095 SOR_120]
WithP1Discard: SOR_120
WithP1Deck: [SOR_063]
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:EXHAUSTED
P1DECKCOUNT:1
P1HANDCOUNT:1
