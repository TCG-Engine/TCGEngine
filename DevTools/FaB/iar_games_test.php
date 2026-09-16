<?php
require_once __DIR__.'/iar_rules_test.php';
// Deterministic mixed IAR engine fixtures, not format-legal decks or a new bot profile.
$failures=[];
foreach([['viserai_between_worlds','levia'],['malice','viserai_between_worlds','levia','malice']] as $heroes){
    $n=count($heroes);$arcReset($n);SetTurnPlayer(1);SetPriorityPlayer(1);SetTurnNumber(1);SetCurrentPhase('MAIN');mt_srand(20260916);$bots=[];
    foreach($heroes as $i=>$id){$p=$i+1;
        foreach(['Hero','Weapons','Equipment','Hand','Deck','Arena','Inventory','Soul','Banish'] as $zone){$get='Get'.$zone;$zr=&$get($p);$zr=[];}unset($zr);
        AddHero($p,CardID:$id,Owner:$p,Controller:$p);AddHealth($p,intval(CardHealth($id)));AddResources($p,0);AddActionPoints($p,$p===1?1:0);
        if($id==='malice')AddWeapons($p,CardID:'vox_necropolis',Owner:$p,Controller:$p);
        if($id==='viserai_between_worlds')AddWeapons($p,CardID:'seven_sin_nebula',Owner:$p,Controller:$p);
        foreach(['dark_arcanite_helm','dark_arcanite_plating','dark_arcanite_gloves','dark_arcanite_boots'] as $e)AddEquipment($p,CardID:$e,Owner:$p,Controller:$p);
        $pool=match($id){
            'malice'=>['restless_outlaw_red','restless_steed_red','restless_cleric_red','acrid_stench_red','ominous_toll_blue','bone_mass_red','malignant_migration_blue','commit_to_corruption_blue','bonded_burial_red','mutual_sacrifice_blue'],
            'levia'=>['gorging_shadowbeast_red','feeding_frenzy_blue','feasting_shadowbeast_red','beckoning_hunger_blue','goremass_summoning_blue','consuming_command_blue','darkest_hour_red','breach_flesh_blue','rumbling_hunger_red','corporeal_chasm_blue'],
            default=>['demonbound_gloomblade_red','bloodfrenzy_gloomblade_blue','murmuring_gloomblade_red','runechant_of_pride_yellow','enshrine_sin_blue','become_the_shadow_lord_blue','shadowake_gloomblade_red','otherworldly_sins_blue','vexing_gloomblade_red','runic_reaving_blue']
        };
        foreach($pool as $cardID)if(!CardName($cardID))throw new RuntimeException('Unknown fixture card: '.$cardID);
        for($j=0;$j<40;++$j)AddDeck($p,CardID:$pool[$j%count($pool)],Owner:$p);$deckRef=&GetDeck($p);shuffle($deckRef);unset($deckRef);DoDrawCard($p,4);$bots[$p]='professor';
    }
    $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);
    for($step=0;$step<8000&&!intval(GetWinner());++$step){
        $p=BotControllerPendingPlayerForClient();if(!$p)throw new RuntimeException('No IAR pending bot.');$GLOBALS['playerID']=$p;
        if(!GetDecisionQueue($p)&&FaBGetState()['window']==='DEFEND_DECLARE'&&intval(GetTurnNumber())>=8){FaBPassPriority($p);GameAfterEngineAction([],[]);continue;}
        if(in_array('--trace',$argv,true))echo json_encode(['step'=>$step,'p'=>$p,'window'=>FaBGetState()['window'],'dq'=>GetDecisionQueue($p),'stack'=>array_map(fn($o)=>$o->CardID,GetStack())])."\n";
        $before=json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]);
        if(empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('IAR bot stalled: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
        if($before===json_encode([GetGameState(),GetDecisionQueue($p),GetHand($p),GetResources($p),GetActionPoints($p),GetStack(),GetPriorityPlayer(),GetConsecutivePasses()]))throw new RuntimeException('IAR unchanged bot action: '.json_encode(['p'=>$p,'state'=>FaBGetState(),'dq'=>GetDecisionQueue($p)]));
    }
    $check(intval(GetWinner())>0,'IAR game exceeded action limit.');echo implode('/',$heroes).": $step actions; winner ".GetWinner().".\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}
