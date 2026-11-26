<?php
/** Read-only checks of the actual imported-cost parser. */
$repo=$argv[1]??dirname(__DIR__,4);$s=file_get_contents($repo.'/FaBSim/Custom/GameLogic.php');if(!preg_match('/function FaBPrintedFixedResourceCost\(.*?\n\}/s',$s,$m))throw new RuntimeException('Actual parser missing');eval($m[0]);$n=0;foreach([''=>0,'0'=>0,'3'=>3,'12'=>12,'X'=>0,'XX'=>0,'X1'=>1,'1X'=>1,'XX2'=>2,'-1'=>0,'invalid'=>0]as$c=>$want){if(FaBPrintedFixedResourceCost((string)$c)!==$want)throw new RuntimeException('Wrong fixed resource component: '.$c);++$n;}echo 'PASS '.$n.' actual fixed-cost parsing checks'.PHP_EOL;
