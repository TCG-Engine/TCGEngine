# OnAttack_EnteredThisRound_NoDeal3
#// SOR_179 Boba Fett — condition gate: the exhausted defender must NOT have entered play this round.
#// P2 plays SOR_046 this round (enters exhausted, flagged SWU_PLAYED_UNIT). Boba attacks it → exhausted
#// but entered-this-round → no deal 3; only combat damage (3). (SOR_046 survives at 7 HP.)

## GIVEN
CommonSetup: yyk/bbw/{theirResources:4;theirHandCardIds:SOR_046}
WithActivePlayer: 1
WithP1GroundArena: SOR_179:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# OnAttack_ExhaustedNotEntered_Deal3
#// SOR_179 Boba Fett — On Attack: if attacking an EXHAUSTED unit that didn't enter play this round,
#// deal 3 to the defender. Boba (3/5) attacks a seeded exhausted SOR_046 (3/7, not played this round):
#// OnAttack deals 3, then combat adds 3 → 6 total. SOR_046 survives (7 HP); Boba takes 3 counter.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# OnAttack_ReadyDefender_NoDeal3
#// SOR_179 Boba Fett — condition gate: the defender must be EXHAUSTED. Attacking a READY SOR_046 →
#// OnAttack does NOT deal 3; only combat damage (3) lands.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# SimulateRequestBoundary_EnteredThisRoundFlagSurvives
#// SOR_179 Boba Fett — the "entered play this round" flag is stamped in P2's play request and read in P1's
#// attack request, two separate processes in production. Mirrors OnAttack_EnteredThisRound_NoDeal3 with a
#// boundary after P2 plays SOR_046: the flag must survive the round-trip, so Boba's On Attack still does
#// NOT deal 3 and only combat damage (3) lands. (A lost flag would read as 6 damage here.)

## GIVEN
CommonSetup: yyk/bbw/{theirResources:4;theirHandCardIds:SOR_046}
WithActivePlayer: 1
WithP1GroundArena: SOR_179:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# OnAttack_DeployedLeaderThisRound_NoDeal3
#// HAPPY PATH FOR THE FIX (bug #1025/#1026 family), and note this card fails the OTHER WAY from Hello
#// There: the check is NEGATIVE ("didn't enter play this round"), so reading the wrong flag made a
#// freshly-DEPLOYED leader look like it had never entered play, and Boba dealt 3 damage to a unit the
#// card is meant to spare. A leader DEPLOYS into play — it enters play but is not "played".
#// P2 deploys SOR_002 Iden Versio and attacks P1's base with her, which is what EXHAUSTS her (a leader
#// deploys READY, so without that attack the "exhausted defender" gate would refuse for the wrong
#// reason). Boba then attacks her.
#// ⚠ THE SIGNAL RUNS THROUGH IDEN'S SHIELD, and that is what makes it binary. Her deployed side has
#// Shielded, so she carries one Shield token that absorbs the FIRST instance of damage:
#//   fixed:  no ability damage → the shield eats the 3 COMBAT damage → she ends on 0 damage
#//   bug:    ability deals 3 first → the shield eats THAT → the 3 combat damage lands → 3 damage
#// 0 vs 3 on the same board, and the previous-round control below is that same board reading 3.
#// ⚠ NOT Darth Sidious, even though he is the card the reports name: his deployed side has HIDDEN, so
#// after the sibling fix he cannot be attacked the phase he deploys and this section could never run.
#// Iden has no Hidden, which is exactly what makes her usable as the defender here.
## GIVEN
CommonSetup: yyk/bbw/{theirLeader:SOR_002}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SOR_179:1:0
## WHEN
- P2>DeployLeader
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2LEADER:DEPLOYED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
#// Iden is 4 power, so Boba takes 4 back — proof the combat actually happened, which is how this is
#// distinguished from an attack that was simply refused (that would read 0/0).
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# OnAttack_CreatedTokenThisRound_NoDeal3
#// HAPPY PATH, the CREATED leg. A token created this round entered play without being played, so it is
#// spared too. P2 plays JTL_099 (When Played: create an X-Wing token); the token enters EXHAUSTED, which
#// satisfies the gate without any extra action.
#// ⚠ THE SIGNAL IS THE DAMAGE BOBA TAKES BACK, not the token dying — the token dies either way (3 damage
#// clears its 3 HP whether that 3 comes from the ability or from combat). The two paths differ in WHEN:
#//   bug:    On Attack deals 3 pre-combat, the token is defeated BEFORE combat damage, Boba takes 0
#//   fixed:  no ability damage, so combat happens and the 2-power token deals 2 back to Boba
## GIVEN
CommonSetup: yyk/bbw/{theirResources:6;theirHandCardIds:JTL_099}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_179:1:0
## WHEN
- P2>PlayHand:0
#// JTL_099 itself is a GROUND unit — the X-Wing token it creates is the only thing in space, at index 0.
- P1>AttackSpaceArena:0:theirSpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:2

---

# OnAttack_LeaderDeployedAPREVIOUSRound_Deal3
#// THE CONTROL, and the one that stops the two sections above passing on a rule that simply spares every
#// leader. Same board, same shield — but the round turns over first, so the marker clears in
#// RegroupPhaseStart and by the next action phase Iden "didn't enter play this round". Boba's 3 damage
#// DOES apply, the shield absorbs THAT instead of the combat damage, and she ends on 3 rather than 0.
#// ⚠ Iden must attack AGAIN in round 2: regroup readies her, and the ability's gate needs an EXHAUSTED
#// defender. Without that second attack this section would read 0 for a reason unrelated to the marker.
## GIVEN
CommonSetup: yyk/bbw/{theirLeader:SOR_002}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SOR_179:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
## WHEN
- P2>DeployLeader
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
#// ⚠ THE NEW ACTION PHASE OPENS ON P1, not P2 — measured. Without this pass, P2's attack lands out of
#// turn and is a silent no-op, Iden stays READY, and the ability's exhausted-defender gate refuses for a
#// reason that has nothing to do with the entered-play marker this section exists to test.
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
