# EnemyDefeatedByGideon_GivesExp
#// SOR_036 Gideon Hask (5/5) — "When an enemy unit is defeated: Give an Experience token to a
#// friendly unit." Gideon attacks and defeats P2's 3/1; the reactive trigger gives an Experience
#// token to the only friendly unit (himself) → 6/6 (with 3 combat damage on him).
#// COVERAGE: offer=EnemyDefeated_OfferIsAllFriendlyUnits (exact recipient pool, both arenas)
#//           decline=N/A (mandatory "give", no "you may") · control=FriendlyOwnedByOpponent_NoTrigger
#//           (controller at defeat decides friendliness, not the owner) · boundary
#//           pair=EnemyDefeatedByGideon_GivesExp / EnemyDefeatedWithEvent_GivesExp (fires) vs
#//           FriendlyDefeated_NoTrigger (silent) + EnemyWithPilotDefeated_TriggersOnce (exactly once)
#//           · reqboundary=EnemyDefeatedWithEvent_GivesExp + EnemyWithPilotDefeated_TriggersOnce (the
#//           pending trigger survives the answered target-choice boundary before the recipient pick)

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_036:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# EnemyDefeatedWithEvent_GivesExp
#// SOR_036 Gideon Hask (5/5) — "When an enemy unit is defeated: Give an Experience token to a
#// friendly unit." Gideon attacks and defeats P2's 3/1; the reactive trigger gives an Experience
#// token to the only friendly unit (himself) → 6/6 (with 3 combat damage on him).

## GIVEN
CommonSetup: bbk/grk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1GroundArena: SOR_036:1:0
WithP1GroundArena: SEC_080
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SOR_077

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>ChooseMyGroundUnit:1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4

---

# EnemyDefeated_OfferIsAllFriendlyUnits
#// SOR_036 Gideon Hask — the Experience recipient pool is EVERY friendly unit, in both arenas, and
#// nothing else. Gideon defeats P2's 3/1 in combat; the recipient choice is left PENDING so the exact
#// legal-target set can be inspected: Gideon himself, the ground Consular Security Force, and the
#// SPACE-arena Cartel Spacer — the enemy has no unit left to appear in the pool.

## GIVEN
CommonSetup: bbk/grk
P1OnlyActions: true
WithP1GroundArena: SOR_036:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0

---

# FriendlyDefeated_NoTrigger
#// SOR_036 Gideon Hask — a FRIENDLY unit being defeated does not trigger him. P1's own 3/1 attacks
#// P2's Wampa (4/5) and dies to the 4 counter-damage (Wampa survives on 3). No Experience is offered
#// or given: Gideon stays bare and no decision is pending.

## GIVEN
CommonSetup: bbk/grk
P1OnlyActions: true
WithP1GroundArena: SOR_036:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# FriendlyOwnedByOpponent_NoTrigger
#// SOR_036 Gideon Hask — "enemy" is judged by CONTROL at the moment of defeat, not ownership. P1
#// controls a Battlefield Marine OWNED by P2 (the end state after a control-take). That marine attacks
#// P2's Wampa and dies: it was friendly to Gideon's controller when defeated, so Gideon must NOT
#// trigger even though the card returns to its owner P2's discard.

## GIVEN
CommonSetup: bbk/grk
P1OnlyActions: true
WithP1GroundArena: SOR_036:1:0
WithP1GroundArenaControlled: SOR_095:2
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_036
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# EnemyWithPilotDefeated_TriggersOnce
#// SOR_036 Gideon Hask — defeating an enemy unit that carries a Pilot upgrade triggers him exactly
#// ONCE: the pilot card leaving play with its host is not a second unit defeat. P1's Vanquish defeats
#// P2's piloted Cartel Spacer; one recipient prompt resolves onto Gideon (5/5 → 6/6) and no further
#// decision is pending afterwards.

## GIVEN
CommonSetup: bbk/grk/{myResources:5;handCardIds:SOR_078}
P1OnlyActions: true
WithP1GroundArena: SOR_036:1:0
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_178:1:0
WithP2SpaceArenaPilot: 0:JTL_108

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION

---

# GideonTradesWithTheEnemy_StillGivesExperience
#// CR simultaneous-removal: the condition reads the pre-defeat state, so a Gideon who dies in the very
#// combat that defeats the enemy unit still reacts. Gideon (5/5) trades with the enemy AT-DW (5/5);
#// the Experience auto-lands on the only surviving friendly, SOR_046 (3/7 -> 4/8).

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_036:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_037:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1NODECISION
