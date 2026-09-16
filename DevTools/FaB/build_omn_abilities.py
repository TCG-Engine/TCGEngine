"""Reproducible OMN authoring. Unhandled identities are never marked implemented."""
import ast
import hashlib
import json
import re
from pathlib import Path

HERE = Path(__file__).parent
for filename, names in [('build_upr_abilities.py', ['deal']), ('build_mon_abilities.py', ['clean']), ('build_arc_abilities.py', ['opt']),
                        ('build_hvy_abilities.py', ['choose', 'refs', 'token', 'hit'])]:
    source = (HERE / filename).read_text()
    for node in ast.parse(source).body:
        if isinstance(node, ast.FunctionDef) and node.name in names:
            exec(ast.get_source_segment(source, node))
UID = "$uid = FaBPENUID($mzID);"
SOURCE = '$uid = intval(DecisionQueueController::GetVariable("rosSource"));'
V = "$victim = intval(FaBGetState()['defender']);"

def arcane(amount, hero=False):
    return choose('FaBPENTargets($player, '+('true' if hero else 'false')+')', 'Choose_arcane_target', False) + '$targetUID = FaBPENUID($chosen); ' + f"$dealt = FaBUPRDeal($player, $uid, $targetUID, {amount}, 'ARCANE');"

def bottom(filter="['type'=>'Instant']", top=False):
    return choose(refs('Graveyard', filters=filter), 'Choose_card_from_graveyard') + 'FaBOMNBottom($player, $chosen, '+('true' if top else 'false')+');'

def flow(body):
    return choose('FaBOMNFlows($player)', 'Destroy_Lightning_Flow') + 'if ($chosen !== "-") { FaBMONDestroy(FaBPENUID($chosen)); '+body+' }'

cache = json.loads((HERE/'../../FaBSim/GeneratedCode/cardArrayCache.json').read_text(encoding='utf-8'))['cardArray']
catalog = [c for c in cache if any(p['set_id']=='OMN' for p in c.get('printings', []))]
(HERE/'omn_catalog.json').write_text(json.dumps(catalog, indent=2)+'\n')
existing = {}
for name in ['wtr','arc','fai','professor','cru','ira','mon','boltyn','ele','evr','upr','dyn','out','dtd','evo','hvy','mst','ros','arakni','hnt','amx','sea','mpg','sup','pen']:
    for entry in json.loads((HERE/(name+'_abilities.json')).read_text()):
        existing[entry['cardId']] = entry
