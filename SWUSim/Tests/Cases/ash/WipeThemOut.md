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

# NoTrigger_OverwhelmUsedExcess
#// ASH_137 Wipe Them Out — Overwhelm consumes the excess damage, leaving none for Wipe Them Out. Wampa
#// (SOR_164, 4/5, Overwhelm) attacks Porg (LOF_254, 1/1): 4 power defeats the 1-HP Porg and the 3 excess
#// goes to the opponent's base via Overwhelm, so there is no Wipe excess to redirect to the other enemy
#// unit (SOR_046 survives untouched).
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_137}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [LOF_254:1:0 SOR_046:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0

