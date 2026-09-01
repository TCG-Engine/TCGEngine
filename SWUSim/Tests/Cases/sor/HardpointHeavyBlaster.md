# OnAttackDeals2
#// SOR_121 Hardpoint Heavy Blaster (Upgrade on a Vehicle) — granted On Attack: if not
#// attacking a base, you may deal 2 to a unit in the defender's arena. P1's Academy
#// Defense Walker (SOR_037, 5/5 Vehicle) carries the blaster and attacks P2's Battlefield
#// Marine (index 0); the blaster's 2 damage is sent to the OTHER P2 ground unit (Consular
#// Security Force, index 1) — isolating it from combat. The Marine is defeated by the
#// 5-power attack, so the surviving Consular Security Force reindexes to 0 with 2 damage.
#// COVERAGE: offer=Offer_DefendersArena_BothPlayersUnits (pending SELECTABLEEXACT: every unit
#//           in the defender's arena — attacking host, friendly bystander and both enemy units)
#//           + Attach_OfferIncludesEnemyVehicle (the ATTACH pool: both Vehicles, neither non-Vehicle)
#//           · decline=Decline_NoExtraDamage ("you may" answered '-') ·
#//           control=EnemyHostedBlaster_ResolvesForTheHostsController (the unqualified "attach to a
#//           VEHICLE unit" reaches enemy Vehicles — Attach_OfferIncludesEnemyVehicle — and the
#//           granted ability then resolves for the HOST'S controller, not the upgrade's owner) ·
#//           boundary=base target (AttackBase_NoTrigger, no prompt) vs unit target
#//           (OnAttackDeals2), plus ground vs space defender arena (OnAttackDeals2 vs
#//           SpaceDefender_HitsSpaceArena) and Vehicle vs non-Vehicle host
#//           (AttachesOnlyToVehicles) · reqboundary=OnAttackDeals2 (attack declaration and
#//           blaster pick span separate requests)

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

---

# Offer_DefendersArena_BothPlayersUnits
#// SOR_121 — the granted On Attack pool is every unit in the DEFENDER'S arena, both players'
#// (the attacking host and P1's bystander Marine included), when the attack target is a
#// ground unit. The "you may" pick is left pending here and the offer asserted.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_037:1:0          # attacker (Vehicle) with the blaster
WithP1GroundArenaUpgrade: 0:SOR_121
WithP1GroundArena: SOR_095:1:0          # friendly bystander — index 1
WithP2GroundArena: SOR_095:1:0          # defender — index 0
WithP2GroundArena: SOR_046:1:0          # enemy bystander — index 1

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirGroundArena-1

---

# SpaceDefender_HitsSpaceArena
#// SOR_121 — when the host attacks a SPACE unit, the 2 damage goes to a unit in the SPACE
#// arena: P1's System Patrol Craft (3/4, +2/+0 from the blaster) attacks P2's Distant
#// Patroller (2/1, dies; 2 back onto the attacker) and the blaster's 2 damage is aimed at
#// P2's OTHER space unit (1/3), which survives at 2. Ground units untouched.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1SpaceArena: SOR_066:1:0           # attacker (Vehicle) with the blaster
WithP1SpaceArenaUpgrade: 0:SOR_121
WithP2SpaceArena: SOR_060:1:0           # defender — dies to the 5-power attack
WithP2SpaceArena: SOR_208:1:0           # blaster target — index 1, reindexes to 0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-1

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_208
P2SPACEARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AttackBase_NoTrigger
#// SOR_121 — "If this unit isn't attacking a base": a base attack raises NO blaster prompt;
#// the base just takes the host's combat damage (5 + the blaster's +2/+0 = 7) and no unit
#// is damaged.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_037:1:0
WithP1GroundArenaUpgrade: 0:SOR_121
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Decline_NoExtraDamage
#// SOR_121 — the granted On Attack is "you may": declining the pick deals only combat
#// damage; the enemy bystander stays clean.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_037:1:0
WithP1GroundArenaUpgrade: 0:SOR_121
WithP2GroundArena: SOR_095:1:0          # defender — dies to the 5-power attack
WithP2GroundArena: SOR_046:1:0          # bystander — untouched

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttachesOnlyToVehicles
#// SOR_121 — "Attach to a VEHICLE unit": with a Vehicle (Academy Defense Walker) and a
#// non-Vehicle (Battlefield Marine) on board, the Walker is the ONLY legal host, so the
#// attach auto-resolves onto it (no prompt) and the Marine stays bare.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_121
WithP1GroundArena: SOR_037:1:0          # Vehicle — the only legal host
WithP1GroundArena: SOR_095:1:0          # non-Vehicle — excluded

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_121
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION

---

# Attach_OfferIncludesEnemyVehicle
#// SOR_121 — "Attach to a VEHICLE unit." carries NO controller word, so per CR 2.e the attach pool
#// is EVERY Vehicle on the table, the opponent's included; only the Vehicle trait narrows it.
#// P1 fields a Vehicle (Academy Defense Walker) and a non-Vehicle (Battlefield Marine) and P2
#// fields a Vehicle (Alliance X-Wing) and a non-Vehicle (Consular Security Force). With two legal
#// hosts the attach no longer auto-resolves, so the host pick is left PENDING and the offer
#// asserted: both Vehicles, neither Trooper. (AttachesOnlyToVehicles pins the single-legal-host
#// auto-resolve; this pins the POOL the restriction actually builds.)

## GIVEN
CommonSetup: ggk/ggk/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_121
WithP1GroundArena: SOR_037:1:0          # friendly Vehicle — legal host
WithP1GroundArena: SOR_095:1:0          # friendly non-Vehicle — excluded
WithP2SpaceArena: SOR_237:1:0           # ENEMY Vehicle — legal host per CR 2.e
WithP2GroundArena: SOR_046:1:0          # enemy non-Vehicle — excluded

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# EnemyHostedBlaster_ResolvesForTheHostsController
#// SOR_121 — the cross-controller half. Because the attach pool reaches enemy Vehicles
#// (Attach_OfferIncludesEnemyVehicle), the blaster can end up on a Vehicle the OPPONENT controls,
#// and the ability it grants belongs to the ATTACHED UNIT — so it resolves for the HOST'S
#// controller, not for whoever owns the upgrade. Here P2's Academy Defense Walker wears the
#// blaster and attacks: P2 is the one offered the "deal 2" pick and P2 aims it at P1's OTHER
#// ground unit. The 7-power attack (5 + the blaster's +2/+0) defeats the defending Marine, so the
#// Consular Security Force reindexes to 0 carrying the blaster's 2.

## GIVEN
CommonSetup: ggk/ggk
P2OnlyActions: true
WithP2GroundArena: SOR_037:1:0          # enemy-controlled Vehicle host
WithP2GroundArenaUpgrade: 0:SOR_121
WithP1GroundArena: SOR_095:1:0          # defender — dies to the 7-power attack
WithP1GroundArena: SOR_046:1:0          # blaster target

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
