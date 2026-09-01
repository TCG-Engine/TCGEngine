# AttackDefeats_ReadiesSelf
#// SOR_149 Mace Windu (5/7) — "When this unit attacks and defeats a unit: Ready him." Mace attacks
#// a 3/3, defeats it, and is readied (so he ends READY despite having attacked). He takes 3
#// counter-damage.
#// COVERAGE: offer=Ambush_Offer_EnemyGroundUnitsOnly (the Ambush target pool left PENDING and
#//           asserted exactly — enemy ground units only; the friendly ground unit and the enemy
#//           SPACE unit are excluded) · decline=Ambush_Declined_StaysExhausted ("it MAY ready and
#//           attack"; the ready clause itself carries no "you may", so it has no decline branch)
#//           + Ambush_NoEnemyUnit_NoOffer (no legal target → no prompt at all) ·
#//           boundary=AttackDefeats_ReadiesSelf vs AttackNoDefeat_StaysExhausted (defender at
#//           exactly lethal vs one HP short) + AttacksBase_NoReady (a base is not "a unit") +
#//           AttackerDefeatedInTheTrade_NoReady (nothing left to ready) · control=N/A (neither
#//           clause names a zone or a player — Ambush attacks "an enemy unit" relative to whoever
#//           played him and "ready HIM" names the unit itself, so an owner/controller split has no
#//           observable channel; the cross-seat read is instead covered structurally, since the
#//           ready trigger is dispatched from the ACTIVE player's combat frame and
#//           Ambush_AttackDefeatsAUnit_ReadyTriggerChains fires it inside the Ambush attack, a
#//           different frame from a normal action attack) · reqboundary=Ambush_Offer_EnemyGround-
#//           UnitsOnly (the target choice is still pending when the play request ends) +
#//           Ambush_AttackDefeatsAUnit_ReadyTriggerChains (the ready resolves after the YES and the
#//           target answer, i.e. two request boundaries after the play that created the unit)

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_149:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_149
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackNoDefeat_StaysExhausted
#// SOR_149 Mace Windu — the ready only triggers on a DEFEAT. Mace attacks a 3/7 that survives his
#// 5 damage, so he is NOT readied and stays exhausted.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_149:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Ambush_ReadiesAndAttacksEnemyUnit
#// SOR_149 Mace Windu — the FIRST clause, Ambush: "After you play this unit, it may ready and attack
#// an enemy unit." Mace is played (entering exhausted), the Ambush is accepted and aimed at a
#// Consular Security Force (3/7) that survives his 5 power. Two enemy units keep the target pick
#// interactive. Mace ends EXHAUSTED — Ambush readied him only so that he could attack, and attacking
#// exhausts him again. No unit was defeated, so the second clause does not fire.

## GIVEN
CommonSetup: rrw/grw/{myResources:7;handCardIds:SOR_149}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0    # Consular Security Force (3/7) — the Ambush target
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine (3/3) — the second legal target

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_149
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENACOUNT:2

---

# Ambush_Declined_StaysExhausted
#// The Ambush is optional ("it MAY ready and attack"). Declining leaves Mace exhausted, undamaged,
#// and leaves both enemy units untouched — the decline branch of clause 1.

## GIVEN
CommonSetup: rrw/grw/{myResources:7;handCardIds:SOR_149}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_149
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:DAMAGE:0
P2DISCARDCOUNT:0

---

# Ambush_NoEnemyUnit_NoOffer
#// Per CR 5.9.a Ambush attacks a UNIT, so with no enemy unit in play there is nothing to aim at and
#// no prompt is raised at all. Mace still enters play, exhausted, and P2's base is untouched — the
#// no-valid-target branch of clause 1.

## GIVEN
CommonSetup: rrw/grw/{myResources:7;handCardIds:SOR_149}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_149
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
P2BASEDMG:0

---

# Ambush_AttackDefeatsAUnit_ReadyTriggerChains
#// Both clauses in one action: the Ambush attack is still an attack, so defeating a unit with it
#// satisfies "when this unit attacks and defeats a unit". Mace ambushes a Battlefield Marine (3/3),
#// his 5 power defeats it, and the ready trigger fires — so a unit played THIS action ends the
#// action READY despite having already attacked. He keeps the Marine's 3 counter-damage.

## GIVEN
CommonSetup: rrw/grw/{myResources:7;handCardIds:SOR_149}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine (3/3) — dies to the ambush
WithP2GroundArena: SOR_046:1:0    # second legal target, keeps the pick interactive

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_149
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:1

---

# AttacksBase_NoReady
#// Intended: the trigger is gated on defeating "a UNIT". A base is not a unit, so an attack that
#// takes P2's base to 5 damage — even a base that survives — never readies Mace. Boundary partner of
#// AttackDefeats_ReadiesSelf: same attack, different class of defender.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_149:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:SOR_149
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# AttackerDefeatedInTheTrade_NoReady
#// Intended: "Ready HIM" has nothing to ready when Mace does not survive the attack he wins. Mace
#// carries 3 damage (4 remaining HP) and attacks an Occupier Siege Tank (SOR_165, 5/4): his 5 power
#// defeats the Tank, and the Tank's 5 counter-damage defeats him in the same exchange. Both go to
#// the discard — the ready trigger fires against a unit that is already gone and must no-op rather
#// than return him to the arena.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_149:1:3
WithP2GroundArena: SOR_165:1:0    # Occupier Siege Tank (5/4)

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2BASEDMG:0

---

# Ambush_Offer_EnemyGroundUnitsOnly
#// Intended: Ambush aims at an ENEMY unit in Mace's own arena — never a friendly unit, never a base,
#// never across the arena boundary. The board carries one friendly ground unit, two enemy ground
#// units and an enemy space unit; the target decision is left PENDING so the exact pool can be
#// inspected (Ambush_ReadiesAndAttacksEnemyUnit resolves it in a separate section).

## GIVEN
CommonSetup: rrw/grw/{myResources:7;handCardIds:SOR_149}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # friendly — excluded
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0     # enemy, wrong arena — excluded

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
