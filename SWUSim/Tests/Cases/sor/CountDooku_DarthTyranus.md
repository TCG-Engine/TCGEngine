# DefeatsLowHpUnit
#// SOR_038 Count Dooku "Darth Tyranus" (Unit, cost 7, [Vigilance][Villainy], Force/Sith/Separatist,
#// UNIQUE, 5/4) — "Shielded (When you play this unit, give him a Shield token.) / When Played: You may
#// defeat a unit with 4 or less remaining HP."
#// COVERAGE: offer=Boundary_FiveRemainingHP_IsNotOffered (menu asserted on a PENDING MZMAYCHOOSE — one
#//           over-the-line unit EXCLUDED plus two legal targets, including Dooku himself) ·
#//           boundary pair=Boundary_ExactlyFourRemainingHP_IsDefeatable (4 remaining, legal) vs
#//           Boundary_FiveRemainingHP_IsNotOffered (5 remaining, excluded) — both on the same printed
#//           3/7 body, so only the DAMAGE differs · decline=Decline_NoUnitIsDefeated ('-' with two
#//           legal targets on the board, nothing dies on either side) · control
#//           change=ControlChange_StolenDookuIsStillDefeatableAndTheEnemyOffersHim (owner P2 /
#//           controller P1: the stolen unit is in the pool and its defeat lands in the OWNER's
#//           discard) · request boundary=covered structurally by every section here — the trigger-order
#//           MZCHOOSE is answered in one request and the target pool is built and answered in the
#//           NEXT, so the pool is recomputed from serialized state rather than from a live in-memory
#//           list; Boundary_FiveRemainingHP_IsNotOffered reads the pool after that boundary with the
#//           decision still pending
#// NOTE: there is no no-valid-target cell — Dooku is printed 5/4, so his own 4 remaining HP puts him
#// inside his own window and the pool is never empty while he is on the board.
#// Dooku has TWO entry triggers (Shielded + this WhenPlayed), so the
#// player first orders them (EffectStack-0 = WhenPlayed), then answers the target choose. Dooku
#// himself (4 remaining HP) AND P2's SEC_080 Imperial Dark Trooper (3 HP) both qualify, so it is a
#// real choice — the player picks the Dark Trooper (theirGroundArena-0) to defeat it.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SOR_038}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0

---

# Shielded_GivesHimAShieldTokenOnEntry
#// CLAUSE 1 — "Shielded (When you play this unit, give him a Shield token.)" Dooku enters with TWO
#// entry triggers, so the player first orders them (MZCHOOSE over EffectStack-0/EffectStack-1, per the
#// standard trigger-ordering window); resolving the Shielded one puts a Shield token on Dooku himself.
#// The When Played defeat is then DECLINED with '-', which isolates the Shielded clause: the only
#// board change is the Shield token, and P2's Imperial Dark Trooper is untouched.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SOR_038}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_038
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:HP:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1NODECISION

---

# Decline_NoUnitIsDefeated
#// THE DECLINE BRANCH of clause 2 — "You MAY defeat a unit with 4 or less remaining HP." Two legal
#// targets are on the board (Dooku himself at 4 remaining HP and P2's 3/3 Dark Trooper), so the offer
#// is a real MZMAYCHOOSE rather than an auto-resolve, and the player answers '-'. Nothing is defeated
#// on EITHER side, no damage is dealt, and no decision is left dangling.
#// The paired positive is DefeatsLowHpUnit above; together they prove the clause is genuinely optional
#// rather than a mandatory defeat with a cosmetic prompt.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SOR_038}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_038
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Boundary_ExactlyFourRemainingHP_IsDefeatable
#// THE N SIDE OF THE BOUNDARY — "4 or LESS REMAINING HP" reads REMAINING hp (printed HP minus damage),
#// not printed HP. SOR_046 Consular Security Force is printed 3/7 — far outside the window — but with
#// 3 damage on it its remaining HP is exactly 4, so it is a legal target and Dooku defeats it.
#// Paired with Boundary_FiveRemainingHP_IsNotOffered below (the same card at 2 damage = 5 remaining,
#// which is excluded); the two together pin the line at exactly 4 and prove damage is subtracted.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SOR_038}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_038

---

# Boundary_FiveRemainingHP_IsNotOffered
#// THE N+1 SIDE, asserted as a MENU on a PENDING decision (assertions evaluate at end state, so an
#// answered choice has no offer left to inspect). Board:
#//   myGroundArena-0    SOR_038 Dooku            5/4, undamaged → 4 remaining → LEGAL
#//   theirGroundArena-0 SEC_080 Dark Trooper     3/3, undamaged → 3 remaining → LEGAL
#//   theirGroundArena-1 SOR_046 Security Force   3/7 with 2 damage → 5 remaining → EXCLUDED
#// The excluded unit is the whole point: it is one point over the line and sits on the same board as
#// two legal targets, so the pool cannot be explained by an empty-or-single-target shortcut.
#// Dooku's presence in his OWN offer is also asserted here — "a unit" is unqualified, so the just-played
#// Dooku is a legal target of his own ability (exercised in SelfDefeat_ShieldDoesNotPreventIt below).

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SOR_038}
P1OnlyActions: true
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:2]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# SelfDefeat_ShieldDoesNotPreventIt
#// SCOPE + SHIELD INTERACTION. "You may defeat A UNIT" names no controller and carries no self-
#// exclusion, so Dooku himself (4 remaining HP) is a legal target of his own When Played. Here the
#// Shielded trigger is resolved FIRST (EffectStack-1, the order proved by
#// Shielded_GivesHimAShieldTokenOnEntry), so Dooku is carrying a Shield token when he is chosen — and
#// he is defeated anyway. Per CR, a Shield is only spent to PREVENT DAMAGE; a defeat effect deals no
#// damage, so the Shield neither absorbs it nor is consumed by it. Dooku goes straight to the discard
#// pile and the enemy unit is untouched.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SOR_038}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_038
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# ShieldedTarget_IsStillDefeated
#// The same Shield-vs-defeat rule from the OTHER side of the table: P2's Dark Trooper is carrying a
#// Shield token (SOR_T02) and is still defeated outright. A Shield can only be spent to prevent
#// damage, and "defeat a unit" deals none — so the token does not save the unit and is not consumed
#// as a substitute; the unit and its token leave play together.
#// The passing control is DefeatsLowHpUnit (same board without the token, same outcome) — the pair is
#// what makes this evidence rather than a coincidence.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SOR_038}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_038

---

# ControlChange_StolenDookuIsStillDefeatableAndTheEnemyOffersHim
#// OWNER vs CONTROLLER. A Dooku OWNED by P2 but CONTROLLED by P1 sits on P1's board (the end state of
#// a take-control effect). The When Played window is long past, so what is under test is the other
#// half of the control axis: a second Dooku played by P1 must see the stolen one as just another unit
#// — it has 4 printed HP and no damage, so it is inside the "4 or less remaining HP" window and must
#// be offered, and defeating it must send it to its OWNER's discard pile, not the controller's.
#// ⚠ SOR_038 is UNIQUE, so the two copies cannot coexist: the uniqueness rule resolves at the end of
#// the action. The section therefore uses the STOLEN copy as the target of the new copy's trigger,
#// which is also the only ordering in which both copies are ever on the board at once.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SOR_038}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_038:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_038
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_038
P1DISCARDCOUNT:0
