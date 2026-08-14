# WhenPlayed_DamagesAndWeakensAnEnemyUnit
#// HMW_202 Inferno Squad, We Can Grieve Later — cost 5, [Cunning][Villainy], Ground 3/6, unique,
#// Traits: Imperial, Trooper.
#// Text: "When Played/When Defeated: You may deal 1 damage to a unit and give a Weakness token to it."
#//
#// TWO trigger windows sharing one effect, so each window needs its own coverage. "a unit" carries no
#// friendly/enemy qualifier → ANY unit is legal, including Inferno Squad itself. The two halves are
#// joined by "and", NOT "if you do" — but a target killed by the 1 damage simply cannot receive the
#// token (there is no host left), which is the no-op case, not a gate.
#//
#// COVERAGE: offer=WhenPlayed_Offer_AnyUnitIncludingItselfAndFriendly (SELECTABLEEXACT, 3 targets)
#//           decline=WhenPlayed_Decline_NoDamageNoToken + WhenDefeated_Decline_NoDamageNoToken
#//           boundary=WhenPlayed_WeaknessShrinkDefeatsTargetAtOneRemainingHp (survives at 1 remaining
#//                    with the damage alone; the Weakness -1 HP is what tips it to 0)
#//           control=WhenDefeated_UnderEnemyControl_NewControllerResolves (owner!=controller: the
#//                    CONTROLLER resolves the trigger, the card goes to the OWNER's discard)
#//           reqboundary=N/A (no state written before the decision and read behind it — the target
#//                    mzID rides the decision answer itself)
#//
#// Baseline positive: 1 damage + a Weakness (-1/-1) on an enemy 3/3 → 2/2 carrying 1 damage.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_202
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_202
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2

---

# WhenPlayed_Decline_NoDamageNoToken
#// The "may" decline branch: neither half happens. Note declining is `-` (MZMAYCHOOSE), not NO.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_202
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_202
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3

---

