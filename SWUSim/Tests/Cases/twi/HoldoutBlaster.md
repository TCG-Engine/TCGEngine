# Decline_NoDamage
#// TWI_256 Hold-Out Blaster — the "may" is optional: declining (AnswerDecision:-) attaches the upgrade but
#// deals no damage.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:TWI_256}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenPlayed_HostDeals1
#// TWI_256 Hold-Out Blaster (Upgrade +1/+0, cost 1, Item/Weapon) — "Attach to a non-Vehicle unit. When
#// Played: You may have attached unit deal 1 damage to a ground unit." Auto-attaches to the sole friendly
#// non-Vehicle host (SOR_046, +1/+0 → power 4); taking the option deals 1 to the enemy ground unit.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:TWI_256}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:DAMAGE:1
