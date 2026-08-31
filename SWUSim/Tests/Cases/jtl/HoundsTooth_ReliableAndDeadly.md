# DealsFirstVsExhausted
#// JTL_185 Hound's Tooth — While attacking an exhausted unit that didn't enter play this phase, it deals
#// combat damage before the defender. Hound's Tooth (3 power) attacks the exhausted SOR_225 (2/1):
#// SOR_225 is defeated before it can deal its counter, so Hound's Tooth takes 0 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_185:1:0
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_185
P1SPACEARENAUNIT:0:DAMAGE:0

---

# SimulateRequestBoundary_EnteredPlayThisPhaseSurvivesRoundTrip
#// JTL_185 Hound's Tooth — "an exhausted unit that DIDN'T enter play this phase" is read from a flag
#// stamped when the defender was played, on an earlier action by the OTHER player. In production those are
#// separate requests, so the flag must live in the gamestate rather than a transient global. P2 plays
#// SOR_225 (2/1, enters exhausted); after the boundary P1's Hound's Tooth (4/3) attacks it. Because the
#// defender DID enter play this phase, Hound's Tooth does NOT deal damage first: they trade simultaneously,
#// so SOR_225 dies AND Hound's Tooth takes its 2. If the flag were lost across the boundary the engine
#// would wrongly grant deals-first and Hound's Tooth would take 0.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: JTL_185:1:0
WithP2Hand: SOR_225
WithP2Resources: 3

## WHEN
- P2>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_185
P1SPACEARENAUNIT:0:DAMAGE:2


---

# NoFirstStrike_VsTokenCreatedThisPhase
#// HAPPY PATH FOR THE FIX (bug #1025/#1026 family). Like SOR_179 this is a NEGATIVE check — "an
#// exhausted unit that DIDN'T enter play this phase" — so reading the wrong flag failed in the GENEROUS
#// direction: anything that entered play without being PLAYED looked like it had never entered, and
#// Hound's Tooth wrongly got to strike first against it.
#// A token CREATED this phase is exactly that shape, and it enters EXHAUSTED, so it satisfies the gate
#// with no extra action. P2 plays JTL_099 (a GROUND unit) whose When Played creates an X-Wing token in
#// space — the token is therefore the only thing in P2's space arena, at index 0.
#//   fixed:  no first strike → simultaneous damage → the 2-power token trades and deals 2 back
#//   bug:    first strike → the token dies before answering → Hound's Tooth takes 0
#// ⚠ P2 NEEDS 12 RESOURCES, not 6. P2's leader here is Villainy and JTL_099 is Heroism, so the aspect
#// penalty applies; at 6 the card silently stayed in hand, P2's board was EMPTY, and the section
#// measured an attack that never happened.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 12
WithP2Hand: JTL_099
WithP1SpaceArena: JTL_185:1:0
## WHEN
- P2>PlayHand:0
- P1>AttackSpaceArena:0:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:2

---

# NoFirstStrike_VsTokenCreatedThisPhase_ControlNextPhase
#// THE CONTROL for the section above: the same token, one round later. The marker clears in
#// RegroupPhaseStart, so the token now "didn't enter play this phase" and Hound's Tooth DOES strike
#// first — the token dies before answering and Hound's Tooth takes 0.
#// ⚠ The token READIES during regroup, and the gate needs an EXHAUSTED defender, so P2 attacks P1's base
#// with it first. The new action phase opens on P1, hence the pass before that attack.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 12
WithP2Hand: JTL_099
WithP1SpaceArena: JTL_185:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
## WHEN
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:0:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:0

#// ⚠ NO DEPLOYED-LEADER SECTION HERE, DELIBERATELY, and this is a real coverage gap rather than an
#// oversight. Hound's Tooth is a SPACE unit, and HMW_004 Grand Moff Tarkin is the ONLY leader in the
#// game whose deployed side lands in the space arena. The Death Star comfortably survives Hound's Tooth's
#// 4 power, and first-strike only changes anything when the attacker KILLS the defender — so a
#// deployed-leader fixture for this card produces identical boards either way and could never fail.
#// The deployed-leader leg of this same fix is covered where it CAN discriminate:
#// Tests/Cases/sor/BobaFett_Disintegrator.md::OnAttack_DeployedLeaderThisRound_NoDeal3 and
#// Tests/Cases/keywords/Hidden_DeployedAndCreated.md. All three read the same helper.
