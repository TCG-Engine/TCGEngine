# OnAttackDealExhaust
#// LAW_076 Vult Skerris's Defender (3/3, space) — On Attack: you may deal 1 damage to a space unit and
#// exhaust it. Attacks the base; hit the enemy SOR_237 (1 damage + exhausted).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_076:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:EXHAUSTED

---

# OnAttackExhaustCannotTakeDamage
#// LAW_076 Vult Skerris's Defender — On Attack: the damage+exhaust is simultaneous. Target the enemy
#// SHD_187 Lurking TIE Phantom, which "can't be captured, damaged, or defeated by enemy card abilities":
#// the damage is prevented (stays 0) but the exhaust still lands.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_076:1:0
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:EXHAUSTED

---

# WhenPlayedNoDiscardNoShield
#// LAW_076 Vult Skerris's Defender — When Played: only gains a Shield if a card was discarded from your
#// hand or deck THIS phase. Nothing was discarded, so it enters with no Shield.

## GIVEN
CommonSetup: ryk/bgw/{myResources:8}
P1OnlyActions: true
WithP1Hand: LAW_076

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_076
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
