# CantBeAttacked
#// TWI_219 On Top of Things (Upgrade +2/+0, cost 2, Cunning) — "When Played: Attached unit can't be
#// attacked this phase (unless it has Sentinel)." After P1 plays it on SOR_046, P2's SOR_095 can't attack
#// the protected SOR_046 — the attack is blocked and SOR_046 stays undamaged.

## GIVEN
CommonSetup: yyk/bbw/{myResources:2;handCardIds:TWI_219}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
