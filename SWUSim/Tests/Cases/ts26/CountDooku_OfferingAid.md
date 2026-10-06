# DeployedOnAttackTwoDroidsRestore
#// TS26_01 Count Dooku (leader deployed, 6/7) — Restore 2 + On Attack: create 2 Battle Droid tokens. The
#// deployed Dooku attacks the enemy base: Restore 2 heals P1's base (3 → 1), 2 Battle Droids are created,
#// and 6 combat damage hits the enemy base.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01:1:1;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENACOUNT:3
P1BASEDMG:1
P2BASEDMG:6

---

# FrontBothHealAndDroid
#// TS26_01 Count Dooku (leader front) — Action [Exhaust]: choose 2 players; they each heal 1 from their
#// base and create a Battle Droid token. In 2-player, both bases heal 1 (3 → 2) and both players get a
#// Battle Droid.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01;myBaseDamage:3;theirBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1BASEDMG:2
P2BASEDMG:2
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED

---

# TwinSuns_ChooseTwoPLAYERS_NotJustBothSeats
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — "Choose 2 PLAYERS. They each heal 1 damage from their base
#// and create a Battle Droid token." Forced at two seats (both), a real pick of 2 out of N above that.
#// It always resolved to the caster + OtherPlayer($player).
#// P1 picks SEATS 3 and 4 — its own TEAMMATE and one opponent — and neither is the caster: P3 and P4 each
#// heal (4 → 3) and gain a droid, while P1's own base stays on 3 and seat 2 is untouched. The old code
#// would have healed P1 and P2 instead, so all four assertions move.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01;myBaseDamage:3}
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:4
WithP4Base: SOR_019:4
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:P3
- P1>AnswerDecision:P4
## EXPECT
SEATCOUNT:4
P3BASEDMG:3
P4BASEDMG:3
P1BASEDMG:3
P3GROUNDARENACOUNT:1
P4GROUNDARENACOUNT:1

---

# TwinSuns_LogNamesTheSeatThatCreatesTheDroid
#// TS26_01 Count Dooku — "Choose 2 players. THEY each ... create a Battle Droid token." The chosen player CREATES
#// the token and so OWNS it (CR 1.x.a). The log used to read "P1's Count Dooku created a Battle Droid token for
#// P3", which reads as P1's droid handed to P3 — game 1485163 (2026-10-03) took it for a unit Tobias Beckett
#// could reclaim. A token another player creates now names THAT player first, the CONTROL line's shape:
#// "P3 created a Battle Droid token ([[TS26_01|Count Dooku]])". The caster's own droid keeps the source line.
#// P1 picks ITSELF and seat 3, so both branches are in one section.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01;myBaseDamage:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP3Base: SOR_019:4
WithP4Base: SOR_019:4
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:P1
- P1>AnswerDecision:P3
## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:1
P3GROUNDARENACOUNT:1
LOGCONTAINS:P1's [[TS26_01|Count Dooku]] created a Battle Droid token
LOGCONTAINS:P3 created a Battle Droid token ([[TS26_01|Count Dooku]])
LOGCOUNT:0:token for P

---

# TwinSuns_ActionStaysOpenUntilBothPlayersArePicked
#// TS26_01 Count Dooku at 3+ seats queues the two player picks, then CLOSED THE ACTION straight away
#// (SWUAfterAction, not SWUQueueAfterAction): the turn passed to seat 2 while P1 still had both picks pending,
#// so seat 2 could act in the middle of Dooku's ability. Found in the Twin Suns log review (2026-10-03) — the
#// close also cleared the log source, so the first pick read "P1 chose P3" with no "(Count Dooku)".
#// ⚠ No P1OnlyActions: it makes TURNPLAYER unobservable. Each pick point pins TURNPLAYER:1; the end pins
#//   exactly one turn pass (TURNPLAYER:2 + NOEXTRAACTION).
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01;myBaseDamage:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:4
WithP4Base: SOR_019:4
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
TURNPLAYER:1

---

# TwinSuns_ActionStaysOpenThroughTheSecondPick
#// Same as above, one pick later: after the FIRST pick, P1 still owes the second and still holds the turn.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01;myBaseDamage:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:4
WithP4Base: SOR_019:4
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:P3
## EXPECT
P1HASDECISION
TURNPLAYER:1

---

# TwinSuns_BothPicksThenOneTurnPass_AndBothPicksNameDooku
#// After both picks: the droids and heals land, the turn passes ONCE, and BOTH choice lines carry the source.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01;myBaseDamage:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:4
WithP4Base: SOR_019:4
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:P3
- P1>AnswerDecision:P4
## EXPECT
P3GROUNDARENACOUNT:1
P4GROUNDARENACOUNT:1
P3BASEDMG:3
P4BASEDMG:3
TURNPLAYER:2
NOEXTRAACTION
LOGCONTAINS:P1 chose P3 ([[TS26_01|Count Dooku]])
LOGCONTAINS:P1 chose P4 ([[TS26_01|Count Dooku]])
