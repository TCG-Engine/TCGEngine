<?php
declare(strict_types=1);
$path=__DIR__.'/assets/animations/enemy-animations.json';
$clips=is_file($path)?json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR):[];
$names=['imdugud'=>'Imdugud','huwawa'=>'Huwawa','bull-of-heaven'=>'Bull of Heaven'];
$labels=['idle'=>'Idle','block'=>'Block','light'=>'Light hit','medium'=>'Medium hit','heavy'=>'Heavy hit','knockout'=>'Knockout'];
function attr(string $value): string { return htmlspecialchars($value,ENT_QUOTES,'UTF-8'); }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sumerian enemy animations · DFGSim</title>
<style>
:root{color-scheme:dark;font-family:system-ui,sans-serif;background:#0b141d;color:#f7ecd4}body{max-width:1400px;margin:auto;padding:32px 24px 60px}h1{font-size:clamp(28px,4vw,44px);margin-bottom:8px}p{color:#b6c4cf;line-height:1.6}a{color:#edc375}section{margin-top:44px}h2{border-bottom:1px solid #344857;padding-bottom:12px}.clips{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}figure{margin:0;border:1px solid #344857;border-radius:12px;overflow:hidden;background:#152535}picture{display:block;background:radial-gradient(ellipse at bottom,#435d67,#1b3044)}img{display:block;width:100%;aspect-ratio:16/9;object-fit:contain}figcaption{display:flex;align-items:center;justify-content:space-between;padding:12px 16px}button{font:inherit;color:#f7ecd4;background:#293f50;border:1px solid #6e8391;border-radius:6px;padding:6px 12px;cursor:pointer}button:focus-visible{outline:3px solid #edc375;outline-offset:2px}button:disabled{opacity:.5}small{color:#b6c4cf}@media(max-width:850px){.clips{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:480px){body{padding:24px 14px}.clips{grid-template-columns:1fr}}@media(prefers-reduced-motion:reduce){button{display:none}}
</style></head><body>
<a href="./">← Roguelike</a><h1>Sumerian enemy animations</h1>
<p>Three new fighters, six animation states each. Replay a reaction to inspect it again. These assets are ready for future encounters; this gallery does not alter your run.</p>
<?php foreach($names as $key=>$name): ?><section><h2><?=attr($name)?></h2><div class="clips">
<?php foreach($labels as $kind=>$label): $entry=$clips[$key][$kind]??null; if(!$entry): ?><figure><figcaption><?=attr($label)?> · awaiting conversion</figcaption></figure><?php continue; endif;
$source='../'.$entry['src'];$still='../'.($kind==='knockout'?$entry['finalSrc']:$clips[$key]['still']); ?>
<figure><picture><source media="(prefers-reduced-motion: reduce)" srcset="<?=attr($still)?>"><img loading="lazy" src="<?=attr($source)?>" alt="<?=attr($name.' — '.$label)?>" data-original="<?=attr($source)?>"></picture><figcaption><span><?=attr($label)?></span><button type="button" aria-label="<?=attr('Replay '.$name.' '.$label)?>">Replay</button></figcaption></figure>
<?php endforeach; ?></div></section><?php endforeach; ?>
<script>
document.querySelectorAll('figure button').forEach(button=>button.addEventListener('click',async()=>{
  if(matchMedia('(prefers-reduced-motion: reduce)').matches)return;
  const image=button.closest('figure').querySelector('img');button.disabled=true;
  try{
    const response=await fetch(image.dataset.original);if(!response.ok)throw Error('Unable to load animation');
    const url=URL.createObjectURL(await response.blob()),old=image.dataset.blob;
    image.onload=image.onerror=()=>{if(old)URL.revokeObjectURL(old);button.disabled=false;};
    image.dataset.blob=url;image.src=url;
  }catch(error){button.disabled=false;button.textContent='Try again';}
}));
</script></body></html>
