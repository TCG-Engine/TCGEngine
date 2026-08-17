# WhenPlayedReadyAnother
#// LAW_185 Ben Solo (8/8, Hidden) — When Played/When Defeated: ready another friendly unit; it can't be
#// attacked this phase. Ready the exhausted SEC_080.

## GIVEN
CommonSetup: rrw/bgw/{myResources:9}
WithP1GroundArena: SEC_080:0:0
WithP1Hand: LAW_185

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY

---

# WhenPlayedProtectionCantBeAttacked
#// LAW_185 Ben Solo — When Played readies another friendly unit AND it can't be attacked this phase.
#// With only the readied SEC_080 (Ben Solo himself is Hidden while controlling it), the enemy SOR_164
#// Wampa has no legal unit target and must attack the base (4 damage). SEC_080 stays ready & undamaged.

## GIVEN
CommonSetup: rrw/bgw/{myResources:9}
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: LAW_185

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:4

---

# WhenPlayedSentinelStillAttackable
#// LAW_185 Ben Solo — the "can't be attacked" protection is overridden by Sentinel. Ben Solo readies the
#// exhausted SOR_098 Echo Base Defender (Sentinel, 4/3); despite the protection, Sentinel forces the enemy
#// SOR_164 Wampa (4/5, Overwhelm) to attack it. Echo Base Defender is defeated and 1 excess spills to base.

## GIVEN
CommonSetup: rrw/bgw/{myResources:9}
WithP1GroundArena: SOR_098:0:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: LAW_185

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_185
P1BASEDMG:1

---

# WhenDefeatedReadyAnother
#// LAW_185 Ben Solo — the same ability also triggers When Defeated. P2's SHD_079 Rival's Fall defeats
#// Ben Solo; his When Defeated readies the only other friendly unit, the exhausted SEC_080.

## GIVEN
CommonSetup: rrw/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 6
WithP1GroundArena: [LAW_185:1:0 SEC_080:0:0]
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY

---

# WhenPlayedProtection_SurvivesTheRequestBoundary
#// LAW_185 Ben Solo — the "ready another friendly unit; it can't be attacked this phase" pick is answered
#// in a SEPARATE request from the play that queued it, so the in-flight When Played continuation and the
#// per-unit "can't be attacked this phase" marker it stamps must both survive serialization.
#// Mirrors WhenPlayedProtectionCantBeAttacked, but a second exhausted friendly unit is seeded in the SPACE
#// arena so the ready pick is a REAL two-option choose (myGroundArena-0 & mySpaceArena-0) rather than a
#// single-target auto-resolve — a boundary before an auto-resolved offer would prove nothing. The space
#// unit is not a legal ground target, so the enemy Wampa still faces only the protected SEC_080 and the
#// Hidden Ben Solo, and must hit the base for 4.

## GIVEN
CommonSetup: rrw/bgw/{myResources:9}
WithP1GroundArena: SEC_080:0:0
WithP1SpaceArena: JTL_095:0:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: LAW_185

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:4

---

# ReadyPool_AnotherFriendlyEitherArena
#// COVERAGE: offer=ReadyPool_AnotherFriendlyEitherArena (the "another friendly unit" pool asserted exactly:
#//           self excluded, enemy excluded, space included, an already-ready unit included) · decline=N/A
#//           (the ready is a mandatory choose, not a "you may") · control=N/A (no control-change text) ·
#//           boundary=WhenPlayedReadyAnother / WhenDefeatedReadyAnother (both trigger halves) and
#//           WhenPlayedProtectionCantBeAttacked vs WhenPlayedSentinelStillAttackable (protection honoured
#//           vs overridden by Sentinel) · reqboundary=WhenPlayedProtection_SurvivesTheRequestBoundary.
#// LAW_185 Ben Solo — "Ready ANOTHER FRIENDLY unit. It can't be attacked this phase." Two restriction
#// words and no arena word, so the board seats a violator for each and a witness for the absence of a
#// third: Ben Solo himself (played into myGroundArena-2) must be OUT on "another"; the enemy SOR_164 must
#// be OUT on "friendly"; the friendly SPACE JTL_095 must be IN because the text names no arena; and the
#// ALREADY-READY friendly SOR_095 must also be IN — readying it is a no-op but the rider makes it a real
#// choice ("it can't be attacked this phase"), so the usual SWUSim ready-only pool filter must NOT apply
#// here. Intended: pool = myGroundArena-0 & myGroundArena-1 & mySpaceArena-0.

## GIVEN
CommonSetup: rrw/bgw/{myResources:9}
WithP1GroundArena: [SEC_080:0:0 SOR_095:1:0]
WithP1SpaceArena: JTL_095:0:0
WithP2GroundArena: SOR_164:0:0
WithP1Hand: LAW_185

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1GROUNDARENAUNIT:2:CARDID:LAW_185
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0

---

# NGOR_WhenDefeatedOffer_IsTheNEWControllersUnitsOnly
#// LAW_185 Ben Solo — the When Defeated resolves for whoever CONTROLS him at the moment he is defeated,
#// so "another friendly unit" must be read from the NEW controller's seat. P2 plays JTL_043 No Glory,
#// Only Results on P1's Ben Solo (take control, then defeat it); the ready-choose is raised on P2's queue
#// and its pool must be P2's OWN two units — P1's exhausted Battlefield Marine must NOT appear in it even
#// though it was friendly to Ben Solo's original controller.
#// Two P2 units are seated deliberately: with only one the mandatory choose auto-resolves and the offer
#// could not be asserted at all.

## GIVEN
CommonSetup: grk/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: [LAW_185:1:0 SOR_095:0:0]
WithP2GroundArena: [SOR_095:0:0 SOR_046:0:0]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P2SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# NGOR_ReadiedUnitIsProtectedFromItsORIGINALController
#// LAW_185 Ben Solo — the second half of the same clause under a control change: the unit the NEW
#// controller readies also gains "can't be attacked this phase", and that protection has to hold against
#// the player who used to own Ben Solo. P2 steals and defeats Ben Solo, readies its own Battlefield
#// Marine (myGroundArena-0), and P1's surviving Consular Security Force then sees a NARROWED attack-target
#// pool: P2's other unit and P2's base, but not the protected Marine.
#// The pair with NGOR_ReadiedUnit_NoProtection_FullPool below is the discriminator — 2 targets vs 3 on an
#// otherwise identical board.

## GIVEN
CommonSetup: grk/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: [LAW_185:1:0 SOR_046:1:0]
WithP2GroundArena: [SOR_095:0:0 SOR_046:0:0]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENACOUNT:1
ATTACKTARGETS:1:G:0:2

---

# NGOR_ReadiedUnit_NoProtection_FullPool
#// LAW_185 Ben Solo — the CONTROL for the section above. Identical board, except Ben Solo is absent, so
#// nothing is readied and nothing is protected: P1's Consular Security Force sees the full pool of 3
#// (both P2 units and P2's base). Without this half, a pool that happened to be narrow for an unrelated
#// reason would read as the protection working.

## GIVEN
CommonSetup: grk/bbk/{}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:0:0]

## WHEN

## EXPECT
ATTACKTARGETS:1:G:0:3
