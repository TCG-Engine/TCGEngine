# FriendlyDamagedDealsToBases
#// ASH_032 Rancor Keeper (Ground, 2/4) — When a friendly unit is dealt damage and survives: deal 1 damage
#// to any number of bases (once each round). Rancor attacks SEC_080 (3/3): both survive, and Rancor (a
#// friendly unit) took 3 counter and survived → the player deals 1 to each base.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_032:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myBase-0&theirBase-0
## EXPECT
P1BASEDMG:1
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:ASH_032
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# DealToSingleBase
#// ASH_032 Rancor Keeper — "any number of bases" may be just one. Rancor attacks SEC_080 and survives the
#// counter; P1 deals the 1 damage to only the enemy base.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_032:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:1
P1BASEDMG:0

---

# ChooseNoBases
#// ASH_032 Rancor Keeper — "deal 1 damage to any number of bases" may be ZERO. P1 plays Daring Raid
#// (TWI_170) on its own SOR_046, which survives the 2 damage, triggering Rancor Keeper. P1 declines and
#// deals to no bases.
## GIVEN
CommonSetup: grk/grk/{myResources:3;handCardIds:TWI_170}
WithP1GroundArena: ASH_032:1:0
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
P1BASEDMG:0
P2BASEDMG:0

---

# DoesNotTriggerWhenFriendlyDefeated
#// ASH_032 Rancor Keeper — the trigger is "dealt damage and SURVIVES". P1 plays Open Fire (SOR_172, deal 4)
#// on its own SOR_095 (3/3), which is DEFEATED, so Rancor Keeper does NOT trigger and no base takes damage.
## GIVEN
CommonSetup: grk/grk/{myResources:3;handCardIds:SOR_172}
WithP1GroundArena: ASH_032:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_032
P1BASEDMG:0
P2BASEDMG:0

---

# DoesNotTriggerWhenEnemyDamaged
#// ASH_032 Rancor Keeper — only a FRIENDLY unit surviving damage triggers it. P1 plays Daring Raid (TWI_170)
#// on the enemy SEC_080 (survives 2), which is not a friendly unit, so Rancor Keeper does not trigger.
## GIVEN
CommonSetup: grk/grk/{myResources:3;handCardIds:TWI_170}
WithP1GroundArena: ASH_032:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1BASEDMG:0
P2BASEDMG:0

---

# OncePerRound
#// ASH_032 Rancor Keeper — "Use this ability only once each round." P1 plays two Daring Raids (TWI_170) on
#// its own SOR_046 in the same round; the first survival triggers the base-damage (1 to the enemy base) but
#// the second survival does NOT (already used this round), so the enemy base still shows only 1.
## GIVEN
CommonSetup: grk/grk/{myResources:4;handCardIds:TWI_170,TWI_170}
WithP1GroundArena: ASH_032:1:0
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:4
P2BASEDMG:1
