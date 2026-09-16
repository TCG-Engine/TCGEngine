# WhenPlayed_CreatesABeast
#// COVERAGE: offer=N/A (STRUCTURAL: nothing chosen) · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=N/A (STRUCTURAL: fixed quantity) · control=N/A (the token is created for the ability's
#//           controller) · reqboundary=N/A (STRUCTURAL: no decision)
#//           paths=WhenDefeated_InCombat and WhenDefeated_OnTheOpponentsTurn
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_144 Howler Pack — Unit (Ground) 3/3, cost 6, [Command], Creature.
#// "When Played/When Defeated: Create a Beast token."  HMW_T03 Beast = 3/3 Creature token.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_144

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# WhenDefeated_InCombat
#// Howler Pack (3/3) attacks LAW_124 (4/7) and dies.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_144:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1DISCARDCOUNT:1

---

# WhenDefeated_OnTheOpponentsTurn

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_144:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03

---

# PlacedButNotPlayed_NoBeast
#// A unit seeded onto the board never ENTERS play, so its When Played does not fire.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_144:1:0

## EXPECT
P1GROUNDARENACOUNT:1
