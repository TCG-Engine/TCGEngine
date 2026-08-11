# PlayingAUnitOffersAnAttackWithPlusTwo
#// HMW_124 Luminara Unduli, Besieged General (7/7, Force/Jedi/Republic) — "When you play a unit
#// (including this one): You may attack with a unit. It gets +2/+0 for this attack."
#// Luminara (power 7) is the only ready unit, so she takes the attack herself: 7 + 2 = 9 to the base.
#// The POWER assertion afterwards is the duration proof — "+2/+0 for THIS attack" must be gone once the
#// attack resolves (a phase buff would still read 9).

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: HMW_124:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:9
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:2
P1NODECISION

---

# LuminarasOwnPlayTriggersHerAbility
#// "(including this one)" — unlike the "another unit" observers, playing Luminara herself arms the
#// trigger. She enters exhausted so she cannot be the attacker; the Battlefield Marine already in play
#// swings for 3 + 2 = 5.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_124

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_124
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:3

---

# DecliningTheOfferDoesNothing
#// "You may" — declining the MZMAYCHOOSE (AnswerDecision:-) leaves the would-be attacker READY and
#// deals no damage.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: HMW_124:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENACOUNT:2
P1NODECISION

---

# PlusTwoAppliesToCombatAgainstAUnitToo
#// The bonus is real attack power, not a base-damage special case. A 3-power Consular Security Force
#// deals 3 + 2 = 5 to a 3/7 defender (which survives) and takes the defender's 3 back. Luminara is
#// seeded exhausted so the attacker choice is unambiguous.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: [HMW_124:0:0 SOR_046:1:0]
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:1:POWER:3
P2BASEDMG:0

---

# EveryUnitPlayTriggersItAgain
#// Not once each round/phase — a second unit played in the same action phase arms it again. Luminara
#// swings for 9, then the Consular Security Force swings for 3 + 2 = 5. Total 14.

## GIVEN
CommonSetup: ggw/ggw/{myResources:12}
P1OnlyActions: true
WithP1GroundArena: [HMW_124:1:0 SOR_046:1:0]
WithP1Hand: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:14
P1GROUNDARENACOUNT:4

---

# TheOfferIsEveryReadyFriendlyUnitInBothArenas
#// The OFFER itself, not a branch: ready units in BOTH arenas are eligible ("a unit" has no arena
#// restriction), an exhausted unit is not, and the unit that was just played (ground index 2, entered
#// exhausted) is not. Answering would prove none of this.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: [HMW_124:1:0 SOR_046:0:0]
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# NoReadyUnitRaisesNoPrompt
#// With every friendly unit exhausted (the played one enters exhausted too) there is nothing to attack
#// with, so the ability raises no decision at all rather than a dead prompt.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: HMW_124:0:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASEDMG:0
P1GROUNDARENACOUNT:2

---

# AUnitThatCantAttackIsNotOffered
#// The restriction is enforced at SELECTION time. JTL_059 ("This unit can't attack.") is READY, so a
#// naive ready-only pool would prompt for it; the pool must come back empty.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: HMW_124:0:0
WithP1SpaceArena: JTL_059:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASEDMG:0

---

# PlayingAnEventDoesNotTrigger
#// "When you play a UNIT" — an event doesn't arm it. SOR_251 Confiscate is neutral (no aspect penalty)
#// and fizzles cleanly with no upgrades in play, so any pending decision would be Luminara's.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: HMW_124:1:0
WithP1Hand: SOR_251

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1DISCARDCOUNT:1

---

# PlayingAnUpgradeDoesNotTrigger
#// Nor does an upgrade. Luminara is the only friendly unit, so Academy Training auto-attaches with no
#// host choice — leaving P1NODECISION as a clean assertion.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: HMW_124:1:0
WithP1Hand: SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# APilotPlayedAsAnUpgradeDoesNotTrigger
#// A Piloting card's CardType is "Unit" even when it is played as a pilot, but CR 17.c makes it an
#// upgrade for that purpose — so playing JTL_046 onto the X-Wing is an upgrade play, not a unit play.
#// Both Luminara and the X-Wing are ready, so a wrongly-armed trigger would leave a decision pending.

## GIVEN
CommonSetup: ggw/ggw/{myResources:12}
P1OnlyActions: true
WithP1GroundArena: HMW_124:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: JTL_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1NODECISION
P2BASEDMG:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:READY

---

# AnOpponentPlayingAUnitDoesNotTrigger
#// "When YOU play a unit" — the observer loop is scoped to the playing player's own units, so an enemy
#// unit play arms nothing for either side.

## GIVEN
CommonSetup: ggw/ggw/{theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1GroundArena: HMW_124:1:0
WithP2Hand: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P1NODECISION
P1BASEDMG:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY

---

# WithoutLuminaraInPlayNothingHappens
#// Absence guard — the attack offer is hers, not a property of playing a unit.

## GIVEN
CommonSetup: ggw/ggw/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
