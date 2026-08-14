# WhenDefeated_ShieldsVigilance
#// SOR_060 Distant Patroller (2/1, Space) — When Defeated: You may give a Shield token to a
#// [Vigilance] unit. The Patroller attacks SOR_066 (3/4, Vigilance) and dies to the return
#// damage (1 HP). Its When Defeated offers a shield to a Vigilance unit. Two Vigilance units
#// qualify — friendly 2-1B (SOR_059) and the enemy SOR_066 — so the choice is explicit; here
#// the friendly 2-1B is chosen and gains a Shield. (Guards the Vigilance aspect filter.)
#// COVERAGE: offer=Offer_VigilancePool_BothPlayersAndLeaderUnit (pending SELECTABLEEXACT:
#//           friendly + enemy [Vigilance] units and a deployed Vigilance leader unit;
#//           non-Vigilance Marine excluded) · decline=Decline_NoShield ("you may" answered '-')
#//           · control=TakenAndDefeated_OpponentGetsTheOffer + TakenAndDefeated_OpponentShieldsOwnUnit
#//           (JTL_043 take-control defeat: the NEW controller resolves the When Defeated and
#//           shields its own unit; the Patroller still hits the owner's discard) ·
#//           boundary=N/A (no numeric threshold in the ability) ·
#//           reqboundary=WhenDefeated_ShieldsVigilance (attack and shield pick span separate
#//           requests)

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0     # Distant Patroller (ready) — attacker, dies, idx 0
WithP1GroundArena: SOR_059:1:0    # 2-1B Surgical Droid (Vigilance) — idx 0, shield recipient
WithP2SpaceArena: SOR_066:1:0     # enemy unit (3/4) that kills the Patroller

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:2

---

# Offer_VigilancePool_BothPlayersAndLeaderUnit
#// SOR_060 — the When Defeated shield pool is EVERY [Vigilance] unit, both players',
#// including a deployed Vigilance leader unit; non-Vigilance units are excluded. P1's
#// Patroller dies attacking the enemy Sentinel (3 power kills the 2/1). Candidates: P1's
#// 2-1B (Vigilance), P1's deployed leader unit (Vigilance aspect), and P2's System Patrol
#// Craft (Vigilance) — P1's Battlefield Marine is NOT offered. The "you may" pick is left
#// pending and the offer asserted.

## GIVEN
CommonSetup: bbw/ggw/{myLeaderDeployed:true}
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0     # Distant Patroller — attacker, dies
WithP1GroundArena: SOR_059:1:0    # 2-1B Surgical Droid (Vigilance) — idx 0
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (non-Vigilance) — idx 1, excluded
WithP2SpaceArena: SOR_066:1:0     # System Patrol Craft (Vigilance, Sentinel) — kills the Patroller

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2&theirSpaceArena-0

---

# TakenAndDefeated_OpponentGetsTheOffer
#// Intended: control change follows the defeat. P2 plays JTL_043 (take control of a
#// non-leader unit, then defeat it) on P1's Distant Patroller. At the defeat the Patroller
#// is under P2's control, so P2 resolves the When Defeated: the shield offer is P2's, and
#// its pool is only the [Vigilance] units — P2's 2-1B qualifies, P2's Bright Hope crewman
#// and P1's Battlefield Marine do not. Sole candidate on a "you may" → the pick still
#// prompts; it is left pending here and the offer asserted for P2.

## GIVEN
CommonSetup: yyk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 5
WithP2Hand: JTL_043
WithP1SpaceArena: SOR_060:1:0     # Distant Patroller — the JTL_043 target
WithP1GroundArena: SOR_095:1:0    # non-Vigilance — must NOT be offered
WithP2GroundArena: SEC_080:1:0    # non-Vigilance — must NOT be offered
WithP2GroundArena: SOR_059:1:0    # 2-1B Surgical Droid (Vigilance) — the only candidate

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_060
P2HASDECISION
P2SELECTABLEEXACT:myGroundArena-1

---

# TakenAndDefeated_OpponentShieldsOwnUnit
#// Intended (resolution of the flow above): P2 accepts and shields its own 2-1B; the
#// Patroller still goes to its OWNER'S (P1) discard and P1 gets no shield anywhere.

## GIVEN
CommonSetup: yyk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 5
WithP2Hand: JTL_043
WithP1SpaceArena: SOR_060:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_060
P2GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# Decline_NoShield
#// SOR_060 — the When Defeated is "you may": declining the pick gives no shield to anyone.
#// Same trade as the offer section; P1 answers '-' and every unit stays shieldless.

## GIVEN
CommonSetup: bbw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_060:1:0     # Distant Patroller — attacker, dies
WithP1GroundArena: SOR_059:1:0    # 2-1B Surgical Droid (Vigilance)
WithP2SpaceArena: SOR_066:1:0     # System Patrol Craft (Vigilance, Sentinel) — kills the Patroller

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2SPACEARENAUNIT:0:SHIELDCOUNT:0
P2SPACEARENAUNIT:0:DAMAGE:2
P1NODECISION
