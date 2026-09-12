"""Build reviewable ARC CardEditor bodies; unhandled cards are never marked implemented."""
import json
from pathlib import Path

HERE=Path(__file__).parent
cards=json.loads((HERE/'arc_catalog.json').read_text(encoding='utf-8'))
existing={c['cardId']:c for c in json.loads((HERE/'fai_abilities.json').read_text(encoding='utf-8'))}
snapshot=[]
pending=[]

def opt(n):
    return f'''$optUIDs = FaBARCStageTop($player, {n});
if (count($optUIDs) > 0) {{
 $orderParam = FaBARCOrderParam($optUIDs);
 $order = await $player.Rearrange($orderParam);
 FaBARCFinishOrder($player, $optUIDs, $order);
}}'''

def effect(name,n=1):
    return f"FaBWTRAddEffect($player, '{name}', {n});"

def choose_move(zone,kind='',dest='Hand',class_='',maxcost=999,may=True,top=False):
    # Searches stage matching cards into the chooser's private Temp zone.
    stage=''
    if zone=='Deck':
        stage=f"$searchRefs = FaBARCSelect($player, 'Deck', '{class_}', '{kind}', {maxcost});\n$searchUIDs = FaBARCStageRefs($player, $searchRefs);\n"
        targets="FaBARCSelect($player, 'Temp')"
    else: targets=f"FaBARCSelect($player, '{zone}', '{class_}', '{kind}', {maxcost})"
    method='MZMayChoose' if may else 'MZChoose'
    move=f"FaBMoveChoice($player, $chosen, '{'Temp' if zone=='Deck' else zone}', '{dest}');"
    if top:move="$topUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0);"
    s=stage+f'''$targets = {targets};
if ($targets !== '') {{
 $chosen = await $player.{method}($targets, "Choose_a_card");
 FaBRevealChoices($player, $chosen);
 {move}
}}'''
    if zone=='Deck':s+="\nFaBFinishSearch($player);"
    if top:s+="\nif (isset($topUID) && $topUID > 0) { FaBARCToDeck($player, $topUID, true); }"
    return s

def arcane_prepare(opposing=True,multiple=False):
    op='true' if opposing else 'false';multi='true' if multiple else 'false'
    s=f'''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$targets = FaBARCHeroTargets($player, {op}, {multi});
$chosen = await $player.MZChoose($targets, "Choose_arcane_damage_target");
$target = FaBARCTargetSeat($player, $chosen, {op}, {multi});
FaBARCSetCard($uid, 'target', $target);'''
    if multiple:s+=f'''
$targets = FaBARCHeroTargets($player, false, true);
$chosen = await $player.MZChoose($targets, "Choose_second_hero_can_be_the_same");
$target = FaBARCTargetSeat($player, $chosen, false, true);
FaBARCSetCard($uid, 'target2', $target);'''
    return s+'\nFaBFinishPreparedCard($uid);'

def damage_body():
    return '''$payment = 0;
if ($target > 0 && $damage > 0 && FaBSeatIsLive($target)) {
 $options = FaBARCBarrierOptions($target, $damage);
 if ($options !== 'Take_damage') {
  $choice = await $target.Modal(1, 1, $options, "Arcane_barrier_prevention");
  $payment = FaBARCBarrierCost($target, $damage, $choice);
  while (intval(GetResources($target)) < $payment) {
   $pitchRefs = FaBARCPitchChoices($target);
   $pitched = await $target.MZChoose($pitchRefs, "Pitch_to_pay_for_arcane_barrier");
   FaBARCPitchForEffect($target, $pitched);
  }
 }
 $dealt = FaBARCDealArcane($player, $target, $damage, $payment);
}'''

