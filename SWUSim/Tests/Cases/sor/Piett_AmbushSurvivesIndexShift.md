# Granted_AmbushResolvesAfterArenaCompacts_NeverAttacksWithAnotherUnit
#// Found 2026-09-28 while checking whether `case 'Ambush'` shares the index-shift defect fixed for
#// `case 'Shielded'` in BUG REPORT #1091 (hmw/Maul_ShieldedSurvivesIndexShift.md). It DOES, and the
#// consequence is worse than a misplaced token: the WRONG UNIT MAKES AN ATTACK.
#//
#// Ambush is bagged as AddTrigger('Ambush', $cardID, $mzID, UID) — the mzID is an INDEX. The dispatch
#// re-resolves by UID, but only `if (SWUObjGone($ambushObj))`. That guard sees an EMPTY slot; it cannot
#// see a slot that has been REFILLED by a different unit, which is exactly what arena compaction does.
#//
#// The reachable path needs three things inside one entry window, and SUPPORT supplies the combat:
#//   1. SOR_079 Admiral Piett (a plain 1/4 unit, NOT a leader) grants Ambush to every friendly non-leader
#//      unit costing 6+. ASH_050 Morgan Elsbeth costs exactly 6 and has Support, so she enters with TWO
#//      entry triggers — Ambush and Support — and the controller ORDERS them.
#//   2. Taking SUPPORT first attacks with ASH_058 Duchess's Protector at a LOWER index. It dies to the
#//      7/7 defender, so the arena compacts and Morgan slides from index 2 down to index 1.
#//   3. The Protector's own When Defeated creates an ASH_T01 Mandalorian token, which is appended — into
#//      index 2, the very slot the still-pending Ambush trigger points at.
#// Ambush then resolves against index 2, finds a unit that is NOT gone, and READIES AND ATTACKS WITH THE
#// TOKEN. Verified before the fix: Nala Se took 2 damage (the token's power) instead of Morgan's 5, and
#// the token ended exhausted while Morgan never attacked.
#//
#// ⚠ IT IS ORDER-DEPENDENT. The sibling section below takes Ambush FIRST and is correct either way —
#// which is why the pre-existing Ambush coverage (sor/WedgeAntilles_StarOfTheRebellion.md) cannot see it.
#//
#// ⚠ WHY HMW_082 Nala Se (1/7 vanilla) IS THE TARGET: both candidate attackers SURVIVE attacking it, so
#// the arena length is identical under both readings and the only thing that differs is WHO swung. A
#// target that kills its attacker would change the indices and make the assertions ambiguous.
#//
#// ⚠ "Morgan is exhausted" IS NOT A DISCRIMINATOR. Units enter play exhausted, which is why Ambush reads
#// "may READY and attack" — she is exhausted under both readings. Use the DAMAGE numbers and the log.
## GIVEN
CommonSetup: yyk/bbw/{myResources:14;handCardIds:ASH_050}
P1OnlyActions: true
#// index 0 — the Support attacker that dies and leaves a token behind (2/3, When Defeated: Mandalorian).
WithP1GroundArena: ASH_058:1:0
#// index 1 — the Ambush GRANTER. Piett must be a unit IN PLAY: HasConditionalKeyword_Ambush loops
#// GetUnitsInPlay(), so seeding him in the arena is what turns Morgan's Ambush on.
WithP1GroundArena: SOR_079:1:0
#// HMW_121 Hijacked AT-ST 7/7 out-damages the 3-HP Protector while surviving its 2 power.
WithP2GroundArena: HMW_121:1:0
WithP2GroundArena: HMW_082:1:0
## WHEN
- P1>PlayHand:0
#// ⚠ EffectStack-1 is SUPPORT here, EffectStack-0 the Ambush. Taking -0 first resolves Ambush before
#// anything moves and the bug never appears — see the control section below.
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
#// The Protector dies holding Morgan's SUPPORT-LENT When Defeated ("Give a unit -2/-2 this phase?").
#// Decline it — it is not part of this bug and applying it would move the numbers asserted below.
- P1>AnswerDecision:-
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
#// The board the stranded trigger sees: Piett compacted to 0, Morgan to 1, the fresh token at 2.
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:ASH_050
P1GROUNDARENAUNIT:2:CARDID:ASH_T01
#// MORGAN is the Ambush unit, so MORGAN swings: Nala Se takes her 5 and she takes the 1 back.
P2GROUNDARENAUNIT:1:DAMAGE:5
P1GROUNDARENAUNIT:1:DAMAGE:1
#// The token was never involved — untouched.
P1GROUNDARENAUNIT:2:DAMAGE:0
LOGCONTAINS:P1's [[ASH_050|Morgan Elsbeth]] attacked P2's [[HMW_082|Nala Se]]
LOGCOUNT:0:[[ASH_T01|Mandalorian]] attacked

---

# Granted_AmbushFirst_IsUnaffected
#// CONTROL for the section above: the same board, but the controller resolves AMBUSH FIRST. Nothing has
#// moved yet, so index 2 still IS Morgan and the correct unit attacks. This must stay green before AND
#// after the fix — it is what proves the identity check did not break the ordinary path, where the mzID
#// the trigger was bagged with is still the right one.
#// Support is left PENDING on purpose; the assertions are all board state, which is already settled.
## GIVEN
CommonSetup: yyk/bbw/{myResources:14;handCardIds:ASH_050}
P1OnlyActions: true
WithP1GroundArena: ASH_058:1:0
WithP1GroundArena: SOR_079:1:0
WithP2GroundArena: HMW_121:1:0
WithP2GroundArena: HMW_082:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
#// Nothing has died, so Morgan is still at the index she entered at.
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:ASH_050
P2GROUNDARENAUNIT:1:DAMAGE:5
P1GROUNDARENAUNIT:2:DAMAGE:1
LOGCONTAINS:P1's [[ASH_050|Morgan Elsbeth]] attacked P2's [[HMW_082|Nala Se]]
