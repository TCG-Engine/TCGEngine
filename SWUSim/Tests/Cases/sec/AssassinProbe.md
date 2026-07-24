# WhenDefeated_AoEExhaustedGround
#// SEC_263 Assassin Probe (Ground, 4/4) — When Defeated: deal 1 to each exhausted enemy ground unit.
#//   SEC_263 attacks LAW_124 (idx0) and dies → the two exhausted enemies take 1 each; the ready SOR_095 is untouched.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_263:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:0
P1NODECISION

---

# WhenDefeated_SpaceAndFriendlyExcluded
#// SEC_263 Assassin Probe (4/4) — the "deal 1 to each exhausted enemy GROUND unit" only hits enemy
#//   ground units: an exhausted enemy SPACE unit and an exhausted FRIENDLY ground unit are untouched.
#//   SEC_263 attacks LAW_124 (4/7, ready) and dies to the 4 counter-damage. The exhausted enemy ground
#//   SOR_046 takes 1; the exhausted enemy space SOR_040 and the exhausted friendly SOR_046 take 0.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SEC_263:1:0
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:0:0
WithP2SpaceArena: SOR_040:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# WhenDefeated_UnderEnemyControl_NGOR
#// SEC_263 Assassin Probe — when P2 takes control of the Probe with No Glory, Only Results (JTL_043) and
#//   defeats it, the When Defeated resolves under P2's control, so "each exhausted ENEMY ground unit" now
#//   means P1's units. P1's exhausted ground SOR_046 takes 1; P2's own exhausted ground SOR_046 takes 0.

## GIVEN
CommonSetup: rrk/bbk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SEC_263:1:0
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_046:0:0
WithP2Resources: 6
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
