"""PEN authoring source. Never import unhandled cards as implemented.

Catalog membership uses printing records, not a card's original set.
"""
import ast
import hashlib
import json
import re
from pathlib import Path

HERE = Path(__file__).parent
for filename, names in [('build_mon_abilities.py', ['clean']),
                        ('build_arc_abilities.py', ['opt']),
                        ('build_sup_abilities.py', ['resource_amount']),
                        ('build_hvy_abilities.py', ['choose', 'many', 'refs', 'token', 'dice'])]:
    source = (HERE / filename).read_text()
    for node in ast.parse(source).body:
        if isinstance(node, ast.FunctionDef) and node.name in names:
            exec(ast.get_source_segment(source, node))

UID = "$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
V = "$victim = intval(FaBGetState()['defender']);"

def hit(code):
    return 'if (FaBFaiHeroHit()) { ' + V + code + ' }'

def discard(filter='[]', optional=True, seat='$player'):
    return choose(refs('Hand', seat, filter), 'Discard_card', optional, seat) + f'FaBDiscardChoice({seat}, $chosen);'

def arcane(amount, hero=False):
    return choose('FaBPENTargets($player, '+('true' if hero else 'false')+')', 'Choose_arcane_target', False) + '$targetUID = FaBPENUID($chosen); ' + f"$dealt = FaBUPRDeal($player, $uid, $targetUID, {amount}, 'ARCANE');"

existing = {}
for name in ['wtr', 'arc', 'fai', 'professor', 'cru', 'ira', 'mon', 'boltyn', 'ele', 'evr', 'upr', 'dyn', 'out', 'dtd', 'evo', 'hvy', 'mst', 'ros', 'arakni', 'hnt', 'amx', 'sea', 'mpg', 'sup']:
    for entry in json.loads((HERE / (name+'_abilities.json')).read_text()):
        merged = {a['macroName']: a for a in existing.get(entry['cardId'], {}).get('abilities', [])}
        merged.update({a['macroName']: a for a in entry['abilities']})
        existing[entry['cardId']] = dict(cardId=entry['cardId'], abilities=list(merged.values()))

