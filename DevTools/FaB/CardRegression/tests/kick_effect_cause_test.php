<?php
/** Actual movement observer with isolated token/state I/O, no native timing claim. */
declare(strict_types=1);
$repo=$argv[1]??dirname(__DIR__,4);$file=$argv[2]??$repo.'/FaBSim/Custom/SUPCards.php';$s=file_get_contents($file);$a=strpos($s,'function FaBSUPAfterMove(');$b=strpos($s,"\nfunction ",$a+20);eval(substr($s,$a,$b-$a));
function FaBHVYToken(...$a){$GLOBALS['tokens'][]=$a;}function FaBGetState(){return [];}function FaBSetState($s){}function HasNoAbilities($o){return false;}
$checks=0;foreach([0=>0,1=>0,2=>4]as$cause=>$want){$GLOBALS['fabEffectController']=$cause;$GLOBALS['tokens']=[];FaBSUPAfterMove(1,(object)['CardID'=>'kick_the_hornets_nest_yellow'],'CombatChain','Graveyard');if(count($GLOBALS['tokens'])!==$want)throw new RuntimeException('Wrong trigger cause '.$cause);++$checks;}
unset($GLOBALS['fabEffectController']);$GLOBALS['tokens']=[];FaBSUPAfterMove(1,(object)['CardID'=>'kick_the_hornets_nest_yellow'],'Hand','Graveyard');if($GLOBALS['tokens'])throw new RuntimeException('Unknown cause is not opponent');++$checks;
$GLOBALS['fabEffectController']=2;$GLOBALS['tokens']=[];FaBSUPAfterMove(1,(object)['CardID'=>'wounding_blow_red'],'CombatChain','Graveyard');if($GLOBALS['tokens'])throw new RuntimeException('Unrelated card');++$checks;echo 'PASS '.$checks.' actual Kick cause-filter checks; token I/O isolated'.PHP_EOL;
