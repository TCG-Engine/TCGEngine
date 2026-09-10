# DarkSanctum_UpgradeNamed_NoRegroupTrigger
#// SEC_046 Galen Erso ("cards with the chosen name lose all abilities") vs FORTIFY — USER RULING
#// 2026-09-10: naming a Fortify UPGRADE makes it lose its abilities; and any Fortification whose text says
#// "Attached base gains …" is ineffective when Galen names the BASE (the base loses all abilities and
#// can't gain any). The upgrade's OWN printed abilities are untouched by naming the base.
#//
#// One shared predicate, _SWUFortifyBlanked(), holds both halves, with an explicit per-card list of the
#// base-granted ones (_SWUFortifyGrantsToBase). Every member gets a guard here:
#//   base-granted (upgrade named → off, base named → off): HMW_070 112 113 126 147 160 172 205 206
#//   upgrade's own (upgrade named → off, base named → STILL ON): HMW_037 081 095 171, and every
#//     Fortify When Played (HMW_172 shown). The STILL-ON sections are what prove the split — adding an
#//     own-ability card to the base-granted list reds only its own section.
#// (HMW_126 Verdant Fortress's pair lives in its own card file.)
#//
#// Fixture shape: P2 plays Galen first (bbw = Vigilance base + Luke, so Galen's Vigilance/Heroism costs
#// its printed 4) and names; then P1, who owns the Fortify upgrade, acts. Where the upgrade is on the
#// DEFENDER's side the roles flip. CommonSetup bases: b = Capital City, g = Echo Base,
#// r = Catacombs of Cadera.
#//
#// HMW_070 Dark Sanctum — "Attached base gains: 'When the regroup phase starts: Draw a card and deal 2
#// damage to this base.'" Named, it does nothing at regroup: no damage, and the deck only loses the two
#// regroup draws (4 → 2; with the Sanctum live it is 4 → 1 and 2 damage, per DarkSanctum.md).

## GIVEN
CommonSetup: bbk/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_070
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Dark Sanctum
- P1>Pass
- P2>Pass

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASEDMG:0
P1DECKCOUNT:2

---

# DarkSanctum_BaseNamed_NoRegroupTrigger
#// HMW_070 — base-granted, so naming the BASE (Capital City) switches it off too.

## GIVEN
CommonSetup: bbk/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_070
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Capital City
- P1>Pass
- P2>Pass

## EXPECT
P1BASE:UPGRADECOUNT:1
P1BASEDMG:0
P1DECKCOUNT:2

---

# AllianceShieldGenerator_UpgradeNamed_DamageLands
#// HMW_081 — "If attached base would be dealt 5 or more damage, prevent that damage. If you do, defeat
#// this upgrade and draw a card." The upgrade's OWN ability. Here the generator is on the DEFENDER (P2),
#// so P1 is the Galen player: P1 names it, P2 passes, and P1's 5-power Strike Team Vanguard hits the base
#// for the full 5 — the generator stays attached, having done nothing.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
WithActivePlayer: 1
WithP1Hand: SEC_046
WithP1GroundArena: ASH_061:1:0
WithP2BaseUpgrade: HMW_081
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Alliance Shield Generator
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P2BASE:UPGRADECOUNT:1
P2HANDCOUNT:0

---

# AllianceShieldGenerator_BaseNamed_StillPrevents
#// ⚠ HMW_081 — the SPLIT. The prevention is printed on the upgrade, not granted to the base, so naming
#// the BASE leaves it working: 0 damage, the generator is defeated, and P2 draws.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4}
WithActivePlayer: 1
WithP1Hand: SEC_046
WithP1GroundArena: ASH_061:1:0
WithP2BaseUpgrade: HMW_081
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Capital City
- P2>Pass
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2BASE:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# CarboniteChamber_UpgradeNamed_ActionUnavailable
#// HMW_095 — "Action [defeat this upgrade]: …" is the upgrade's OWN Action. Named, it is not offered:
#// using the base does nothing, the Chamber stays attached, and no decision opens.

## GIVEN
CommonSetup: bbw/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_095
WithP2GroundArena: SOR_095:0:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Carbonite Chamber
- P1>UseBaseAbility