previous = {e['cardId']: e for e in json.loads((HERE/'omn_abilities.json').read_text())} if (HERE/'omn_abilities.json').exists() else {}
out, pending = [], []
for c in catalog:
    id = c['id']; b = re.sub(r'_(red|yellow|blue)$','',id); txt = c['functional_text_plain']; a = []; handled = False
    def add(macro, code):
        global handled
        handled = True
        old = next((x for x in a if x['macroName']==macro), None)
        if old: old['abilityCode'] += '\n'+clean(code)
        else: a.append(dict(macroName=macro, abilityCode=clean(code), isImplemented=True))
    if id in existing:
        out.append(existing[id]); continue
    if not txt or txt == 'Go again': handled = True
    if 'Fragment' in c['card_keywords']:
        fragment = SOURCE+'FaBOMNFragment($player, $uid);'
        if b == 'shattering_flowtide': fragment += token('lightning_flow')
        if b == 'scattering_conflux': fragment += token('embodiment_of_lightning')
        if b == 'fraying_lifeforce': fragment += 'FaBCRUGainLife($player, 1);'
        if b == 'pulsing_cardia': fragment += 'AddResources($player, intval(GetResources($player)) + 1);'
        if b == 'ebbing_arcstride': fragment += "FaBTagUID($uid, 'GO_AGAIN');"
        if b == 'polarus_pulse_ray': fragment += '$victim = intval(DecisionQueueController::GetVariable("omnVictim")); $targetUID = FaBUPRHeroUID($victim); $dealt = FaBUPRDeal($player, $uid, $targetUID, 1, "ARCANE");'
        if b == 'blink_of_an_eye': fragment += choose('FaBOMNBlinkChoices($player)', 'Banish_aura_and_return_with_holo')+'FaBOMNBlink($player, $chosen);'
        if b == 'unwinding_finality': fragment += choose("FaBOMNFiltered($player, 'Graveyard', ['Instant','Lightning'])",'Put_Lightning_instant_on_top')+'FaBOMNBottom($player, $chosen, true);'
        add('ResolveAbility', 'if (DecisionQueueController::GetVariable("rosEvent") === "omnFragment") { '+fragment+' }')
    if b == 'unwinding_finality': add('Hit','DoDrawCard($player, 1);')
    if b == 'clear_conscience': add('Hit',hit('$seats = FaBLiveSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = $seats[$i]; '+choose(refs('Hand','$seat'),'Bottom_a_card',False,'$seat')+'FaBOMNBottom($seat, $chosen); '+token('ponder','$seat')+' }'))
    if b == 'conflicting_thoughts': add('AttackDeclared', opt(1))
    if b in ['astral_assault','stellar_glide']: add('AttackDeclared', UID+flow("FaBTagUID($uid, '"+('WTR_POWER:2' if b=='astral_assault' else 'GO_AGAIN')+"');"))
    if b == 'arc_ramp': add('ResolveCard',UID+f"FaBMSTAdd($player, 'AMP', {4-int(c['pitch'])});"+flow("FaBOMNResolvedGoAgain($player);"))
    if b == 'astral_strike': add('AttackDeclared',UID+'if (FaBOMNCount($player, "FLOW_DESTROYED")) { $mode = await $player.Modal(1, 1, "Draw&Power&Go_again", "Astral_Strike"); if ($mode === "0") { DoDrawCard($player, 1); } else { FaBTagUID($uid, $mode === "1" ? "WTR_POWER:2" : "GO_AGAIN"); } }')
    if b == 'flowshard_elemental': add('AttackDeclared',UID+choose(refs('Hand',filters="['type'=>'Instant']"),'Discard_instant')+'if ($chosen !== "-") { FaBDiscardChoice($player, $chosen); '+token('lightning_flow')+"FaBTagUID($uid, 'GO_AGAIN'); }")
    if b in ['flittering_spike','volatile_fluxor']:
        add('AttackPowerModifier',f'return FaBOMNInstantLink($player) ? {2 if b=="flittering_spike" else 3} : 0;')
        add('Hit', token('lightning_flow'))
    if b == 'flittering_forcefield': add('DefenseModifier', 'return FaBOMNInstantLink($player) ? 1 : 0;')
    if b == 'browbeat': add('AttackPowerModifier',"return count(FaBChoiceRefs($player, 'Hand'));")
    if b == 'arcanic_cunning': handled = True
    if b in ['stinging_sprite','rush_of_power','scorpio_comet_tail']:
        for m in (['AttackDeclared','Defended'] if b=='stinging_sprite' else ['Hit']):
            add(m, UID+(arcane(1,True) if b=='stinging_sprite' else hit('$targetUID = FaBUPRHeroUID($victim); $dealt = FaBUPRDeal($player, $uid, $targetUID, 1, "ARCANE");')))
    if b in ['flash_bolt','comet_collision','meteoric_impact','lightning_overload','nebula_duality','aethersling','enion_surge','nucleus_aetherbolt','tap_lessons_past','turn_to_mindfire']:
        n = int(re.search(r'Deal (\d+) arcane', txt)[1]); damage = str(n)
        if b in ['comet_collision','meteoric_impact']: damage = '(FaBOMNCount($player, "STARFALL") ? '+re.search(r'instead deal (\d+)',txt)[1]+' : '+str(n)+')'
        body = UID+arcane(damage,b=='flash_bolt')
        if b == 'lightning_overload': body += 'if (FaBOMNCount($player, "STARFALL")) { '+token('lightning_flow')+' }'
        if b in ['aethersling','enion_surge','nucleus_aetherbolt','tap_lessons_past','turn_to_mindfire']:
            extra = {'aethersling':"FaBOMNResolvedGoAgain($player);",'enion_surge':token('lightning_flow'),'nucleus_aetherbolt':'$uid = FaBUPRHeroUID($player); '+arcane(1),'tap_lessons_past':bottom(),'turn_to_mindfire':token('ponder')}[b]
            body += 'if ($dealt > 0 && FaBOMNHeroReady($player)) { $mode = await $player.Modal(1, 1, "Decline&Tap_hero", "Tap_hero_for_bonus"); if ($mode === "1" && FaBOMNTapHero($player)) { '+extra+' } }'
        add('ResolveCard',body)
    if b == 'cosmic_flare': add('ResolveCard','AddResources($player, intval(GetResources($player)) + '+str(txt.count('{r}'))+');')
    if b in ['constella_contemplation','constella_flowslide','cosmic_suture']:
        body = token('ponder' if b=='constella_contemplation' else 'lightning_flow') if b!='cosmic_suture' else "FaBWTRAddEffect($player, 'PREVENT_DAMAGE', "+re.search(r'next (\d+)',txt)[1]+');'
        add('ResolveCard',UID+body+'if (FaBOMNCount($player, "STARFALL")) { '+arcane(1,True)+' }')
    if b in ['starworld_warning','tome_of_quandaries']: add('ResolveCard', token('lightning_flow' if b=='starworld_warning' else 'ponder',n=2))
    if b == 'starlight_road': add('ResolveCard','$mode = await $player.Modal(1, 1, "Embodiment_of_Lightning&Lightning_Flow", "Create_token"); if ($mode === "0") { '+token('embodiment_of_lightning')+' } else { '+token('lightning_flow')+' }')
    if b == 'voltaris': add('CardPitched', token('lightning_flow'))
    if b == 'visionary_of_orbits': add('Hit', bottom())
    if b == 'ominous_excavation': add('ResolveCard',bottom()+'if ($chosen !== "-") { FaBShuffleDeck($player); } if (FaBOMNCount($player, "AURA_DESTROYED")) { '+token('ponder')+' }')
    if b == 'ominous_respite': add('ResolveCard','FaBCRUGainLife($player, FaBOMNCount($player, "AURA_DESTROYED") ? 3 : 2);')
    if b in ['auric_shards','crackle_from_afar','fleeing_starbreeze']:
        filt = 'Fragment' if b=='auric_shards' else ''
        effect = "'GO_AGAIN'" if b=='fleeing_starbreeze' else ("'WTR_POWER:'.(FaBOMNHolo(FaBFindUID($uid)['object']) ? "+str(5-int(c['pitch']))+" : 1)" if b=='auric_shards' else "'WTR_POWER:1'")
        add('ResolveAbility',SOURCE+choose(f"FaBOMNAttackRefs($player, '{filt}')",'Choose_attack')+f'if ($chosen !== "-") {{ FaBTagUID(FaBPENUID($chosen), {effect}); }}')
    if b == 'nourishing_glow': add('ResolveAbility','FaBCRUGainLife($player, 1);')
    if b in ['circular_flowtide','elliptical_conflux','nebulus_cycle','holo_shield','sigil_of_astral_flow']: handled = True
    if b == 'corrosive_space_dust': add('ResolveAbility',SOURCE+arcane(1,True))
    if b == 'flicker_reality': add('ResolveAbility',choose('FaBOMNBlinkChoices($player)', 'Banish_aura_and_return_with_holo')+'FaBOMNBlink($player, $chosen);')
    if b == 'echoflash':
        add('ResolveCard',UID+arcane(1,True))
        add('ResolveAbility','$uid = FaBUPRHeroUID($player); '+arcane(1,True))
    if b in ['dashing_flashfoot','electryn_mindmeld','prophetic_quickstep','singeing_flowstride','stunning_swipe','tempestuous_kiss','rush_of_power','destructive_fleetfoot']:
        if '+1{p}' in txt: add('AttackPowerModifier','return FaBOMNQuick($subjectObj) ? 1 : 0;')
        if 'When this attacks a hero' in txt:
            add('AttackDeclared',UID+'if (FaBOMNQuick(FaBFindUID($uid)["object"]) && FaBFaiHeroHit()) { '+V+'$targetUID = FaBUPRHeroUID($victim); $dealt = FaBUPRDeal($player, $uid, $targetUID, 1, "ARCANE"); }')
        first = {'dashing_flashfoot':token('embodiment_of_lightning'),'electryn_mindmeld':bottom(),'prophetic_quickstep':token('ponder'),'singeing_flowstride':token('lightning_flow'),
                 'tempestuous_kiss':choose(refs('Hand','$victim'),'Discard_a_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen);',
                 'stunning_swipe':choose('FaBOMNStunChoices($victim)','Tap_Lightning_hero_or_weapon',False)+'FaBPENTap($chosen);'}.get(b)
        if first: add('ResolveAbility','$victim = intval(DecisionQueueController::GetVariable("rosTarget")); '+first)
    if b in ['caress_of_the_reaper','destructive_fleetfoot','rift_breaker']:
        filt = "['type'=>'Aura']" if b=='caress_of_the_reaper' else ("['base'=>'lightning_flow']" if b=='rift_breaker' else "['type'=>'Aura','token'=>true]")
        body=choose(refs('Arena','$victim',filt),'Destroy_aura',False)+'if ($chosen !== "-") { FaBMONDestroy(FaBPENUID($chosen)); }'
        add('ResolveAbility' if b=='caress_of_the_reaper' else 'Hit',('$victim = intval(DecisionQueueController::GetVariable("rosTarget")); '+body) if b=='caress_of_the_reaper' else hit(body))
    if b in ['cosmic_duality','nebula_duality','voltbound_duality','glide_through_starlight']:
        body=UID+arcane(1,True)+token('lightning_flow') if b!='glide_through_starlight' else "FaBOMNPrevention($player, 1, 'lightning_flow');"
        add('ResolveAbility','if (DecisionQueueController::GetVariable("rosEvent") !== "omnFragment") { '+body+' }')
    if b in ['flowing_stormstrike','meteoric_rise','voltic_impact']:
        add('ResolveAbility',UID+"FaBTagUID($uid, 'WTR_POWER:1');")
        add('Hit',token('lightning_flow'))
    if b == 'path_of_same_ends':
        add('AttackDeclared',UID+hit('$targetUID = FaBUPRHeroUID($victim); $dealt = FaBUPRDeal($player, $uid, $targetUID, 1, "ARCANE"); if ($dealt > 0) { FaBTagUID($uid, "GO_AGAIN"); }'))
        add('ResolveAbility',UID+"FaBTagUID($uid, 'GO_AGAIN');")
    if b in ['boots_of_astral_sanctuary','gloves_of_astral_sanctuary','helm_of_astral_sanctuary','robe_of_astral_sanctuary','boots_of_omnis_ward','constella_tiara','laced_lightning','starflow_robes']:
        t={'constella_tiara':'ponder','laced_lightning':'embodiment_of_lightning','starflow_robes':'lightning_flow'}.get(b,'')
        add('ResolveAbility',f"FaBOMNPrevention($player, 1, '{t}');")
        if b=='boots_of_omnis_ward':add('DefenseModifier','return FaBOMNCount($player, "ARCANE_TAKEN") ? 1 : 0;')
    if b == 'calmveil_of_volthaven':add('ResolveCard',f"FaBOMNPrevention($player, {re.search(r'next (\d+)',txt)[1]}, 'lightning_flow');")
    if b == 'haven_veil': add('ResolveAbility',f"FaBOMNPrevention($player, {re.search(r'next (\d+)',txt)[1]}, '', true);")
    if b in ['constella_waves','volzar_meteor_storm']:add('ResolveAbility',"FaBMSTAdd($player, 'AMP', 1);")
    if b == 'aphrodias':add('ResolveAbility',UID+arcane(2,True))
    if b in ['aurora_emissary_of_lightning','aurora_legacy_of_tempest','zyggy','zyggy_starlight','oscilio_forked_continuum','oscilio_scion_of_the_third_age','third_eye_of_the_sphinx']:
        costref = "implode('&', FaBMONArena($player, 'ponder'))" if b=='third_eye_of_the_sphinx' else 'FaBOMNFlows($player)'
        prep=UID+choose(costref,'Choose_token_for_cost',False)+'if ($chosen !== "-") { FaBMONDestroy(FaBPENUID($chosen)); }'
        if b.startswith('zyggy'):prep+=choose('FaBOMNBlinkChoices($player)','Banish_Lightning_aura_for_cost',False)+'FaBOMNPrepareBlink($uid, $player, $chosen);'
        add('PrepareCard',prep+'FaBFinishPreparedCard($uid);')
        body=token('embodiment_of_lightning') if b.startswith('aurora') else 'DoDrawCard($player, 1);'
        if b.startswith('zyggy'):body='$stackUID = intval(DecisionQueueController::GetVariable("arcAbilityUID")); FaBOMNReturnPreparedBlink($player, $stackUID);'
        if b.startswith('oscilio'):body=choose(refs('Hand'),'Discard_card',False)+'FaBOMNDiscardPlayable($player, $chosen);'+token('ponder')
        add('ResolveAbility',body)
    if b == 'constella_uplift':add('ResolveCard',UID+choose(refs('Weapons',filters="['type'=>'Staff']"),'Untap_staff',False)+'FaBOMNReady($chosen); if (FaBOMNCount($player, "STARFALL")) { '+arcane(1,True)+' }')
    if b == 'astral_bridge':add('ResolveCard',UID+'FaBOMNMillPlayable($player); if (FaBOMNCount($player, "STARFALL")) { '+arcane(1,True)+' }')
    if b == 'core_reaction':add('ResolveAbility',SOURCE+'FaBMONDestroy($uid); '+arcane(int(re.search(r'deal (\d+)',txt)[1])))
    if b in ['fingers_of_fragmentation','ominous_aggression','flow_through','livewire_press']:
        filt='fragmented' if b=='fingers_of_fragmentation' else ('Action' if b=='ominous_aggression' else 'Lightning')
        effect="'WTR_POWER:2'" if b=='fingers_of_fragmentation' else ("'WTR_POWER:'.(FaBOMNCount($player, 'AURA_DESTROYED') ? 4 : 2)" if b=='ominous_aggression' else ("'WTR_POWER:1'" if b=='flow_through' else "'OMN_LIVEWIRE'"))
        body=choose(f"FaBOMNAttackRefs($player, '{filt}')",'Choose_attack',False)+f'if ($chosen !== "-") {{ FaBTagUID(FaBPENUID($chosen), {effect});'
        if b=='flow_through':body+="FaBTagUID(FaBPENUID($chosen), 'OMN_FLOW_HIT');"
        add('ResolveAbility' if b=='fingers_of_fragmentation' else 'ResolveCard',body+' }')
    if b == 'starfield_touch':add('ResolveAbility',choose(refs('Weapons',filters="['base'=>'aphrodias']"),'Untap_Aphrodias',False)+'FaBOMNReady($chosen);')
    if b == 'starfield_veil':add('ResolveAbility',"FaBWTRAddEffect($player, 'OMN_HOLO', 1);")
    if b == 'induce_panic':add('Defended','$mode = await $player.Modal(1, 1, "Red&Yellow&Blue", "Choose_color"); FaBOMNPanic(intval($mode) + 1);')
    if b == 'static_shelter':add('Defended','$mode = await $player.Modal(1, 1, "Decline&Pay_one", "Create_Lightning_Flow"); if ($mode === "1") { while (GetResources($player) < 1 && FaBARCPitchChoices($player) !== "") { '+choose('FaBARCPitchChoices($player)','Pitch_to_pay',False)+'FaBARCPitchForEffect($player, $chosen); } if (GetResources($player) >= 1) { AddResources($player, intval(GetResources($player)) - 1); '+token('lightning_flow')+' } }')
    if b == 'fractal_creation':add('Hit',choose(refs('Arena',filters="['type'=>'Aura']"),'Copy_aura')+'FaBOMNCopyAura($player, $chosen);')
    if b == 'a_bit_off_the_side':
        add('ResolveCard',"FaBWTRAddEffect($player, 'OMN_AXES', 1);")
        add('ResolveAbility','$victim = intval(DecisionQueueController::GetVariable("rosTarget")); '+choose(refs('Hand','$victim'),'Discard_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen);')
    if b in ['electryn_joltstep','quick_succession','mercurial_skies','leech_memory','leech_renown','leech_vitality']:
        kind='RL' if b=='electryn_joltstep' else ('RLAA' if b in ['quick_succession','mercurial_skies'] else 'AA')
        power=int(re.search(r'\+(\d+)\{p\}',txt)[1]) if b not in ['quick_succession','mercurial_skies'] else 0
        tag='GO_AGAIN' if b in ['quick_succession','mercurial_skies'] else ('OMN_'+b.removeprefix('leech_').upper() if b.startswith('leech_') else '')
        body=f"FaBOMNNext($player, '{kind}', {power}, '{tag}');"
        if b=='electryn_joltstep':body+=token('lightning_flow')
        if b=='quick_succession':body+=f"FaBOMNNext($player, 'ATTACK', 0, 'OMN_QUICK_POWER', {4-int(c['pitch'])});"
        if b=='mercurial_skies':body+="FaBOMNNext($player, 'RLAA', 0, 'OMN_MERCURIAL');"
        add('ResolveCard',body)
        if b.startswith('leech_'):
            effect=bottom("['attackAction'=>true]") if b=='leech_memory' else ('FaBCRUGainLife($player, 1);' if b=='leech_vitality' else choose("FaBOMNFiltered($victim, 'Arena', ['Aura','Token'])",'Destroy_aura_token',False)+'if ($chosen !== "-") { FaBMONDestroy(FaBPENUID($chosen)); }')
            add('ResolveAbility','$victim = intval(DecisionQueueController::GetVariable("rosTarget")); '+effect)
        if b=='mercurial_skies':add('ResolveAbility',SOURCE+'$victim = intval(DecisionQueueController::GetVariable("rosTarget")); '+flow('$targetUID = FaBUPRHeroUID($victim); $dealt = FaBUPRDeal($player, $uid, $targetUID, 3, "ARCANE");'))
    if b == 'livewire_press':add('ResolveAbility',SOURCE+'$victim = intval(DecisionQueueController::GetVariable("rosTarget")); $targetUID = FaBUPRHeroUID($victim); $dealt = FaBUPRDeal($player, $uid, $targetUID, 4, "GENERIC");')
    if b == 'swift_pickup':add('AttackDeclared',UID+choose("FaBOMNFiltered($player, 'Graveyard', ['Item','Shuriken'])",'Bottom_shuriken')+'if ($chosen !== "-") { FaBOMNBottom($player, $chosen); FaBTagUID($uid, "WTR_POWER:1"); }')
    if b == 'gear_turner':add('Hit',choose("FaBStageSearch($player, ['type'=>'Cog'])",'Find_cog')+"FaBMoveChoice($player, $chosen, 'Temp', 'Arena'); FaBFinishSearch($player);")
    if b == 'crash_site_salvage':
        add('PrepareCard',UID+choose("implode('&', array_merge(FaBChoiceRefs($player, 'Graveyard', ['type'=>'Item']), FaBChoiceRefs($player, 'Graveyard', ['type'=>'Equipment'])))",'Scrap_item_or_equipment')+'FaBOMNScrap($player, $uid, $chosen); FaBFinishPreparedCard($uid);')
        add('AttackDeclared',UID+'if (FaBARCCard($uid, "evoScrap")) { FaBTagUID($uid, "GO_AGAIN"); if (FaBARCCard($uid, "omnCog")) { '+token('gold')+' } }')
    if b == 'tempt_over':add('AttackDeclared',hit(choose("FaBOMNFiltered($victim, 'Arena', ['Aura','Token'])",'Steal_aura_token',False)+'FaBOMNStealAura($player, $chosen);'))
    if b == 'unmake_the_underlings':
        add('AttackDeclared',hit(choose(refs('Graveyard','$victim',"['type'=>'Ally']"),'Turn_graveyard_ally_face_down',False)+'FaBSEAFaceDown($chosen);'))
        add('Hit','if (!FaBFaiHeroHit()) { FaBMONDestroy(intval(FaBGetState()["attackTarget"]["uid"] ?? 0)); }')
    if b in ['stormshard','stormshatter','stormwhirl']:
        add('PrepareCard',UID+choose('FaBOMNFlows($player)','Destroy_Lightning_Flow_instead_of_resource_cost')+'FaBOMNStormCost($player, $uid, $chosen); FaBFinishPreparedCard($uid);')
        effect={'stormshard':'WTR_POWER:3','stormshatter':'WTR_POWER:-3','stormwhirl':'GO_AGAIN'}[b]
        add('ResolveCard',choose("FaBOMNAttackRefs($player, 'Lightning')",'Choose_Lightning_attack',False)+f"if ($chosen !== '-') {{ FaBTagUID(FaBPENUID($chosen), '{effect}'); }}")
    if b == 'snap_fingers':add('ResolveAbility','$uid = intval(FaBGetState()["attackUID"]); if (FaBOMNOwnLightningAttack($player)) { $targetUID = FaBUPRHeroUID(intval(FaBGetState()["defender"])); $dealt = FaBUPRDeal($player, $uid, $targetUID, 1, "ARCANE"); }')
    if b == 'thunderous_retort':add('ResolveAbility',SOURCE+"FaBMONDestroy($uid); FaBOMNNext($player, 'ATTACK', 0, 'GO_AGAIN');")
    if b == 'feral_instinct':add('CostModifier',"return FaBHVYCount($player, 'INTIMIDATED') ? -3 : 0;")
    if b == 'arcanic_reproach':add('ResolveAbility',SOURCE+'if (DecisionQueueController::GetVariable("rosEvent") === "omnStart") { '+choose(refs('Arena',filters="['type'=>'Aura']"),'Destroy_an_aura',False)+'if ($chosen !== "-") { FaBMONDestroy(FaBPENUID($chosen)); } } else { $victim = intval(DecisionQueueController::GetVariable("rosTarget")); '+choose(refs('Hand',filters="['type'=>'Lightning']"),'Reveal_Lightning_card')+'if ($chosen !== "-") { FaBRevealChoices($player, $chosen); $targetUID = FaBUPRHeroUID($victim); $dealt = FaBUPRDeal($player, $uid, $targetUID, 1, "ARCANE"); } }')
    if b == 'arcbane_grasp':add('ResolveCard','if (FaBEVOTransform($player, $mzID)) { '+token('spellbane_aegis')+' }')
    if b == 'beckoning_brilliance':add('AttackDeclared','FaBOMNAdd($player, "DISCOUNT:".intval(FaBGetState()["chainLink"]));')
    if b == 'blessing_of_aegis':handled=True
    if b == 'chromatic_refinement':add('ResolveAbility',SOURCE+f"FaBMONDestroy($uid); FaBWTRAddEffect($player, 'OMN_CHROMATIC:{c['pitch']}', 1);")
    if b == 'draco_fire':
        add('ResolveCard',"FaBOMNNext($player, 'DRACONIC', 2); FaBWTRAddEffect($player, 'OMN_DRACO_COST', 1);")
        add('ResolveAbility','$refs = implode("&", FaBChoiceRefs($player, "Graveyard", ["base"=>"draco_fire"])); if (count(explode("&", $refs)) >= 2) { $chosen = await $player.MZMultiChoose($refs, 0, 2, "Banish_two_Draco_Fire"); FaBOMNDraco($player, $chosen); }')
    if b == 'flowstate_embodiment':add('ResolveAbility','$mode = await $player.Modal(1, 1, "Embodiment_of_Lightning&Lightning_Flow", "Create_token"); if ($mode === "0") { '+token('embodiment_of_lightning')+' } else { '+token('lightning_flow')+' }')
    if b == 'settle_the_bill':
        add('ResolveCard','if (!FaBChoiceRefs($player, "Arsenal")) { '+choose(refs('Hand',filters="['type'=>'Arrow']"),'Put_arrow_face_up_in_arsenal')+'FaBOMNSettle($player, $chosen); }')
        add('ResolveAbility','$victim = intval(DecisionQueueController::GetVariable("rosTarget")); '+choose(refs('Arsenal','$victim'),'Destroy_arsenal_card',False)+'if ($chosen !== "-") { FaBMONDestroy(FaBPENUID($chosen)); }')
    if b == 'starfield_carapace':add('ResolveAbility',"FaBWTRAddEffect($player, 'OMN_STARFIELD', 1);")
    if b == 'step_between':add('ResolveAbility',UID+"FaBTagUID($uid, 'WTR_POWER:1'); FaBWTRAddEffect($player, 'OMN_UNPREVENTABLE', 1);")
    if b == 'plutonic_starplate':handled=True
    if b == 'lionclaw_maul':
        add('AttackPowerModifier','// The greater-than-base check uses final modified power in FaBOMNFinalPower.\nreturn 0;')
        add('Hit',hit('FaBSUPCrowd($player, false);'))
    if b == 'pile_driver':add('AttackDeclared',UID+hit('$mode = await $player.Modal(1, 1, "Decline&Wager_Gold", "Wager_with_defending_hero"); if ($mode === "1") { FaBHVYWager($player, $uid, $victim, ["gold"]); }'))
    if b in ['evasive_nageboshi','razor_ring','stun_star']:
        add('GoAgainModifier','return 1;')
        if b=='stun_star':add('Hit',hit('FaBOMNTapHero($victim);'))
        if b=='razor_ring':add('Hit',hit("FaBWTRAddEffect($victim, 'OMN_RAZOR', 1);"))
    if b == 'gauntlet_of_sword_and_sorcery':
        add('ResolveAbility','if (DecisionQueueController::GetVariable("rosEvent") === "omnAttack") { '+SOURCE+choose('FaBOMNOpposingTargets($player)','Choose_opposing_target',False)+'$targetUID = FaBPENUID($chosen); $dealt = FaBUPRDeal($player, $uid, $targetUID, 1, "ARCANE"); if ($dealt > 0) { FaBTagUID($uid, "WTR_POWER:1"); } } else { FaBOMNNext($player, "AA", 0, "OMN_GAUNTLET"); }')
    if b == 'fortitude_of_anvilheim':add('ResolveAbility',choose('FaBOMNFortitudeChoices($player)','Return_defending_action',False)+'FaBOMNReturnDefender($chosen);')
    if b == 'golden_skull':add('ResolveAbility','if (DecisionQueueController::GetVariable("rosEvent") === "seaWatery") { FaBSEAFaceDown($mzID); }')
    if b in ['omens_of_arcana','spellbane_sigil']:handled=True
    if b == 'red_lure_harpoon':add('Hit',hit('if (FaBSEACount($player, "CANNON")) { '+choose(refs('Graveyard','$victim',"['type'=>'Action','pitch'=>1]"),'Banish_red_action',False)+'FaBOMNHarpoon($player, $chosen); }'))
    if b == 'beckon_steel':
        add('ResolveCard',choose('FaBOMNSwordAttack($player)','Choose_sword_attack',False)+'if ($chosen !== "-") { FaBTagUID(FaBPENUID($chosen), "OMN_BECKON"); }')
        add('ResolveAbility',SOURCE+'if (DecisionQueueController::GetVariable("rosEvent") === "omnRepeat") { '+choose('FaBOMNAttackTargets($player)','Choose_attack_target',False)+'FaBOMNFreeSword($player, $uid, $chosen); } else { FaBOMNBeckon($player, $uid); }')
    if id not in existing:
        for ability in a:
            def damage_replace(m):
                first = m[1].split(',',3)
                args = [x.strip() for x in first[:3]+first[3].rsplit(',',1)]
                if len(args)!=5 or args[0]!='$player': raise ValueError(m[0])
                code=deal(args[3],args[2],args[1],physical=args[4].strip("'\"")!='ARCANE')
                code=code.replace("$cost = FaBUPRBarrierValue($barrier);", "$cost = FaBUPRBarrierValue($barrier); if (FaBOMNVariableBarrier($barrier)) { $maxX = min($packet, FaBAvailablePitch($victim)); $x = await $victim.NumberChoose(1, $maxX, \"Choose_arcane_barrier_X\"); $cost = intval($x); }")
                if args[4].strip("'\"")=='GENERIC':code=code.replace("'PHYSICAL'", "'GENERIC'")
                return code
            ability['abilityCode']=clean(re.sub(r'\$dealt\s*=\s*FaBUPRDeal\(([^;]+)\);',damage_replace,ability['abilityCode']))
            ability['abilityCode']='\n'.join(line.strip() for line in ability['abilityCode'].splitlines() if line.strip())+'\n'
    if handled: out.append(dict(cardId=id, abilities=a))
    else: pending.append(dict(cardId=id, text=txt))

for entry in out:
    for ability in entry['abilities']:
        old = next((x for x in previous.get(entry['cardId'],{}).get('abilities',[]) if x['macroName']==ability['macroName']),None)
        if old and old['abilityCode'].strip()!=ability['abilityCode'].strip(): ability['previousCodeHash']=hashlib.sha256(old['abilityCode'].strip().encode()).hexdigest()
        elif old and 'previousCodeHash' in old: ability['previousCodeHash']=old['previousCodeHash']
(HERE/'omn_abilities.json').write_text(json.dumps(out,indent=2)+'\n')
(HERE/'omn_pending.json').write_text(json.dumps(pending,indent=2)+'\n')
print(f'OMN: {len(out)}/{len(catalog)} authored, {len(pending)} pending')
if pending or len(out)!=251:
    raise RuntimeError('Incomplete OMN coverage; do not import this snapshot.')
