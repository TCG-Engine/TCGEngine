# WhenPlayed_OnChewbacca_ResourcesTopCard
#// HMW_127 Chewbacca's Bowcaster (+3/+1, Command/Heroism, cost 3) — "Attach to a non-Vehicle unit. When
#// Played: if attached unit is Chewbacca, resource the top card of your deck (enters exhausted)." SOR_196
#// Chewbacca is the only non-Vehicle unit, so the play auto-attaches to it; the host is Chewbacca, so the
#// top deck card becomes a resource (RESCOUNT 3→4, DECKCOUNT 3→2).

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_127
WithP1GroundArena: SOR_196:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_196
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P1RESCOUNT:4
P1RESAVAILABLE:0
P1DECKCOUNT:2

---

# WhenPlayed_OnNonChewbacca_NoResource
#// The "if attached unit is Chewbacca" gate: attached to a non-Chewbacca unit (SOR_095), nothing is
#// resourced. The +3/+1 still applies (that is the vanilla upgrade stat loop, not the conditional clause).

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_127
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:6
P1RESCOUNT:3
P1DECKCOUNT:3

---

# CannotAttachToAVehicle
#// "Attach to a NON-Vehicle unit." With only a Vehicle in play (SEC_214 Skyhopper Canyon Runner, a ground
#// Vehicle) there is no legal host, so the play finds nothing and the Bowcaster stays in hand.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_127
WithP1GroundArena: SEC_214:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
