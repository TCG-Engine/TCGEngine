## Star Wars: Unlimited — Rules Summary

**Goal:** Destroy the opponent's base (typically 30 HP, some 25 HP).

### Premier Format Deck Construction
- **1 Leader** (double-sided; starts horizontal, flips to unit side via Epic Action)
- **1 Base**
- **≥50 card draw deck** (no maximum)
- **Up to 10-card sideboard** (no leaders/bases; same card counts toward the 3-copy limit across deck + sideboard)
- **Max 3 copies** of any single card
- Leaders and bases are not counted in the 50-card minimum during gameplay

### Aspects (6 total)
Your leader + base together provide aspect icons. Playing off-aspect cards costs **+2 resources per missing aspect icon**.
- **Heroism** (white)
- **Villainy** (black)
- **Aggression** (red)
- **Command** (green)
- **Cunning** (yellow)
- **Vigilance** (blue)

### Game Structure
**Setup:** Place base + leader (horizontal), draw 6 cards, mulligan once, place 2 cards face-down as starting resources.

**Each Round:**
1. **Action Phase** — Players alternate taking one action each until both pass consecutively:
   - Play a card (pay cost, put unit/upgrade into play or resolve event)
   - Attack with a unit (exhaust attacker → target enemy unit or base)
   - Use an action ability
   - Take the initiative (auto-pass rest of round; go first next round)
   - Pass
2. **Regroup Phase** — Draw 2 cards → optionally resource 1 card from hand → ready all exhausted cards

**Combat:** Attacker exhausts, both attacker and defender deal damage simultaneously. "On Attack" abilities fire before damage. Excess damage doesn't carry over unless the attacker has **Overwhelm**.

### Key Keywords
- **Ambush** — when played, controller may ready this unit and immediately attack an enemy unit (not base)
- **Sentinel** — enemy units in the same arena must attack this unit
- **Overwhelm** — excess combat damage spills to the enemy base
- **Shielded** — enters with a Shield token (absorbs one damage instance)
- **Raid X** — +X power when attacking
- **Restore X** — heals your base for X whenever this unit attacks
- **Grit** — gains +1 power for each damage counter on it
- **Saboteur** — ignores Sentinel, defeats defender's shields on attack
- **Bounty** — grants a reward to the opponent who defeats this unit
- **Smuggle Y** — can be played as a resource, then later played from the resource zone at an alternate cost
- **Coordinate** - while you have 3 or more units in play (including itself), this unit gains ability specified
- **Exploit X** - while paying for this unit's cost, you may defeat up to X units to pay 2R less per unit defeated this way
- **Piloting Y** - this unit can be played as an upgrade onto a Vehicle unit for an alternate cost
- **Hidden** - this unit cannot be attacked the same phase it was played
- **Plot** - this unit can be played from the resource zone (paying its cost) when a leader deploys

when Smuggle or Plot are used, the top card of the deck is placed into the resource zone exhausted. if there are no cards in deck, then ignore it. the resource is just lost in this case.

### Card Types
- **Unit** — enters exhausted, placed in Ground or Space arena; Leader flips to unit via Epic Action (enters ready, returns to horizontal when defeated)
- **Event** — played for its effect, then discarded
- **Upgrade** — attached to a unit (slide under it); defeated when the host unit leaves play
- **Base** — your HQ; losing it = losing the game
- **Leader** — starts horizontal with an action ability + Epic Action (once per game deploy)

### Tokens
#### Token Upgrades
All token upgrades are 0 costa and are not "played" but "created"
- Shield Token: token upgrades that provide +0/+0 with the effect to prevent damage that would be done to a unit by defeating a shield token on the unit. it has the trait "Armor"
- Experience Token: token upgrades that give a unit +1/+1 stats (power/hp) with no other effect. it has the trait "Learned"
#### Token Units
All token units are 0 cost and are not "played" but "created"
- Battle Droid: a 1/1 `Villainy` Ground unit with the traits "Separatist,Droid,Trooper"
- Clone Trooper: a 2/2 `Heroism` Ground unit with the traits "Republic,Clone,Trooper"
- TIE Fighter: a 1/1 `Villainy` Space unit with the traits "Vehicle,Fighter"
- X-Wing: a 2/2 `Heroism` Space unit with the traits "Vehicle,Fighter"
- Spy: a 0/2 Ground unit with no aspects. it has `Raid 2` keyword and the trait "Official"
#### Special Tokens
- Force Token: used as a cost for certain abilities. it is of type "Force Token". there is only one Force Token per player and they can only create or defeat this token. it is created from effects that state "The Force is with you." and it is defeated by effects that state "Use the Force."
- Credit Tokens: special tokens with the effect "While paying resources, you may defeat this token. If you do, pay 1R less". It is of type "Credit Token" and has the trait "Supply".

