# Deal1EnemyBaseHealOwn
#// TS26_19 Coleman Trebor (Unit 2/2, cost 1) — Hidden. When Played: deal 1 to each enemy base, then heal
#// 1 from your base per damage dealt. In 2-player: 1 damage to the enemy base → heal 1 from your own base.
## GIVEN
CommonSetup: bgw/rrk/{myResources:3;myBaseDamage:3;handCardIds:TS26_19}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2BASEDMG:1
P1BASEDMG:2

---

# NoDamageDealtMeansNoHeal
#// TS26_19 Coleman Trebor — "Heal 1 damage from your base FOR EACH DAMAGE DEALT this way". P2 plays Close
#// the Shield Gate (JTL_074) on their own base first, so Coleman's 1 damage is prevented: their base stays
#// on 2 and P1's base gets no heal, staying on 2 as well.
#// Discriminating: the heal used to fire unconditionally, handing P1 a free point off damage that never
#// landed.

## GIVEN
CommonSetup: bgw/bbw/{myResources:3;myBaseDamage:2;theirResources:3;theirBaseDamage:2;handCardIds:TS26_19}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: JTL_074
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myBase-0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P2BASEDMG:2

---

# TwinSuns_EachEnemyBase_AndTheHealCountsThemAll
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — batch 3, "each enemy base" is a FAN-OUT.
#// This took OtherPlayer(), so above two seats only ONE enemy base was hit — and because the heal is
#// "1 for each damage dealt this way", the heal was undercounted to match. Both halves are asserted here.
#// P1's opponents are 2 and 4; 3 is a TEAMMATE and must take nothing. P1's base starts on 3 damage and
#// ends on 1, i.e. healed TWO — the count is what proves the fan-out, not just that a second base was hit.
## GIVEN
CommonSetup: bgw/rrk/{myBaseDamage:3}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 3
WithP1Hand: TS26_19
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P2BASEDMG:1
P4BASEDMG:1
P3BASEDMG:0
P1BASEDMG:1
