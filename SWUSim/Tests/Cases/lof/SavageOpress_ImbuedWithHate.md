# NoForce_Deals9ToOwnBase
#// LOF_137 Savage Opress (9/6) — When Played: you may use the Force. If you DON'T (here: can't, no Force
#// token), deal 9 damage to your own base.

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:LOF_137}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:9
P1GROUNDARENACOUNT:1

---

# UseForce_NoSelfDamage
#// LOF_137 Savage Opress — with the Force, P1 uses it (YES) and avoids the 9 self-damage.

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:LOF_137}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1BASEDMG:0

---

# WhenDefeated_NoForce_Deals9ToOwnBase
#// LOF_137 Savage Opress — the "may use the Force, else 9 to your base" also fires When Defeated (same
#// handler wired to both triggers). Savage (pre-damaged to 5) attacks Vanguard Infantry (1/2); it kills
#// the Infantry but takes 1 counter → dies. With no Force, its When Defeated deals 9 to P1's own base.
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_137:1:5
WithP2GroundArena: SOR_108:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:9

---

# WhenPlayed_DeclineForce_Deals9ToOwnBase
#// LOF_137 Savage Opress — When Played "may use the Force" is a real choice: with a Force token in hand,
#// P1 DECLINES (answers NO). Declining is the punished branch → 9 damage to P1's own base, and the Force
#// token is retained (not spent, since it was never used).

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:LOF_137}
P1OnlyActions: true
WithP1Force: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HASFORCE
P1BASEDMG:9
P1GROUNDARENACOUNT:1

---

# WhenDefeated_UseForce_NoSelfDamage
#// LOF_137 Savage Opress — the When Defeated window also lets you USE the Force to avoid the 9 self-damage.
#// Savage (pre-damaged to 5) attacks Vanguard Infantry (1/2): kills it, takes 1 counter → dies. With a
#// Force token, P1 uses it (YES) on the When Defeated → no self-damage, Force spent.
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_137:1:5
WithP2GroundArena: SOR_108:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:0
P1NOFORCE
P1BASEDMG:0

---

# WhenDefeated_DeclineForce_Deals9ToOwnBase
#// LOF_137 Savage Opress — When Defeated with a Force token, P1 DECLINES (NO) → 9 damage to P1's own base,
#// Force retained. Same combat as the use-Force variant (Savage trades into Vanguard Infantry and dies).
## GIVEN
CommonSetup: rrk/ggw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_137:1:5
WithP2GroundArena: SOR_108:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENACOUNT:0
P1HASFORCE
P1BASEDMG:9

---

# NoForce_SelfDamageCanBeLETHAL_P1Loses
#// LOF_137 Savage Opress — the 9 self-damage is real damage to your OWN base with no cap or safety valve,
#// so playing him without the Force can lose you the game outright. P1's Dagobah Swamp (SOR_021, 30 HP) is
#// already at 22 damage; the forced 9 takes it to 31 and P1 loses. Boundary partner of the 21-damage
#// section below, which survives at exactly 30.
## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:LOF_137;myBase:SOR_021;myBaseDamage:22;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2WIN

---

# NoForce_SelfDamageExactlyLethalBoundary_P1Survives
#// LOF_137 Savage Opress — the exactly-at-the-line case: 21 damage + the forced 9 = exactly 30 on a 30-HP
#// base. A base is defeated when damage EXCEEDS its HP, so P1 survives at exactly 30 and Savage still
#// enters play. (Pairs with the 22-damage section above, which is lethal at 31.)
## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:LOF_137;myBase:SOR_021;myBaseDamage:21;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:30
P1GROUNDARENACOUNT:1
