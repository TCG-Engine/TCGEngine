# GrantsPlayUnitDiscounted
#// TWI_120 Strategic Acumen — Upgrade: "Attached unit gains: Action [Exhaust]: Play a unit
#// from your hand. It costs 1 resource less." Validates the upgrade-GRANTED unit action: the
#// host (Battlefield Marine SOR_095, no action of its own) gains the action via the attached
#// upgrade. The action plays a second unit from hand (SOR_095, cost 2 → discounted 1) with
#// exactly 1 ready resource, exhausting the host. Mirrors Alliance Dispatcher (SOR_093) but
#// proves the provider is resolved from the upgrade, not the unit's own CardID.

## GIVEN
CommonSetup: ggw/ggw/{myResources:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0           # host unit (ready) — index 0
WithP1GroundArenaUpgrade: 0:TWI_120      # Strategic Acumen attached to the host

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
P1HANDCOUNT:0
