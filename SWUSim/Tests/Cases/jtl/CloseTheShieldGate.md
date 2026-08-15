# PreventThenConsume
#// JTL_074 Close the Shield Gate — "Choose a base. The next time damage would be dealt to it this phase,
#// prevent that damage." P1 protects the opponent's base, then attacks it twice with 2-power X-Wings.
#// The FIRST attack's 2 damage is prevented; the SECOND deals 2 (proves prevent + one-shot consume).

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:JTL_074}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:1:BASE

## EXPECT
P2BASEDMG:2

---

# CombatDamageToNonChosenBaseNotPrevented
#// JTL_074 Close the Shield Gate — the shield only protects the CHOSEN base. P1 chooses its OWN base
#// (myBase-0), then attacks the opponent's (non-chosen) base with a 2-power X-Wing. The 2 combat damage
#// is NOT prevented because the shield is sitting on P1's base, not P2's.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:JTL_074}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# PreventsCardAbilityDamageToChosenBase
#// JTL_074 Close the Shield Gate prevents CARD-ABILITY damage (not just combat) to the chosen base. P1
#// chooses P2's base, then plays Daring Raid (TWI_170, "Deal 2 to a unit or base") pointed at P2's base —
#// the 2 ability damage is prevented, so P2's base takes 0.

## GIVEN
CommonSetup: brw/rrk/{myResources:2;handCardIds:JTL_074,TWI_170}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:0

---

# CardAbilityDamageToNonChosenBaseNotPrevented
#// JTL_074 Close the Shield Gate — card-ability damage to a NON-chosen base is not prevented. P1 chooses
#// its own base (myBase-0), then plays Daring Raid (TWI_170) at P2's base. The shield is on P1's base, so
#// P2's base takes the full 2.

## GIVEN
CommonSetup: brw/rrk/{myResources:2;handCardIds:JTL_074,TWI_170}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2

---

# IndirectDamageToChosenBaseNotPrevented
#// JTL_074 Close the Shield Gate — INDIRECT damage is unpreventable, so it is NOT prevented even when it
#// hits the chosen base. P1 chooses P2's base, then plays Planetary Bombardment (JTL_181, "Deal 8 indirect
#// to a player") at the opponent. With no P2 units, all 8 auto-assign to P2's base and land in full — the
#// shield does not stop indirect damage.

## GIVEN
CommonSetup: brw/brw/{
  theirBase:SOR_021;
  myResources:7;
  handCardIds:JTL_074,JTL_181
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:8

---

# IndirectDamageConsumesTheShield
#// JTL_074 Close the Shield Gate — although indirect damage is not prevented, it still CONSUMES the
#// one-shot protection. P1 chooses P2's base, deals 8 indirect (Planetary Bombardment, JTL_181) → lands in
#// full AND uses up the shield. P1 then plays Daring Raid (TWI_170) at P2's base → the 2 card-ability
#// damage is NOT prevented (shield already spent) → total 8 + 2 = 10. (If indirect had failed to consume
#// the shield, Daring Raid's 2 would be prevented and the base would sit at 8.)

## GIVEN
CommonSetup: brw/brw/{
  theirBase:SOR_021;
  myResources:9;
  handCardIds:JTL_074,JTL_181,TWI_170
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:10

---

# SimulateRequestBoundary_ShieldSurvivesAcrossActions
#// JTL_074 Close the Shield Gate — the base choice ends one request and each attack is its own action, so
#// in production the "next time damage would be dealt to this base this phase, prevent it" marker is
#// written in one process and read in a later one. It therefore has to be SERIALIZED per-base state, not a
#// transient global. Mirrors PreventThenConsume with a boundary before the base pick AND between the two
#// attacks: the first attack's 2 must still be prevented and the second must still land → P2 base = 2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:JTL_074}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0
- P1>SimulateRequestBoundary
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AttackSpaceArena:1:BASE

## EXPECT
P2BASEDMG:2
