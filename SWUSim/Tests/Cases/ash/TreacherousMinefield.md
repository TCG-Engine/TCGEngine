# ArenaOnAttackSelfDamage
#// ASH_186 Treacherous Minefield (Event, cost 2) — Choose an arena. For this phase, each unit in that
#// arena gains "On Attack: deal 2 damage to this unit." P1 plays it choosing Ground; then SOR_046 attacks
#// the enemy base and takes 2 self-damage from the granted On Attack.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_186}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# SpaceArena_SelfDamage
#// ASH_186 Treacherous Minefield — the chosen arena may be Space. Choosing Space grants the self-damage On
#// Attack to space units: SOR_237 (2 power) attacks P2's base for 2 and takes 2 self-damage.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_186}
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:2
P1SPACEARENAUNIT:0:DAMAGE:2

---

# OtherArenaUnaffected
#// ASH_186 Treacherous Minefield — only units in the CHOSEN arena gain the self-damage On Attack. Choosing
#// Ground leaves space units untouched: SOR_237 attacks the enemy base for 2 and takes 0 self-damage.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_186}
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:2

---

# OpponentUnitAlsoSelfDamages
#// ASH_186 Treacherous Minefield — the granted On Attack applies to EACH unit in the arena, including the
#// opponent's. P1 plays it choosing Ground; then P2's SOR_046 attacks P1's base for 3 and takes 2
#// self-damage.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_186}
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P2>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1BASEDMG:3

---

# NoUnits_ResolvesAfterArenaChoice
#// ASH_186 Treacherous Minefield — with no units in play the arena is still chosen and the event simply
#// resolves with no effect; the card leaves hand and no further decision is presented.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_186}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
## EXPECT
P1HANDCOUNT:0
P1NODECISION
