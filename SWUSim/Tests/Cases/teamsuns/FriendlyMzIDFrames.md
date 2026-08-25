# Damage_LandsOnTheTeammatesUnit
#// THE FRAME HAZARD (spec §6.4). A `p{n}GroundArena-{i}` mzID has, until Team Suns, only ever meant an
#// OPPONENT's card. Now a FRIENDLY pool can hand one to an applier that assumes a foreign frame — and
#// the failure mode is SILENT: the effect simply does not land. No error, no crash, nothing in the log.
#// So every effect class gets a section that ANSWERS a teammate's mzID and asserts the result landed.
#//
#// SOR_172 Open Fire — "Deal 4 damage to a unit." Answer seat 3 (the RED teammate) and the damage must
#// be ON seat 3's unit, with seat 1's own unit untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SOR_172
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P4GROUNDARENAUNIT:0:DAMAGE:0

---

# Defeat_RemovesTheTeammatesUnit
#// IBH_095 You Have Failed Me — "Defeat a friendly unit. If you do, ready a friendly unit with 5 or less
#// power." USER RULING 2026-08-25: a teammate's unit is a legal sacrifice. Answer seat 3 and its unit
#// must actually LEAVE PLAY — a frame mismatch here would look like the defeat silently fizzling while
#// the "If you do" rider still ran.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: IBH_095
WithP1GroundArena: [SOR_046:1:0 SOR_046:0:0]
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2

---

# Heal_LandsOnTheTeammatesUnit
#// SOR_074 Repair — "Heal 3 damage from a unit or base." Seat 3's unit starts on 5 damage; after
#// answering it, 2 must remain. A frame mismatch would leave it on 5.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SOR_074
WithP1GroundArena: SOR_046:1:2
WithP3GroundArena: SOR_046:1:5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Buff_AppliesToTheTeammatesUnit
#// LAW_151 Profiteering Hunter — "When Played: ANOTHER FRIENDLY unit gets +1/+1 for this phase."
#// SOR_046 is 3 power / 7 HP, so a buffed teammate reads 4/8 while seat 1's own unit stays 3/7.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: LAW_151
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:POWER:4
P3GROUNDARENAUNIT:0:HP:8
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7

---

# Tokens_LandOnTheTeammatesUnit
#// LOF_054 Calm in the Storm — "Exhaust a friendly unit. If you do, give a Shield token and 2 Experience
#// tokens to it." Three separate appliers (exhaust + Shield + Experience) all aimed at a foreign-frame
#// mzID, so this covers the token classes and the exhaust in one section.

## GIVEN
CommonSetup: bgw/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: LOF_054
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:EXHAUSTED
P3GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:READY

---

# UpgradeAttach_LandsOnTheTeammatesUnit
#// SEC_069 Nimble Prowess — "Attach to a FRIENDLY unit." Attaching an upgrade across seats is the
#// riskiest frame case: the upgrade is played by seat 1 but must become a subcard of seat 3's unit.

## GIVEN
CommonSetup: bgw/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SEC_069
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Bounce_ReturnsToTheTEAMMATES_Hand_NotTheCasters
#// ⚠ THE SHARPEST FRAME CASE. SOR_209 Pirated Starfighter — "When Played: Return a FRIENDLY non-leader
#// unit to ITS OWNER'S hand." Seat 1 plays the card, but the chosen unit belongs to seat 3, so it must
#// land in SEAT 3's hand. Owner and controller diverge here, and the classic failure is the card going
#// to whoever RESOLVED the effect (seat 1) instead of whoever OWNS it.
#//
#// Both hand counts are asserted, so a card landing in the wrong hand fails twice over rather than
#// being masked by "well, a card went somewhere".
#// Seat 1's own space unit is left in play as the untouched control.

## GIVEN
CommonSetup: gyw/bbw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: SOR_209
WithP1SpaceArena: SOR_237:1:0
WithP3SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3SpaceArena-0

## EXPECT
SEATCOUNT:4
P3SPACEARENACOUNT:0
P3HANDCOUNT:1
P1HANDCOUNT:0
P1SPACEARENACOUNT:2
