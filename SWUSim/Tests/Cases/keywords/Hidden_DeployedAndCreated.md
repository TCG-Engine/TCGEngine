# Hidden covers PLAYED, DEPLOYED and CREATED — not just played.
#
# Bug reports #1025 (game 4161) and #1026 (game 4162), and they are ONE bug:
#   "Darth Sidious is missing the Hidden overlay when deployed this phase"
#   "Darth Sidious able to be attacked on deploy phase"
# The overlay (ObjectHiddenUnattackable) and the attack gate (_SWUHiddenBlocksAttack) read the SAME
# predicate, so a single wrong answer produces both symptoms at once.
#
# THE RULE. CR 18.a: "'Hidden' is a keyword whose effect is the same as the constant ability: 'This unit
# can't be attacked if it was **played/deployed/created** this phase.'" A leader deploy is explicitly
# covered. ⚠ The card's printed reminder text says only "if it was played this phase" — reminder text is
# abbreviated, and taking it literally is exactly how this bug was written. CR 6.x is emphatic that a
# leader "is considered deployed, NOT played", so "played" alone excludes the deploy.
#
# ROOT CAUSE. `_SWUHiddenBlocksAttack` tests the `SWU_PLAYED_UNIT_{uid}` global effect, and that flag is
# set in exactly ONE place — `ActivateCard`'s unit-entry branch (GameLogic.php). `SWUDeployLeader` builds
# the arena unit directly with `AddGroundArena(...)` and never goes through `ActivateCard`, so a deployed
# leader never carries it. `SWUCreateUnitToken` doesn't set it either.
#
# ⚠ THE FLAG IS CARRYING TWO DIFFERENT RULES, which is the real defect and why this file tests more than
# Sidious. The card texts settle which is which:
#     "a unit that ENTERED PLAY this phase"      TWI_052 Hello There, ASH_001 The Armorer
#     "didn't ENTER PLAY this round/phase"       SOR_179 Boba Fett, JTL_185 Hound's Tooth
#     "a unit YOU PLAYED this phase"             SOR_005 Luke Skywalker
#     "a unit that WAS PLAYED this phase"        SEC_236 Undercover Operation
# `SWU_PLAYED_UNIT_` implements PLAYED, which is right for the last two and wrong for the rest — Hidden
# included. Hence a separate ENTERED-play marker rather than widening the played flag and silently
# breaking Luke and Undercover Operation.

---

# DeployedThisPhase_CannotBeAttacked
#// THE BUG (#1026). HMW_011 Darth Sidious's deployed side has Hidden. P1 deploys him with their action;
#// the turn passes to P2, still the SAME action phase. P2's ground unit may then attack P1's BASE ONLY —
#// one legal target, not two. Before the fix Sidious was in the pool and could be attacked outright.
## GIVEN
CommonSetup: rrk/bbk/{myLeader:HMW_011}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
#// Base only. A count of 2 means Sidious is in the pool — the reported bug.
ATTACKTARGETS:2:G:0:1

---

# DeployedThisPhase_AnAttackHitsTheBaseInstead
#// The same claim driven through a real attack, because the target POOL and the attack RESOLUTION are
#// two different code paths and only this one is what the player experiences.
#// ⚠ THE ATTACK AIMS AT SIDIOUS, NOT THE BASE. A first draft aimed at BASE and passed before the fix —
#// vacuous: of course an attack declared on the base hits the base. It has to be declared on the unit
#// the rule protects, and be refused.
## GIVEN
CommonSetup: rrk/bbk/{myLeader:HMW_011}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>DeployLeader
- P2>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
#// Sidious is a 5-HP unit; SOR_046 hits for 3. Undamaged = the attack never landed on him.
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# DeployedInAPREVIOUSPhase_CanBeAttacked
#// THE CONTROL, and without it the two sections above pass on a board where Sidious was never attackable
#// for some unrelated reason. Hidden lapses when the phase does: the round advances through regroup, and
#// in the NEW action phase Sidious is a legal target again — 2 targets, and an attack damages him.
#// This is also what proves the marker is CLEARED at RegroupPhaseStart rather than set once forever.
## GIVEN
CommonSetup: rrk/bbk/{myLeader:HMW_011}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
## WHEN
- P1>DeployLeader
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
ATTACKTARGETS:2:G:0:2

---

# NonHiddenLeaderDeployedThisPhase_IsStillAttackable
#// THE SECOND CONTROL: the block must come from HIDDEN, not from "deployed this phase". SOR_002 Iden
#// Versio has no Hidden on her deployed side, so deploying her the same way leaves 2 targets. If this
#// ever reads 1, the fix stopped attacks on every freshly-deployed leader instead of only Hidden ones.
#// ⚠ Iden, not Darth Vader: SOR_010's Epic Action needs SEVEN resources, so at 6 he silently never
#// deployed and the section measured an empty board. Iden's threshold is 6, matching Sidious's.
## GIVEN
CommonSetup: rrk/bbk/{myLeader:SOR_002}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:DEPLOYED
ATTACKTARGETS:2:G:0:2

---

# PlayedThisPhase_StillCannotBeAttacked
#// THE REGRESSION GUARD for the path that already worked. LOF_154 Witch of the Mist has printed Hidden
#// and is PLAYED from hand, so it takes the ActivateCard route that sets the flag today. Widening the
#// predicate must not break it — base only, exactly as before.
## GIVEN
CommonSetup: rrk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 6
WithP1Hand: [LOF_154]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
ATTACKTARGETS:2:G:0:1
