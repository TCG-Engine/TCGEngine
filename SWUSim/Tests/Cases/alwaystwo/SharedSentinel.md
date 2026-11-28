#// ALWAYS TWO — Sentinels protect the whole team. docs/superpowers/specs/2026-11-20-swusim-always-two-design.md ("Shared Sentinels").
#// Doubles rules: "Friendly sentinels protect your teammate's units and bases." So when either enemy board has a Sentinel in the
#// attacker's arena, the attacker must attack one of those Sentinels — the other enemy board's units and base, and the Sentinel
#// owner's base, are off-limits. Saboteur still ignores Sentinel. In Twin Suns / Team Suns a Sentinel guards only its own seat
#// (the control at the bottom). SOR_098 Echo Base Defender = Sentinel; SOR_095 Battlefield Marine = plain.
# TeammatesSentinel_ProtectsBoardOne
#// Blue board 2 attacks. Red board 3 has two Sentinels; Red board 1 only a plain unit. Only board 3's Sentinels are legal.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_098:1:0
WithP3GroundArena: SOR_098:1:0
## WHEN
- P2>AttackGroundArena:0:?
## EXPECT
P2HASDECISION
P2SELECTABLEEXACT:p3GroundArena-0&p3GroundArena-1

---

# SentinelsOnBothBoards_OnlyTheSentinels
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_098:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_098:1:0
## WHEN
- P2>AttackGroundArena:0:?
## EXPECT
P2HASDECISION
P2SELECTABLEEXACT:p1GroundArena-0&p3GroundArena-0

---

# Saboteur_BypassesSharedSentinel
#// SOR_239 Rebel Pathfinder (Saboteur): every enemy unit and both enemy bases.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_239:1:0
WithP3GroundArena: SOR_098:1:0
## WHEN
- P2>AttackGroundArena:0:?
## EXPECT
P2HASDECISION
P2SELECTABLEEXACT:p1GroundArena-0&p1Base-0&p3GroundArena-0&p3Base-0

---

# NoSentinel_EverythingOffered
#// No Sentinel anywhere: both enemy boards' units and bases.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_095:1:0
## WHEN
- P2>AttackGroundArena:0:?
## EXPECT
P2HASDECISION
P2SELECTABLEEXACT:p1GroundArena-0&p1Base-0&p3GroundArena-0&p3Base-0

---

# Ambush_RespectsSharedSentinel
#// SOR_213 Syndicate Lackeys (Ambush), played by Blue board 2: the Ambush attack obeys the same team-wide Sentinel.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP2Resources: 10
WithP2Hand: SOR_213
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_098:1:0
WithP3GroundArena: SOR_098:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
## EXPECT
P2HASDECISION
P2SELECTABLEEXACT:p3GroundArena-0&p3GroundArena-1

---

# Overwhelm_ExcessGoesToTheDefendersController
#// SOR_164 Wampa (4 power, Overwhelm) kills board 3's 3-HP Marine: the 1 excess hits board 3's base, not board 1's.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_164:1:0
WithP3GroundArena: SOR_095:1:0
## WHEN
- P2>AttackGroundArena:0:P3G0
## EXPECT
P3BASEDMG:1
P1BASEDMG:0

---

# Control_TeamSuns_SentinelGuardsOnlyItsOwnSeat
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_098:1:0
WithP3GroundArena: SOR_098:1:0
## WHEN
- P2>AttackGroundArena:0:?
## EXPECT
P2HASDECISION
P2SELECTABLEEXACT:p1GroundArena-0&p1Base-0&p3GroundArena-0&p3GroundArena-1
