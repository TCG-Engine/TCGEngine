# ChooseBaseHealOthers
#// TS26_42 Relief Frigate (Unit 3/7 space, cost 5) — When Played: choose a base; heal 3 from each OTHER
#// base. Choosing the enemy base heals 3 from your own (damage 5 → 2); the chosen enemy base is unhealed.
## GIVEN
CommonSetup: bgw/rrk/{myResources:5;myBaseDamage:5;theirBaseDamage:4;handCardIds:TS26_42}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1BASEDMG:2
P2BASEDMG:4

---

# TheCHOSENBaseIsTheOneThatDoesNotHeal
#// TS26_42 Relief Frigate — "Choose a base. Heal 3 damage from each OTHER base." Choosing your own
#// damaged base protects nothing: P1's 5 damage stays, and the only other base (P2's, undamaged) has
#// nothing to heal. The inverse of the existing section, where choosing the enemy base heals P1's.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5;myBaseDamage:5;handCardIds:TS26_42}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:5
P2BASEDMG:0
