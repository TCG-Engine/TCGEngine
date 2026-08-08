# BaseHealReaction
#// LOF_252 The Daughter — "When damage is dealt to your base: you may use the Force → heal 2 damage from
#// your base." P1's SOR_046 (3 power) attacks P2's base for 3; P2 controls The Daughter and uses the
#// Force to heal 2, leaving net 1 base damage.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP2Force: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_252:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES

## EXPECT
P2NOFORCE
P2BASEDMG:1

---

# BaseHealReaction_FromEvent
#// LOF_252 The Daughter — the "when damage is dealt to your base" reaction fires for damage from an EVENT,
#// not just an attack. P1 plays Daring Raid (SHD_178, deal 2 to a unit or base) at P2's base; P2 controls
#// The Daughter and uses the Force to heal 2, so the net damage to P2's base is 0. Intended: "heal 2 ... (from an
#// event)".

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP2Force: true
WithP1Hand: SHD_178
WithP1Resources: 1
WithP2GroundArena: LOF_252:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase
- P2>AnswerDecision:YES

## EXPECT
P2NOFORCE
P2BASEDMG:0

---

# NoTrigger_DamageToUnit
#// LOF_252 The Daughter — the reaction only triggers on damage to YOUR BASE, not to a unit. P1 plays Daring
#// Raid (SHD_178) at P2's The Daughter unit (2 damage). No base damage occurs, so the reaction never offers,
#// P2 keeps the Force, and The Daughter simply takes 2. Intended: "not allow ... when damage is dealt to unit".

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP2Force: true
WithP1Hand: SHD_178
WithP1Resources: 1
WithP2GroundArena: LOF_252:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2HASFORCE
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoTrigger_DamageToOpponentBase
#// LOF_252 The Daughter — the reaction is "damage dealt to YOUR base" only. P2 controls The Daughter; P1
#// plays Daring Raid (SHD_178) at P1's OWN base... no — here P2 owns The Daughter, so the opponent (from The
#// Daughter's perspective) is P1. Damage dealt to P1's base must NOT trigger P2's Daughter. P2 plays Daring
#// Raid at P1's base; P2 keeps the Force and P1's base takes the full 2. Intended: "not allow ... when damage is
#// dealt to opponent base".

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Force: true
WithP2Hand: SHD_178
WithP2Resources: 1
WithP2GroundArena: LOF_252:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirBase

## EXPECT
P2HASFORCE
P1BASEDMG:2

---

# DeclineForce_NoHeal_ForceRetained
#// LOF_252 The Daughter — "you MAY use the Force" is optional and distinct from having none. With a Force
#// token available P2 DECLINES: the base keeps the full 3 damage and the Force token is RETAINED.
## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP2Force: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_252:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:NO
## EXPECT
P2HASFORCE
P2BASEDMG:3
