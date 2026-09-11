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
