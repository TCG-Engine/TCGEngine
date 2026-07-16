# DrawAttackBase_SelfDefeat
#// SOR_150 Heroic Sacrifice (Aggression/Heroism event, cost 1, Tactic) — "Draw a card, then attack with
#// a unit. For this attack, it gets +2/+0 and gains: 'When this unit deals combat damage: Defeat it.'"
#// P1 draws (deck 1 → 0, hand → 1), the attacker (SOR_095, 3/3) gets +2/+0 → deals 5 to the enemy base,
#// then is defeated by its granted self-defeat trigger (even though the base dealt no counter-damage).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:1
P2BASEDMG:5
P1GROUNDARENACOUNT:0

---

# NoReadyUnit_DrawOnly
#// SOR_150 Heroic Sacrifice — with no unit able to attack (only an EXHAUSTED unit present), the draw
#// still happens but there is no attack and no self-defeat. The exhausted unit survives.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P2BASEDMG:0
P1NODECISION

---

# SurvivesCounter_StillSelfDefeats
#// SOR_150 Heroic Sacrifice — the self-defeat fires on dealing combat damage to a UNIT too, and it
#// defeats the attacker even when it survives the counter. SOR_046 (3/7) gets +2/+0 → 5 power and must
#// attack the Sentinel SOR_063 (2/4): it kills the Sentinel (5 ≥ 4) and survives the 2-power counter
#// (7 HP), but the granted "when it deals combat damage: defeat it" still defeats it.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