previous = {e['cardId']: e for e in json.loads((HERE/'pen_abilities.json').read_text())} if (HERE/'pen_abilities.json').exists() else {}
out, pending = [], []
for c in json.loads((HERE/'pen_catalog.json').read_text()):
    id = c['id']
    b = re.sub(r'_(red|yellow|blue)$', '', id)
    v = 4-int(c['pitch'] or 0)
    a = []
    handled = False

    def add(macro, code, prereq=None):
        global handled
        handled = True
        row = dict(macroName=macro, abilityCode=clean(code or 'return;'), isImplemented=True)
        if prereq is not None:
            row['prereqCode'] = prereq
        prior = next((x for x in a if x['macroName'] == macro), None)
        if prior:
            prior['abilityCode'] += '\n' + row['abilityCode']
        else:
            a.append(row)

    if id in existing:
        out.append(existing[id])
        continue
    # Entire rules text is already implemented by shared keyword handling.
    if b in ['shamanic_shinbones', 'enclosed_firemind', 'aetherstorm_wellingtons',
             'helm_of_might_and_magic', 'mbrio_base_walkers', 'heart_wrencher']:
        handled = True
    if b == 'future_sight': add('ResolveCard', token('sigil_of_fate', n=v))
    if b == 'oath_of_oak': add('ResolveCard', token('embodiment_of_earth', n=v))
    if b == 'sprout_strength':
        add('ResolveCard', "FaBWTRAddEffect($player, 'NEXT_ATTACK', 1);"*v)
    if b == 'song_of_larinkmorth_white':
        add('ResolveCard', '$seats = FaBOpponents($player); for ($i = 0; $i < count($seats); $i = $i + 1) { $victim = intval($seats[$i]); '+token('frostbite', '$victim')+' }')
    if b == 'pound_of_flesh':
        add('ResolveCard', '$seats = FaBLiveSeats(); $losses = []; for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = intval($seats[$i]); '+choose(refs('Hand', '$seat'), 'Banish_a_card_from_your_hand', False, '$seat')+'$losses[] = FaBPENPound($seat, $chosen); } FaBPENPoundLosses($player, $losses);')
    if b == 'break_open_the_chests': add('ResolveCard', 'FaBPENOpenChests($player);')
    if b == 'concoct_disorder': add('AttackDeclared', UID+'FaBPENConcoct($uid);')
    if b == 'tentacular_toll':
        add('ResolveCard', many(refs('Graveyard', filters="['type'=>'Ally']"),v,tip='Turn_allies_face_down')+'$turned = FaBPENTurnGraves($player, $chosen); '+token('gold', n='$turned'))
    if b == 'drag_down': add('Defended', f"FaBTagUID(intval(FaBGetState()['attackUID']), 'WTR_POWER:-{v}');")
    if b == 'buzzard_helm': add('Defended', UID+"if (FaBMONDrawDiscard($player)) { FaBTagUID($uid, 'WTR_DEFENSE:1'); }")
    if b == 'reckless_arithmetic': add('AttackDeclared', UID+dice()+"FaBCRUSelfTagUID($uid, 'WTR_POWER:' . $roll);")
    if b == 'blunten': add('Defended', "$victim = intval(FaBGetState()['attacker']); if (FaBPENWeaponAttack()) { "+discard(optional=False, seat='$victim')+' }')
    if b == 'mind_meets_might': add('Hit', hit('FaBPENMindMeetsMight($victim);'))
    if b == 'rip_off_the_top': add('ResolveCard', 'FaBPENRipTop($player);')
    if b in ['man_overboard','fire_that_burns_within','astravolt_elemental']:
        filt={'man_overboard':"['type'=>'Ally']", 'fire_that_burns_within':"['base'=>'phoenix_flame']", 'astravolt_elemental':"['type'=>'Instant']"}[b]
        body=choose(refs('Hand',filters=filt),'Discard_to_empower')+"if ($chosen !== '-') { FaBDiscardChoice($player, $chosen); "
        if b=='man_overboard':body+="FaBCRUSelfTagUID($uid, 'WTR_POWER:1'); FaBTagUID($uid, 'GO_AGAIN');"
        if b=='fire_that_burns_within':body+="DoDrawCard($player, 1); FaBCRUSelfTagUID($uid, 'WTR_POWER:2');"
        if b=='astravolt_elemental':body+='DoDrawCard($player, 1); '+token('embodiment_of_lightning')
        add('AttackDeclared',UID+body+' }')
    if b.startswith('phoenix_bannerman_'):
        t={'head':'ponder','chest':'vigor','arms':'might','legs':'agility'}[b.split('_')[-1]]
        add('ResolveCard',choose("FaBStageSearch($player, ['base'=>'phoenix_flame'])",'Find_Phoenix_Flame')+"FaBRevealChoices($player, $chosen); FaBMoveChoice($player, $chosen, 'Temp', 'Hand'); FaBFinishSearch($player);"+token(t))
    if b in ['clearwater_elixir','restvine_elixir','sapwood_elixir']:
        t={'clearwater_elixir':'bloodrot_pox','restvine_elixir':'inertia','sapwood_elixir':'frailty'}[b]
        add('ResolveCard',"FaBWTRAddEffect($player, 'NEXT_ATTACK', 3);"+choose(refs('Arena',filters="['base'=>'"+t+"']"),'Destroy_token_to_gain_life')+"if ($chosen !== '-') { FaBMONDestroy(FaBPENUID($chosen)); FaBCRUGainLife($player, 1); }")
    if b in ['runic_fellingsong','weeping_battleground']:
        add('AttackDeclared' if b=='runic_fellingsong' else 'ResolveCard',UID+choose(refs('Graveyard',filters="['type'=>'Aura']"),'Banish_aura')+"if ($chosen !== '-') { FaBMoveChoice($player, $chosen, 'Graveyard', 'Banish'); "+arcane(1,True)+' }')
    if b in ['glyph_power_spell','painful_premonition']:
        add('PrepareCard',UID+choose('FaBPENTargets($player)', 'Choose_arcane_target',False)+'FaBUPRStoreTarget($uid, $chosen); FaBFinishPreparedCard($uid);')
        amount="(FaBPENSigilCount($player) > 0 ? 6 : 4)" if b=='glyph_power_spell' else str(v)
        add('ResolveCard',UID+"$targetUID = intval(FaBARCCard($uid, 'uprTargetUID')); $damage = "+amount+"; $dealt = FaBUPRDeal($player, $uid, $targetUID, $damage, 'ARCANE');"+('if ($dealt > 0) { '+token('sigil_of_fate')+' }' if b=='painful_premonition' else ''))
    if b == 'become_the_bottle':
        add('AttackDeclared',UID+choose('FaBPENChainCards()', 'Choose_a_card_name',False)+'FaBPENCopyName($uid, $chosen);')
    if b == 'become_the_cup':
        add('PrepareCard',UID+'$color = await $player.Modal(1, 1, "Red&Yellow&Blue", "Choose_color"); FaBPENColor($uid, intval($color) + 1); FaBFinishPreparedCard($uid);')
    if b == 'soul_bond_belief': add('AttackDeclared',UID+'FaBPENSoulBelief($player, $uid);')
    if b == 'four_feathers_one_crown': add('AttackDeclared',UID+"FaBCRUSelfTagUID($uid, 'WTR_POWER:' . FaBPENBannermen($player));")
    if b == 'submerge':
        add('PrepareCard',UID+choose(refs('Hand'), 'Put_card_fifth_from_top',False)+'FaBPENFifth($player, $chosen); FaBFinishPreparedCard($uid);')
    if b == 'distant_rumbling':
        add('ResolveCard','DoDrawCard($player, 1);'+choose(refs('Hand'),'Put_card_fifth_from_top',False)+'FaBPENFifth($player, $chosen);')
        add('StartTurn',UID+"FaBMONDestroy($uid);"+token('seismic_surge',n=v))
    if b == 'rites_of_earthlore':
        add('ResolveCard',token('seismic_surge'))
        add('StartTurn',UID+"FaBMONDestroy($uid); FaBHVYNext($player, "+str(v)+", 'Guardian');")
    if b == 'lay_down_the_challenge':
        add('ResolveCard',choose('FaBPENTargets($player, true)', 'Intimidate_hero',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); FaBIntimidate($player, $victim); if (FaBHandCount($victim) > FaBHandCount($player)) { DoDrawCard($player, 1); }")
    if b in ['basalt_boots','blackstone_greaves','unyielding_grip','mbrio_base_cortex','limbs_of_lignum_vitae','dynastic_diadem']:
        # Dynastic Diadem additionally needs destruction protection before enabling.
        conditions={'basalt_boots':"count(FaBMONArena($player, 'seismic_surge')) > 0",'blackstone_greaves':'FaBPENArcaneDealt($player) > 0','unyielding_grip':'FaBHandCount($player) === 0','mbrio_base_cortex':"count(FaBMONArena($player, 'hyper_driver')) + count(FaBMONArena($player, 'hyper_driver_red')) + count(FaBMONArena($player, 'hyper_driver_yellow')) + count(FaBMONArena($player, 'hyper_driver_blue')) > 0",'limbs_of_lignum_vitae':"count(FaBChoiceRefs($player, 'Banish', ['type'=>'Earth'])) >= 4"}
        if b in conditions:add('DefenseModifier','return !HasNoAbilities($subjectObj) && ('+conditions[b]+') ? '+str({'unyielding_grip':3,'mbrio_base_cortex':2}.get(b,1))+' : 0;')
    if b in ['blast_rig','ghost_protocol_mainframe']:
        if b=='blast_rig':add('AttackPowerModifier','return HasNoAbilities($subjectObj) ? 0 : FaBEVOCount($player);')
    if b == 'hulk_up': add('CostModifier', 'return FaBSUPAllLife($player, true) ? -1 : 0;')
    if b == 'emboldened_by_the_crowd': add('CostModifier', "return FaBSUPCount($player, 'CHEER') > 0 ? -3 : 0;")
    if b == 'energy_of_the_audience': add('AttackPowerModifier', "return !HasNoAbilities($subjectObj) && FaBSUPAllLife($player, true) ? count(FaBChoiceRefs($player, 'Arena', ['keyword'=>'Suspense'])) : 0;")
    if b == 'power_of_make_believe': add('AttackPowerModifier','return HasNoAbilities($subjectObj) ? 0 : FaBPENSixDefenders();')
    if b == 'sigil_of_fate': add('ResolveAbility',opt(1))
    if b == 'sigil_of_voltaris': add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); "+arcane(1,True))
    if b == 'cloud_cover': add('ResolveCard',f"FaBWTRAddEffect($player, 'PEN_NEXT_DAMAGE', {v});")
    if b == 'bad_breath': add('ResolveCard',choose('FaBPENTargets($player, true)','Intimidate_hero',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); FaBIntimidate($player, $victim); FaBWTRAddEffect($player, 'PEN_HIT_MIGHT', "+str(v)+');')
    if b == 'aggressive_pounce': add('GoAgainModifier',"return !HasNoAbilities($subjectObj) && FaBHVYCount($player, 'INTIMIDATED') > 0 ? 1 : 0;")
    if b == 'bear_hug': add('PlayCard','',"return FaBPENPitchedSix($player);")
    if b == 'feign_vengeance': add('ChainLinkResolved',"if (count(FaBSUPDefenders()) > 0) { DoDrawCard($player, 1); }")
    if b == 'descend_into_madness': add('ResolveCard',V+"$banished = FaBRandomHandUID($victim); if ($banished > 0) { FaBMoveUID($banished, 'Banish', $victim); } DoDrawCard($victim, 1);")
    if b == 'look_within': add('ResolveCard',choose("FaBStageSearch($player, ['type'=>'Chi'])",'Find_Chi')+'$topUID = FaBPENUID($chosen); FaBRevealChoices($player, $chosen); FaBFinishSearch($player); if ($topUID > 0) { FaBARCToDeck($player, $topUID, true); }')
    if b == 'trench_of_watery_depths': add('Defended',choose(refs('Graveyard',filters="['pitch'=>3]"),'Pitch_blue_card')+'FaBPENPitchGrave($player, $chosen);')
    if b == 'hyper_inflation': add('AttackDeclared',"FaBWTRAddEffect($player, 'PEN_INFLATION', 1);")
    if b == 'by_the_book': handled=True
    if b == 'leave_em_speechless': add('ResolveCard',UID+'$name = await $player.NameCard("", "Name_a_card"); FaBARCSetCard($uid, "penProhibitedName", $name);')
    if b == 'glory_plate': add('DefenseModifier',"return HasNoAbilities($subjectObj) ? 0 : FaBARCEffect($player, 'PEN_TOUGHNESS_LEFT');")
    if b == 'mournful_casket': add('DefenseModifier',"return !HasNoAbilities($subjectObj) && FaBARCEffect($player, 'PEN_ALLY_GRAVE') > 0 ? 1 : 0;")
    if b in ['fluid_motion','manifest_muscle','mistborn_protector']:
        macro={'fluid_motion':'GoAgainModifier','manifest_muscle':'AttackPowerModifier','mistborn_protector':'DefenseModifier'}[b]
        add(macro,"return !HasNoAbilities($subjectObj) && FaBARCEffect($player, 'PEN_CREATED') > 0 ? 1 : 0;")
    if b == 'shimmering_specter': handled=True
    if b in ['silken_shroud','silken_shawl','silken_symphony','silken_slippers']:
        add('ResolveAbility',token({'silken_shroud':'ponder','silken_shawl':'vigor','silken_symphony':'might','silken_slippers':'agility'}[b]))
    if b == 'robe_of_resourcefulness': add('ResolveAbility','AddResources($player, intval(GetResources($player)) + 2);')
    if b == 'shroud_of_the_fate_watcher': add('ResolveAbility',token('sigil_of_fate'))
    if b == 'tempest_dancers': add('ResolveAbility',"FaBWTRAddEffect($player, 'ARC_NEXT_NAA_INSTANT', 1);")
    if b == 'gloves_of_erasure': add('ResolveAbility',choose("FaBPENPermanents($player, 'Aura', true)",'Destroy_aura_token',False)+'FaBMONDestroy(FaBPENUID($chosen));')
    if b == 'crown_of_everbloom': add('ResolveAbility',choose(refs('Arsenal'),'Bottom_arsenal_card',False)+"if ($chosen !== '-') { FaBHVYBottomChoice($chosen); DoDrawCard($player, 1); "+token('spellbane_aegis')+' }')
    if b == 'grimoire_of_fellingsong': add('ResolveAbility',token('runechant'))
    if b == 'reach_beyond_the_grave': add('ResolveAbility',choose(refs('Graveyard',filters="['type'=>'Ally']"),'Return_ally',False)+"FaBMoveChoice($player, $chosen, 'Graveyard', 'Hand');"+discard(optional=False))
    if b == 'strike_twice':
        add('PrepareCard',UID+choose('FaBPENTargets($player)','Choose_arcane_target',False)+'FaBUPRStoreTarget($uid, $chosen); FaBFinishPreparedCard($uid);')
        add('ResolveCard',UID+"$targetUID = intval(FaBARCCard($uid, 'uprTargetUID')); $dealt = FaBUPRDeal($player, $uid, $targetUID, 3, 'ARCANE');")
    if b == 'high_current_currency': add('ResolveCard',choose("FaBPENPermanents($player, '', false, true)",'Remove_energy_counters',False)+'$energy = FaBPENRemoveEnergy($chosen); '+token('gold',n='$energy'))
    if b == 'art_of_the_phoenix_war':
        add('PlayCard','',"return count(FaBChoiceRefs($player, 'Hand', ['base'=>'phoenix_flame'])) > 0;")
        add('PrepareCard',UID+discard("['base'=>'phoenix_flame']",False)+'FaBFinishPreparedCard($uid);')
        add('ResolveCard',"FaBWTRAddEffect($player, 'PEN_DRACONIC_POWER', 1); DoDrawCard($player, 2);")
    if b == 'depths_of_despair': add('Defended',UID+"FaBTagUID($uid, 'ELE_BANISH_REPLACE');")
    if b in ['engulfing_shadows','embraforged_gauntlet']: handled=True
    if b in ['seeds_of_strength','frosthaven_sheath','leaven_sheath','stormwind_sheath','laden_with_earth','laden_with_frost','laden_with_lightning']:
        element='Earth' if b in ['seeds_of_strength','leaven_sheath','laden_with_earth'] else 'Ice' if b in ['frosthaven_sheath','laden_with_frost'] else 'Lightning'
        body=UID+("FaBWTRAddEffect($player, 'NEXT_ATTACK', 3);" if b.startswith('laden_') else '')
        if b=='seeds_of_strength':body+="$number = FaBPENBond($uid, 'Earth') ? 4 : 3; "+token('might',n='$number')
        else:
            body+="if (FaBPENBond($uid, '"+element+"')) { "
            if b=='laden_with_frost':body+=choose('FaBPENTargets($player, true)','Create_Frostbite_under_hero',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); "+token('frostbite','$victim')
            elif b=='frosthaven_sheath':body+="$victim = intval(FaBGetState()['attacker']); "+token('frostbite','$victim')
            else:body+=token('embodiment_of_earth' if element=='Earth' else 'embodiment_of_lightning')
            body+=' }'
        add('ResolveCard',body)
    if b == 'elemental_strike':
        add('PlayCard','return;',"return FaBHandCount($player) > (FaBIdentityFromMZ($mzID)['zone'] === 'Hand' ? 1 : 0);")
        add('PrepareCard',UID+choose(refs('Hand'),'Banish_a_card',False)+'FaBPENElementalStrike($player, $uid, $chosen); FaBFinishPreparedCard($uid);')
    if b == 'colors_of_aria':handled=True
    if b == 'put_on_ice':add('ResolveCard',many("FaBPENPermanents($player, 'Ally')",v,tip='Freeze_allies')+"FaBPENFreeze($player, $chosen); if (DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') { DoDrawCard($player, 1); }")
    if b == 'crown_of_frozen_thoughts':add('Defended',"$victim = intval(FaBGetState()['attacker']); FaBPENFreezeHero($victim);")
    if b == 'shattering_grasp':add('ResolveAbility',choose("FaBPENFrozen($player, 'Ally')",'Destroy_frozen_ally',False)+'FaBMONDestroy(FaBPENUID($chosen));')
    if b == 'voltic_veil':add('ResolveCard',UID+"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 4); if (FaBPENBond($uid, 'Lightning')) { $seats = FaBOpponents($player); for ($i = 0; $i < count($seats); $i = $i + 1) { $targetUID = FaBUPRHeroUID(intval($seats[$i])); $dealt = FaBUPRDeal($player, $uid, $targetUID, 1, 'ARCANE'); } }")
    if b == 'chorus_of_rotwood':
        add('ResolveCard',many(refs('Graveyard',filters="['type'=>'Earth']"),2,tip='Banish_two_Earth_cards')+"$earth = $chosen; if (count(FaBUPRUIDs($earth)) === 2) { "+choose('FaBPENDecomposeActions($player, $earth)','Banish_action_card')+"if (FaBROSDecompose($player, $earth, $chosen)) { "+token('embodiment_of_earth')+' } } '+token('runechant',n=3))
    if b in ['double_cross_strap','predatory_plating']:add('ResolveAbility','AddResources($player, intval(GetResources($player)) + 1);')
    if b == 'two_steps_forward':add('ResolveAbility',token('agility'))
    if b == 'voltic_vanguard':add('ResolveAbility',"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 2);")
    if b == 'templar_spellbane':add('ResolveAbility',"$amount = FaBPENWeaponActivated($player) ? 2 : 1; FaBWTRAddEffect($player, 'ARC_PREVENT', $amount);")
    if b == 'insult_to_injury':add('AttackDeclared',hit(UID+"if (GetHealth($player) > GetHealth($victim)) { FaBTagUID($uid, 'GO_AGAIN'); }"))
    if b == 'spellbane_trap':
        add('ResolveCard',f"FaBWTRAddEffect($player, 'ARC_NEXT_ARROW', {v});")
        add('Defended',"if (FaBPENArcaneDealt(intval(FaBGetState()['attacker'])) > 0) { "+token('spellbane_aegis')+" $victim = intval(FaBGetState()['attacker']); if (FaBOUTHero($player, 'riptide')) { DoDamage($player, $mzID, $victim, 1, 'PHYSICAL'); } }")
    if b == 'frost_spike':add('ResolveCard',"$options = FaBMPGJarlOptions($player); if ($options !== '') { $slot = await $player.Modal(1, 1, $options, \"Choose_exposed_equipment_zone\"); FaBPENFrostSpike($player, $options, $slot); }")
    if b == 'whispering_mist':add('ResolveCard',"FaBWTRAddEffect($player, 'PEN_WHISPER', 1);")
    if b == 'tough_as_a_rok':handled=True
    if b == 'rockyard_rodeo':handled=True
    if b == 'gloves_of_azure_waves':handled=True
    if b in ['skera_strapping','volcanic_vice','mask_of_the_swarming_claw']:handled=True
    if b in ['plating_of_unity','pillar_of_unity']:handled=True
    if b == 'swordmasters_shine':
        add('CostModifier','return -FaBPENSwordCounters($player);')
        add('ResolveCard',choose("FaBDYNAttacks($player, 'WEAPON')",'Choose_weapon_attack',False)+"FaBDYNTag($chosen, 'WTR_POWER:5');")
    if b == 'excessive_bloodloss': add('Hit',hit('FaBPENBloodloss($player, $victim);'))
    if b == 'lighten_the_load':add('AttackDeclared',UID+choose("FaBPENLightenChoices($player)",'Discard_card_or_destroy_item')+"if (FaBPENLighten($player, $chosen)) { FaBTagUID($uid, 'GO_AGAIN'); }")
    if b == 'fasting_carcass':add('ResolveCard',f"FaBWTRAddEffect($player, 'PEN_NEXT_COLOR_GO', {int(c['pitch'])});")
    if b == 'embalm':add('ResolveCard',UID+"if (DecisionQueueController::GetVariable('fabSourceZone') === 'Banish') { FaBTagUID($uid, 'GO_AGAIN'); } "+choose("FaBPENEmbalm($player)",'Bottom_blood_debt_attack',False)+'FaBHVYBottomChoice($chosen);')
    if b == 'glyph_destruction_nodes':add('ResolveCard',UID+many('FaBPENTargets($player, false, FaBPENSigilCount($player) >= 2)','FaBPENSigilCount($player)',tip='Choose_up_to_X_targets')+'$targets = FaBUPRUIDs($chosen); for ($i = 0; $i < count($targets); $i = $i + 1) { $targetUID = intval($targets[$i]); $dealt = FaBUPRDeal($player, $uid, $targetUID, 3, "ARCANE"); }')
    if b == 'pilfer_the_tomb':
        add('ResolveCard','$modes = await $player.Modal(1, 2, "Banish_instant&Banish_yellow", "Choose_modes"); if (str_contains($modes, "0")) { '+choose("FaBPENOpposingGraves($player, 'Instant')",'Banish_instant',False)+'FaBPENBanishRef($chosen); } if (str_contains($modes, "1")) { '+choose("FaBPENOpposingGraves($player, 'Yellow')",'Banish_yellow',False)+'FaBPENBanishRef($chosen); }')
    if b == 'destructive_tendencies':
        add('ResolveCard','$modes = await $player.Modal(1, 2, "Item_token&Aura_token", "Choose_modes"); if (str_contains($modes, "0")) { '+choose("FaBPENPermanents($player, 'Item', true)",'Remove_item_counters',False)+'FaBPENRemoveCounters($chosen); } if (str_contains($modes, "1")) { '+choose("FaBPENPermanents($player, 'Aura', true)",'Remove_aura_counters',False)+'FaBPENRemoveCounters($chosen); }')
    if b == 'shatter_sorcery':
        add('ResolveCard','$modes = await $player.Modal(1, 2, "Destroy_Sigil&Prevent_arcane", "Choose_modes"); if (str_contains($modes, "0")) { '+choose('FaBPENSigils($player)','Destroy_Sigil',False)+'FaBMONDestroy(FaBPENUID($chosen)); } if (str_contains($modes, "1")) { '+choose('FaBPENTargets($player, true)','Protect_hero',False)+"$seat = intval(FaBIdentityFromMZ($chosen)['player']); FaBWTRAddEffect($seat, 'ARC_PREVENT', 1); }")
    if b == 'sigil_of_silphidae':add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); "+choose('FaBPENOtherAuras($player, $uid)','Banish_another_aura')+"if ($chosen !== '-') { FaBMoveChoice($player, $chosen, 'Graveyard', 'Banish'); "+arcane(1,True)+' }')
    if b == 'sigil_of_gravespawning':add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); "+arcane(1,True))
    if b == 'blessing_of_bellona':handled=True
    if b == 'blessing_of_themis':add('ResolveCard',UID+'$name = await $player.NameCard("", "Name_a_card"); FaBPENThemis($uid, $name);')
    if b == 'billowing_mist':add('ResolveCard',"FaBWTRAddEffect($player, 'NEXT_ATTACK', 1); FaBWTRAddEffect($player, 'PEN_EXTRA_EPHEMERAL', 1);")
    if b == 'spreading_mist':add('ResolveCard',"FaBWTRAddEffect($player, 'PEN_NEXT_ATTACK_GO', 1); FaBWTRAddEffect($player, 'PEN_EXTRA_EPHEMERAL', 1);")
    if b == 'chain_of_brutality':add('Hit',hit("if (FaBAttackPower(FaBGetState()) >= 6) { FaBWTRAddEffect($player, 'PEN_NEXT_BASE_SIX', 1); }"))
    if b == 'tome_of_pandemonium':add('ResolveCard','FaBPENPandemonium($player);')
    if b == 'valahai_riven':add('Defended',resource_amount()+token('seismic_surge',n='$amountPaid'))
    if b == 'duty_bound_blitz':add('PlayCard','return;',"return FaBARCEffect($player, 'PEN_YELLOW_SOUL') > 0;")
    if b == 'two_faced':add('Defended',"$victim = intval(FaBGetState()['attacker']); DoDrawCard($victim, 1); $previews = FaBDYNPrivateHand($player, $victim); "+choose('FaBPENPreviewRefs($previews)','Discard_opponent_card',False)+'FaBPENDiscardPreview($victim, $chosen, $previews);')
    if b == 'snarky_prick':add('AttackDeclared',hit(UID+'$previews = FaBDYNPeekTop($player, $victim); '+choose('FaBPENRedPreviews($previews)','Destroy_red_top_card')+"if ($chosen !== '-') { FaBMoveUID(FaBPENUID($chosen), 'Graveyard', $victim); FaBCRUSelfTagUID($uid, 'WTR_POWER:4'); } FaBDYNFinishPeek($victim, $previews, false);"))
    if b == 'speed_demon':
        add('PrepareCard',UID+many("FaBEVORefs($player, 'Graveyard', 'scrap')",1,tip='Scrap_item_or_equipment')+'FaBEVOHyperScrap($player, $uid, $chosen); FaBFinishPreparedCard($uid);')
        add('AttackPowerModifier',"return !HasNoAbilities($subjectObj) && count(FaBMONArena($player, 'hyper_driver')) > 0 ? 1 : 0;")
        add('AttackDeclared',UID+"if (FaBARCCard($uid, 'evoDrivers') > 0) { FaBPENDriver($player); }")
    if b in ['ghost_protocol_architect','ghost_protocol_mainframe']:
        if b=='ghost_protocol_mainframe':add('AttackPowerModifier','return HasNoAbilities($subjectObj) ? 0 : FaBEvoCount($player);')
        else:add('AttackDeclared',choose("FaBStageSearch($player, ['type'=>'Evo', 'maxCost'=>FaBEvoCount($player)])",'Banish_Evo')+"FaBMoveChoice($player, $chosen, 'Temp', 'Banish'); FaBFinishSearch($player);")
    if b == 'heavy_metal_hardcore':add('AttackPowerModifier',"return !HasNoAbilities($subjectObj) && FaBARCEffect($player, 'PEN_BOOST_EVO') > 0 ? 1 : 0;")
    if b == 'teklo_trebuchet_2000':add('AttackDeclared',"FaBWTRAddEffect($player, 'PEN_BOOST_NEXT', 2);")
    if b == 'knife_through':add('GoAgainModifier',"return !HasNoAbilities($subjectObj) && FaBARCEffect($player, 'PEN_DAGGER_HIT') > 0 ? 1 : 0;")
    if b == 'beneath_the_surface':handled=True
    if b == 'skywarden_no161803':add('Defended',UID+choose(refs('Arena',filters="['type'=>'Item']"),'Destroy_item_to_galvanize')+'FaBPENSkywarden($player, $uid, $chosen);')
    if b == 'bone_puppetry':add('Defended',choose(refs('Graveyard',filters="['type'=>'Ally']"),'Return_ally_until_end_phase')+'FaBPENPuppetry($player, $chosen);')
    if b == 'scuttle_toes':add('ResolveAbility',choose(refs('Arena',filters="['type'=>'Ally']"),'Untap_ally',False)+'FaBPENScuttle($chosen);')
    if b == 'unflinching_foothold':add('ResolveAbility',"FaBTagUID(intval(FaBGetState()['attackUID']), 'PEN_NO_DOMINATE');")
    if b == 'enflame_the_firebrand':add('AttackDeclared',UID+"$links = FaBFaiChainCount($player); if ($links >= 2) { FaBTagUID($uid, 'GO_AGAIN'); } if ($links >= 3) { FaBWTRAddEffect($player, 'PEN_DRACONIC_CHAIN', 1); } if ($links >= 4) { FaBCRUSelfTagUID($uid, 'WTR_POWER:2'); }")
    if b == 'overcharge':add('AttackPowerModifier',"return !HasNoAbilities($subjectObj) && FaBARCEffect($player, 'PEN_INSTANT_CHAIN') > 0 ? 3 : 0;")
    if b == 'verdant_tide':add('ResolveCard',UID+"FaBWTRAddEffect($player, 'PEN_VERDANT', 1); if (FaBPENBond($uid, 'Earth')) { "+token('embodiment_of_earth')+' }')
    if b.startswith('evo_beta_base_'):handled=True
    if b == 'savage_claw':add('AttackDeclared',UID+"if (FaBARCCard($uid, 'penPaidSix')) { FaBCRUSelfTagUID($uid, 'WTR_POWER:1'); }")
    if b == 'shield_beater':handled=True
    if b == 'boo_resident_spook':handled=True
    if b == 'helm_of_safe_haven':add('Defended','if (FaBPENSafeHaven($player)) { '+discard(optional=False)+' }')
    if b == 'burnished_bunkerplate':add('ResolveAbility',choose(refs('Arsenal',filters="['type'=>'Action']"),'Add_arsenal_defender')+'FaBPENAddDefender($player, $chosen);')
    if b == 'doomsaying':add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); $number = FaBPENDoom($uid); $seats = FaBLiveSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = intval($seats[$i]); $left = $number; while ($left > 0) { "+choose(refs('Arena','$seat',"['type'=>'Aura']"),'Destroy_aura',False,'$seat')+"if ($chosen === '-') { break; } FaBMONDestroy(FaBPENUID($chosen)); $left = $left - 1; } }")
    if b == 'comeback_kicks':add('ResolveAbility',"if (FaBSUPAllLife($player, true)) { "+choose("implode('&', FaBCRUEquipment($player, 'comeback_kicks'))",'Destroy_to_gain_action_point')+"if ($chosen !== '-') { FaBMONDestroy(FaBPENUID($chosen)); AddActionPoints($player, intval(GetActionPoints($player)) + 1); } }")
    if b == 'rainbow_goo_trap':add('Defended',"if (FaBPENRainbow()) { FaBPENSuppressAttack(); $victim = intval(FaBGetState()['attacker']); if (FaBOUTHero($player, 'riptide')) { DoDamage($player, $mzID, $victim, 1, 'PHYSICAL'); } }")
    if b in ['courageous_crossing','frail_swingline','quickening_sand']:
        t={'courageous_crossing':'courage','frail_swingline':'frailty','quickening_sand':'quicken'}[b]
        add('ResolveCard',choose('FaBPENTargets($player, true)','Choose_hero',False)+"$seat = intval(FaBIdentityFromMZ($chosen)['player']); "+token(t,'$seat'))
        condition={'courageous_crossing':'FaBPENAttackDelta() > 0','frail_swingline':'FaBPENAttackDelta() < 0','quickening_sand':'FaBPENAttackGoAgain()'}[b]
        body={'courageous_crossing':choose("FaBPENPermanents($player)",'Remove_power_counter',False)+'FaBPENRemovePower($chosen);','frail_swingline':"$victim = intval(FaBGetState()['attacker']); "+discard(optional=False,seat='$victim'),'quickening_sand':choose('FaBPENTargets($player)','Tap_hero_or_ally',False)+'FaBPENTap($chosen);'}[b]
        add('Defended','if ('+condition+') { '+body+"$victim = intval(FaBGetState()['attacker']); if (FaBOUTHero($player, 'riptide')) { DoDamage($player, $mzID, $victim, 1, 'PHYSICAL'); } }")
    if b == 'solforge_gauntlet':handled=True
    if b == 'shimmering_mirage':handled=True
    if b == 'stadium_security':handled=True
    if b == 'monolith_of_galcia':
        body='$modes = await $player.Modal(1, 4, "Ally&Aura&Equipment&Item", "Choose_frozen_types");'
        for i,kind in enumerate(['Ally','Aura','Equipment','Item']):body+=f'if (str_contains($modes, "{i}")) {{ '+choose(f"FaBPENFrozen($player, '{kind}')",'Destroy_frozen_card',False)+'FaBMONDestroy(FaBPENUID($chosen)); }'
        add('ResolveCard',body)
    if b in ['channel_the_skybreaker','channel_iceloch_glaze','channel_galcias_cradle']:
        element='Earth' if b=='channel_the_skybreaker' else 'Ice'
        add('StartTurn',UID+"$needed = FaBELEFlow($uid); $refs = FaBELESelect($player, 'Pitch', '"+element+"'); $chosen = '-'; if ($refs !== '' && count(explode('&', $refs)) >= $needed) { $chosen = await $player.MZMultiChoose($refs, 0, $needed, \"Pay_channel_upkeep_or_destroy\"); } FaBELEChannelPay($player, $uid, $chosen, $needed);")
        if b=='channel_the_skybreaker':add('ResolveCard',token('might',n=2));add('ResolveAbility',token('might',n=2))
        if b=='channel_galcias_cradle':
            add('ResolveCard',UID+choose('FaBPENCradleTargets($player)','Freeze_permanent',False)+'FaBPENCradle($uid, $chosen);')
            add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); "+choose('FaBPENCradleTargets($player)','Freeze_permanent',False)+'FaBPENCradle($uid, $chosen);')
    if b == 'arc_bending':add('AttackDeclared',UID+"FaBWTRAddEffect($player, 'PEN_ARC_BENDING', 1); if (FaBPENBond($uid, 'Lightning')) { FaBTagUID($uid, 'GO_AGAIN'); }")
    if b == 'cut_n_carve':add('ResolveCard',choose('FaBPENSwords($player)','Sharpen_sword',False)+f'FaBPENSharpen($player, $chosen, {int(c["pitch"])});')
    if b == 'display_of_craftsmanship':add('ResolveCard',choose("FaBDYNAttacks($player, 'WEAPON')",'Empower_weapon_attack',False)+f'FaBPENCraftsmanship($chosen, {v+1});')
    if b == 'rend_flesh':
        add('ResolveCard',"FaBWTRAddEffect($player, 'PEN_REND', 1);")
        add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); $victim = intval(DecisionQueueController::GetVariable('rosTarget')); if (FaBPENRendReady($uid)) { $mode = await $player.Modal(1, 1, \"Decline&Remove_power_counter\", \"Rend_Flesh\"); if ($mode === '1') { FaBPENRend($player, $uid, $victim); } }")
    if b == 'gentle_breeze':handled=True
    if b == 'havoc_wrap':add('ResolveAbility','return;')
    if b == 'myrkhellir_helm':
        add('DefenseModifier',"return !HasNoAbilities($subjectObj) && count(FaBMONArena($player, 'gold')) > 0 ? 1 : 0;")
        add('ResolveAbility',"FaBWTRAddEffect($player, 'PEN_GOLD_DRAW', 1);")
    if b == 'sowing_thorns':
        add('ResolveCard',"FaBCRUGainLife($player, 1);"+many(refs('Graveyard',filters="['type'=>'Earth']"),2,tip='Banish_two_Earth_cards')+"$earth = $chosen; if (count(FaBUPRUIDs($earth)) === 2) { "+choose('FaBPENDecomposeActions($player, $earth)','Banish_action_card')+"if (FaBROSDecompose($player, $earth, $chosen)) { "+choose('FaBPENSowSearch($player)','Find_Earth_aura')+'$topUID = FaBPENUID($chosen); FaBRevealChoices($player, $chosen); FaBFinishSearch($player); if ($topUID > 0) { FaBARCToDeck($player, $topUID, true); } } }')
    if b == 'wind_cutter':add('ResolveAbility',choose("FaBStageSearch($player, ['type'=>'Shuriken'])",'Find_Shuriken',False)+"FaBMoveChoice($player, $chosen, 'Temp', 'Arena'); FaBFinishSearch($player);")
    if b == 'farflight_longbow':add('ResolveAbility',choose(refs('Hand',filters="['type'=>'Arrow']"),'Load_arrow',False)+'FaBARCLoadArsenal($player, $chosen, true);')
    if b == 'boltn_boots':add('ResolveAbility',choose("FaBPENPoweredArrow($player)",'Give_arrow_go_again',False)+"FaBDYNTag($chosen, 'GO_AGAIN');")
    if b == 'mist_hunter':add('Hit',hit("if (FaBHasType(GetHero($victim)[0], 'Mystic')) { "+many("FaBPENInnerChi($victim)",100,tip='Banish_Inner_Chi')+'FaBPENBanishChi($player, $victim, $chosen); }'))
    if b == 'temporal_wobble':add('ResolveCard',choose('FaBPENWobbleTargets($player)','Negate_non_attack_action',False)+'FaBPENWobble($chosen);')
    if b == 'conquer_the_icy_terrain':add('Hit',hit('$canPay = FaBAvailablePitch($victim) >= 2; $mode = "0"; if ($canPay) { $mode = await $victim.Modal(1, 1, "Decline&Pay_two_resources", "Conquer_the_Icy_Terrain"); } if ($mode === "1") { while (intval(GetResources($victim)) < 2) { '+choose('FaBARCPitchChoices($victim)','Pitch_to_pay',False,'$victim')+'FaBARCPitchForEffect($victim, $chosen); } AddResources($victim, intval(GetResources($victim)) - 2); } else { '+choose('FaBPENConquerTargets($victim)','Destroy_frozen_card')+'FaBMONDestroy(FaBPENUID($chosen)); }'))
    if b == 'ion_charged':add('ResolveCard',"FaBWTRAddEffect($player, 'PEN_ION', 1);")
    if b == 'smoldering_steel':add('ResolveCard',choose("FaBDYNAttacks($player, 'DAGGER')",'Empower_dagger',False)+"FaBDYNTag($chosen, 'WTR_POWER:1'); FaBDYNTag($chosen, 'PEN_SMOLDER');")
    if b == 'wax_and_wane':add('ResolveCard','$modes = await $player.Modal(1, 2, "Blue_aura&Aura_with_ward", "Choose_modes"); if (str_contains($modes, "0")) { '+choose("FaBPENWaxTargets($player, false)",'Empower_blue_aura',False)+'FaBPENPowerCounter($chosen); } if (str_contains($modes, "1")) { '+choose("FaBPENWaxTargets($player, true)",'Empower_ward_aura',False)+'FaBPENPowerCounter($chosen); } if (str_contains($modes, "0") && str_contains($modes, "1")) { '+choose("FaBStageSearch($player, ['base'=>'inner_chi'])",'Find_Inner_Chi')+'$topUID = FaBPENUID($chosen); FaBRevealChoices($player, $chosen); FaBFinishSearch($player); if ($topUID > 0) { FaBARCToDeck($player, $topUID, true); } }')
    if b == 'ransack_and_raze':
        add('PrepareCard',UID+'$maximum = FaBAvailablePitch($player); $amount = await $player.NumberChoose(0, $maximum, "Choose_X"); FaBPENSetX($uid, intval($amount)); FaBFinishPreparedCard($uid);')
        add('ResolveCard',UID+'$amount = intval(FaBARCCard($uid, "penX")); '+choose('FaBPENLandmarks($player, $amount)','Destroy_landmark',False)+'FaBMONDestroy(FaBPENUID($chosen)); '+token('gold',n='$amount'))
    if b in ['synapse_sparkcap','mbrio_base_digits','runebleed_robe','carrion_crown','dyed_silk_sleeves','graven_gaslight']:
        add('PrepareCard',UID+choose('FaBPENCostChoices($player, $uid)','Pay_additional_cost',False)+'FaBPENPayChosenCost($player, $uid, $chosen); FaBFinishPreparedCard($uid);')
        body={'synapse_sparkcap':token('ponder'),'mbrio_base_digits':UID+"FaBTagUID($uid, 'WTR_DEFENSE:1');",'runebleed_robe':"FaBWTRAddEffect($player, 'ARC_PREVENT', 1);",'carrion_crown':'DoDrawCard($player, 1);','dyed_silk_sleeves':choose("FaBPENNinjaAttack($player, true)",'Empower_Ninja_attack',False)+"FaBDYNTag($chosen, 'WTR_POWER:1'); FaBPENDyed($player);",'graven_gaslight':'FaBPENEquipGraven($player, intval(DecisionQueueController::GetVariable("penAbilitySource")));'}[b]
        if b=='graven_gaslight':
            a=[]
            add('PrepareCard',UID+many("implode('&', FaBMONArena($player, 'silver'))",2,minimum=2,tip='Destroy_two_Silver')+'FaBPENPaySilver($player, $uid, $chosen); FaBFinishPreparedCard($uid);')
        add('ResolveAbility',body)
    if b in ['graven_cowl','graven_vestment','graven_gloves','graven_walkers','seeker_kunai']:
        add('StartTurn',UID+many("implode('&', FaBMONArena($player, 'silver'))",2,tip='Destroy_two_Silver_to_return')+'FaBPENReturnGraven($player, $uid, $chosen);')
        if b=='seeker_kunai':add('ResolveAbility',choose("FaBDYNAttacks($player, 'ASSASSIN')",'Empower_Assassin_attack',False)+"FaBDYNTag($chosen, 'WTR_POWER:1');")
    if b == 'assembly_module':
        add('ResolveAbility',choose("FaBStageSearch($player, ['base'=>'hyper_driver'])",'Find_Hyper_Driver',False)+"FaBMoveChoice($player, $chosen, 'Temp', 'Arena'); FaBFinishSearch($player);")
    if b in ['touch_of_reality','beckoning_haunt']:
        multiplier=2 if b=='beckoning_haunt' else 1
        add('PrepareCard',UID+f'$maximum = max(0, intdiv(FaBAvailablePitch($player) - {1 if multiplier==2 else 0}, {multiplier})); $amount = await $player.NumberChoose(0, $maximum, "Choose_X"); FaBPENSetX($uid, intval($amount) * {multiplier}); FaBARCSetCard($uid, "penChosenX", intval($amount)); FaBFinishPreparedCard($uid);')
        if b=='touch_of_reality':add('ResolveAbility','FaBPENTouch(intval(DecisionQueueController::GetVariable("penAbilitySource")), intval(DecisionQueueController::GetVariable("penChosenX")));')
        else:add('ResolveAbility','$amount = intval(DecisionQueueController::GetVariable("penChosenX")); '+choose('FaBPENAuraCost($player, $amount)','Return_aura',False)+"FaBMoveChoice($player, $chosen, 'Graveyard', 'Hand');")
    if b == 'tigrine_reflex':
        add('AttackDeclared','FaBIraCombo($mzID, 1, true);')
        add('ResolveAbility',choose('FaBPENNinjaAttack($player)','Empower_Ninja_attack',False)+"FaBDYNTag($chosen, 'WTR_POWER:1'); FaBCreateTigers($player, 1);")
    if b == 'herald_of_victoria':add('ResolveAbility',"FaBWTRAddEffect($player, 'PEN_VICTORIA', 1);")
    if b == 'sense_weakness':add('ResolveCard',"FaBWTRAddEffect($player, 'PEN_SENSE', 1);")
    if b == 'shallow_water_shark_harpoon':add('Hit',hit("if (FaBSEACount($player, 'CANNON') > 0) { "+choose(refs('Arsenal','$victim'),'Destroy_arsenal_card',False)+"if ($chosen !== '-') { FaBMONDestroy(FaBPENUID($chosen)); "+token('gold')+' } }'))
    if b == 'rune_snare':
        add('ResolveCard',"FaBWTRAddEffect($player, 'ARC_NEXT_ARROW', 3);")
        add('Defended',"$victim = intval(FaBGetState()['attacker']); if (FaBARCEffect($victim, 'PEN_AURAS') >= 2) { "+choose(refs('Arena','$victim',"['type'=>'Aura']"),'Destroy_attacker_aura',False)+'FaBMONDestroy(FaBPENUID($chosen)); }')
    if b == 'haboob':add('ResolveAbility',' $uid = intval(DecisionQueueController::GetVariable("rosSource")); $number = FaBPENStorm($uid); '+many(refs('Arena',filters="['base'=>'ash']"),'$number',tip='Destroy_ash_or_destroy_Haboob')+'FaBPENHaboobUpkeep($player, $uid, $chosen, $number);')
    if b == 'shapeless_form':
        add('ResolveCard',"FaBWTRAddEffect($player, 'PEN_SHAPELESS', 1);")
        add('ResolveAbility','$uid = intval(DecisionQueueController::GetVariable("rosTarget")); $name = await $player.NameCard("", "Choose_attack_name"); FaBARCSetCard($uid, "penNames", [$name]);')
    if b == 'deep_recesses_of_existence':
        add('CombatChainClosed','return;')
        add('ResolveAbility','$uid = intval(DecisionQueueController::GetVariable("rosSource")); $mode = await $player.Modal(1, 1, "Decline&Banish_face_down", "Deep_Recesses_of_Existence"); if ($mode === "1") { FaBPENBanishDown($uid); $seats = FaBDTDLostSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = intval($seats[$i]); '+choose(refs('Graveyard','$seat'),'Banish_graveyard_card',False)+'FaBPENBanishRef($chosen); } }')
    if b == 'lobotomy':
        add('AttackDeclared',choose(refs('Inventory',filters="['base'=>'orbitoclast']"),'Equip_Orbitoclast')+'$orbitUID = FaBPENUID($chosen); if ($orbitUID > 0) { while (FaBHNTOpenHands($player) < 1) { '+choose(refs('Weapons'),'Replace_weapon',False)+'if ($chosen === "-") { break; } FaBMONDestroy(FaBPENUID($chosen)); } FaBPENOrbitoclast($player, FaBDTDSource($orbitUID)); }')
        add('Hit',hit("if (count(FaBChoiceRefs($player, 'Weapons', ['base'=>'orbitoclast'])) > 0) { FaBPENLobotomy($victim); }"))
    if b == 'rippling_wave':add('ResolveAbility',choose('FaBPENBlueDefenders()','Return_blue_defender')+'FaBPENReturnOwner($chosen);')
    if b == 'kimono_of_layered_lessons':add('ResolveAbility','FaBPENKimono(intval(DecisionQueueController::GetVariable("penAbilitySource")));')
    if b == 'recede_to_mistform':
        add('PrepareCard',UID+'$maximum = FaBAvailablePitch($player); $amount = await $player.NumberChoose(0, $maximum, "Choose_X"); FaBPENSetX($uid, intval($amount)); FaBFinishPreparedCard($uid);')
        add('ResolveCard',UID+'$amount = intval(FaBARCCard($uid, "penX")); '+many('FaBPENCloaked($player)','$amount',minimum='$amount',tip='Turn_equipment_face_down')+'FaBPENFaceDown($chosen);')
    if b == 'serpents_kiss':
        add('AttackDeclared','if (FaBMSTCount($player, "TRANSCENDED") > 0) { FaBMSTEphemeral($player, "fang_strike_blue"); FaBMSTEphemeral($player, "slither_blue"); } else { $mode = await $player.Modal(1, 1, "Fang_Strike&Slither", "Create_card"); FaBMSTEphemeral($player, $mode === "0" ? "fang_strike_blue" : "slither_blue"); }')
        add('Hit',hit('$previews = FaBPENPeekTwo($player, $victim); '+choose('FaBPENPreviewRefs($previews)','Banish_one_card',False)+'FaBPENBanishPeek($player, $victim, $chosen); FaBDYNFinishPeek($victim, $previews, false);'))
    if b in ['concealed_nerve_gas','concealed_pathogen','concealed_sedative']:handled=True
    if b == 'magmatic_carapace':add('ResolveAbility','$uid = intval(DecisionQueueController::GetVariable("rosSource")); if (FaBPENMagmaticReady($player, $uid)) { $mode = await $player.Modal(1, 1, "Decline&Tap_and_pay_one", "Magmatic_Carapace"); if ($mode === "1") { while (intval(GetResources($player)) < 1) { '+choose('FaBARCPitchChoices($player)','Pitch_to_pay',False)+'FaBARCPitchForEffect($player, $chosen); } if (FaBPENMagmaticReady($player, $uid)) { AddResources($player, intval(GetResources($player)) - 1); FaBPENTap(FaBDTDSource($uid)); '+token('seismic_surge')+' } } }')
    if b == 'seismic_shift':
        add('PrepareCard',UID+many('FaBPENReadySurges($player)',100,tip='Tap_Seismic_Surges')+'FaBPENShiftCost($uid, $chosen); FaBFinishPreparedCard($uid);')
        add('ResolveCard',UID+'$number = intval(FaBARCCard($uid, "penShift")); '+many("FaBPENPermanents($player, 'Aura', true)",'$number',minimum='$number',tip='Destroy_aura_tokens')+'FaBDYNDestroyUIDs(FaBUPRUIDs($chosen));')
    if b == 'bubba_lubba_run_aground':
        add('PrepareCard',UID+choose('FaBPENPoweredAllies($player)','Remove_ally_power_counter',False)+'FaBPENRemovePower($chosen); FaBFinishPreparedCard($uid);')
        add('ResolveAbility','if (DecisionQueueController::GetVariable("rosEvent") !== "seaWatery") { '+choose("FaBPENPermanents($player, 'Aura', true, false, false)",'Destroy_aura_token',False)+'FaBMONDestroy(FaBPENUID($chosen)); }')
    if b == 'dynastic_diadem':add('DefenseModifier',"return !HasNoAbilities($subjectObj) && count(FaBMONArena($player, 'fealty')) >= 3 ? 1 : 0;")
    if b == 'topsy_turvy':add('ResolveAbility',"FaBWTRAddEffect($player, 'PEN_TOPSY', 1);")
    if b in ['solray_plating','mbrio_base_vizier','vestige_of_flagellation']:handled=True
    if b == 'vestige_of_flagellation':add('ResolveAbility','$refs = (string)DecisionQueueController::GetVariable("penVestiges"); $amount = intval(DecisionQueueController::GetVariable("penGainAmount")); '+choose('$refs','Choose_life_gain_replacement',False)+'FaBPENVestige($chosen, $amount);')
    if b == 'embody_greatness':add('ResolveCard','$names = FaBPENLivingLegends(); $name = await $player.NameCard($names, "Name_living_legend_hero"); FaBPENEmbody($player, $name);')
    if b == 'tiger_trap':add('Defended','FaBPENTigerTrap(intval(FaBGetState()["attacker"]));')
    if b == 'walk_in_my_shoes':add('Hit',hit('if (intval(FaBGetState()["damageDealt"]) >= 4) { FaBPENShoes($victim); }'))
    if b in ['lunar_mirage','doubling_season','line_crossers']:handled=True
    if b == 'stormweavers_aegis':add('ResolveAbility',"FaBWTRAddEffect($player, 'PEN_STORMWEAVER', 1);")
    if b == 'cheating_scoundrel':
        add('ResolveCard',"FaBWTRAddEffect($player, 'PEN_CHEAT_NEXT', 1); FaBWTRAddEffect($player, 'PEN_CHEAT_LOSS', 1);")
        add('ResolveAbility','$uid = intval(DecisionQueueController::GetVariable("penWagerUID")); $wagers = (array)DecisionQueueController::GetVariable("penWagers"); $attacker = intval(DecisionQueueController::GetVariable("penWagerAttacker")); for ($i = 0; $i < count($wagers); $i = $i + 1) { $wager = $wagers[$i]; $winner = intval($wager["winner"]); $loser = $winner === $attacker ? intval($wager["victim"]) : $attacker; if (FaBPENConsumeCheat($loser)) { '+choose(refs('Hand','$loser'),'Discard_to_win_wager',True,'$loser')+'if ($chosen !== "-") { FaBDiscardChoice($loser, $chosen); $winner = $loser; } } FaBPENWagerPrize($attacker, $uid, $winner, $wager); }')
    if b in ['smoldering_scales','smoldering_steel']:
        add('ResolveAbility','$number = intval(DecisionQueueController::GetVariable("penFrostNumber")); $creator = intval(DecisionQueueController::GetVariable("penFrostCreator")); $action = boolval(DecisionQueueController::GetVariable("penFrostAction")); $wager = boolval(DecisionQueueController::GetVariable("penFrostWager")); '+choose('FaBPENFrostReplacements($player)','Replace_Frostbite_creation')+'FaBPENFinishFrost($player, $chosen, $number, $creator, $action, $wager);')
    if handled:
        if 'Watery Grave' in c['functional_text_plain']:
            add('ResolveAbility',"if (DecisionQueueController::GetVariable('rosEvent') === 'seaWatery') { FaBSEAFaceDown($mzID); }")
        out.append(dict(cardId=id, abilities=a))
    else:
        pending.append(dict(cardId=id, text=c['functional_text_plain']))

if pending or len(out) != 348:
    (HERE/'pen_pending.json').write_text(json.dumps(pending,indent=2)+'\n')
    raise RuntimeError(f'Incomplete PEN coverage: {len(out)}/348 identities.')
for entry in out:
    for ability in entry['abilities']:
        old = next((x for x in previous.get(entry['cardId'], {}).get('abilities', []) if x['macroName'] == ability['macroName']), None)
        if old and old['abilityCode'].strip() != ability['abilityCode'].strip():
            ability['previousCodeHash'] = hashlib.sha256(old['abilityCode'].strip().encode()).hexdigest()
        elif old and 'previousCodeHash' in old:
            ability['previousCodeHash'] = old['previousCodeHash']
(HERE/'pen_abilities.json').write_text(json.dumps(out,indent=2)+'\n')
(HERE/'pen_pending.json').write_text(json.dumps(pending,indent=2)+'\n')
print(f'PEN: {len(out)}/348 identities authored; {len(pending)} pending.')
