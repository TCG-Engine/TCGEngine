# OnAttack_DealDamageEqualToOwnDamage
#// COVERAGE: offer=OnAttack_Offer_AnyGroundUnitIncludingKrrsantanHimself (pending SELECTABLEEXACT — both
#//           sides' ground units in, Krrsantan himself in, the space unit out) ·
#//           decline=WhenPlayed_DeclineTheReady (the "you may ready") and OnAttack_DeclineTheDamage (the
#//           "you may deal") · boundary=the scaling amount, 0 damage → no offer at all
#//           (OnAttack_NoOfferWhenKrrsantanIsUndamaged) vs 3 → 3 (this section) vs 4 → 4
#//           (OnAttack_AmountTracksTheDamageOnKrrsantan_Four); plus the Bounty gate on/off pair
#//           WhenPlayed_ReadyIfEnemyBounty vs WhenPlayed_NoReadyWhenNoEnemyBounty ·
#//           control=N/A — "an ENEMY unit has a Bounty" is re-read live from the resolving controller's
#//           seat at When Played and neither clause leaves a per-unit marker behind, so there is no stored
#//           seat for a control change to invalidate · reqboundary=N/A — both abilities resolve entirely
#//           inside the action that triggers them; nothing is written by one request and read by a later one
#// SHD_139 Krrsantan — "On Attack: Choose a ground unit. You may deal 1 damage to it for each damage on this
#// unit." Krrsantan has 3 damage; attacking the base, it deals 3 to the enemy SOR_046 (proves the amount =
#// own damage).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_139:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayed_ReadyIfEnemyBounty
#// SHD_139 Krrsantan (5-cost, Villainy/Aggression) — "When Played: If an enemy unit has a Bounty, you may
#// ready this unit." With the enemy Bounty unit SHD_095 in play, P1 readies Krrsantan (it enters exhausted,
#// then becomes ready).

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_139
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_139
P1GROUNDARENAUNIT:0:READY

---

# WhenPlayed_NoReadyWhenNoEnemyBounty
#// SHD_139 Krrsantan — the negative of WhenPlayed_ReadyIfEnemyBounty. The only enemy unit is the vanilla
#// SOR_095 Battlefield Marine, which has no Bounty, so the "If an enemy unit has a Bounty" gate fails: no
#// ready offer is made at all (P1NODECISION) and Krrsantan stays exhausted from being played this turn.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_139
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_139
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# WhenPlayed_ReadyWhenTheEnemyBountyIsGranted
#// SHD_139 Krrsantan — "If an enemy unit has a Bounty" is satisfied by a GRANTED Bounty just as much as a
#// printed one. The enemy SOR_095 has no Bounty of its own; it wears SHD_261 Rich Reward, whose "Attached
#// unit gains: 'Bounty - …'" is the only source. Krrsantan is offered the ready and takes it.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_139
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_261

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_139
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:HASKEYWORD:Bounty

---

# WhenPlayed_DeclineTheReady
#// SHD_139 Krrsantan — "you MAY ready this unit" is optional. Same fixture as WhenPlayed_ReadyIfEnemyBounty
#// (the enemy SHD_095 Clone Deserter carries a printed Bounty) but P1 declines: Krrsantan stays exhausted.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_139
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_139
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# OnAttack_NoOfferWhenKrrsantanIsUndamaged
#// SHD_139 Krrsantan — "deal 1 damage to it FOR EACH DAMAGE ON THIS UNIT" scales to zero when Krrsantan is
#// undamaged, so no target is even offered. Krrsantan attacks the enemy base with 0 damage on him: the
#// attack resolves (5 base damage from his 3 power plus nothing else) and the enemy ground unit is untouched.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_139:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# OnAttack_Offer_AnyGroundUnitIncludingKrrsantanHimself
#// SHD_139 Krrsantan — "Choose A GROUND UNIT" has no friendly/enemy qualifier and no "another", so the
#// pool is every ground unit on the board INCLUDING Krrsantan himself. The pick is left pending so the
#// offer can be asserted: both friendly ground units (Krrsantan at index 0, SOR_046 at index 1) and the
#// enemy ground unit are in; the friendly SPACE unit is out.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: [SHD_139:1:3 SOR_046:1:0]
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# OnAttack_AmountTracksTheDamageOnKrrsantan_Four
#// SHD_139 Krrsantan — the boundary partner of OnAttack_DealDamageEqualToOwnDamage (3 damage → 3 dealt).
#// One more damage on Krrsantan means one more dealt: with 4 damage on him the enemy SOR_046 takes 4.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_139:1:4
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# OnAttack_DeclineTheDamage
#// SHD_139 Krrsantan — "You MAY deal 1 damage to it for each damage on this unit" is optional. The decline
#// branch of OnAttack_DealDamageEqualToOwnDamage: same 3-damage Krrsantan, same attack, P1 declines and the
#// enemy unit takes nothing. The attack itself is unaffected — the base still takes Krrsantan's 3.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_139:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
