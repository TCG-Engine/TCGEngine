# OnAttackDeals2
#// SOR_121 Hardpoint Heavy Blaster (Upgrade on a Vehicle) — granted On Attack: if not
#// attacking a base, you may deal 2 to a unit in the defender's arena. P1's Academy
#// Defense Walker (SOR_037, 5/5 Vehicle) carries the blaster and attacks P2's Battlefield
#// Marine (index 0); the blaster's 2 damage is sent to the OTHER P2 ground unit (Consular
#// Security Force, index 1) — isolating it from combat. The Marine is defeated by the
#// 5-power attack, so the surviving Consular Security Force reindexes to 0 with 2 damage.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_037:1:0          # Academy Defense Walker (Vehicle), index 0
WithP1GroundArenaUpgrade: 0:SOR_121     # Hardpoint Heavy Blaster on the Walker
WithP2GroundArena: SOR_095:1:0          # defender — index 0
WithP2GroundArena: SOR_046:1:0          # blaster target — index 1

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
