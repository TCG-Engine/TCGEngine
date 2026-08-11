# DealsOneToAnEnemyUnit_NoEndorBase_NoAttack
#// HMW_193 Nightfall (Aggression event, cost 2) — "Deal 1 damage to an enemy unit. / If you control an
#// Endor base, you may attack with a unit. It gets +2/+0 for this attack."
#// P1's base is SOR_021 Dagobah Swamp (trait Dagobah, NOT Endor), so the second clause is off entirely:
#// the enemy unit takes its 1 damage, no attack is offered, and P2's base is untouched.

## GIVEN
CommonSetup: rrw/bgw/{
  myBase:SOR_021;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_193
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:0
P1NODECISION

---

# EndorBase_MayAttackWithPlusTwo
#// With an Endor base (JTL_020 Shield Generator Complex, trait Endor) the second clause turns on:
#// SOR_095 Battlefield Marine (3 power) attacks P2's base at 3 + 2 = 5. The first clause still deals its
#// 1 damage to the enemy unit — the two clauses are independent, both resolve.

## GIVEN
CommonSetup: rrw/bgw/{
  myBase:JTL_020;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_193
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:5

---

# EndorBase_DecliningTheAttack
#// "You MAY attack" — declining leaves the attack unmade while the first clause's damage still stands.
#// The would-be attacker is untouched and still READY (it never attacked).

## GIVEN
CommonSetup: rrw/bgw/{
  myBase:JTL_020;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_193
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY

---

# TheBonusIsForThisAttackOnly
#// "+2/+0 for THIS attack" must not linger. After the attack resolves, SOR_095 is back to its printed 3
#// power. (Asserting only the 5 damage would pass identically if the buff were applied for the phase —
#// this is the section that pins the duration.)

## GIVEN
CommonSetup: rrw/bgw/{
  myBase:JTL_020;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_193
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3

---

# NoEnemyUnit_TheAttackClauseStillResolves
#// The two clauses are joined by a full stop, not "If you do" — so the first having no legal target does
#// NOT fizzle the second. With no enemy unit on the board at all, the Endor clause still offers the
#// attack and SOR_095 hits P2's base for 3 + 2 = 5.

## GIVEN
CommonSetup: rrw/bgw/{
  myBase:JTL_020;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_193
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:5

---

# TheDamageTargetsANYEnemyUnitInEitherArena
#// "an ENEMY unit" is unqualified by arena, so both P2's ground and space units are offered — and no
#// friendly unit is. The choice is left pending so the offer itself is what's asserted (two candidates
#// also keep it from auto-resolving).

## GIVEN
CommonSetup: rrw/bgw/{
  myBase:SOR_021;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_193
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# OnlyREADYUnitsAreOfferedAsTheAttacker
#// "You may attack with a unit" carries no "even if it's exhausted" rider, and BeginSWUAttack does not
#// enforce readiness for an effect-driven attack — so the filter has to be the card's own. P1 controls a
#// READY SOR_095 and an EXHAUSTED SOR_046; only the ready one is offered. (The offer is left pending; a
#// MayChoose never auto-resolves, so a single candidate is still assertable.)

## GIVEN
CommonSetup: rrw/bgw/{
  myBase:JTL_020;
  myResources:8
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_193
WithP1GroundArena: [SOR_095:1:0 SOR_046:0:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_unit_to_attack_with
P1SELECTABLEEXACT:myGroundArena-0
