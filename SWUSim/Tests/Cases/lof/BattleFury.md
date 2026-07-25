# OnAttackDiscard
#// LOF_139 Battle Fury — attached gains "On Attack: discard a card from your hand." SOR_095 (with Battle
#// Fury) attacks the base and P1 discards its only hand card.

## GIVEN
CommonSetup: rrk/ggw/{handCardIds:SOR_046}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_139

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:0

---

# EnemyAttach_EnemyDiscards
#// LOF_139 Battle Fury — the granted "On Attack: discard a card from your hand" discards from the CONTROLLER
#// of the attached unit, even when attached to an enemy unit. P2's unit carries Battle Fury; when P2 attacks,
#// P2 (not the caster P1) discards its only hand card. (FT: "discards from the unit's controller if it is
#// played on an enemy unit".)

## GIVEN
CommonSetup: bbk/rrk/{}
SkipPreGame: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_139
WithP2Hand: SOR_046
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P2HANDCOUNT:0

---

# ControlChange_NewControllerDiscards
#// LOF_139 Battle Fury — the granted discard follows the CURRENT controller after a control change. P1's unit
#// carries Battle Fury; P2 plays Change of Heart (SOR_224) to seize it, then attacks with it → P2 discards its
#// remaining hand card. (FT: "discards from the correct player when the unit changes controllers".)

## GIVEN
CommonSetup: bbk/yyk/{theirResources:6}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_139
WithP2Hand: SOR_224,SOR_046
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true

## WHEN
- P2>PlayHand:0
- P2>AttackGroundArena:0:BASE

## EXPECT
P2HANDCOUNT:0
