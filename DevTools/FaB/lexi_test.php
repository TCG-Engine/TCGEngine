<?php
require_once __DIR__.'/arc_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$failures=[];$deck=FaBBotDeck('lexi');
$check(count($deck['mainDeck'])===40&&!FaBUPFDeckErrors($deck),'Lexi deck not legal.');
$check(FaBNormalizeDeckPayload(json_decode(file_get_contents(__DIR__.'/lexi_source.json'),true))===$deck,'Lexi deck differs from source.');
$resetLexi=function(int $seats)use($arcReset){$arcReset($seats);foreach(FaBSeatOrder() as $p){$z=&GetSoul($p);$z=[];}$p=$seats;$h=&GetHero($p);$h=[];AddHero($p,CardID:'lexi',Owner:$p,Controller:$p);SetTurnPlayer($p);SetPriorityPlayer($p);SetTurnNumber(1);};
foreach([2,4] as $seats){
    $resetLexi($seats);$p=$seats;$s=FaBGetState();$s['botProfiles']=[$p=>'lexi'];FaBSetState($s);
    AddWeapons($p,CardID:'shiver',Owner:$p,Controller:$p);
    AddHand($p,CardID:'blizzard_bolt_red');AddHand($p,CardID:'winters_bite_blue');
    $d=(object)['Type'=>'MZMAYCHOOSE','Tooltip'=>'Load_arrow','Param'=>'p'.$p.'Hand-0'];
    $check(FaBLexiChoice($p,$d)==='p'.$p.'Hand-0','Lexi failed to choose an arrow.');
    $check(FaBLexiAbilityScore($p,GetWeapons($p)[0])>0,'Lexi failed to load an affordable arrow.');
    $ice=AddArsenal($p,CardID:'winters_bite_blue',FaceDown:1);
    $check(FaBLexiAbilityScore($p,GetHero($p)[0])>0,'Lexi ignored an Ice reveal.');
    $check(FaBLexiAbilityScore($p,GetWeapons($p)[0])<0,'Lexi tried loading a full arsenal.');
    $own=AddCombatChain($p,CardID:'boltn_shot_red',Owner:$p,Controller:$p,Role:'ATTACK');
    AddCombatChain(1,CardID:'boltn_shot_red',Owner:1,Controller:1,Role:'ATTACK');
    $d=(object)['Type'=>'MZCHOOSE','Tooltip'=>'Empower_attack','Param'=>'p1CombatChain-0&p'.$p.'CombatChain-0'];
    $check(FaBLexiChoice($p,$d)==='p'.$p.'CombatChain-0','Lexi buffed another player.');
    AddStack(CardID:'lightning_press_red',Controller:$p);
    foreach(FaBChoiceRefs($p,'Stack') as $r)$check(FaBIdentityFromMZ($r)!==null,'Shared stack reference is invalid.');
}
foreach([['lexi','fai'],['lexi','lexi','lexi','lexi'],['lexi','professor','levia','prism']] as $profiles){
    $seats=count($profiles);$resetLexi($seats);SetTurnPlayer(1);SetPriorityPlayer(1);mt_srand(20260914);$bots=[];
    foreach($profiles as $i=>$profile){$p=$i+1;$d=FaBBotDeck($profile);$h=&GetHero($p);$h=[];AddHero($p,CardID:$d['hero'],Owner:$p,Controller:$p);
        foreach($d['weapons'] as $id)AddWeapons($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['equipment'] as $id)AddEquipment($p,CardID:$id,Owner:$p,Controller:$p);
        foreach($d['mainDeck'] as $id)AddDeck($p,CardID:$id);$z=&GetDeck($p);shuffle($z);DoDrawCard($p,4);$bots[$p]=$profile;
    }
    $s=FaBGetState();$s['botProfiles']=$bots;FaBSetState($s);$safeTurns=[];$auraAttacks=0;
    for($step=0;$step<8000&&!intval(GetWinner());++$step){
        $p=BotControllerPendingPlayerForClient();
        if(!$p||empty(ProcessBotControllerStep(1,'FaBSim')['applied']))throw new RuntimeException('Bot stalled '.json_encode(['player'=>$p,'state'=>FaBGetState(),'dq'=>$p?GetDecisionQueue($p):[]]));
        if(FaBIsLexiBot($p)){
            foreach(GetArsenal($p) as $o)if(empty($o->removed))$safeTurns[$p.':'.GetTurnNumber()]=true;
            $s=FaBGetState();$a=FaBFindUID(intval($s['attackUID']));if($a&&intval($s['attacker'])===$p&&FaBHasType($a['object'],'Arrow'))++$auraAttacks;
        }
    }
    $check(intval(GetWinner())>0,'Lexi game exceeded limit.');$check(count($safeTurns)>0,'Lexi never loaded arsenal.');$check($auraAttacks>0,'Lexi never attacked with arrows.');
    echo "$seats-player ".implode('/',$profiles).": $step steps; ".count($safeTurns)." turns with arsenal, $auraAttacks arrow observations.\n";
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
