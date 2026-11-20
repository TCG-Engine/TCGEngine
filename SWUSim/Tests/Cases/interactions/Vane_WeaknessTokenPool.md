# LeaderAction_WeaknessOnMyUnit_IsOffered_PoolExact
#// Bug reports P-10 / P-11 (Twin Suns, 2026-10-28): the same player's Vane leader Action "sometimes" offered
#// the Weakness token Maz Kanata had given their own unit. Official Vane ruling: "A friendly upgrade is any
#// upgrade you put into play or any token upgrade on a friendly unit." So a Weakness on P1's unit IS
#// friendly, and a Weakness on P2's unit is NOT. P1's SOR_095 #0 carries a Weakness, #1 carries P1's
#// SOR_120; P2's SOR_046 carries a Weakness. The cost pool is exactly P1's two hosts (N+1, so it doesn't
#// auto-resolve), and paying with the Weakness defeats it and deals the 2.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# LeaderAction_WeaknessOnMyUnit_PaysTheCost
#// Same board as above, answered: P1 defeats the Weakness on SOR_095 #0 (back to 3/3) and deals 2 to P2's
#// base. The SOR_120 on #1 and P2's own Weakness are untouched.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:2
P1LEADER:EXHAUSTED

---

# LeaderAction_EnemyGaveTheWeakness_StillFriendly
#// "Any token upgrade on a friendly unit" — who GAVE the token doesn't matter. P2 plays HMW_100 Torrent
#// ("Give a Weakness token to a unit") onto P1's SOR_095 #0; P1's #1 carries SOR_120. On P1's turn the
#// Vane cost pool is both of P1's hosts.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 6
WithP2Hand: HMW_100
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# P10_JuniorSenatorReturnsTheWeakness_LoggedAndVaneHasNoCost
#// The P-10 board: between P1 giving their unit a Weakness and P1 trying Vane, P2 played SEC_200 Junior
#// Senator ("you may return an upgrade that costs 3 or less to its owner's hand") on it. A returned token
#// leaves play and ceases to exist, so P1 had no friendly upgrade left and Vane's cost was unpayable —
#// correct. But Junior Senator's token branch removed the token WITHOUT a game-log line, so the player
#// still believed the Weakness was there. The removal must be logged, and Vane must stay ready.
## GIVEN
CommonSetup: grk/brk/{myLeader:ASH_012}
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 6
WithP2Hand: SEC_200
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0.u0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:0
LOGCONTAINS:P2's [[SEC_200|Junior Senator]] returned a Weakness token on P1's [[SOR_095|Battlefield Marine]] (it left play)
P1LEADER:READY
P2BASEDMG:0
