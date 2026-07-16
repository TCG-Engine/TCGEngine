# GrantsRaid2
#// SOR_154 Rallying Cry (Event, cost 3) — "Each friendly unit gains Raid 2 this
#// phase." After playing it, P1's Battlefield Marine (power 3) attacks P2's base
#// with Raid 2: 3 + 2 = 5 damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_154}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# Raid2_ExpiresNextPhase
#// SOR_154 Rallying Cry — "Each friendly unit gains Raid 2 this phase." The grant is a CardID
#// turn-effect token ("SOR_154#2") resolved by the registry to a Raid value of 2 (phase duration).
#// After both players pass (action phase ends → regroup), the centralized duration expiry strips it,
#// so the Battlefield Marine no longer has Raid. (Previously the granted Raid persisted — a latent
#// bug fixed by giving turn effects real durations.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_154}
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
