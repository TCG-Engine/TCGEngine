<?php
declare(strict_types=1);
require_once __DIR__.'/World.php';
$champions=ADChampions();$catalog=DFGCatalog();
$cards=[];foreach(array_keys($champions) as $key)foreach(ADRewards($key) as $id)$cards[]=ADPublicCard($catalog[$id]);
function artEscape(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><base href="../"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Champion Techniques · El’s Invitation</title><link rel="icon" href="assets/favicon-no-pips-32.png"><link rel="stylesheet" href="cards.css"><link rel="stylesheet" href="adventure/world.css?v=<?=filemtime(__DIR__.'/world.css')?>"><style>
main{max-width:1440px;padding-bottom:60px}.gallery-intro{max-width:700px;line-height:1.8;color:var(--muted)}.gallery-nav{display:flex;flex-wrap:wrap;gap:10px;margin:22px 0 35px}.gallery-nav a{padding:10px 14px;background:var(--paper);border:1px solid var(--line);border-radius:7px;text-decoration:none;font-size:13px}.champion-gallery{margin-top:40px;scroll-margin-top:20px}.champion-gallery h2{font-weight:500}.champion-gallery .card-library{grid-template-columns:repeat(3,minmax(0,1fr))}.champion-gallery .card-origin{min-height:60px}.full-art-link{display:block;padding:10px;background:#eadfc8;font-size:11px;text-align:center;border-radius:0 0 8px 8px}@media(max-width:1000px){.champion-gallery .card-library{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:700px){.champion-gallery .card-library{grid-template-columns:1fr}.gallery-nav a{font-size:11px}.world-header{min-height:70px}}
</style></head><body>
<header class="world-header"><a class="brand" href="adventure/"><span class="brand-sun">☀</span><span><small>EL’S INVITATION</small><strong>Champion Techniques</strong></span></a><a href="adventure/">Return to your adventure →</a></header>
<main><span class="eyebrow">THE FIRST COURTYARD · 24 TECHNIQUES</span><h1>A move worth remembering</h1><p class="gallery-intro">Every champion brings a different way of fighting. Study their techniques here; defeat them in the courtyard to add their six moves to your collection.</p><nav class="gallery-nav" aria-label="Champions"><?php foreach($champions as $key=>$npc): ?><a href="adventure/technique-art.php#<?=artEscape($key)?>"><?=artEscape(explode(',',$npc['name'])[0])?></a><?php endforeach; ?></nav>
<?php foreach($champions as $key=>$npc): ?><section class="champion-gallery" id="<?=artEscape($key)?>"><span class="eyebrow"><?=artEscape($npc['title'])?></span><h2><?=artEscape($npc['name'])?></h2><div class="card-library" data-champion="<?=artEscape($key)?>"></div></section><?php endforeach; ?>
</main><script src="card-renderer.js?v=<?=filemtime(__DIR__.'/../card-renderer.js')?>"></script><script>
'use strict';
const cards=<?=json_encode($cards,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR)?>;
const colors=<?=json_encode(DFG_STYLES,JSON_THROW_ON_ERROR)?>;
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
for(const c of cards){const node=document.createElement('article');node.className='library-entry champion-technique'+(c.headline?' headline-technique':'');node.dataset.card=c.id;node.innerHTML=`<div class="card-origin"><strong>${esc(c.roleTitle)}</strong><span>${esc(c.tradeoff)}</span></div>${DFGCardRenderer.details(c,colors)}<a class="full-art-link" href="${esc(c.artPath)}" target="_blank" rel="noopener">View ${esc(c.name)} illustration ↗</a>`;document.querySelector(`[data-champion="${c.champion}"]`).append(node);}
</script></body></html>
