# Restore2_OnUnitAttack_HealsBase
#// COVERAGE: offer=WhenDefeated_Both_DrawBoth + siblings (the You/Opponent/Both option choice is
#//           exercised across all three picks; single fixed option set, no target pool to assert)
#//           · decline=WhenDefeated_None_NobodyDraws ("any number" includes zero players)
#//           · control=WhenDefeated_ControlTakenAtDefeat_NewControllerChooses (take-control defeat:
#//           the chooser is the defeating controller, "You" = that player)
#//           · boundary=Restore2_ClampsAtBaseDamage1 (1 damage, heal 2 → clamps at 0) vs
#//           Restore2_OnUnitAttack_HealsBase (3 damage, heal 2 → 1); Restore2_UndamagedBase_NoChange
#//           holds the zero edge and Restore2_DoesNotFireWhenYodaDEFENDS the "when this unit ATTACKS"
#//           negative
#//           · reqboundary=WhenDefeated_You_DrawSelf (the OPTIONCHOOSE pends across the request
#//           boundary before the answer resolves the draw)
#// SOR_045 Yoda — Restore 2 fires "When this unit attacks" on ANY attack, not just base attacks
#// (regression guard for the Restore fix). Yoda attacks a UNIT (SOR_063, 2/4) and survives; P1's base
#// heals 2 (3 damage → 1). SOR_063 takes Yoda's 2 combat damage.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenDefeated_Both_DrawBoth
#// SOR_045 Yoda — "When Defeated: choose any number of players, they each draw." Choosing "Both" →
#// both P1 and P2 draw a card.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Both

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:1

---

# WhenDefeated_Opponent_DrawOpp
#// SOR_045 Yoda — choosing "Opponent" → only P2 draws a card; P1 draws nothing.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Opponent

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:1

---

# WhenDefeated_You_DrawSelf
#// SOR_045 Yoda — "When Defeated: Choose any number of players. They each draw a card." Yoda attacks
#// LAW_124 (4/7) and dies (2/4 takes 4). On defeat, choosing "You" → only P1 (Yoda's controller) draws.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:You

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:0

---

# WhenDefeated_None_NobodyDraws
#// SOR_045 Yoda — "Choose any number of players" includes ZERO: declining the choice means nobody
#// draws. Same trade as the sibling sections; the option choice is answered with a decline.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1DISCARDCOUNT:1

---

# WhenDefeated_ControlTakenAtDefeat_NewControllerChooses
#// SOR_045 Yoda — a defeat via take-control-then-defeat (JTL_043) hands the When Defeated to the
#// DEFEATING controller: P2 takes control of P1's Yoda and defeats it, so P2 makes the choice and
#// "You" means P2. P2 draws; P1 does not. Yoda still returns to its OWNER's (P1's) discard.
#// P2's own SOR_095 is seeded as a second legal JTL_043 target so the take-control pick stays a
#// real choice.

## GIVEN
CommonSetup: rrk/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: JTL_043
WithP1Deck: SOR_095
WithP2Deck: SEC_080

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:You

## EXPECT
P2HANDCOUNT:1
P1HANDCOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1

---

# Restore2_ClampsAtBaseDamage1
#// SOR_045 Yoda — the Restore 2 boundary one step BELOW the heal amount. The ledger's boundary pair
#// reads 3 → 1, which is a plain subtraction and could never expose an over-heal; with only 1 damage
#// on the base, "heal 2" has to clamp at 0 rather than run negative (or, worse, wrap). Yoda attacks
#// P2's base: P1's base goes 1 → 0 and P2's takes Yoda's 2.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:1}
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2BASEDMG:2

---

# Restore2_UndamagedBase_NoChange
#// SOR_045 Yoda — the zero edge of the same clause: an UNDAMAGED base cannot be healed below zero, so
#// the Restore is a no-op and the attack is otherwise ordinary. Separated from the clamp section so a
#// negative-going heal cannot hide behind a base that had damage to give back.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_045:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2BASEDMG:2

---

# Restore2_DoesNotFireWhenYodaDEFENDS
#// SOR_045 Yoda — "Restore 2 (When this unit ATTACKS, heal 2 damage from your base.)" Every other
#// Restore section in this file has Yoda attacking, so the "when this unit attacks" qualifier itself
#// was never proven load-bearing — a Restore wired to any combat involving Yoda would pass them all.
#// Here Yoda is the DEFENDER: P2's Battlefield Marine (3/3) attacks him, so nothing is healed and P1's
#// base stays on 3 damage. Yoda (2/4) takes 3 and survives; the Marine takes his 2 back.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_045:1:0
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine 3/3 — attacker

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:CARDID:SOR_045
P2GROUNDARENAUNIT:0:DAMAGE:2
