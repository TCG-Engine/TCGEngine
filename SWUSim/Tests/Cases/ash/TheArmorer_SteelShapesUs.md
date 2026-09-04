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

---

# LeaderAction_CreatedTokenIsAValidHost
#// HAPPY PATH FOR THE FIX (bug #1025/#1026 family). The Armorer's Action hosts on "a unit that ENTERED
#// PLAY this phase", and a token CREATED this phase entered play without being played — so it is an
#// eligible host. It was invisible before: the host scan read SWU_PLAYED_UNIT_, which only ActivateCard
#// sets, so created tokens (and deployed leaders) were skipped.
#// P1 plays SEC_097 Beloved Orator, whose When Played creates a Spy token, so TWO units entered this
#// phase and the host choose becomes interactive — which is the point: it proves the token is in the
#// OFFERED POOL rather than being the last thing standing. The token (index 1) takes the upgrade.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_120:1,13:SOR_095:1
WithP1Hand: SEC_097
WithP1Deck: [SOR_063]
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
#// index 0 is SEC_097 itself; index 1 is the Spy token it created.
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:EXHAUSTED

---

# LeaderAction_SoftPass_TokenCreatedAPREVIOUSPhase
#// THE CONTROL, and it is what stops the section above passing on a rule that simply offers every unit.
#// The same Spy token, one round later: the marker clears in RegroupPhaseStart, so neither SEC_097 nor
#// its token "entered play this phase" any more and the Action is a soft pass — The Armorer exhausts,
#// no upgrade is played, and the deck top is not resourced.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_120:1,13:SOR_095:1
WithP1Hand: SEC_097
WithP1Deck: [SOR_063 SOR_063 SOR_063 SOR_063]
## WHEN
- P1>PlayHand:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1LEADER:EXHAUSTED

#// ⚠ NO DEPLOYED-LEADER SECTION HERE, and it is unreachable rather than forgotten: this ability lives on
#// The Armorer's UNDEPLOYED side ("Action [Exhaust]"), so using it requires P1's own leader to still be
#// a leader. A player controls exactly one leader, so there is no friendly deployed leader available to
#// host while the Action is usable. The deployed-leader leg of this fix is covered where it is reachable
#// — Tests/Cases/keywords/Hidden_DeployedAndCreated.md and
#// Tests/Cases/sor/BobaFett_Disintegrator.md::OnAttack_DeployedLeaderThisRound_NoDeal3.

---

# Deployed_AttackEnd_ResourcePaysPartOfItsOwnCost
#// CR 6.2 pays a card's cost at step 4 and puts it into play at step 5, so a card played OUT OF the
#// resource row is still a resource while paying — CR 8.22.e says so outright for Smuggle, and
#// SWUSmuggleResource implements it. The Armorer's play-from-resources did not: it moved the upgrade to
#// hand FIRST, so the full cost came out of OTHER resources and the player lost the slot on top of it —
#// a cost-3 upgrade cost 4 resources (live report 2026-09-03, Whistling Birds ASH_183). Here 4 ready
#// resources, one of them the cost-2 Academy Training: it pays 1 of its own cost, ONE other resource
#// exhausts, and 2 stay ready. (The deck's replacement card enters exhausted, CR 1.7.7.)
## GIVEN
CommonSetup: gbw/brk/{myLeader:ASH_001:1:1:1}
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
P1RESAVAILABLE:2

---

# LeaderAction_ExactlyAffordableViaSelfPayment
#// The same rule on the affordability side. Battlefield Marine costs 4 here (ASH_001 provides no
#// Heroism), leaving exactly 2 ready resources — one generic and the cost-2 Academy Training itself.
#// Academy Training pays 1 of its own cost + 1 generic, so it IS playable. The gate used to read
#// `cost > capacity - selfReady`, which for a READY upgrade is `cost >= capacity`: an upgrade costing
#// exactly the capacity was dropped from the offer and the Action silently soft-passed, reported live as
#// "The Armorer won't let me play Armor of Fortune". Same defect HMW_017 Osha carried (bug #976), which
#// inherited the line from here. Bug #955's EXHAUSTED case still holds — see
#// LeaderAction_ExhaustedUpgradeResource_StillPlayable.
## GIVEN
CommonSetup: gbw/brk/{myLeader:ASH_001}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5:SOR_046:1,1:SOR_120:1
WithP1Hand: SOR_095
WithP1Deck: [SOR_063]
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# LeaderAction_PlotUpgradeFromResources_ArmorOfFortune
#// The card the live report named: SEC_070 Armor of Fortune is a PLOT upgrade sitting in the resource
#// row. Plot is not a second cause — with resources to spare it plays onto the unit that entered this
#// phase exactly like any other upgrade. Kept as the control that pins the affordability gate above as
#// the ONLY defect on this path.
## GIVEN
CommonSetup: gbw/brk/{myLeader:ASH_001}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8:SOR_046:1,1:SEC_070:1
WithP1Hand: SOR_095
WithP1Deck: [SOR_063]
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SEC_070
