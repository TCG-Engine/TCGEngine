# OppHealsCasterGivesExp
#// TS26_51 Lom Pyke (Unit 5/5, cost 5) — When Played: each opponent may heal 5 from their base; for each
#// that does, give 2 Experience tokens to a unit. The opponent heals their base (5 → 0), and the caster
#// gives 2 Experience to SEC_080 (3 power → 5).
## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_51;theirBaseDamage:5}
WithP1GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:POWER:5

---

# AnOpponentWhoPASSESGrantsNoExperience
#// TS26_51 Lom Pyke — "each opponent MAY heal 5 … FOR EACH PLAYER THAT DOES, give 2 Experience tokens".
#// P2 declines: their base keeps its 5 damage and P1's SEC_080 stays at 3 power with no offer raised.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_51;theirBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3
P1NODECISION

---

# AnOpponentWithNothingToHealGrantsNoExperience
#// TS26_51 Lom Pyke — an undamaged base cannot be healed, so no player "does" and no Experience is handed
#// out: SEC_080 stays at 3 power. Distinguishes "a heal effect was offered" from "damage was removed".

## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_51}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P2BASEDMG:0

---

# AnOpponentBLOCKEDFromHealingGrantsNoExperience
#// TS26_51 Lom Pyke — TWI_132 Confederate Tri-Fighter ("bases can't be healed") is in play, so even
#// though P2 accepts, no damage is removed: their base stays at 5 and SEC_080 gains nothing.
#// The sharp case — the opponent said yes, and it still does not count.

## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_51;theirBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: TWI_132:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:POWER:3
