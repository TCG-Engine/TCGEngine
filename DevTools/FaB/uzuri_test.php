<?php
require_once __DIR__.'/out_rules_test.php';
require_once __DIR__.'/../../FaBSim/BotDeck.php';
$failures=[];$deck=FaBBotDeck('uzuri');
$check(!FaBUPFDeckErrors($deck),'Uzuri list is not UPF legal.');
$check(FaBNormalizeDeckPayload(json_decode(file_get_contents(__DIR__.'/uzuri_source.json'),true))===$deck,'Uzuri differs from linked source.');
$covered=[];foreach(glob(__DIR__.'/*_abilities.json') as $file)foreach(json_decode(file_get_contents($file),true) as $c)$covered[$c['cardId']]=true;
foreach(array_merge([$deck['hero']],$deck['weapons'],$deck['equipment'],$deck['mainDeck']) as $id)$check(isset($covered[$id]),'Unimplemented '.$id);
foreach([2,4] as $p){
 $outReset($p);$s=FaBGetState();$s['botProfiles']=[$p=>'uzuri'];FaBSetState($s);GetHero($p)[0]->CardID='uzuri';
 $a=$attack($p,'isolate_blue');$swap=AddHand($p,CardID:'sneak_attack_red');$blue=AddHand($p,CardID:'razors_edge_blue');
 $check(FaBUzuriWantsSwap($p),'Bot missed stealth swap.');
 $check(FaBUzuriAbilityScore($p,GetHero($p)[0])>0,'Bot declined Uzuri.');
 $d=(object)['Type'=>'MZCHOOSE','Tooltip'=>'Banish_card_face_down','Param'=>$ref($blue).'&'.$ref($swap)];
 $check(FaBBotChoice($p,$d)===$ref($swap),'Bot chose invalid swap card.');
 FaBWTRActivate($p,$ref(GetHero($p)[0]));
 $check(FaBBotAct($p),'Bot failed swap payment.');
 $top=FaBStackTop();if($top)DoResolveCard($p,$ref($top));
 $check(FaBFindUID(intval(FaBGetState()['attackUID']))['object']->CardID==='sneak_attack_red','Chosen swap did not resolve.');
 $check(FaBAttackPower(FaBGetState())>=7,'Sneak Attack missed reaction bonus.');
 $outReset($p);$s=FaBGetState();$s['botProfiles']=[$p=>'uzuri'];FaBSetState($s);
 $stealth=AddHand($p,CardID:'isolate_blue');$swap=AddHand($p,CardID:'death_touch_red');$bad=AddHand($p,CardID:'unmovable_blue');
 $check(FaBUzuriPlayScore($p,$stealth,'Hand')>FaBUzuriPlayScore($p,$swap,'Hand'),'Bot plays payload before stealth.');
 $incoming=$attack(1,'death_touch_red',$p);$legs=AddEquipment($p,CardID:'ironhide_legs',Owner:$p,Controller:$p);
 $check(FaBUzuriBlockScore($p,$legs,'Equipment')>0,'Bot never blocks with Ironhide.');
 $check(FaBBotChoice($p,(object)['Type'=>'MZMODAL','Tooltip'=>'Pay_for_equipment_defense','Param'=>'1|1|Decline&Pay'])==='1','Bot declines Ironhide defense payment.');
 $a=$attack($p,'isolate_blue');$swap->removed=true;$stealth->removed=true;
 $check(!FaBUzuriWantsSwap($p),'Bot swaps into non-attack.');
 $d=(object)['Type'=>'MZCHOOSE','Tooltip'=>'Choose_attack_target','Param'=>FaBARCHeroTargets($p)];$first=FaBBotChoice($p,$d);AddHand(1,CardID:'command_and_conquer_red');
 $check(FaBBotChoice($p,$d)===$first,'Bot reads hidden opponent hand.');
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures));exit(1);}echo "Uzuri source, coverage, and swap heuristics passed in duels and UPF.\n";
