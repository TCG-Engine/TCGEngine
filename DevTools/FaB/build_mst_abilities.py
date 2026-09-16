"""Part the Mistveil saved card-editor source; every identity must be accounted for."""
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
existing={c['cardId']:c for p in sorted(HERE.glob('*_abilities.json')) if p.name!='mst_abilities.json' for c in json.loads(p.read_text())}
old={c['cardId']:c for c in json.loads((HERE/'mst_abilities.json').read_text())} if (HERE/'mst_abilities.json').exists() else {}
out=[];pending=[]
for c in json.loads((HERE/'mst_catalog.json').read_text()):
 id=c['id'];b=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id;v=4-int(c['pitch'] or 0);txt=c['functional_text_plain'];a=[];handled=False
 def add(m,code):
  global handled
  handled=True;prior=next((x for x in a if x['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:out.append(existing[id]);continue
 if b in ['enigma','enigma_ledger_of_ancestry']:add('ResolveAbility','FaBMSTShield($player, 1);')
 if b in ['zen','zen_tamer_of_purpose']:add('ResolveAbility','FaBMSTTiger($player); '+choose('FaBMSTComboSearch($player)','Banish_Combo_to_play_this_turn')+'FaBMSTSearchBanish($player, $chosen);')
 if b in ['nuu','nuu_alluring_desire']:
  add('ResolveAbility',choose('FaBDYNHeroTargets($player, true, true)','Choose_opponent',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); FaBMSTNuuPermission($player, $victim); $uids = FaBHVYPeek($player, $victim, 1); if (count($uids) > 0) { $param = FaBARCOrderParam($uids, 'Top'); $order = await $player.Rearrange($param); $blue = FaBMSTPeekBlue($victim, $uids); if ($blue) { $mode = await $player.Modal(1, 1, \"Leave_on_top&Banish\", \"Choose_destination\"); if ($mode === '1') { FaBMSTBanishPeek($player, $victim, $uids); } } FaBHVYFinishPeek($player, $victim, $uids, $order); }")
 if b=='enigma_new_moon':add('ResolveAbility',choose('FaBMSTCloakedRefs($player)','Turn_equipment_face_up',False)+"$flipUID = FaBUPRUIDs($chosen)[0] ?? 0; FaBMSTFlip($flipUID); if (FaBMSTHasWard($flipUID)) { FaBMSTShield($player, 0, 3); }")
 if b=='mask_of_recurring_nightmares':add('ResolveAbility',V+choose(refs('Hand','$victim'),'Banish_from_hand',False,'$victim')+'FaBDYNBanishChoice($chosen, $victim);')
 if b=='meridian_pathway':add('ResolveAbility',UID+"$event = DecisionQueueController::GetVariable('mstEvent'); if ($event === 'pitch') { $mode = await $player.Modal(1, 1, \"Decline&Gain_Ward_3\", \"Gain_ward_until_end_of_turn\"); if ($mode === '1') { FaBTagUID($uid, 'MST_WARD3'); } FaBTryCompletePayment(); } else { FaBMSTAdd($player, 'AURA_INSTANT'); }")
 if b=='twelve_petal_kasaya':add('ResolveAbility',"$event = DecisionQueueController::GetVariable('mstEvent'); if ($event === 'transcend') { $mode = await $player.Modal(1, 1, \"Gain_resource&Decline\", \"Gain_one_resource\"); if ($mode === '0') { AddResources($player, intval(GetResources($player)) + 1); } } else { FaBWTRCreateArena($player, 'zen_state'); }")
 if b in ['aqua_laps','waves_of_aqua_marine']:add('ResolveAbility',choose('FaBMSTAttackRefs($player)','Choose_attack',False)+"FaBDYNTag($chosen, '"+('GO_AGAIN' if b=='aqua_laps' else 'WTR_POWER:1')+"');")
 if b=='aqua_seeing_shell':add('ResolveAbility','DoDrawCard($player, 1);')
 if b=='truths_retold':add('ResolveAbility',choose(refs('Graveyard',filters="['type'=>'Aura']"),'Bottom_aura',False)+'FaBHVYBottomChoice($chosen);')
 if b=='uphold_tradition':add('ResolveAbility',choose('FaBMSTAuras($player, true)','Put_power_counter_on_aura',False)+'FaBMSTCounters($chosen, 1);')
 if b in ['skybody_keikoi','skycrest_keikoi','skyhold_keikoi','skywalker_keikoi']:add('ResolveAbility',"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 1);")
 if b in ['arousing_wave','undertow_stilettos']:add('ResolveAbility',"FaBMSTEphemeral($player, '"+('fang_strike' if b=='arousing_wave' else 'slither')+"');")
 if b in ['aqua_laps','aqua_seeing_shell','waves_of_aqua_marine']:add('StartTurn',UID+"if (!FaBMSTHidden(FaBFindUID($uid)['object'])) { FaBMONDestroy($uid); }")
 if b in ['heirloom_of_rabbit_hide','koi_blessed_kimono']:
  add('StartTurn',UID+"if (intval(GetHealth($player)) === 1 && FaBMSTHidden(FaBFindUID($uid)['object'])) { $mode = await $player.Modal(1, 1, \"Remain_cloaked&Turn_face_up\", \"Reveal_equipment\"); if ($mode === '1') { FaBMSTFlip($uid); } }")
 if b=='koi_blessed_kimono':add('ResolveAbility',UID+'FaBMONDestroy($uid); '+choose('FaBMSTChiSearch($player)','Find_Inner_Chi')+'FaBMSTSearchHand($player, $chosen);')
 if b=='longdraw_half_glove':
  add('PrepareCard',UID+many('FaBHVYHandArsenal($player)',2,2,'Bottom_two_cards')+'FaBMSTBottomMany($chosen); FaBFinishPreparedCard($uid);')
  add('ResolveAbility',"FaBMSTNext($player, 4, 'arrow');")
 if b=='restless_coalescence':
  add('ResolveAbility','FaBMSTShield($player);')
  add('ResolveCard',UID+"$again = !FaBARCCard($uid, 'mstRestlessMoved'); FaBARCSetCard($uid, 'mstRestlessMoved', true); while ($again) { "+choose('FaBMSTCounterRefsExcept($player, $uid)','Move_power_counters_from_aura')+"if ($chosen === '-') { $again = false; } else { $maximum = FaBMSTCounterAmount($chosen); $n = await $player.NumberChoose(1, $maximum, \"Move_counters\"); FaBMSTRemoveCounters($player, $uid, $chosen, intval($n), true); } }")
 if b=='10000_year_reunion':add('PrepareCard',UID+"$total = FaBMSTTotalCounters($player); if ($total >= 3) { $mode = '1'; if (FaBAvailablePitch($player) >= intval(FaBGetState()['pendingPayment']['cost'])) { $mode = await $player.Modal(1, 1, \"Pay_resources&Remove_three_power_counters\", \"Choose_cost\"); } if ($mode === '1') { $remaining = 3; while ($remaining > 0) { "+choose('FaBMSTCounterRefs($player)','Remove_power_counters',False)+"$maximum = min($remaining, FaBMSTCounterAmount($chosen)); $n = await $player.NumberChoose(1, $maximum, \"Remove_counters\"); FaBMSTRemoveCounters($player, $uid, $chosen, intval($n)); $remaining = $remaining - intval($n); } FaBMSTReplaceCost($uid); } } FaBFinishPreparedCard($uid);")
 if b in ['astral_etchings','spectral_manifestations']:
  add('ResolveCard',choose('FaBMSTAuras($player, true)','Put_power_counters_on_aura',False)+f'FaBMSTCounters($chosen, {v});' if b=='astral_etchings' else f'FaBMSTShield($player, count(FaBMSTIllusionAuras($player)) === 0 ? {v} : 0);')
 if b=='sigil_of_solitude':add('StartTurn',UID+'if (count(FaBMSTIllusionAuras($player, $uid)) > 0) { FaBMONDestroy($uid); }')
 if b=='mistcloak_gully':add('EndTurn',"$uid = intval(FaBIdentityFromMZ((string)DecisionQueueController::GetVariable('mzID'))['object']->UniqueID);"+"$all = FaBMSTBlue($player) > 0 && FaBMSTCount($player, 'BLUE_PLAYED') > 0 && FaBMSTCount($player, 'BLUE_DEFENDED') > 0; if ($all) { FaBMSTTranscend($player, $uid); } else { if (FaBMSTBlue($player) + FaBMSTCount($player, 'BLUE_PLAYED') + FaBMSTCount($player, 'BLUE_DEFENDED') === 0) { FaBMONDestroy($uid); } }")
 if b=='dense_blue_mist':add('ResolveCard',UID+"FaBMSTAdd($player, 'DENSE_MIST'); if (FaBARCCard($uid, 'mstChiPitched')) { FaBMSTAdd($player, 'NO_HIT'); }")
 if b=='moon_chakra':add('ResolveCard',f"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', {v} + (FaBMSTCount($player, 'TRANSCENDED') ? 2 : 0));")
 if b in ['fang_strike','slither','wide_blue_yonder','hiss','venomous_bite','tide_chakra']:
  kind='hybrid' if b in ['hiss','venomous_bite','tide_chakra'] else 'aa' if b in ['fang_strike','slither'] else ''
  power='FaBMSTBlue($player)' if b=='wide_blue_yonder' else f"{v} + (FaBMSTCount($player, 'TRANSCENDED') ? 2 : 0)" if b=='tide_chakra' else str(v) if b in ['hiss','venomous_bite'] else '1'
  add('ResolveCard',choose(f"FaBMSTAttackRefs($player, '{kind}')",'Choose_attack',False)+("FaBDYNTag($chosen, 'GO_AGAIN');" if b=='slither' else f"FaBDYNTag($chosen, 'WTR_POWER:' . ({power}));")+("if (FaBMSTBlue($player)) { FaBMSTEphemeral($player, '"+('slither' if b=='hiss' else 'fang_strike')+"'); }" if b in ['hiss','venomous_bite'] else ''))
 if b in ['wind_chakra','tiger_form_incantation']:
  add('ResolveCard',f"FaBMSTNext($player, {v}"+(" + (FaBMSTCount($player, 'TRANSCENDED') ? 2 : 0)" if b=='wind_chakra' else '')+", 'tiger');"+("if (FaBMSTBlue($player)) { FaBMSTTiger($player); }" if b=='tiger_form_incantation' else ''))
 if b.startswith('first_tenet_of_chi_'):add('ResolveCard',{'first_tenet_of_chi_moon':"FaBMSTNext($player, 0, 'blue', 'MST_DRAW_ATTACK');",'first_tenet_of_chi_tide':"FaBMSTNext($player, 2, 'blue');",'first_tenet_of_chi_wind':"FaBMSTAdd($player, 'BLUE_GO');"}[b])
 if b=='prismatic_leyline':add('ResolveCard',"FaBMSTNext($player, 1, 'red'); FaBMSTNext($player, 2, 'yellow'); FaBMSTNext($player, 3, 'blue');")
 if b=='beckoning_mistblade':add('Hit',hit("FaBMSTNext($player, 1, 'blue', 'GO_AGAIN');"))
 if b=='tiger_taming_khakkara':add('AttackDeclared',"FaBMSTNext($player, 1, 'tiger', '', true);")
 if b in ['water_the_seeds','untamed']:add('AttackDeclared',"FaBMSTNext($player, 1, '"+('small' if b=='water_the_seeds' else 'tiger')+"', '', true);")
 if b=='biting_breeze':add('Hit',hit('FaBMSTTiger($player, false);'))
 if b in ['companion_of_the_claw','harmony_of_the_hunt']:add('AttackDeclared','if (FaBMSTBlue($player)) { FaBMSTTiger($player); }')
 if b in ['breed_anger','chase_the_tail'] or b.startswith('aspect_of_tiger_'):
  test="FaBMSTPreviousTiger($uid)" if b in ['breed_anger','chase_the_tail'] else "FaBARCCard($uid, 'mstPreviousAA') && intval(FaBARCCard($uid, 'mstPreviousPitch')) === "+str({'aspect_of_tiger_body':1,'aspect_of_tiger_mind':3,'aspect_of_tiger_soul':2}[b])
  add('AttackDeclared',UID+f"if ({test}) {{ FaBTagUID($uid, 'GO_AGAIN'); "+(f"FaBMSTNext($player, {v}, 'tiger', '', true);" if b=='chase_the_tail' else 'FaBMSTTiger($player, false);')+' }')
 if b=='tooth_and_claw':add('AttackDeclared',UID+many(refs('Hand',filters="['base'=>'crouching_tiger']"),99,0,'Reveal_Crouching_Tigers')+"$n = count(FaBUPRUIDs($chosen)); FaBRevealChoices($player, $chosen); if ($n >= 1) { FaBTagUID($uid, 'GO_AGAIN'); } if ($n >= 2) { FaBTagUID($uid, 'WTR_POWER:1'); } if ($n >= 3) { DoDrawCard($player, 1); }")
 if b=='shifting_winds_of_the_mystic_beast':
  add('ResolveCard',UID+"FaBMSTAdd($player, 'NAME_TIGERS'); if (FaBARCCard($uid, 'mstChiPitched')) { FaBMSTTiger($player, true, 2); }")
  add('ResolveAbility',UID+'$preview = ""; $name = await $player.NameCard($preview, "Choose_card_name"); FaBOUTNameCard($uid, $name);')
 if b=='levels_of_enlightenment':add('AttackDeclared',UID+"$n = min(3, FaBMSTBlue($player)); if ($n > 0) { $modes = await $player.Modal($n, $n, \"Draw_a_card&Gain_two_power&Gain_go_again\", \"Choose_modes\"); FaBMSTLevels($player, $uid, $modes); }")
 if b in ['emissary_of_moon','emissary_of_tides','emissary_of_wind']:add('AttackDeclared',UID+choose(refs('Hand'),'Bottom_a_card_for_bonus')+"if ($chosen !== '-') { FaBHVYBottomChoice($chosen); "+{'emissary_of_moon':'DoDrawCard($player, 1);','emissary_of_tides':"FaBTagUID($uid, 'WTR_POWER:2');",'emissary_of_wind':"FaBTagUID($uid, 'GO_AGAIN');"}[b]+' }')
 if b in ['desires_of_flesh','impulsive_desire','minds_desire','art_of_desire_body','art_of_desire_mind','art_of_desire_soul','double_trouble','bonds_of_attraction','bonds_of_memory','persuasive_prognosis']:
  body=UID+f"$color = FaBMSTBanishTop($player, $uid, $victim, {2 if b=='double_trouble' else 1});"
  if b in ['bonds_of_attraction','bonds_of_memory']:body+=choose(refs('Graveyard','$victim'),'Banish_from_graveyard',False)+'FaBMSTBanish($player, $uid, $chosen);'
  if b=='persuasive_prognosis':body+="$private = FaBMSTPrivateColor($player, $victim, $color); "+choose("$private['refs']",'Banish_matching_color',False)+"FaBMSTBanish($player, $uid, $chosen); FaBHVYClearPreviews(FaBEVRUIDRefs($private['uids']));"
  add('Hit',hit(body))
 if b=='gravekeeping':add('AttackDeclared',hit(choose(refs('Graveyard','$victim'),'Banish_opponent_graveyard_card')+'FaBDYNBanishChoice($chosen, $player);'))
 if b=='blanch':add('Hit',hit("FaBMSTBlanch($player, $victim);"))
 if b=='gorgons_gaze':add('ResolveCard',UID+"FaBMSTEphemeral($player, 'slither'); FaBMSTGorgon($player, $uid);")
 if b in ['just_a_nick','maul']:
  first='Increase_small_attack_power';second='Add_stealth_banish_hit' if b=='just_a_nick' else 'Add_Tiger_creation_hit'
  add('ResolveCard',f'$modes = await $player.Modal(1, 2, "{first}&{second}", "Choose_one_or_both"); if (strpos("," . $modes . ",", ",0,") !== false) {{ '+choose("FaBMSTAttackRefs($player, 'small')",'Choose_small_attack',False)+f"FaBDYNTag($chosen, 'WTR_POWER:{5 if b=='just_a_nick' else 3}');"+' } if (strpos("," . $modes . ",", ",1,") !== false) { '+choose("FaBMSTAttackRefs($player, '"+('stealth' if b=='just_a_nick' else 'tiger')+"')",'Choose_attack',False)+"FaBDYNTag($chosen, '"+('MST_BANISH_HIT' if b=='just_a_nick' else 'MST_TIGERS_HIT')+"'); }")
 if b=='sirens_call':add('ResolveCard',V+"$private = FaBMSTPrivateColor($player, $victim, 3); "+choose("$private['refs']",'Add_blue_card_as_defender',False)+"if (FaBMSTAddDefense($player, $victim, $chosen)) { DoDrawCard($player, 1); } FaBHVYClearPreviews(FaBEVRUIDRefs($private['uids']));")
 if b=='intimate_inducement':add('ResolveCard',choose("FaBMSTAttackRefs($player, 'hybrid')",'Choose_attack',False)+"FaBDYNTag($chosen, 'WTR_POWER:1'); "+V+"$uids = FaBHVYPeek($player, $victim, 4); "+choose('FaBEVRUIDRefs($uids)','Choose_card_to_defend',False)+"FaBMSTAddDefense($player, $victim, $chosen, true); $remaining = FaBMSTRemainingTemp($uids); if (count($remaining) > 0) { $param = FaBARCOrderParam($remaining, 'Top'); $order = await $player.Rearrange($param); FaBHVYFinishPeek($player, $victim, $remaining, $order); }")
 if b=='rowdy_locals':add('Hit',hit(choose(refs('Hand'),'Discard_a_card')+"if ($chosen !== '-') { FaBDiscardChoice($player, $chosen); "+choose(refs('Hand','$victim'),'Discard_a_card',False,'$victim')+'FaBDiscardChoice($victim, $chosen); }'))
 if b=='unravel_aggression':add('ResolveCard',UID+"if (FaBARCCard($uid, 'mstChiPitched')) { DoDrawCard($player, 1); }")
 if b=='orihon_of_mystic_tenets':add('ResolveCard',UID+"DoDrawCard($player, FaBARCCard($uid, 'mstChiPitched') ? 3 : 2);")
 if b in ['stride_of_reprisal','traverse_the_universe','mask_of_wizened_whiskers','stonewall_gauntlet']:
  body={'stride_of_reprisal':'FaBMSTTiger($player);','traverse_the_universe':choose('FaBMSTChiSearch($player)','Find_Inner_Chi')+'FaBMSTSearchHand($player, $chosen);','mask_of_wizened_whiskers':choose(refs('Graveyard',filters="['keyword'=>'Combo']"),'Bottom_Combo',False)+'FaBHVYBottomChoice($chosen);','stonewall_gauntlet':"if (FaBHVYAbove(intval(FaBGetState()['attackUID']))) { FaBMSTAdd($player, 'STONEWALL'); }"}[b];add('Defended',body)
 if b in ['a_drop_in_the_ocean','homage_to_ancestors','pass_over','path_well_traveled','preserve_tradition','rising_sun_setting_moon','stir_the_pot','the_grain_that_tips_the_scale']:
  body=''
  if b in ['a_drop_in_the_ocean','path_well_traveled','the_grain_that_tips_the_scale']:body=choose("FaBMSTAttackRefs($player, '', false)",'Choose_attack',False)+"FaBDYNTag($chosen, '"+{'a_drop_in_the_ocean':'WTR_POWER:-1','path_well_traveled':'GO_AGAIN','the_grain_that_tips_the_scale':'WTR_POWER:1'}[b]+"');"
  if b=='homage_to_ancestors':body='FaBCRUGainLife($player, 1);'
  if b=='pass_over':body=choose('FaBMSTOpponentGraves($player)','Banish_opponent_graveyard_card',False)+'FaBDYNBanishChoice($chosen, $player);'
  if b=='preserve_tradition':body=choose(refs('Graveyard',filters="['type'=>'Action']"),'Bottom_action',False)+'FaBHVYBottomChoice($chosen);'
  if b=='rising_sun_setting_moon':body='DoDrawCard($player, 1); '+choose(refs('Hand'),'Bottom_a_card',False)+'FaBHVYBottomChoice($chosen);'
  if b=='stir_the_pot':body='FaBShuffleDeck($player);'
  add('ResolveCard',UID+body+'if (FaBMSTOtherBlue($player, $uid)) { FaBMSTTranscend($player, $uid); }')
 if b.startswith('sacred_art_'):
  modes={'sacred_art_immortal_lunar_shrine':('Create_two_Shields&Add_power_counters&Transcend','FaBMSTShield($player, 0, 2);',"FaBMSTAllWardCounters($player);"),'sacred_art_jade_tiger_domain':('Create_two_Tigers&Empower_Tigers&Transcend','FaBMSTTiger($player, true, 2);',"FaBMSTAdd($player, 'TIGER_POWER');"),'sacred_art_undercurrent_desires':('Create_reactions&Banish_graveyard_cards&Transcend',"FaBMSTEphemeral($player, 'fang_strike'); FaBMSTEphemeral($player, 'slither');",choose('FaBDYNHeroTargets($player, true, true)','Choose_opponent',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); "+many(refs('Graveyard','$victim'),2,0,'Banish_up_to_two')+'FaBMSTBanishMany($player, $chosen);')}[b]
  add('ResolveCard',UID+'$n = FaBMSTOtherBlue($player, $uid) ? 3 : 1; '+f'$modes = await $player.Modal($n, $n, "{modes[0]}", "Choose_modes"); '+''.join(f'if (strpos("," . $modes . ",", ",{i},") !== false) {{ {body} }}' for i,body in enumerate([modes[1],modes[2],'FaBMSTTranscend($player, $uid);'])))
 if b in ['cosmo_scroll_of_ancestral_tapestry','manifestation_of_miragai','waxing_specter','single_minded_determination','rage_specter','solitary_companion','haunting_specter','waning_vengeance','vengeful_apparition','essence_of_ancestry_body','essence_of_ancestry_mind','essence_of_ancestry_soul','three_visits','haze_shelter','deep_blue_sea','droplet','rising_tide','spillover','tidal_surge','second_tenet_of_chi_tide','second_tenet_of_chi_moon','second_tenet_of_chi_wind','cosmic_awakening','big_blue_sky','territorial_domain','wash_away','pick_to_pieces','evasive_leap','inner_chi','heirloom_of_snake_hide','heirloom_of_tiger_hide','dust_from_stillwater_shrine']:handled=True

 if b=='attune_with_cosmic_vibrations':
  add('AttackDeclared',hit(UID+'FaBMSTAttune($player, $uid, $victim);'))
  add('Defended',UID+"FaBMSTAttune($player, $uid, intval(FaBGetState()['attacker']));")
 if b=='battlefront_bastion':handled=True
 if b=='bonds_of_agony':add('Hit',hit(UID+"if (intval(FaBARCCard($uid, 'mstReactions')) >= 3) { $previews = FaBDYNPrivateHand($player, $victim); "+choose('FaBEVRUIDRefs($previews)','Choose_card_name',False)+"$matches = FaBMSTAgonySearch($player, $victim, $chosen); FaBHVYClearPreviews(FaBEVRUIDRefs($previews)); "+many('$matches',3,0,'Banish_up_to_three_matching_cards')+'FaBMSTFinishAgony($player, $victim, $chosen, $matches); }'))
 if b=='fact_finding_mission':add('Hit',hit(choose('FaBMSTHiddenTargets($victim)','Look_at_hidden_card')+"$uids = FaBMSTPreview($player, $chosen); if (count($uids) > 0) { $param = FaBARCOrderParam($uids, 'Cards'); $ignored = await $player.Rearrange($param); FaBHVYClearPreviews(FaBEVRUIDRefs($uids)); }"))
 if b=='the_weakest_link':add('Hit',hit("$private = FaBMSTNoDefense($player, $victim); "+choose("$private['refs']",'Choose_card_without_defense',False)+"if (FaBMSTDiscardPrivate($victim, $chosen)) { DoDrawCard($player, 1); } FaBHVYClearPreviews(FaBEVRUIDRefs($private['uids']));"))
 if b=='eloquent_eulogy':add('CombatChainClosed',"if (FaBMSTAnyLifeLost()) { FaBWTRCreateArena($player, 'eloquence'); }")
 if b=='dust_from_stillwater_shrine':add('ResolveCard',UID+"FaBMoveUID($uid, 'Arena', $player);")
 if b=='kindle':add('ResolveCard',"FaBMSTAdd($player, 'AMP', 1); if (FaBHandCount($player) === 0) { DoDrawCard($player, 1); }")
 if b=='shadowrealm_horror':
  add('PrepareCard',UID+'FaBMSTShadowCost($player, $uid); FaBFinishPreparedCard($uid);')
  add('AttackDeclared',UID+"$n = intval(FaBARCCard($uid, 'mstSix')); if ($n >= 1) { FaBTagUID($uid, 'WTR_POWER:1'); } if ($n >= 2) { FaBTagUID($uid, 'GO_AGAIN'); } if ($n >= 3) { "+choose('FaBMSTShadowRefs($uid)','Allow_one_banished_card_to_be_played')+'FaBMSTPlayable($chosen); }')
 if b=='murky_water':
  add('AttackDeclared',UID+"if (FaBDYNAimed($uid)) { FaBTagUID($uid, 'WTR_POWER:1'); FaBTagUID($uid, 'DOMINATE'); }")
  add('Hit',hit("$traps = "+refs('Graveyard',filters="['type'=>'Trap']")+"; if (count(array_filter(explode('&', $traps))) >= 3) { $mode = await $player.Modal(1, 1, \"Decline&Banish_three_traps\", \"Put_random_trap_into_arsenal\"); if ($mode === '1') { "+many('$traps',3,3,'Banish_three_traps')+'FaBMSTMurky($player, $chosen); } }'))
 if b.startswith('evo_'):
  add('ResolveCard','FaBEVOTransform($player, $mzID);')
  if b=='evo_heartdrive':body="FaBMSTAdd($player, 'NEXT_DISCOUNT');"
  if b=='evo_speedslip':body=UID+"if (DecisionQueueController::GetVariable('mstEvent') === 'boost') { $mode = await $player.Modal(1, 1, \"Boost&Do_not_boost\", \"Banish_top_card_to_boost\"); if ($mode === '0') { FaBARCBoost($player, $uid); } FaBMSTContinuePrepare($player, $uid); } else { FaBMSTNext($player, 0, 'aa', 'MST_BOOST'); }"
  if b=='evo_recall':body=choose('FaBMSTMechActions($player)','Top_banished_Mechanologist_action')+'FaBDTDTopChoice($player, $chosen);'
  if b=='evo_shortcircuit':body=UID+choose('FaBUPRAnyTargets($player)','Deal_one_physical_damage',False)+"$targetUID = FaBUPRUIDs($chosen)[0] ?? 0; "+deal(1,physical=True)
  add('ResolveAbility',body)
 if b=='supercell':
  add('PrepareCard',UID+'$maxX = min(FaBHVYMaxX($player, $uid), count(FaBChoiceRefs($player, "Arena", ["base"=>"hyper_driver"]))); $x = await $player.NumberChoose(0, $maxX, "Choose_X"); FaBHVYSetX($uid, intval($x)); '+many('FaBMSTHyperDrivers($player)','intval($x)','intval($x)','Choose_Hyper_Drivers')+"FaBARCSetCard($uid, 'mstDrivers', FaBUPRUIDs($chosen)); FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+"$x = intval(FaBARCCard($uid, 'evoX')); $targets = FaBEVRUIDRefs((array)FaBARCCard($uid, 'mstDrivers', [])); FaBMSTSupercell($player, $x, $targets); if ($x >= 3) { "+choose(refs('Banish',filters="['base'=>'construct_nitro_mechanoid']"),'Shuffle_Construct_into_deck')+"if ($chosen !== '-') { FaBEVOShuffle($player, $chosen); } }")
 if b=='visit_goldmane_estate':add('ResolveCard',"FaBHVYToken($player, 'gold'); $n = count(FaBChoiceRefs($player, 'Arena', ['base'=>'gold'])); if ($n >= 3) { FaBHVYToken($player, 'might', $n, $player); }")
 if b=='visit_the_golden_anvil':
  add('PrepareCard',UID+many('FaBHVYGold($player)',99,0,'Destroy_Gold')+"FaBARCSetCard($uid, 'mstGold', count(FaBUPRUIDs($chosen))); FaBHVYDestroyChoices($chosen); FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+"$n = intval(FaBARCCard($uid, 'mstGold')); for ($i = 0; $i < $n; $i = $i + 1) { "+choose('FaBMSTInventory($player)','Equip_from_inventory')+'FaBMSTEquip($player, $chosen); }')
 if not handled:pending.append(id)
 out.append(dict(cardId=id,abilities=a))
if pending:print('Unhandled',pending);raise SystemExit(1)
for c in out:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],existing.get(c['cardId'],{})).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior is None:prior=next((x for x in existing.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior.get('previousCodeHash'):a['previousCodeHash']=prior['previousCodeHash']
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'mst_abilities.json').write_text(json.dumps(out,indent=2)+'\n');print('MST:',len(out),'identities;',sum(len(c['abilities']) for c in out),'macros')
