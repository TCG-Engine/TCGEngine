# Deployed_ShieldedResolvesAfterMaulDies_NeverLandsOnAnotherUnit
#// BUG REPORT #1091, game 1400002: "Maul deploy, played Elsbeth from discard, used support first, maul
#// died, maul's shielded trigger was given to morgan." The reporter's own read — "seems like a MZ index
#// issue" — is exactly right.
#//
#// HMW_016 Maul (Old Master) deploys with TWO entry triggers, Shielded and the When Deployed, and the
#// controller ORDERS them (see Maul_OldMaster.md::Deployed_HasShieldedAndEntersWithAShieldToken). Taking
#// the When Deployed FIRST is what strands the other one:
#//   1. Shielded is bagged as AddTrigger('Shielded', $cardID, $mzID) — an INDEX, "myGroundArena-N".
#//   2. The When Deployed plays ASH_050 Morgan Elsbeth back from the discard; she ENTERS the arena.
#//   3. Her Support attacks with another unit — Maul — and Maul dies to the counter-damage, so his slot
#//      is removed and the arena indices collapse underneath the still-pending trigger.
#//   4. Shielded finally resolves against "myGroundArena-N", which is now MORGAN, and she gets Maul's
#//      Shield token. The live log line is literally
#//      "P1's [[HMW_016|Maul]] gave a Shield token to P1's [[ASH_050|Morgan Elsbeth]]".
#//
#// ⚠ IT IS ORDER-DEPENDENT, which is why it survived the existing coverage: resolving Shielded FIRST
#// puts the token on Maul correctly (an earlier attempt in that same game logged "Maul's Shield token
#// prevented the damage"). Every pre-existing deployed section answers the ordering prompt the other way.
#//
#// ⚠ THE FIX MUST NOT BE "re-resolve when the slot is empty". Here the slot is NOT gone, it holds a
#// DIFFERENT unit, so a gone-only guard would never fire. The check has to be one of IDENTITY: is the
#// object at this mzID still the unit the trigger was bagged for?
#// The sibling `case 'Ambush'` shipped with exactly that gone-only guard and had the SAME bug — and its
#// version was worse, because the stranded trigger made the wrong unit ATTACK. Chased down and fixed
#// 2026-09-28; the repro lives in sor/Piett_AmbushSurvivesIndexShift.md.
#//
#// A dead Maul gets no Shield (he is not in play to receive one) — the point is that nobody ELSE does.
## GIVEN
CommonSetup: yyk/bbw/{myResources:8;myLeader:HMW_016}
P1OnlyActions: true
#// Morgan must be in the discard AS "defeated this phase" — Maul's When Deployed filters on the
#// SWU_DEFEATED_CARD_<id> multiset, so she has to be genuinely killed here, not seeded into the pile.
#// HMW_121 Hijacked AT-ST is a 7/7: it out-damages both Morgan (6 HP) and Maul (6 HP) while surviving
#// her 5. As a DEFENDER its Overwhelm and When Played never fire, so it is inert apart from its stats.
WithP1GroundArena: ASH_050:1:0
WithP2GroundArena: HMW_121:1:0
## WHEN
- P1>AttackGroundArena:0:0
#// Morgan's OWN When Defeated fires as she dies here ("Give a unit -2/-2 this phase?") — decline it;
#// it is not part of this bug and answering it would change the board under the later assertions.
- P1>AnswerDecision:-
- P1>DeployLeader
#// ⚠ EffectStack-0 is the WHEN DEPLOYED here, EffectStack-1 the Shielded. Taking -1 first gives
#// Maul the token before he attacks, he survives on it, and the bug never appears — which is exactly
#// how the pre-existing deployed sections miss it.
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
#// Maul dies here holding Morgan's SUPPORT-LENT When Defeated, so that fires before the stranded
#// Shielded trigger gets its turn. Decline it — and note the test is worthless without this line: stop
#// one step earlier and Shielded has not resolved yet, so Morgan is still clean and the section PASSES
#// against the broken engine.
- P1>AnswerDecision:-
## EXPECT
#// Maul's slot was removed, so Morgan COMPACTED from index 1 to index 0 — which is precisely the index
#// the stranded trigger still points at. She must not inherit his token.
P1GROUNDARENAUNIT:0:CARDID:ASH_050
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
LOGCOUNT:0:gave a Shield token to P1's [[ASH_050|Morgan Elsbeth]]
