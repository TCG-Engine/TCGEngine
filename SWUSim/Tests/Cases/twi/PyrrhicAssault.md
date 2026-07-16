# GrantedWhenDefeated_Deals2
#// TWI_103 Pyrrhic Assault (Event, cost 3, Command/Command, Disaster) — "For this phase, each friendly
#// unit gains: 'When Defeated: Deal 2 damage to an enemy unit.'" After playing it, SOR_095 (3/3) attacks
#// SOR_046 (3/7): SOR_095 dies to the 3 counter-damage, and its granted When Defeated deals 2 to the only
#// enemy unit (SOR_046). SOR_046 ends with 3 (combat) + 2 (granted) = 5 damage. Base g + leader gw cover
#// both Command pips.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_103}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# NoGrant_NoBonus
#// TWI_103 Pyrrhic Assault — absence guard: WITHOUT playing Pyrrhic Assault, SOR_095 attacking SOR_046 and
#// dying to the counter deals NO bonus damage (SOR_046 ends with just the 3 combat damage). Proves the +2
#// comes from the granted When Defeated, not from combat.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
