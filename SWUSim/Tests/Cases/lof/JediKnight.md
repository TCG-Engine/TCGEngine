# Initiative_Deal2
#// LOF_145 Jedi Knight — When Played: if you have the initiative, deal 2 damage to an enemy ground unit.
#// P1 holds the initiative, so the 3/7 takes 2.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3;handCardIds:LOF_145}
WithInitiativePlayer: 1
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoInitiative_NoDamage
#// LOF_145 Jedi Knight — negative: without the initiative the When-Played damage does not fire.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3;handCardIds:LOF_145}
WithInitiativePlayer: 2
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
