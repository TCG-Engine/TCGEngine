# Event_NoLegalTarget_HadNoEffect
#// User decision 2026-09-11 (gamelog-updates #2): an ability that resolves and changes NOTHING says so.
#// ~610 "no legal target → return" exits in 521 card files made an ability resolve silently. The
#// dispatchers compare the whole serialized gamestate before and after the ability's closure.
#// SOR_077 Takedown: "Defeat a unit with 5 or less remaining HP." — P2 controls only a 7-HP unit.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_077
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
LOGCONTAINS:P1's [[SOR_077|Takedown]] had no effect

---

# Trigger_ConditionNotMet_HadNoEffect
#// SHD_140 Trandoshan Hunters: "When Played: If an enemy unit has a Bounty, give an Experience token to
#// this unit." No enemy has a Bounty. (Fixture shape from shd/TrandoshanHunters.md.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_140
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
LOGCONTAINS:P1's [[SHD_140|Trandoshan Hunters]] had no effect

---

# AbilityThatDidSomething_NoLine
#// The negative: an ability that changed anything (here, a token) never gets the line.
#// SOR_036 Gideon Hask: "When an enemy unit is defeated: Give an Experience token to a friendly unit."

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_036:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
LOGCOUNT:0:had no effect

---

# DispatcherWithoutAClosure_NoLine
#// A dispatcher reached for a card with no closure of that kind must stay silent. JTL_221 Stolen AT-Hauler's
#// "When Defeated" is applied when the card is DISCARDED (cardDiscardedHandlers), so its When Defeated
#// dispatch runs nothing — the first draft (a probe around the whole DispatchTrigger switch) logged "had
#// no effect" for a permission it had in fact granted. (Fixture from jtl/StolenAthauler.md,
#// StealBackAndForth — the exact board that produced the false line.)

## GIVEN
CommonSetup: grw/yrw
WithP1SpaceArena: JTL_221:1:3
WithP1SpaceArena: JTL_153
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P2>PlayFromOpponentDiscard:0
- P1>AttackSpaceArena:0:0

## EXPECT
LOGCONTAINS:P2 played [[JTL_221|Stolen AT-Hauler]] from P1's discard pile
LOGCOUNT:0:had no effect

---

# OnAttack_ConditionalMay_GateFails_HadNoEffect
#// The On Attack dispatcher's leg. SOR_067 Rugged Survivors: "On Attack: If you control a leader unit, you
#// may draw a card." No leader unit → the gate fails → nothing happens. (Had the gate held, the queued
#// "you may" question is itself a state change, so it would NOT log — even if later declined.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_067:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
LOGCONTAINS:P1's [[SOR_067|Rugged Survivors]] had no effect

---

# OverTrigger_ConditionalUpgradeGrant_NotCollected
#// Triage of the "had no effect" audit (2026-09-11): an ability that doesn't EXIST in the current state was
#// still collected and dispatched, its closure bailed, and the log said "had no effect" for an ability that
#// never triggered. SOR_054 Jedi Lightsaber grants its On Attack only while attached to a Force unit; SOR_046
#// is not one. Fixed by consulting _SWUOnAttackAbilityActive where On Attack triggers are collected.
#// (Fixture from sor/JediLightsaber.md, NonForceHostNoDebuff.)

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_054
WithP2GroundArena: SOR_119:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:6
LOGCOUNT:0:had no effect

---

# OverTrigger_CoordinateOnAttackInactive_NotCollected
#// "Coordinate - On Attack" exists only while its controller has 3+ units. TWI_096 Aayla Secura attacks alone.
#// (Fixture from twi/AaylaSecura_MasterOfTheBlade.md, Coordinate_Inactive_TakesCounter.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_096:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
LOGCOUNT:0:had no effect

---

# OverTrigger_CoordinateWhenPlayedInactive_NotCollected
#// "Coordinate - When Played" — TWI_095 Pelta Supply Frigate enters with only 2 friendly units.
#// (Fixture from twi/PeltaSupplyFrigate.md, Coordinate_Inactive_NoClone.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:TWI_095}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
LOGCOUNT:0:had no effect

---

# OverTrigger_UnitOnlyWhenPlayed_NotCollectedAsAPilot
#// JTL_100 Poe Dameron: "When played as a unit: …" does not exist when Poe is played as a PILOT. The engine
#// used to dispatch a registered no-op stub (to block the fallback to his unit When Played) — which logged
#// "had no effect" AND, as a real side bug, CONSUMED an armed LOF_197 Qui-Gon's Aethersprite repeat ("the
#// next time you use a When Played ability this phase…") on an ability that never triggered. A "When played
#// as a unit"-only card now collects no entry trigger at all when played as an upgrade.
#// (Fixture from jtl/PoeDameron_OneHellOfAPilot.md, PlayAsPilot_NoToken, plus an armed repeat.)

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0
WithP1GlobalEffect: SWU_LOF197_REPEAT

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_100
P1GLOBALEFFECT:SWU_LOF197_REPEAT
LOGCOUNT:0:had no effect

---

# EffectAppliedElsewhere_Stub_NoFalseLine
#// Triage of the audit: a few On Attack abilities register an intentionally EMPTY closure because the effect
#// is applied synchronously in ExecuteSWUAttack (IBH_010 Han Solo: "On Attack: the defender gets -2/-0 for
#// this attack"). The closure never changes anything, so every use logged a FALSE "had no effect" — while the
#// -2/-0 visibly worked (Han takes 2, not 4). The stub is registered in $swuLogEffectAppliedElsewhere.
#// (Fixture from ibh/HanSolo_ScruffylookingNerfHerder.md, OnAttack_DefenderMinusTwo.)

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: IBH_010:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
LOGCOUNT:0:had no effect