for c in cards:
    id=c['id'];base=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id
    pitch=int(c['pitch'] or 0);v=4-pitch
    abilities=[];handled=False
    def add(macro,code):
        if macro == 'Hit' and base in ['command_and_conquer','searing_shot','hamstring_shot','red_in_the_ledger']:
            code = "if (FaBFaiHeroHit()) {\n" + code + "\n}"
        if base == 'bracers_of_belief':
            code = "if (count(FaBChoiceRefs($player, 'Deck')) > 0) {\n" + code + "\n}"
        abilities.append({'macroName':macro,'abilityCode':code,'isImplemented':True})
    if id in existing:
        snapshot.append(existing[id]);continue
    if base in ['nullrune_boots','nullrune_gloves','nullrune_hood','nullrune_robe','rusted_relic','arcanite_skullcap','amplify_the_arknight','ninth_blade_of_the_blood_oath','rune_flash','cracked_bauble','sic_em_shot','life_for_a_life','vigor_rush','push_the_point']:
        handled=True
    if base in ['zap','voltic_bolt','scalding_rain','aether_flare','aether_spindle','blazing_aether','forked_lightning','lesson_in_lava','reverberate','sonic_boom']:
        handled=True
        opposing=base not in ['zap','voltic_bolt','scalding_rain','blazing_aether','forked_lightning']
        add('PrepareCard',arcane_prepare(opposing,base=='forked_lightning'))
        n={'zap':v,'voltic_bolt':v+2,'scalding_rain':v+1,'aether_flare':v,'aether_spindle':v+1}.get(base,3)
        if base=='forked_lightning':n=2
        code=f'''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$target = intval(FaBARCCard($uid, 'target'));
$damage = FaBARCArcaneAmount($player, $uid, {n}, $target);
$dealt = 0;'''
        if base=='forked_lightning':code+="\n$second = intval(FaBARCCard($uid, 'target2'));\n$packet = $damage;\nif ($second === $target) { $damage *= 2; }"
        code+='\n'+damage_body()
        if base=='forked_lightning':code+="\nif ($second !== $target) {\n $target = $second;\n $damage = $packet;\n"+damage_body()+"\n}"
        if base=='aether_flare':code+='\n'+effect('ARC_NEXT_ARCANE','$dealt')
        if base=='aether_spindle':code+='\n'+opt('$dealt')
        if base=='lesson_in_lava':code+="\nif ($dealt > 0) {\n"+choose_move('Deck',class_='Wizard',maxcost='$dealt',top=True)+"\n}"
        if base in ['reverberate','sonic_boom']:
            if base=='reverberate':code+="\nif ($dealt > 0) {\n$targets = FaBARCSelect($player, 'Hand', 'Wizard', 'NAA', $dealt);"
            else:code+="\nif ($dealt > 0) {\n$lookUIDs = FaBARCStageTop($player, 1);\n$targets = FaBARCSelect($player, 'Temp', 'Wizard', 'NAA');"
            zone='Hand' if base=='reverberate' else 'Temp'
            discount='0' if base=='reverberate' else '$dealt'
            code+=f'''
if ($targets !== '') {{
 $chosen = await $player.MZMayChoose($targets, "Banish_to_play_as_an_instant");
 FaBARCBanishInstant($player, $chosen, '{zone}', {discount});
}}'''
            if base=='sonic_boom':code+="\nFaBARCFinishOrder($player, $lookUIDs, '');"
            code+='\n}'
        add('ResolveCard', '$caster = $player;\n' + code.replace('$player', '$caster'))
    if base in ['come_to_fight','force_sight','locked_and_loaded','take_aim','oath_of_the_arknight','lead_the_charge','absorb_in_aether','read_the_runes','mordred_tide','rapid_fire','three_of_a_kind','high_octane','stir_the_aetherwinds']:
        handled=True
        effects={'come_to_fight':('ARC_NEXT_AA',v),'force_sight':('ARC_NEXT_AA',v),'locked_and_loaded':('ARC_NEXT_MECH',v),'take_aim':('ARC_NEXT_RANGER',v),'oath_of_the_arknight':('ARC_NEXT_RUNEBLADE',v),'lead_the_charge':('ARC_LEAD',pitch-1),'absorb_in_aether':('ARC_NEXT_ARCANE',2),'mordred_tide':('ARC_MORDRED',1),'rapid_fire':('ARC_RAPID',1),'three_of_a_kind':('ARC_THREE_KIND',1),'high_octane':('ARC_OCTANE',1),'stir_the_aetherwinds':('ARC_NEXT_WIZARD_INSTANT',v+1)}
        code=effect(*effects[base]) if base in effects else f'FaBARCCreateRunes($player, {v});'
        if base=='oath_of_the_arknight':code+='\nFaBARCCreateRunes($player, 1);'
        if base=='three_of_a_kind':code+='\nDoDrawCard($player, 3);'
        if base=='high_octane':code+='\nDoDrawCard($player, 1);'
        if base=='force_sight':code+="\nif (DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') {\n"+opt('2')+'\n}'
        if base=='locked_and_loaded':code+="\nif (FaBARCEffect($player, 'ARC_BOOSTED') > 0) {\n"+opt('1')+'\n}'
        if base in ['take_aim','rapid_fire']:code+='\n'+'''if (count(FaBChoiceRefs($player, 'Arsenal')) === 0) {
 $targets = FaBARCSelect($player, 'Hand');
 if ($targets !== '') {
  $chosen = await $player.MZMayChoose($targets, "Reload_a_card_face_down");
  FaBARCLoadArsenal($player, $chosen, false);
 }
}'''
        add('ResolveCard',code)
    if base in ['fate_foreseen','whisper_of_the_oracle','fervent_forerunner','talismanic_lens','eye_of_ophidia']:
        handled=True
        add('Hit' if base=='fervent_forerunner' else 'ResolveAbility' if base=='talismanic_lens' else 'CardPitched' if base=='eye_of_ophidia' else 'ResolveCard',opt({'fate_foreseen':1,'whisper_of_the_oracle':v+1,'fervent_forerunner':2,'talismanic_lens':2,'eye_of_ophidia':2}[base]) + ('\nFaBTryCompletePayment();' if base=='eye_of_ophidia' else ''))
    if base in ['spellblade_assault','spellblade_strike','drawn_to_the_dark_dimension','arknight_ascendancy','nebula_blade','reduce_to_runechant']:
        handled=True
        macro='Hit' if base in ['arknight_ascendancy','nebula_blade'] else 'ResolveCard' if base=='reduce_to_runechant' else 'AttackDeclared'
        code='DoDrawCard($player, 1);' if base=='drawn_to_the_dark_dimension' else "FaBARCCreateRunes($player, "+('$amount' if base=='arknight_ascendancy' else '2' if base=='spellblade_assault' else '1')+');'
        add(macro,code)
    if base in ['command_and_conquer','endless_arrow','salvage_shot','over_loop','searing_shot','pursuit_of_knowledge','cadaverous_contraband','pedal_to_the_metal','rifting','moon_wish']:
        handled=True
        code={'command_and_conquer':"$defender = intval(FaBGetState()['defender']);\nforeach (FaBChoiceRefs($defender, 'Arsenal') as $ref) { FaBMoveChoice($defender, $ref, 'Arsenal', 'Graveyard'); }",'endless_arrow':"FaBARCReturnAttack($player, $mzID);",'salvage_shot':"FaBARCToDeck($player, intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), false);",'over_loop':"FaBARCToDeck($player, intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), false);",'pursuit_of_knowledge':effect('INTELLECT',1),'pedal_to_the_metal':"FaBWTRAddEffect($player, 'NEXT_ATTACK', 0, ['dominate'=>true]);",'rifting':effect('ARC_NEXT_NAA_INSTANT',1),'searing_shot':"FaBARCLoseLife(intval(FaBGetState()['defender']), 1, $player);"}.get(base,'')
        if base=='cadaverous_contraband':code=choose_move('Graveyard','NAA',dest='Deck',top=True)
        if base=='moon_wish':code=choose_move('Deck',class_='',kind='SunKiss')
        add('Hit',code)
        if base in ['over_loop','pedal_to_the_metal','moon_wish']:handled=False  # preparation added below
    if base=='ravenous_rabble':
        handled=True;add('AttackDeclared',"$penalty = FaBARCRevealPitch($player);\nFaBWTRTag(FaBIdentityFromMZ($mzID)['object'], 'WTR_POWER:' . (-$penalty));")
    if base=='eirinas_prayer':
        handled=True;add('ResolveCard',f"$pitch = FaBARCRevealPitch($player);\nFaBWTRAddEffect($player, 'ARC_PREVENT', max(0, {v+3} - $pitch));")
    if base in ['throttle','zero_to_sixty','zipper_hit','over_loop','pedal_to_the_metal']:
        handled=True
        add('PrepareCard', '''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
if (count(FaBChoiceRefs($player, 'Deck')) > 0) {
 $boost = await $player.Modal(1, 1, "Boost&Do_not_boost", "Banish_top_card_to_boost");
 if ($boost === '0') { FaBARCBoost($player, $uid); }
}
FaBFinishPreparedCard($uid);''')
    if base in ['head_shot','maximum_velocity','back_alley_breakline','bloodspill_invocation','enchanting_melody','viserai','viserai_rune_blood']:
        handled=True
    if base=='ridge_rider_shot':
        handled=True;add('StartTurn',opt(1))
    if base=='take_cover':
        handled=True;add('ResolveCard', '''if (count(FaBChoiceRefs($player, 'Arsenal')) === 0) {
 $targets = FaBARCSelect($player, 'Hand');
 if ($targets !== '') {
  $chosen = await $player.MZMayChoose($targets, "Reload_a_card_face_down");
  FaBARCLoadArsenal($player, $chosen, false);
 }
}''')
    if base in ['hamstring_shot','red_in_the_ledger']:
        handled=True
        add('Hit',"$victim = intval(FaBGetState()['defender']);\nFaBWTRAddEffect($victim, '"+('ARC_FIRST_ATTACK_COST' if base=='hamstring_shot' else 'ARC_LEDGER')+"', 1, [], true);")
    if base=='plunder_run':
        handled=True;add('ResolveCard',effect('ARC_PLUNDER',1)+f"\nif (DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') {{ FaBWTRAddEffect($player, 'ARC_NEXT_AA', {v}); }}")
    if base=='sun_kiss':
        handled=True;add('ResolveCard',f"AddHealth($player, intval(GetHealth($player)) + {v});\nif (count(array_filter(FaBARCPlayed($player), fn($id) => FaBWTRBase($id) === 'moon_wish')) > 0) {{ DoDrawCard($player, 1); AddActionPoints($player, intval(GetActionPoints($player)) + 1); }}")
    if base=='tome_of_aetherwind':
        handled=True
        add('ResolveCard', '''for ($i = 0; $i < 2; ++$i) {
 $mode = await $player.Modal(1, 1, "Increase_next_arcane_damage&Draw_a_card", "Choose_a_mode_repeat_allowed");
 if ($mode === '0') { FaBWTRAddEffect($player, 'ARC_NEXT_ARCANE', 1); }
 else { DoDrawCard($player, 1); }
}''')
    if base=='tome_of_the_arknight':
        handled=True;add('ResolveCard', 'FaBARCTome($player);')
    if base=='moon_wish':
        handled=True
        add('PrepareCard', '''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$targets = FaBARCSelect($player, 'Hand');
if ($targets !== '') {
 $chosen = await $player.MZMayChoose($targets, "Put_a_card_on_top_instead_of_paying_resources");
 if ($chosen !== 'PASS' && $chosen !== '-') {
  $otherUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0);
  FaBARCToDeck($player, $otherUID, true);
  FaBARCSetPendingCost($uid, 0);
 }
}
FaBFinishPreparedCard($uid);''')
    if base in ['index','silver_the_tip']:
        handled=True
        n=v+2 if base=='index' else v+1
        code=f'$lookUIDs = FaBARCStageTop($player, {n});\n'
        if base=='index':
            code+='''$targets = FaBARCSelect($player, 'Temp');
if ($targets !== '') {
 $chosen = await $player.MZChoose($targets, "Choose_one_card_for_top_of_deck");
 $topUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID);
 FaBARCToDeck($player, $topUID, true);
}'''
        else:
            code+='''$targets = FaBARCSelect($player, 'Temp', '', 'Arrow');
if ($targets !== '') {
 $chosen = await $player.MZMayChoose($targets, "Put_an_arrow_face_up_into_arsenal");
 FaBARCLoadArsenal($player, $chosen, true);
}'''
        code+='''
$orderParam = FaBARCOrderParam($lookUIDs, 'Bottom');
$order = await $player.Rearrange($orderParam);
FaBARCFinishOrder($player, $lookUIDs, $order);'''
        if base=='silver_the_tip':code="if (count(FaBChoiceRefs($player, 'Arsenal')) === 0) {\n"+code+'\n}'
        add('ResolveCard',code)
    if base=='chains_of_eminence':
        handled=True;add('ResolveCard', '''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$name = await $player.NameCard("", "Name_a_card_that_cannot_be_played_pitched_or_defended");
FaBARCName($uid, $name);''')
    if base=='become_the_arknight':
        handled=True
        add('ResolveCard', '''$targets = FaBARCSelect($player, 'Hand', '', 'Action');
if ($targets !== '') {
 $chosen = await $player.MZMayChoose($targets, "Discard_an_action_to_search");
 $kind = FaBARCDiscardKind($player, $chosen);
 if ($kind !== '') {
'''+choose_move('Deck',kind="' . $kind . '",class_='Runeblade',may=False).replace("'' . $kind . ''",'$kind')+''' 
 }
}''')
    if base=='nock_the_deathwhistle':
        handled=True
        add('ResolveCard',choose_move('Deck','Arrow',top=True,may=False)+'''\nif (count(FaBChoiceRefs($player, 'Arsenal')) === 0) {
 $targets = FaBARCSelect($player, 'Hand');
 if ($targets !== '') {
  $chosen = await $player.MZMayChoose($targets, "Reload");
  FaBARCLoadArsenal($player, $chosen, false);
 }
}''')
    if base=='pour_the_mold':
        handled=True
        add('ResolveCard',f'''$targets = FaBARCSelect($player, 'Hand', 'Mechanologist', 'Item', {3-pitch});
if ($targets !== '') {{
 $chosen = await $player.MZChoose($targets, "Put_a_Mechanologist_item_into_the_arena");
 FaBARCPutItem($player, $chosen, 'Hand', true);
}}''')
    if base=='spark_of_genius':
        handled=True
        add('PrepareCard','''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$maximum = max(0, intdiv(FaBAvailablePitch($player), 2));
$x = await $player.NumberChoose(0, $maximum, "Choose_X_for_item_cost");
FaBARCSetCard($uid, 'x', intval($x));
FaBARCSetPendingCost($uid, 2 * intval($x));
FaBFinishPreparedCard($uid);''')
        add('ResolveCard', '''$x = intval(FaBARCCard(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'x'));
$searchRefs = FaBARCExactItems($player, $x);
$searchUIDs = FaBARCStageRefs($player, $searchRefs);
$targets = FaBARCSelect($player, 'Temp');
if ($targets !== '') {
 $chosen = await $player.MZMayChoose($targets, "Search_for_a_Mechanologist_item");
 FaBARCPutItem($player, $chosen, 'Temp');
}
FaBFinishSearch($player);
if (FaBARCEffect($player, 'ARC_BOOSTED') > 0) { DoDrawCard($player, 1); }''')
    if base in ['dash','dash_inventor_extraordinaire']:
        handled=True
        add('StartTurn', '''$searchRefs = FaBARCSelect($player, 'Deck', 'Mechanologist', 'Item', 2);
$searchUIDs = FaBARCStageRefs($player, $searchRefs);
$targets = FaBARCSelect($player, 'Temp');
if ($targets !== '') {
 $chosen = await $player.MZMayChoose($targets, "Start_with_a_Mechanologist_item");
 FaBARCPutItem($player, $chosen, 'Temp');
}
FaBFinishSearch($player);
DoDrawCard($player, 4);''')
    simple_abilities={
      'achilles_accelerator':'AddActionPoints($player, intval(GetActionPoints($player)) + 1);',
      'crucible_of_aetherweave':effect('ARC_NEXT_ARCANE'),
      'robe_of_rapture':'AddResources($player, intval(GetResources($player)) + 3);',
      'storm_striders':effect('ARC_NEXT_WIZARD_INSTANT',1),
      'mage_master_boots':effect('ARC_NEXT_NAA_GO'),
      'grasp_of_the_arknight':'FaBARCCreateRunes($player, 1);',
      'bracers_of_belief':"$p = FaBARCRevealPitch($player);\nFaBWTRAddEffect($player, 'ARC_NEXT_AA', max(0, 3 - $p));",
      'teklo_foundry_heart':'FaBARCFoundry($player);',
      'convection_amplifier':"FaBWTRAddEffect($player, 'ARC_NEXT_AA', 0, ['dominate'=>true]);",
      'optekal_monocle':opt(1),
      'dissipation_shield':"$n = intval(DecisionQueueController::GetVariable('arcAbilitySteam'));\nFaBWTRAddEffect($player, 'ARC_PREVENT_ONCE', $n);",
      'skullbone_crosswrap':opt(1),
    }
    if base in simple_abilities:
        handled=True;add('ResolveAbility',simple_abilities[base])
    if base in ['aether_sink','convection_amplifier','dissipation_shield','hyper_driver','optekal_monocle','teklo_core','cognition_nodes','induction_chamber']:
        handled=True;add('ResolveCard',"FaBARCEnterItem($player, FaBIdentityFromMZ($mzID)['object']);")
    if base in ['aether_sink','cognition_nodes','induction_chamber','teklo_plasma_pistol']:
        handled=True
        code="""$index = intval(DecisionQueueController::GetVariable('arcAbilityIndex'));
if ($index === 0) { FaBARCCharge($mzID); }
else {"""
        code+= {'aether_sink':"FaBWTRTag(FaBIdentityFromMZ($mzID)['object'], 'ARC_BARRIER_2');",'cognition_nodes':"FaBTagUID(intval(DecisionQueueController::GetVariable('arcAttackUID')), 'ARC_BOTTOM_HIT');",'induction_chamber':"FaBTagUID(intval(DecisionQueueController::GetVariable('arcAttackUID')), 'GO_AGAIN');",'teklo_plasma_pistol':''}[base]+'\n}'
        add('ResolveAbility',code)
    if base=='dissipation_shield':
        add('StartTurn', '''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$choice = await $player.Modal(1, 1, "Remove_a_steam_counter&Destroy_shield", "Maintain_Dissipation_Shield");
FaBARCMaintainShield($player, $uid, $choice);''')
    if base in ['kano','kano_dracai_of_aether']:
        handled=True
        add('ResolveAbility','''$lookUIDs = FaBARCStageTop($player, 1);
$targets = FaBARCSelect($player, 'Temp', '', 'NAA');
if ($targets !== '') {
 $chosen = await $player.MZMayChoose($targets, "Banish_to_play_as_an_instant");
 FaBARCBanishInstant($player, $chosen, 'Temp');
}
FaBARCFinishOrder($player, $lookUIDs, '');''')
    if base in ['death_dealer','bulls_eye_bracers']:
        handled=True
        code='''if (count(FaBChoiceRefs($player, 'Arsenal')) === 0) {
 $targets = FaBARCSelect($player, 'Hand', '', 'Arrow');
 if ($targets !== '') {
  $chosen = await $player.MZMayChoose($targets, "Load_an_arrow_face_up");
'''+("  if (FaBARCLoadArsenal($player, $chosen, true)) { DoDrawCard($player, 1); }" if base=='death_dealer' else '  FaBARCLoadArsenal($player, $chosen, true, 1);')+'\n }\n}'
        add('ResolveAbility',code)
    if base in ['azalea','azalea_ace_in_the_hole']:
        handled=True
        add('ResolveAbility','''$targets = FaBARCSelect($player, 'Arsenal');
if ($targets !== '') {
 $chosen = await $player.MZChoose($targets, "Put_an_arsenal_card_on_the_bottom");
 FaBARCAzalea($player, $chosen);
}''')
    if base=='crown_of_dichotomy':
        handled=True
        add('ResolveAbility','''$uids = [];
$targets = FaBARCSelect($player, 'Graveyard', 'Runeblade', 'AA');
if ($targets !== '') {
 $chosen = await $player.MZChoose($targets, "Choose_a_Runeblade_attack_action");
 $uids[] = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID);
}
$targets = FaBARCSelect($player, 'Graveyard', 'Runeblade', 'NAA');
if ($targets !== '') {
 $chosen = await $player.MZChoose($targets, "Choose_a_Runeblade_non_attack_action");
 $uids[] = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID);
}
foreach ($uids as $uid) { FaBMoveUID($uid, 'Temp', $player, false); }
$orderParam = FaBARCOrderParam($uids, 'Top');
$order = await $player.Rearrange($orderParam);
FaBARCFinishOrder($player, $uids, $order);''')
    if base=='vest_of_the_first_fist':
        handled=True;add('Hit','''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$choice = await $player.Modal(1, 1, "Destroy_vest_for_two_resources&Keep_vest", "Vest_of_the_First_Fist");
if ($choice === '0') { FaBMoveUID($uid, 'Graveyard', $player); AddResources($player, intval(GetResources($player)) + 2); }''')
    if base=='runechant':
        handled=True
        add('PrepareCard','''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$targets = FaBARCHeroTargets($player);
$chosen = await $player.MZChoose($targets, "Choose_Runechant_target");
$target = FaBARCTargetSeat($player, $chosen);
FaBARCSetCard($uid, 'target', $target);''')
        code='''$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$target = intval(FaBARCCard($uid, 'target'));
$damage = 1;
$dealt = 0;
'''+damage_body()
        add('ResolveCard','$caster = $player;\n'+code.replace('$player','$caster'))
    if handled:snapshot.append({'cardId':id,'abilities':abilities})
    else:pending.append(id)

(HERE/'arc_abilities.json').write_text(json.dumps(snapshot,indent=2)+'\n',encoding='utf-8')
if pending: raise RuntimeError('Unhandled ARC cards: ' + ', '.join(pending))
print(f'Authored: {len(snapshot)} / {len(cards)}; pending: {len(pending)}')
