"""Rosetta saved card-editor source; every identity must be accounted for."""
import ast,json,hashlib,re
from pathlib import Path
HERE=Path(__file__).parent
for filename,names in [('build_mon_abilities.py',['clean']),('build_dyn_abilities.py',['pick']),('build_out_abilities.py',['pay']),('build_upr_abilities.py',['deal'])]:
 src=(HERE/filename).read_text()
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and node.name in names:exec(ast.get_source_segment(src,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
V="$victim = intval(FaBGetState()['defender']);"
def choose(expr,tip='Choose_card',may=True,p='$player',var='$chosen'):
 return f'{var} = "-"; $refs = {expr}; if ($refs !== "") {{ '+(f'{var} = await {p}.MZMultiChoose($refs, 0, 1, "{tip}");' if may else f'{var} = await {p}.MZChoose($refs, "{tip}");')+' }'
def many(expr,maximum,minimum=0,tip='Choose_cards'):
 return f'$minimum = {minimum}; $refs = {expr}; $chosen = "-"; $maximum = min({maximum}, count(array_filter(explode("&", $refs)))); if ($maximum >= $minimum && $maximum > 0) {{ $chosen = await $player.MZMultiChoose($refs, $minimum, $maximum, "{tip}"); }}'
def refs(z,p='$player',filters='[]'):return f"implode('&', FaBChoiceRefs({p}, '{z}', {filters}))"
def token(t,p='$player',n=1):return f"FaBHVYToken({p}, '{t}', {n}, $player);"
def hit(code):return 'if (FaBFaiHeroHit()) { '+V+code+' }'
original_deal=deal
def deal(*args,**kwargs):
 code=original_deal(*args,**kwargs)
 code=re.sub(r'MZMayChoose\((\$\w+), ',r'MZMultiChoose(\1, 0, 1, ',code)
 return code.replace("=== 'PASS'","=== '-'")
existing={}
for name in ['wtr','arc','cru','mon','ele','evr','upr','dyn','out','dtd','evo','hvy','mst']:
 for entry in json.loads((HERE/(name+'_abilities.json')).read_text()):
  previous=existing.get(entry['cardId'],{'abilities':[]})
  merged={a['macroName']:a for a in previous['abilities']}
  merged.update({a['macroName']:a for a in entry['abilities']})
  existing[entry['cardId']]={'cardId':entry['cardId'],'abilities':list(merged.values())}
# The DTD revision of Runechant includes Vynnset's prevention timing.
for entry in json.loads((HERE/'dtd_abilities.json').read_text()):
 if entry['cardId']=='runechant':
  for ability in entry['abilities']:
   existing['runechant']['abilities']=[a for a in existing['runechant']['abilities'] if a['macroName']!=ability['macroName']]+[ability]
old={c['cardId']:c for c in json.loads((HERE/'ros_abilities.json').read_text())} if (HERE/'ros_abilities.json').exists() else {}
out=[];pending=[]
def damage(n,hero=False,prepare=True):
 prep=choose('FaBUPRAnyTargets($player, '+('true' if hero else 'false')+')','Choose_damage_target',False)+"FaBUPRStoreTarget($uid, $chosen);"
 return prep,UID+"$targetUID = intval(FaBARCCard($uid, 'uprTargetUID')); "+deal(n)
def decompose(body):
 return UID+"if (FaBROSDecomposeReady($player)) { $mode = await $player.Modal(1, 1, \"Decline&Decompose\", \"Banish_two_Earth_and_an_action\"); if ($mode === '1') { "+many("FaBROSDecomposeEarth($player)",2,2,'Choose_two_Earth_cards')+"$earth = $chosen; "+choose('FaBROSActionExcept($player, $earth)','Choose_another_action',False)+"if (FaBROSDecompose($player, $earth, $chosen)) { "+body+' } } }'
def all_bottom(zone):
 return "$seats = FaBLiveSeats(); for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = $seats[$i]; "+choose(refs(zone,'$seat'),'Bottom_a_card',False,'$seat')+'FaBUPRBottom($chosen); }'
for c in json.loads((HERE/'ros_catalog.json').read_text()):
 id=c['id'];b=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id;v=4-int(c['pitch'] or 0);txt=c['functional_text_plain'];a=[];handled=False
 def add(m,code):
  global handled
  handled=True;prior=next((x for x in a if x['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:out.append(existing[id]);continue
 native=['florian','florian_rotwood_harbinger','star_fall','rotwood_reaper','arcanite_fortress','helm_of_lignum_vitae','fluttersteps','flittering_charge','arcanic_spike','hit_the_high_notes','runerager_swarm','vantage_point','strength_of_four_seasons','arcane_cussing','malefic_incantation','earths_embrace','harvest_season','strong_yield','dust_from_the_fertile_fields','cracked_bauble','sanctuary_of_aria']
 if b in native or not txt or txt in ['Go again','Arcane Barrier 1\nSpellvoid 1']:handled=True
 if b in ['aurora','aurora_shooting_star']:add('ResolveAbility',token('embodiment_of_lightning'))
 if b in ['verdance','verdance_thorn_of_the_rose']:add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); "+choose('FaBROSAnyOpposing($player)','Deal_one_arcane_damage')+"if ($chosen !== '-') { $targetUID = FaBUPRUIDs($chosen)[0]; "+deal(1)+' }')
 if b in ['oscilio','oscilio_constella_intelligence','bloodtorn_bodice']:
  expr="FaBROSRefs($player, 'Hand', 'Instant')" if b.startswith('oscilio') else "FaBROSRefs($player, 'Arena', 'Aura')"
  add('PrepareCard',UID+choose(expr,'Pay_additional_cost',False)+( "FaBDiscardChoice($player, $chosen);" if b.startswith('oscilio') else "FaBHVYDestroyChoices($chosen);")+"FaBFinishPreparedCard($uid);")
  add('ResolveAbility','DoDrawCard($player, 1);' if b.startswith('oscilio') else 'AddResources($player, intval(GetResources($player)) + 1);')
 if b in ['aether_bindings_of_the_third_age','hold_focus','runehold_release','calming_cloak','calming_gesture','lightning_greaves','ink_lined_cloak','volzar_the_lightning_rod','staff_of_verdant_shoots']:
  add('ResolveAbility',{'aether_bindings_of_the_third_age':"FaBROSAdd($player, 'BINDINGS');",'hold_focus':"FaBMSTAdd($player, 'AMP');",'runehold_release':"FaBARCCreateRunes($player, 1);",'calming_cloak':"FaBROSAdd($player, 'AURA_DISCOUNT');",'calming_gesture':"FaBMSTShield($player);",'lightning_greaves':"FaBROSAdd($player, 'INSTANT_GO');",'ink_lined_cloak':'AddResources($player, intval(GetResources($player)) + 1);','volzar_the_lightning_rod':"FaBMSTAdd($player, 'AMP', FaBROSCount($player, 'LIGHTNING'));",'staff_of_verdant_shoots':"FaBMSTAdd($player, 'AMP'); if (FaBARCCard(intval(DecisionQueueController::GetVariable('arcAbilityUID')), 'rosEarthPitch')) { FaBROSAdd($player, 'STAFF_EARTH'); }"}[b])
 if b in ['bruised_leather','four_finger_gloves','hood_of_second_thoughts','twinkle_toes','well_grounded']:add('ResolveAbility',f"FaBROSAdd($player, 'PREVENT', {2 if b in ['twinkle_toes','well_grounded'] else 1});")
 if b in ['arcane_twining','photon_splicing','chorus_of_the_amphitheater','fruits_of_the_forest','haunting_rendition','mental_block','trip_the_light_fantastic']:
  code="FaBMSTAdd($player, 'AMP');" if b in ['arcane_twining','photon_splicing'] else "FaBROSAdd($player, 'CHORUS');" if b=='chorus_of_the_amphitheater' else f'FaBCRUGainLife($player, 2);' if b=='fruits_of_the_forest' else "FaBROSAdd($player, 'PREVENT', 2, ['token'=>'"+({'haunting_rendition':'runechant','mental_block':'ponder'}.get(b,''))+"']);"
  add('ResolveAbility',code)
 if b in ['exploding_aether','high_voltage']:add('ResolveCard',f"FaBMSTAdd($player, 'AMP', {v if b=='exploding_aether' else 1});")
 if b=='will_of_arcana':add('CardPitched',"FaBMSTAdd($player, 'AMP');")
 if b in ['aether_quickening','arcane_twining','photon_splicing','chorus_of_the_amphitheater','destructive_aethertide','etchings_of_arcana','eternal_inferno','glyph_overlay','open_the_flood_gates','overflow_the_aetherwell','perennial_aetherbloom','pop_the_bubble','trailblazing_aether']:
  n=int(re.search(r'Deal (?:X\+)?(\d+) arcane',txt)[1]);hero='target hero' in txt
  prep,body=damage(str(n)+( "+ count(array_filter(explode('&', FaBROSSigils($player))))" if b=='glyph_overlay' else ''),hero)
  add('PrepareCard',UID+prep+'FaBFinishPreparedCard($uid);')
  surge={'aether_quickening':"FaBROSGrantGo($player, $uid);",'trailblazing_aether':"FaBROSGrantGo($player, $uid);",'eternal_inferno':"FaBROSResurge($player, $uid, 'Banish');",'perennial_aetherbloom':"FaBROSResurge($player, $uid, 'Deck');",'open_the_flood_gates':'DoDrawCard($player, 2);','overflow_the_aetherwell':'AddResources($player, intval(GetResources($player)) + 2);','glyph_overlay':"FaBCRUGainLife($player, 1); FaBROSShuffle($player, FaBROSSigils($player));",'etchings_of_arcana':choose("FaBROSSigils($player, 'Graveyard')",'Return_Sigil')+"FaBROSReturn($chosen);",'destructive_aethertide':"$target = FaBFindUID($targetUID); if ($target !== null && $target['zone'] === 'Hero') { $victim = intval($target['player']); "+choose(refs('Arsenal','$victim'),'Destroy_arsenal_card',False)+'FaBHVYDestroyChoices($chosen); }','pop_the_bubble':"$target = FaBFindUID($targetUID); if ($target !== null && $target['zone'] === 'Hero') { $victim = intval($target['player']); "+choose("FaBROSRefs($victim, 'Arena', 'Aura')",'Destroy_aura',False)+'FaBHVYDestroyChoices($chosen); }'}.get(b,'')
  threshold=int(re.search(r"more than (\d+)",txt)[1]) if surge else 0
  if surge:body+=f'if ($dealt > {threshold}) {{ '+surge+' }'
  add('ResolveCard',body)
 if 'Meld' in txt:
  first_action='Action' in c['type_text'].split(' // ')[0]
  prep=UID+'$options = FaBROSMeldOptions($player, $uid); $mode = await $player.Modal(1, 1, $options, "Choose_half_or_meld"); FaBROSMeldChoose($player, $uid, $mode);'
  if b.endswith('_shock'):prep+="if (FaBARCCard($uid, 'rosMeld') !== 0) { "+choose('FaBUPRAnyTargets($player)','Choose_Shock_target',False)+"FaBARCSetCard($uid, 'rosShock', FaBUPRUIDs($chosen)[0] ?? 0); }"
  if b in ['pulsing_aether_life','comet_storm_shock']:prep+="if (FaBARCCard($uid, 'rosMeld') !== 1) { "+choose('FaBUPRAnyTargets($player)','Choose_left_half_target',False)+"FaBUPRStoreTarget($uid, $chosen); }"
  if b=='null_shock':prep+="if (FaBARCCard($uid, 'rosMeld') !== 1) { "+choose('FaBROSInstantStack($uid)','Choose_instant_to_negate',False)+"FaBARCSetCard($uid, 'rosNull', FaBUPRUIDs($chosen)[0] ?? 0); }"
  add('PrepareCard',prep+'FaBFinishPreparedCard($uid);')
  right="FaBCRUGainLife($player, 1);" if b.endswith('_life') else "$targetUID = intval(FaBARCCard($uid, 'rosShock')); "+deal(1)
  left={'arcane_seeds_life':'FaBARCCreateRunes($player, 1); FaBARCCreateRunes($player, 1);','thistle_bloom_life':"FaBARCCreateRunes($player, FaBROSCount($player, 'LIFE'));",'rampant_growth_life':"FaBMSTAdd($player, 'AMP', FaBROSCount($player, 'LIFE'));",'burn_up_shock':"FaBROSAdd($player, 'BURN', 1, ['source'=>$uid]);",'regrowth_shock':choose("FaBROSRegrowth($player)",'Return_attack_action',False)+"FaBROSReturn($chosen);",'null_shock':"FaBROSNull($player, intval(FaBARCCard($uid, 'rosNull')));",'vaporize_shock':choose("FaBROSVaporize($player, false)",'Destroy_aura_permanent')+"FaBHVYDestroyChoices($chosen);"+many("FaBROSVaporize($player, true)","FaBROSCount($player, 'ARCANE_OPP')",0,'Destroy_aura_tokens')+'FaBHVYDestroyChoices($chosen);'}.get(b)
  if b in ['pulsing_aether_life','comet_storm_shock']:left="$targetUID = intval(FaBARCCard($uid, 'uprTargetUID')); "+deal(int(re.search(r'Deal (\d+) arcane',txt)[1]))
  assert left,b
  add('ResolveCard',UID+"if (FaBARCCard($uid, 'rosSide') === 1) { "+right+"FaBROSMeldResume($uid); } else { "+left+' }')
 if b=='burn_up_shock':add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); $targetUID = FaBUPRHeroUID(intval(DecisionQueueController::GetVariable('rosTarget'))); "+deal(4))
 if b=='arc_lightning':
  add('ResolveCard',UID+"FaBROSAdd($player, 'ARC_LIGHTNING', 1, ['source'=>$uid]); FaBROSAdd($player, 'NEXT_GO');")
  add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); "+choose('FaBUPRAnyTargets($player)','Choose_arc_lightning_target',False)+"$targetUID = FaBUPRUIDs($chosen)[0] ?? 0; "+deal(1))
 if b in ['blossoming_decay','cadaverous_tilling','felling_of_the_crown','plow_under','rootbound_carapace','summers_fall']:
  body={'blossoming_decay':'FaBCRUGainLife($player, 1);','cadaverous_tilling':"FaBTagUID($uid, 'WTR_POWER:2');",'felling_of_the_crown':all_bottom('Hand'),'plow_under':all_bottom('Arsenal'),'rootbound_carapace':"FaBTagUID($uid, 'WTR_DEFENSE:1');",'summers_fall':choose('FaBROSAllAuras($player)','Bottom_target_aura')+'FaBUPRBottom($chosen);'}[b]
  add('ResolveCard' if b=='rootbound_carapace' else 'AttackDeclared',decompose(body))
 if b=='heartbeat_of_candlehold':add('ResolveCard','FaBCRUGainLife($player, 1); FaBCRUGainLife($player, 1); FaBCRUGainLife($player, 1);')
 if b=='germinate':
  add('PrepareCard',UID+'$maximum = FaBHVYMaxX($player, $uid); $x = await $player.NumberChoose(0, $maximum, "Choose_X"); FaBHVYSetX($uid, intval($x)); FaBFinishPreparedCard($uid);')
  add('ResolveCard',UID+"$x = intval(FaBARCCard($uid, 'evoX')); for ($i = 0; $i <= $x; $i = $i + 1) { $mode = await $player.Modal(1, 1, \"Runechant&Embodiment_of_Earth\", \"Create_token\"); FaBHVYToken($player, $mode === '0' ? 'runechant' : 'embodiment_of_earth'); } FaBCRUGainLife($player, $x + 1);")
 if b in ['arcane_polarity','fertile_ground','count_your_blessings']:
  expr="FaBROSCount($player, 'ARCANE_TAKEN') > 0 ? "+str(v+1)+" : 1" if b=='arcane_polarity' else f'FaBROSEarth($player) >= 4 ? {v+2} : {2}' if b=='fertile_ground' else str(v)+" + count(FaBChoiceRefs($player, 'Graveyard', ['base'=>'count_your_blessings'])) - 1"
  add('ResolveCard',f'FaBCRUGainLife($player, {expr});')
 if b=='brush_off':add('ResolveCard',f"FaBROSAdd($player, 'BRUSH', {v});")
 if b=='seeds_of_tomorrow':
  add('PrepareCard',UID+choose(refs('Arsenal'),'Bottom_arsenal_as_cost',False)+'FaBUPRBottom($chosen); FaBFinishPreparedCard($uid);')
  add('ResolveCard',"FaBROSAdd($player, 'PREVENT', 5);")
 if b in ['deadwood_dirge','condemn_to_slaughter']:
  body=choose("FaBROSRefs($player, 'Arena', 'Aura')",'Destroy_your_aura',b=='condemn_to_slaughter')+"if ($chosen !== '-') { FaBHVYDestroyChoices($chosen); "
  if b=='deadwood_dirge':body+=f'FaBARCCreateRunes($player, {v});'
  else:body+="$seats = FaBOpponents($player); for ($i = 0; $i < count($seats); $i = $i + 1) { $seat = $seats[$i]; "+choose("FaBROSRefs($seat, 'Arena', 'Aura')",'Destroy_your_aura',False,'$seat')+'FaBHVYDestroyChoices($chosen); }'
  add('ResolveCard',(f"FaBROSAdd($player, 'RUNE_NEXT', {v});" if b=='condemn_to_slaughter' else '')+body+' }')
 if b=='oath_of_the_arknight':add('ResolveCard',f"FaBROSAdd($player, 'RUNE_NEXT', {v}); FaBARCCreateRunes($player, 1);")
 if b=='machinations_of_dominion':add('ResolveCard',"FaBROSAdd($player, 'DOMINION');")
 if b=='electrostatic_discharge':add('ResolveCard',f"FaBROSAdd($player, 'DISCHARGE', {v}, ['cost'=>1]);")
 if b=='unsheathed':add('ResolveCard',f"FaBROSAdd($player, 'UNSHEATHED', {v});")
 if b=='arcane_cussing':add('ResolveAbility',f'FaBARCCreateRunes($player, {v});')
 if b=='hocus_pocus':add('AttackDeclared','FaBARCCreateRunes($player, 1);')
 if b in ['earth_form','lightning_form']:add('Hit',"FaBHVYToken($player, 'embodiment_of_"+('earth' if b=='earth_form' else 'lightning')+"');")
 if b=='current_funnel':add('AttackDeclared',UID+"if (FaBARCCard($uid, 'rosLastLightning')) { FaBTagUID($uid, 'GO_AGAIN'); FaBROSAdd($player, 'NEXT_GO'); }")
 if b=='second_strike':add('AttackDeclared',UID+"if (FaBROSCount($player, 'DAMAGE')) { FaBTagUID($uid, 'WTR_POWER:1'); FaBTagUID($uid, 'GO_AGAIN'); }")
 if b=='eclectic_magnetism':add('AttackDeclared',"FaBROSAdd($player, 'MAGNETISM');")
 if b in ['gone_in_a_flash','blast_to_oblivion']:
  add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); "+choose('FaBDTDSource($uid)' if b=='gone_in_a_flash' else 'FaBROSAllAuras($player, true, 1)','Return_to_owners_hand')+'FaBROSReturn($chosen);')
 if b=='electromagnetic_somersault':add('ResolveCard',many(f'FaBROSChainAttacks({3-v})',2,0,'Return_when_chain_link_resolves')+"FaBROSReturnLater($chosen);")
 if b in ['consuming_volition','snuff_out','cut_through_the_facade','splintering_deadwood']:
  body=UID
  if b=='consuming_volition':body+="if (FaBROSCount($player, 'ARCANE')) { "+choose(refs('Hand','$victim'),'Discard_a_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen); }'
  else:
   expr="FaBROSRefs($victim, 'Arena', 'Aura')" if b=='cut_through_the_facade' else "FaBROSRefs($player, 'Arena', 'Aura')"
   body+=choose(expr,'Destroy_aura')+"if ($chosen !== '-') { FaBHVYDestroyChoices($chosen); "+('FaBARCCreateRunes($player, 1);' if b=='splintering_deadwood' else choose(refs('Hand','$victim'),'Discard_a_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen);' if b=='snuff_out' else '')+' }'
  add('Hit',hit(body))
  if b=='splintering_deadwood':add('AttackDeclared',choose("FaBROSRefs($player, 'Arena', 'Aura')",'Destroy_aura')+"if ($chosen !== '-') { FaBHVYDestroyChoices($chosen); FaBARCCreateRunes($player, 1); }")
 if b in ['smash_up','tongue_tied','hand_behind_the_pen']:
  add('Hit',hit(choose(refs('Arsenal','$victim'),'Turn_arsenal_face_up',False)+"FaBROSFaceUp($chosen); "+choose("FaBROSArsenal($victim, '"+{'smash_up':'Attack','tongue_tied':'Instant','hand_behind_the_pen':'NAA'}[b]+"')",'Banish_matching_arsenal',False)+"FaBDYNBanishChoice($chosen, $player);"))
 if b=='splatter_skull':add('Hit',hit(choose('FaBROSIntimidated($victim)','Put_intimidated_card_in_graveyard',False)+"FaBROSReturn($chosen, 'Graveyard');"))
 if b=='gustwave_of_the_second_wind':add('AttackDeclared',UID+"if (FaBWTRBase(FaBGetState()['previousAttackCardID'] ?? '') === 'surging_strike') { FaBTagUID($uid, 'GO_AGAIN'); }")
 if b=='succumb_to_temptation':
  add('ResolveCard',"FaBROSAdd($player, 'TEMPTATION');")
  add('ResolveAbility',"$victim = intval(DecisionQueueController::GetVariable('rosTarget')); $private = FaBEVRUIDRefs(FaBDYNPrivateHand($player, $victim)); "+choose('$private','Choose_opponent_discard',False)+"FaBDYNDiscardPrivate($victim, $chosen); FaBHVYClearPreviews($private);")
 if b=='barkskin_of_the_millennium_tree':add('Defended',"if (FaBROSEarth($player) >= 4) { FaBHVYToken($player, 'embodiment_of_earth'); }")
 if b=='flash_of_brilliance':add('Defended',choose("FaBROSRefs($player, 'Hand', 'Lightning')",'Discard_Lightning')+"if ($chosen !== '-') { FaBDiscardChoice($player, $chosen); "+choose("FaBROSRefs($player, 'Arena', 'Aura')",'Return_aura',False)+'FaBROSReturn($chosen); }')
 if b=='face_purgatory':add('ResolveAbility',"$victim = intval(DecisionQueueController::GetVariable('rosTarget')); "+choose(refs('Hand','$victim'),'Discard_a_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen); DoDrawCard($player, 1);')
 if b=='ten_foot_tall_and_bulletproof':
  for m in ['AttackDeclared','Defended']:add(m,'FaBROSTenFoot($player);')
 if b.startswith('sigil_'):
  bodies={'sigil_of_brilliance':'DoDrawCard($player, 1);','sigil_of_conductivity':token('embodiment_of_lightning'),'sigil_of_cycles':choose(refs('Hand'),'Discard_a_card',False)+'FaBDiscardChoice($player, $chosen); DoDrawCard($player, 1);','sigil_of_deadwood':'FaBARCCreateRunes($player, 1);','sigil_of_earth':token('embodiment_of_earth'),'sigil_of_forethought':token('ponder'),'sigil_of_fyendal':'FaBCRUGainLife($player, 1);','sigil_of_lightning':token('embodiment_of_lightning'),'sigil_of_sanctuary':token('embodiment_of_earth'),'sigil_of_temporal_manipulation':'FaBROSTemporal($player);','sigil_of_the_arknight':'FaBROSArknight($player);'}
  if b=='sigil_of_aether':add('ResolveAbility',"$uid = intval(DecisionQueueController::GetVariable('rosSource')); "+choose('FaBUPRAnyTargets($player)','Choose_arcane_target',False)+"$targetUID = FaBUPRUIDs($chosen)[0] ?? 0; "+deal(1)+"if ($dealt > 0) { FaBMSTAdd($player, 'AMP'); }")
  elif b in bodies:add('ResolveAbility',bodies[b])
 if b in ['channel_lightning_valley','channel_the_millennium_tree']:
  add('StartTurn',UID+"$flow = FaBELEFlow($uid); "+many("FaBELESelect($player, 'Pitch', '"+('Earth' if b=='channel_the_millennium_tree' else 'Lightning')+"')",'$flow',0,'Bottom_element_cards_to_keep_Channel')+'FaBELEChannelPay($player, $uid, $chosen, $flow);')
  if b=='channel_the_millennium_tree':add('ResolveCard',"FaBMSTAdd($player, 'AMP', 3);")
 if b=='save_the_thought':add('ResolveCard',many("FaBROSRefs($player, 'Graveyard', 'NAA')",v,0,'Shuffle_non_attack_actions')+"FaBROSShuffle($player, $chosen); "+token('ponder'))
 if b=='call_to_the_grave':add('ResolveCard',choose('FaBStageSearch($player, [])','Search_for_card')+"FaBMoveChoice($player, $chosen, 'Temp', 'Graveyard'); FaBFinishSearch($player);")
 if b=='truce':add('ResolveCard',UID+choose("FaBARCHeroTargets($player, true, true)",'Choose_opponent',False)+"FaBROSSetTruce($uid, $chosen);")
 if b=='sanctuary_of_aria':add('ResolveAbility',UID+choose('FaBROSAllSources()','Choose_damage_source',False)+"FaBROSAdd($player, 'SOURCE', 1, ['source'=>FaBUPRUIDs($chosen)[0] ?? 0]); FaBROSMarkSanctuary($uid);")
 if b=='adaptive_dissolver':add('ResolveAbility',UID+"$slots = FaBROSSlots($player, $uid); if ($slots !== '') { $slot = await $player.Modal(1, 1, $slots, \"Choose_equipment_zone\"); FaBROSSlot($player, $uid, intval($slot)); }")
 if b=='drink_em_under_the_table':add('AttackDeclared',UID+"if (FaBFaiHeroHit()) { $mode = await $player.Modal(1, 1, \"Decline&Wager\", \"Wager_draw_and_discard\"); if ($mode === '1') { FaBHVYWager($player, $uid, intval(FaBGetState()['defender']), ['ROS_DRINK']); } }")
 if b=='drink_em_under_the_table':add('ResolveAbility',"$victim = intval(DecisionQueueController::GetVariable('rosTarget')); "+choose(refs('Hand','$victim'),'Discard_a_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen);')
 if b=='plan_for_the_worst':
  add('PrepareCard',UID+choose('FaBUPRAnyTargets($player, true)','Choose_hero',False)+'FaBUPRStoreTarget($uid, $chosen); FaBFinishPreparedCard($uid);')
  add('ResolveCard',UID+"$target = FaBUPRTarget($uid); $victim = intval($target['player'] ?? 0); $private = FaBROSPlan($player, $victim); "+choose('$private','Look_at_hand_and_arsenal')+"FaBHVYClearPreviews($private); "+many("FaBStageSearch($player, ['type'=>'Trap'])",3,0,'Find_up_to_three_traps')+"FaBRevealChoices($player, $chosen); FaBMoveChoices($player, $chosen, 'Temp', 'Hand'); FaBFinishSearch($player); $minimum = min(2, FaBHandCount($player)); "+many(refs('Hand'),'$minimum','$minimum','Shuffle_two_cards_into_deck')+'FaBROSShuffle($player, $chosen);')
 if not handled:pending.append(id)
 out.append(dict(cardId=id,abilities=a))
if pending:print('Unhandled',pending);raise SystemExit(1)
for c in out:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],existing.get(c['cardId'],{})).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior.get('previousCodeHash'):a['previousCodeHash']=prior['previousCodeHash']
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'ros_abilities.json').write_text(json.dumps(out,indent=2)+'\n');print('ROS:',len(out),'identities;',sum(len(c['abilities']) for c in out),'macros')