### Mechanics To Consider
When abilities are resolved in the same timing window, then the active player (AP) gets to pick which player gets to resolve triggers in their bag first. For example,
- AP has only one unit with two On Attack triggers; NAP has only one unit with an On Defense trigger
- AP chooses to attack the enemy unit with their own unit
- AP gets to choose which player resolves their triggers first
- AP choose NAP
- NAP chooses to resolve their On Defense trigger
- AP now gets to choose the order to resolve their On Attack triggers

#### On Attack Abilites
- If a unit has an On Attack ability, it gets resolved before combat damage is dealt.
- A unit can have more than one On Attack ability, usually from CurrentEffects or through Upgrades
- If multiple On Attack abilities are triggered, the active player gets to decide the order to resolve them

#### While Attacking abilties
- Some units have abilities like "While attacking," or "While attacking a damaged unit," which are passive and resolved during combat.
- Some While attacking abilities have more conditions like "While attacking a Force unit, this unit gets +2/+0" (when unit attacks and targets a unit with the Force trait, then it gets +2 power)
- Raid can be seen as a keyword that grants a "While attacking" ability
- Restore is not a passive "While attacking" ability. It should be modeled as an `On Attack` trigger that resolves in the declare-attack window before combat damage.

##### Shoot First
- If a unit has an ability that states "While attacking, this unit deals combat damage before the defender" or variations on that phrase, then it has potential to defeat an enemy unit and receive no combat damage. It is similar to "First Strike" in Magic.
- If a unit with this ability is attacking a base, then the extra text is irrelevant. Bases do not deal combat damage.

#### On Defense Abilities
- On Defense is resolved at the same time as On Attack
- If both are triggered, then the active player (the attacking player in this case) gets to choose the order of resolution

#### While Defending abilities
- Some units have an ability "While defending," which are passive and resolved during combat when the unit is chosen as a target for an attack.
- The difference can be seen betweeen "Lando Calrissian" (LAW_108) and "Canto Bight Security" (LAW_121):
  - the first states "While this unit is defending, the attacker gets -1/-0."
  - the second states "On Defense: Credit a Credit token."

#### When Played abilities
- Some units have an ability that triggers when they are played
- Some upgrades have an ability that triggers when they are played
- The timing window for these is the same as "When a card is played" abilities or "When a unit is played" abilities
- If a player is able to play multiple units (usually as part of an event or other ability), if they have "When Played" abilities, then those get added to a bag to be resolved. The player can then choose which order to resolve those abilities.

#### When a card/unit is played
If any player has abilities that state "When you play a card" or "When an opponent plays a card" or variations on these, then a trigger is added to the bag to resolve.
- The timing window for these is the same as "When Played" abilities
- If the active player (AP) plays a unit with a "When Played" ability, and the NAP has a leader or unit with a "When an opponent plays a card"/"When an opponent plays a unit" ability, then the AP gets to choose which player's triggers resolve first.
  - If multiple are triggered per player, then the players get to choose the order to resolve those triggers after it has been decided who gets to resolve first

#### When Defeated abilities
- Some units have an ability that triggers when they are defeated
- The trigger is put in the bag and is resolved after the unit moves to the discard zone
- If multiple units are defeated with "When Defeated" triggers, then the active player gets to decide which player resolves their bag first.

#### Take control of a card
##### Control of upgrades
- a "friendly" upgrade refers to upgrades played/created and controlled by a player
- when a player plays an upgrade on a unit, they control that upgrade
- when a player creates a shield or experience token, they control those token upgrades
- there are a few cards that take control of upgrades:
  - Evidence of the Crime (SHD_077): Take control of an upgrade that costs 3 or less and attach it to an eligible unit of your choice.
  - Pre Vizsla - Power Hungry (SHD_142): When Played/On Attack: You may pay the cost of an upgrade attached to another non-Vehicle unit. If you do, take control of that upgrade and attach it to this unit, if able. If it can't attach to this unit, defeat it instead.
  - Hondo Ohnaka - Superfluous Swindler (JTL_056): On Attack: You may take control of a non-Pilot upgrade on a unit and attach it to a different eligible unit.
  - Shuttle ST-149 - Under Krennic's Authority (JTL_242): When Played/When Defeated: You may take control of a token upgrade on a unit and attach it to a different eligible unit.
  - Death Star Plans (JTL_260): When attached unit is attacked: The attacking player takes control of this upgrade and attaches it to a unit they control. Attached unit gains: "The first unit you play each round costs 2 Resources less."
##### Control of units
- a "friendly" unit refers to units controlled by a player (ie. they exist in the player's Ground or Space zones)
- when a player takes control of a unit, they only control the unit, and not the upgrades attached to the unit
- if a leader upgrade or unit with a leader upgrade that makes the unit a leader unit (eg. not Poe JTL pilot leader) is moved to an invalid zone, then it is defeated instead. Examples:
  - bouncing upgrades on a unit with Bamboozle (SOR_199) when a pilot leader is attached to a unit
  - Sly Moore's (TWI_211) when played ability on a Space token unit/vehicle when pilot leader is attached to it