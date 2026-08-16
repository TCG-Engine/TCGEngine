# WhenDefeatedShieldEnemy
#// LAW_091 Val (2/4) — When Defeated: give a Shield token to an enemy unit. Pre-damaged Val (2) attacks
#// SOR_046 and dies to the counter; the enemy SOR_046 gains a Shield.

## GIVEN
CommonSetup: byk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_091:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# WhenPlayedShieldFriendly
#// LAW_091 Val (2/4) — When Played: give a Shield token to another friendly unit. SOR_063 is the only
#// other -> auto.

## GIVEN
CommonSetup: byk/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Hand: LAW_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# WhenPlayedShieldPool_AnotherFriendlyEitherArena
#// LAW_091 Val — "When Played: Give a Shield token to ANOTHER FRIENDLY unit." Two restriction words, no
#// arena word and no non-leader word, and the board carries a witness for each: Val herself (played into
#// myGroundArena-3) must be OUT on "another"; P2's SOR_095 must be OUT on "friendly"; P1's SPACE SOR_237
#// must be IN because the text names no arena; and P1's DEPLOYED LEADER at myGroundArena-2 must be IN,
#// because a leader unit is a friendly unit and nothing here says "non-leader". WhenPlayedShieldFriendly
#// seats a single legal target and auto-resolves, so the filter has never actually been read.

## GIVEN
CommonSetup: byk/bgw/{myResources:2;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [SOR_063:1:0 SOR_046:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1GROUNDARENAUNIT:2:ISLEADERUNIT
P1GROUNDARENAUNIT:3:CARDID:LAW_091
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2&mySpaceArena-0

---

# WhenDefeatedShieldPool_EnemyOnlyEitherArena
#// COVERAGE: offer=WhenPlayedShieldPool_AnotherFriendlyEitherArena (friendly half: "another", controller
#//           scope, both arenas, leader unit included) + WhenDefeatedShieldPool_EnemyOnlyEitherArena
#//           (defeat half: the mirrored ENEMY scope, both arenas, leader unit included) · decline=N/A
#//           (neither trigger is a "you may"; both are mandatory chooses) · control=N/A (no
#//           control-change text) · boundary=WhenPlayedShieldFriendly (friendly grant) vs
#//           WhenDefeatedShieldEnemy (enemy grant) — the two halves of the same card point at opposite
#//           sides · reqboundary=WhenDefeatedShieldPool_EnemyOnlyEitherArena (the pick is read in the
#//           request after the combat that defeated Val).
#// LAW_091 Val — "When Defeated: Give a Shield token to AN ENEMY unit." The scope inverts relative to the
#// When Played half, and the pool must invert with it. Pre-damaged Val (2 damage on a 2/4) attacks P2's
#// SOR_046 and dies to the counter-damage; her When Defeated must then offer EVERY enemy unit and nothing
#// else: P1's surviving SOR_095 must be OUT on "enemy", P2's SPACE SOR_225 must be IN (no arena word), and
#// P2's DEPLOYED LEADER at theirGroundArena-2 must be IN (no non-leader word). WhenDefeatedShieldEnemy
#// seats a single enemy and auto-resolves, so the enemy-only scope has never been proven.

## GIVEN
CommonSetup: byk/bgw/{theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [LAW_091:1:2 SOR_095:1:0]
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:2:ISLEADERUNIT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2&theirSpaceArena-0
