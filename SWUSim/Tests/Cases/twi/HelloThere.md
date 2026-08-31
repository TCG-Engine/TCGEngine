# DebuffEnteredThisPhase
#// TWI_052 Hello There (Event, cost 3, Vigilance/Heroism) — "Choose a unit that entered play this phase.
#// It gets -4/-4 for this phase." SOR_046 is played this phase (marking it entered), then Hello There
#// gives it -4/-4 → power 0 (floored), HP 3.

## GIVEN
CommonSetup: bbw/grw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Hand: TWI_052

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:3

---

# DebuffDeployedLeader_EnteredThisPhase
#// HAPPY PATH FOR THE FIX (bug #1025/#1026 family). A leader that DEPLOYS "enters play" but is NOT
#// "played" — CR 6.x, "considered deployed, not played" — and Hello There says ENTERED PLAY, so a leader
#// deployed this phase is a legal target. It was invisible before: the filter read SWU_PLAYED_UNIT_,
#// which only ActivateCard sets, so this card offered NO target at all and silently did nothing.
#// Sidious is the only unit that entered this phase, so the choose auto-resolves onto him. He is 4/5, so
#// -4/-4 leaves 0/1 (power floors at 0).
#// ⚠ THE LEADER MUST ACTUALLY DEPLOY IN THE `WHEN`, NOT BE PLACED IN THE `GIVEN`. A first draft used
#// `WithP2GroundArena: HMW_011:1:0:DEPLOYED` and read 4/5 — no debuff — because a fixture builds board
#// state directly and never runs an entry, so the unit carries no entered-play marker at all. That also
#// means `P1OnlyActions` cannot be used here: P2 has to take a real action.
## GIVEN
CommonSetup: bbw/grw/{theirLeader:HMW_011}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 7
WithP2Resources: 6
WithP1Hand: TWI_052
## WHEN
- P2>DeployLeader
- P1>PlayHand:0
## EXPECT
P2LEADER:DEPLOYED
P2GROUNDARENAUNIT:0:CARDID:HMW_011
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:1

---

# DebuffCreatedToken_EnteredThisPhase
#// HAPPY PATH, the CREATED leg. CR wording for this family is "entered play", and a token CREATED this
#// phase entered play without being played. JTL_082 is played (so it entered too) and creates a TIE
#// Fighter token, giving TWO legal targets — which is deliberate: it proves the token is in the POOL
#// rather than being the last thing standing. The token is the one debuffed, and a 1/1 TIE Fighter at
#// -4/-4 is defeated outright — which is the strongest available assertion that the debuff really landed
#// on it (an unaffected token would still be sitting in the arena).
## GIVEN
CommonSetup: bbw/grw/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_082
WithP1Hand: TWI_052
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
#// Two units entered this phase (JTL_082 itself and its token), so this does NOT auto-resolve —
#// answering it is what proves the token is in the offered pool.
- P1>AnswerDecision:mySpaceArena-1
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_082

---

# NoTarget_WhenNothingEnteredThisPhase
#// UNHAPPY PATH. A unit placed by the fixture never entered play during the phase, so it carries no
#// marker and Hello There has nothing to choose — no decision, no debuff, stats untouched.
#// ⚠ This is what stops the sections above passing on a filter that simply offers EVERY unit.
## GIVEN
CommonSetup: bbw/grw/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_052
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
