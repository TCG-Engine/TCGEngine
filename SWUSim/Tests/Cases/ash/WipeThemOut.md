# AttackExcessToAnotherUnit
#// ASH_137 Wipe Them Out (Event, cost 2) — Attack with a unit. For this attack, you may deal its excess
#// damage to another unit in the same arena. SOR_046 (3/7) attacks SOR_128 (3/1): 3 damage defeats it with
#// 2 excess; the player deals the 2 excess to the friendly SOR_095 (a unit in the same arena). SOR_046
#// survives the 3 counter.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_137}
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# NoDefeat_NoExcess
#// ASH_137 Wipe Them Out — the "deal excess to another unit" bonus only happens when the attack DEFEATS a
#// unit. SOR_095 (3/3) attacks the AT-ST (SOR_232, 6/7): the AT-ST survives with 3 damage (no defeat), so
#// there is no excess prompt, and SOR_095 is defeated by the 6 counter.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_137}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_232:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# NoTrigger_DefeatedByOnAttackAbility
#// ASH_137 Wipe Them Out — no excess bonus when the defender is defeated by an ON-ATTACK ability rather
#// than by combat damage. SOR_249 Frontier AT-RT carries SOR_121 Hardpoint Heavy Blaster ("On Attack: deal
#// 2 damage to a unit in the defender's arena"); attacking the Jawa Scavenger (SOR_205, 2/1), the Hardpoint
#// 2 damage defeats the Jawa before combat damage, so the AT-RT takes no counter (0 damage) and there is no
#// Wipe Them Out excess.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_137}
WithP1GroundArena: SOR_249:1:0
WithP1GroundArenaUpgrade: 0:SOR_121
WithP2GroundArena: SOR_205:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_249
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Overwhelm_HeldExcess_ChooseAnotherUnit
#// ASH_137 Wipe Them Out + Overwhelm (user ruling 2026-10-03): the excess can go to another unit in the arena
#// OR to the base via Overwhelm, so combat holds it and the player picks. Wampa (SOR_164, 4/5, Overwhelm)
#// defeats Porg (LOF_254, 1/1) with 3 excess; the offer is the other unit in the arena or P2's base. P1 picks
#// SOR_046, which takes the 3 — and the base takes nothing.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_137}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [LOF_254:1:0 SOR_046:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# Overwhelm_HeldExcess_OffersUnitsInArenaAndTheBase
#// ASH_137 Wipe Them Out + Overwhelm — the held-excess prompt offers exactly the other units in the attacker's
#// arena plus the defending player's base (not the attacker itself, not the space arena's units).
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_137}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [LOF_254:1:0 SOR_046:1:0]
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirBase-0
P2BASEDMG:0

---

# Overwhelm_HeldExcess_ChooseBase
#// ASH_137 Wipe Them Out + Overwhelm — picking the base lets Overwhelm take the 3 excess as usual.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_137}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [LOF_254:1:0 SOR_046:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Overwhelm_HeldExcess_ChooseBase_FiresBaseHitAbilities
#// ASH_137 Wipe Them Out + Overwhelm — the held excess that goes to the base is still Overwhelm COMBAT damage
#// to a base, so "dealt 3 or more combat damage to a base" abilities see it. Ezra (ASH_013 leader) offers
#// to exhaust for an Advantage token once the 3 lands on P2's base.
## GIVEN
CommonSetup: ggk/ggk/{myLeader:ASH_013;myResources:4;handCardIds:ASH_137}
SkipPreGame: true
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [LOF_254:1:0 SOR_046:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED

---

# Overwhelm_NoOtherUnit_SpillsWithoutAsking
#// ASH_137 Wipe Them Out + Overwhelm — with no other unit in the arena there is nothing to choose, so the 3
#// excess spills to P2's base by Overwhelm straight away.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_137}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: LOF_254:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:0
P1NODECISION