## EXPECT
P1BASE:UPGRADECOUNT:1
P1NODECISION

---

# CarboniteChamber_BaseNamed_ActionStillWorks
#// ⚠ HMW_095 — the SPLIT. Naming the BASE leaves an upgrade's own Action alone: it is offered, pays its
#// cost (the Chamber is defeated) and asks for its non-Vehicle target (P2's Marine or the just-played
#// Galen — two, so the pick is real).

## GIVEN
CommonSetup: bbw/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_095
WithP2GroundArena: SOR_095:0:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Capital City
- P1>UseBaseAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASE:UPGRADECOUNT:0
P1NODECISION

---

# BactaTank_UpgradeNamed_ActionUnavailable
#// HMW_037 — "Action [defeat this upgrade]: Put a non-Vehicle unit from your discard pile on top of your
#// deck." Its own Action; named, nothing happens: deck and discard unchanged, Bacta Tank still attached.

## GIVEN
CommonSetup: bgw/bbw/{discardCardIds:SOR_095;theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_037
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Bacta Tank
- P1>UseBaseAbility

## EXPECT
P1BASE:UPGRADECOUNT:1
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1NODECISION

---

# BactaTank_BaseNamed_ActionStillWorks
#// ⚠ HMW_037 — the SPLIT: naming the base does not touch the upgrade's own Action.

## GIVEN
CommonSetup: bgw/bbw/{discardCardIds:SOR_095;theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_037
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Capital City
- P1>UseBaseAbility
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:3

---

# MilitaryAcademy_UpgradeNamed_NoOverwhelm
#// HMW_112 — "Attached base gains: 'Friendly units gain Overwhelm.'" Named → the Marine has no Overwhelm.

## GIVEN
CommonSetup: grk/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_112
WithP1GroundArena: SOR_095:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Military Academy

## EXPECT
P1BASE:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# MilitaryAcademy_BaseNamed_NoOverwhelm
#// HMW_112 — base-granted, so naming the BASE (Echo Base) switches it off too.

## GIVEN
CommonSetup: grk/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_112
WithP1GroundArena: SOR_095:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Echo Base

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# SinisterWarMemorial_UpgradeNamed_NoHeal
#// HMW_113 — "Attached base gains 'When a friendly unit is defeated: Heal 1 damage from this base.'"
#// Named → the Marine trades with Industrious Team and the base stays on 3 damage.

## GIVEN
CommonSetup: gbk/bbw/{myBaseDamage:3;theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_113
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Sinister War Memorial
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:3

---

# SinisterWarMemorial_BaseNamed_NoHeal
#// HMW_113 — base-granted, so naming the BASE (Echo Base) switches it off too.

## GIVEN
CommonSetup: gbk/bbw/{myBaseDamage:3;theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_113
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Echo Base
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:3

---

# BeastLair_UpgradeNamed_NoActionPhaseTrigger
#// HMW_147 — "Attached base gains: 'When the action phase starts: You discard a card from your hand. If
#// you do, create a Beast token.'" Named → the next action phase starts with no discard and no Beast
#// (the regroup draw leaves P1 two cards to discard, so the trigger COULD have fired).

## GIVEN
CommonSetup: ggw/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_147
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Beast Lair
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
PHASE:MAIN
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:0
P1HANDCOUNT:2
P1NODECISION

---

# BeastLair_BaseNamed_NoActionPhaseTrigger
#// HMW_147 — base-granted, so naming the BASE (Echo Base) switches it off too.

## GIVEN
CommonSetup: ggw/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_147
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Echo Base
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Drain

## EXPECT
PHASE:MAIN
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:0
P1HANDCOUNT:2
P1NODECISION

---

# NoxiousRefinery_UpgradeNamed_NoRegroupPing
#// HMW_160 — "Attached base gains: 'When the regroup phase starts: Reveal the top card of your deck. If
#// it's Aggression, deal 1 damage to an enemy unit.'" Roles flipped so the ping is OBSERVABLE: the
#// Refinery is P2's, its top card is Aggression (Death Star Stormtrooper), and P1's just-played Galen is
#// P2's ONLY enemy unit — so a live Refinery auto-targets Galen for 1. Named → Galen stays undamaged.
#// ⚠ Do not add a second enemy unit: the pick is mandatory, and with two targets it sits pending behind
#// the regroup resource prompt, so "0 damage" would hold whether or not the gate exists.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1Hand: SEC_046
WithP2BaseUpgrade: HMW_160
WithP2Deck: [SOR_128 SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Noxious Refinery
- P2>Pass
- P1>Pass
- P2>Drain

## EXPECT
P2BASE:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_046
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# NoxiousRefinery_BaseNamed_NoRegroupPing
#// HMW_160 — base-granted, so naming the BASE (Catacombs of Cadera) switches it off too.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1Hand: SEC_046
WithP2BaseUpgrade: HMW_160
WithP2Deck: [SOR_128 SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Catacombs of Cadera
- P2>Pass
- P1>Pass
- P2>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_046
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# TrapField_UpgradeNamed_NoReaction
#// HMW_171 — "When a non-leader ground unit enters play: You may defeat this upgrade. If you do, deal 3
#// damage to that unit." The upgrade's OWN ability.
#// ⚠ FIXTURE: a freshly played Galen is itself a ground unit entering play, so Galen names the card
#// BEFORE any Trap Field is on the table — P1 plays Galen and names it, P2 then plays Trap Field, and
#// P1's Consular Security Force is the entrant. Named → no offer, no damage, Trap Field stays attached.

## GIVEN
CommonSetup: bbw/rrw/{myResources:8;theirResources:2}
WithActivePlayer: 1
WithP1Hand: SEC_046
WithP1Hand: SOR_046
WithP2Hand: HMW_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Trap Field
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2BASE:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0
P2NODECISION

---

# TrapField_BaseNamed_StillReacts
#// ⚠ HMW_171 — the SPLIT. Naming the BASE (Catacombs of Cadera) leaves the upgrade's own reaction alone:
#// the entrant is offered to Trap Field, which defeats itself and deals 3.

## GIVEN
CommonSetup: bbw/rrw/{myResources:8;theirResources:2}
WithActivePlayer: 1
WithP1Hand: SEC_046
WithP1Hand: SOR_046
WithP2Hand: HMW_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Catacombs of Cadera
- P2>PlayHand:0
- P1>PlayHand:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:YES

## EXPECT
P2BASE:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# HeavyIonCannon_UpgradeNamed_GrantedActionUnavailable
#// HMW_172 — "Attached base gains: 'Action [discard a card from your hand]: Deal 2 damage to a unit.'"
#// Named → using the base does nothing: the hand is untouched and no decision opens.

## GIVEN
CommonSetup: rrw/bbw/{theirResources:4}
WithActivePlayer: 2
SkipPreGame: true
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Heavy Ion Cannon
- P1>UseBaseAbility

## EXPECT
P1HANDCOUNT:2
P1BASEUPGRADECOUNT:1
P1NODECISION

---

# HeavyIonCannon_BaseNamed_GrantedActionUnavailable
#// ⚠ HMW_172 — the counterpart of the two STILL-WORKS Action sections above: this Action is GRANTED to
#// the base, so naming the BASE (Catacombs of Cadera) removes it, where it did not remove Bacta Tank's or
#// Carbonite Chamber's own Actions. Same generic Action loop, opposite answers — the per-card list is
#// what decides.

## GIVEN
CommonSetup: rrw/bbw/{theirResources:4}
WithActivePlayer: 2
SkipPreGame: true
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_172
WithP1Hand: [SOR_095 SEC_080]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Catacombs of Cadera
- P1>UseBaseAbility

## EXPECT
P1HANDCOUNT:2
P1BASEUPGRADECOUNT:1
P1NODECISION

---

# HeavyIonCannon_UpgradeNamed_WhenPlayedDrawsNothing
#// HMW_172 — "When Played: Draw a card." is the upgrade's own ability, gated on its name by
#// CollectWhenPlayedAsUpgradeTriggers. Galen names it first; P1 then plays it — it attaches, draws nothing.

## GIVEN
CommonSetup: rrw/bbw/{myResources:3;theirResources:4}
WithActivePlayer: 2
SkipPreGame: true
WithP2Hand: SEC_046
WithP1Hand: HMW_172
WithP1Deck: [SOR_128 SOR_046]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Heavy Ion Cannon
- P1>PlayHand:0

## EXPECT
P1BASEUPGRADECOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# HeavyIonCannon_BaseNamed_WhenPlayedStillDraws
#// ⚠ HMW_172 — the SPLIT on one card: naming the BASE removes the Cannon's granted Action (above) but not
#// its own When Played. It attaches and draws.

## GIVEN
CommonSetup: rrw/bbw/{myResources:3;theirResources:4}
WithActivePlayer: 2
SkipPreGame: true
WithP2Hand: SEC_046
WithP1Hand: HMW_172
WithP1Deck: [SOR_128 SOR_046]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Catacombs of Cadera
- P1>PlayHand:0

## EXPECT
P1BASEUPGRADECOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# IntelligenceAgency_UpgradeNamed_CannotSeeTopCard
#// HMW_205 — "Attached base gains: 'You may look at the top card of your deck at any time.'" Named → the
#// permission is gone (P1NOTSEESTOPCARD asserts the server-side permission _SWUCanSeeOwnTopCard).

## GIVEN
CommonSetup: ggk/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_205
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Intelligence Agency

## EXPECT
P1BASE:UPGRADECOUNT:1
P1NOTSEESTOPCARD

---

# IntelligenceAgency_BaseNamed_CannotSeeTopCard
#// HMW_205 — base-granted, so naming the BASE (Echo Base) removes the permission too.

## GIVEN
CommonSetup: ggk/bbw/{theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_205
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Echo Base

## EXPECT
P1NOTSEESTOPCARD

---

# TheTarkinDoctrine_UpgradeNamed_NoExhaust
#// HMW_206 — "Attached base gains: 'When you play a Fortification upgrade: Exhaust an enemy unit.'"
#// Named → P1 plays Carbonite Chamber (a Fortification) and nothing is exhausted, no decision opens.

## GIVEN
CommonSetup: bbk/bbw/{myResources:1;theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_206
WithP1Hand: HMW_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:The Tarkin Doctrine
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# TheTarkinDoctrine_BaseNamed_NoExhaust
#// HMW_206 — base-granted, so naming the BASE (Capital City) switches it off too.

## GIVEN
CommonSetup: bbk/bbw/{myResources:1;theirResources:4}
WithActivePlayer: 2
WithP2Hand: SEC_046
WithP1BaseUpgrade: HMW_206
WithP1Hand: HMW_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Capital City
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:READY
P1NODECISION

---

# TwinSuns_ASeatTwoGalenBlanksASeatThreeFortify
#// ⚠ _SWUGalenNames used OtherPlayer(targetOwner), which answers 1 for every seat but seat 1 — so a Galen
#// on seat 2 never saw a card owned by seat 3. Official ruling: "Galen's ability affects each card owned
#// by each opponent." It now loops OpponentsOf(). Seat 2 names Military Academy; seat 3's Academy stops
#// granting its Marine Overwhelm.
#// Cannot pass at two seats, and cannot pass under the old line (seat 3's only OtherPlayer is seat 1).

## GIVEN
CommonSetup: rrk/bbw/{theirResources:4}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP2Hand: SEC_046
WithP3Base: SOR_024
WithP3BaseUpgrade: HMW_112
WithP3GroundArena: SOR_095:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Military Academy

## EXPECT
SEATCOUNT:3
P3BASE:UPGRADECOUNT:1
P3GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# TeamSuns_GalenDoesNotBlankATeammatesFortify
#// ⚠ The Team Suns half of the same fix. Galen reads cards owned by OPPONENTS — a teammate is never one.
#// Under the old OtherPlayer() line a seat-1 Galen was "the opponent" of seat 3 (OtherPlayer(3) = 1), so
#// it blanked its own TEAMMATE's cards. Seat 1 names Military Academy: the ENEMY (seat 2) Academy stops
#// granting Overwhelm; the TEAMMATE's (seat 3) keeps granting it.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SEC_046
WithP2BaseUpgrade: HMW_112
WithP2GroundArena: SEC_080:1:0
WithP3Base: SOR_024
WithP3BaseUpgrade: HMW_112
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Military Academy

## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P3GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
