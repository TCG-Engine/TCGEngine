# DefeatFriendlyDeal4
#// TWI_140 Self-Destruct (Event, cost 2, Aggression/Villainy, Tactic) — "Defeat a friendly unit. If you
#// do, deal 4 damage to a unit." The lone friendly SOR_095 is defeated (auto), then 4 is dealt to the only
#// remaining unit, the enemy SOR_046.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_140}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# NoFriendly_NoEffect
#// TWI_140 Self-Destruct — condition guard: with no friendly unit to defeat, nothing happens (the "if you
#// do" damage never fires); the enemy unit is untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_140}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
