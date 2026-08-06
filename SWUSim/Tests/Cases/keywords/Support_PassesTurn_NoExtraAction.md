# SupportAttack_AttackEndDecision_PassesTurn
#// Bug #922. A Support bonus attack is nested inside the unit's own play action, so exactly ONE
#//   after-action must run and the turn must pass. Two SWUVars decide who runs it, and they are
#//   meant to be MUTUALLY EXCLUSIVE (CombatLogic.php BeginSWUAttack):
#//     SWU_COMBAT_SKIP_AFTERACTION  set when the attacker bears SUPPORT_GRANT → combat stands down
#//     SWU_COMBAT_OWNS_AFTERACTION  set when FINISH_PLAY_CARD is pending      → the play stands down
#//   FINISH_PLAY_CARD is queued for EVERY card play, including a unit, so a Support attack sets BOTH
#//   and NOBODY passes the turn — the player keeps priority and gets a free extra action. Note the
#//   symptom is a MISSING after-action, not a double one: a double swap would skip the opponent.
#//
#//   Repro from the report: ASH_222 Unsanctioned Patrol (Space 4/4, Support) is played; JTL_221 Stolen
#//   AT-Hauler (4 power) is the supported attacker and hits P2's base for 4. That base damage arms
#//   ASH_016 Shin Hati's undeployed "when a friendly unit's attack ends" trigger (a unit costing less
#//   than 4 exists — the AT-Hauler at 3), which prompts. P1 declines. SimulateRequestBoundary models
#//   the real HTTP boundary that prompt creates, where a transient global would be lost.
#//   Initiative is deliberately UNCLAIMED (WithActivePlayer) — a claimed initiative masks the bug by
#//   making the missing swap unobservable.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;myLeader:ASH_016:1;handCardIds:ASH_222}
WithActivePlayer: 1
WithP1SpaceArena: JTL_221:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
TURNPLAYER:2

---

# SupportAttack_NoPause_PassesTurn
#// The same invariant with NO mid-attack decision at all. Both flags are set at BeginSWUAttack
#//   regardless of whether anything later pauses, so this case isolates the flag collision from the
#//   request-boundary handling: if it fails alongside the case above, the bug is in the mutual
#//   exclusivity itself; if it passes while the above fails, only the boundary path is affected.
#//   Default leader (no Shin), P2 has no units, so the bonus attack auto-targets the base and
#//   resolves start-to-finish in one request.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_222}
WithActivePlayer: 1
WithP1SpaceArena: JTL_221:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2BASEDMG:4
TURNPLAYER:2

---

# SupportDeclined_PassesTurn
#// Support is a "may". Declining the bonus attack means no combat runs at all, so the play's own
#//   FINISH_PLAY_CARD is unambiguously the only after-action owner — the turn must still pass.
#//   Guards the opposite failure mode: a fix that suppresses OWNS for every Support unit, rather
#//   than only when a bonus attack actually happened, would strand the turn here instead.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_222}
WithActivePlayer: 1
WithP1SpaceArena: JTL_221:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1SPACEARENACOUNT:2
TURNPLAYER:2