# WhenPlayed_Offer_AnyUnitIncludingItselfAndFriendly
#// OFFER cell — answering a target proves the branch, never the pool. The decision is left PENDING so
#// the legal-target set itself can be inspected. "a unit" is unqualified, so the pool must be all three
#// units on the board: the friendly bystander, Inferno Squad ITSELF (no "another"), and the enemy.
#// Three targets also guarantee no single-target auto-resolve.
#// Inferno Squad lands at ground index 1, after the pre-placed SOR_095.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_202
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# WhenPlayed_CanTargetAFriendlyUnit
#// "a unit" reaches your OWN units — a friendly-only restriction would be a bug. SOR_095 (3/3) →
#// 2/2 with 1 damage.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_202
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_CanTargetItself
#// The self-target case: the text says "a unit", not "another unit", so Inferno Squad may damage and
#// weaken ITSELF. 3/6 → 2/5 carrying 1 damage.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_202
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_202
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_LethalDamage_TargetDefeated_TokenNotStrandedOnABystander
#// INDEX-SHIFT guard. The 1 damage kills the chosen 1-HP unit (SOR_128, a 3/1), and defeating it runs
#// CleanupRemovedCards — so SEC_080 shifts DOWN from theirGroundArena-1 into theirGroundArena-0. A
#// handler that re-used the captured mzID string after the damage would hand the Weakness token to
#// SEC_080, the wrong unit. Resolving the target by UniqueID is what makes this pass.
#// Assert the survivor is completely untouched: no token, printed stats, no damage.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_202
WithP2GroundArena: [SOR_128:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3

---

# WhenPlayed_WeaknessShrinkDefeatsTargetAtOneRemainingHp
#// The two halves compound. SEC_080 (3/3) enters with 1 damage: the 1 damage takes it to 2, leaving 1
#// remaining HP — it SURVIVES the damage alone. The Weakness then drops printed HP 3 → 2, so damage 2
#// meets HP 2 and the shrink sweep defeats it. Proves the token is applied (not just the damage) and
#// that GIVE_WEAKNESS's shrink-defeat sweep runs, since HP loss has no state-based defeat of its own.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_202
WithP2GroundArena: SEC_080:1:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_202

---

# WhenPlayed_ShieldedTarget_AbsorbsDamage_ButStillGetsTheWeakness
#// The two halves are joined by "and", NOT "if you do" — so the Weakness is NOT conditional on the
#// damage landing. A Shield token absorbs the 1 damage entirely, and the token must STILL attach.
#// This is the section that catches an implementation which (reasonably but wrongly) gates the token
#// on the damage having been applied.
#// SEC_080 (3/3) ends undamaged, shield spent, and 2/2 from the Weakness.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_202
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2

---

# WhenDefeated_ByCombat_DamagesAndWeakens
#// The SECOND trigger window, reached via the COMBAT defeat path. Inferno Squad (3/6) enters with 5
#// damage and attacks SOR_046 Consular Security Force (3/7): it deals 3 and takes 3 back, reaching 8
#// damage on 6 HP → defeated. Driving it as the ATTACKER's self-defeat keeps the trigger inside P1's
#// own action so it resolves inline.
#// SOR_046 ends on 3 combat + 1 ability = 4 damage, and 2/6 from the Weakness (2 remaining, survives).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_202:1:5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6

---

# WhenDefeated_ByEffectDefeat_DamagesAndWeakens
#// The same window reached via the EFFECT defeat path, which is SEPARATE engine code from the combat
#// path (SWUDefeatUnit vs the combat defender/attacker branches) and can order its steps differently.
#// P1 plays SOR_078 Vanquish ("Defeat a non-leader unit") on its OWN Inferno Squad. Vanquish is
#// [Vigilance] against a Cunning base + Cunning/Villainy leader → +2 off-aspect, so it costs 7.
#// Two legal Vanquish targets (Inferno Squad, SEC_080) make that a real choose.

## GIVEN
CommonSetup: yyk/rrk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_078
WithP1GroundArena: HMW_202:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2

---

# WhenDefeated_Decline_NoDamageNoToken
#// Decline on the When Defeated window specifically — the decline must be wired on BOTH windows, not
#// just the one the play path exercises. SOR_046 keeps only its 3 combat damage.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: HMW_202:1:5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7

---

# WhenDefeated_UnderEnemyControl_NewControllerResolves
#// CONTROL cell, both readings at once. Inferno Squad is OWNED by P1 but CONTROLLED by P2. P1 defeats
#// it with Vanquish, and:
#//   • WHO RESOLVES — the When Defeated belongs to the CONTROLLER (P2), so P2 picks the target and
#//     aims it at P1's unit (P2's own frame: theirGroundArena-0 is P1's SOR_095).
#//   • OWNERSHIP — a defeated card goes to its OWNER's discard, so Inferno Squad lands in P1's discard
#//     alongside the Vanquish that killed it (P1DISCARDCOUNT:2), and P2's discard stays empty.
#// P2 must genuinely act, so initiative is left UNCLAIMED rather than using P1OnlyActions (which would
#// make P2 auto-pass and eat the decision).
#//
#// ⚠ The `P2>Drain` is load-bearing, not padding. P1's action leaves P2 holding an UNDISPATCHED
#// `CUSTOM RESOLVE_TRIGGER|WhenDefeated|HMW_202` — the non-active player's queue is not processed
#// during the acting player's action, so P2's MZMAYCHOOSE does not exist yet. Without the drain, the
#// answer below lands on that RESOLVE_TRIGGER and CANCELS the trigger instead of resolving it, which
#// presents identically to "the When Defeated never fired". Drain is the harness stand-in for
#// production's post-action ProcessGoldfishAutomation.

## GIVEN
CommonSetup: yyk/rrk/{myResources:9}
WithActivePlayer: 1
WithP1Hand: SOR_078
WithP1GroundArena: SOR_095:1:0
WithP2GroundArenaControlled: HMW_202:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Drain
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
P1DISCARDCOUNT:2
P2DISCARDCOUNT:0
