<?php
require_once __DIR__ . '/../../Database/functions.inc.php';

// Profile "Blocked Users" section. Renders nothing when logged out.
function RenderBlockedUsers($userId)
{
    $userId = (int)$userId;
    if ($userId <= 0) return '';
    $blocks = LoadBlockedUsersDetailed($userId);
    $esc = function($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

    $rows = '';
    foreach ($blocks as $b) {
        $rows .= "<li class='blocked-user-row' data-id=\"" . (int)$b['id'] . "\">"
               . "<span class='bu-name'>" . $esc($b['username']) . "</span>"
               . "<button class='bu-unblock' data-id=\"" . (int)$b['id'] . "\">Unblock</button>"
               . "</li>";
    }
    if ($rows === '') $rows = "<li class='blocked-user-empty'>You haven't blocked anyone.</li>";

    return "<div class='blocked-users'>"
         . "<div class='blocked-user-addrow'>"
         . "<input type='text' id='blocked-user-add' placeholder='Username to block' />"
         . "<button id='blocked-user-add-btn'>Block</button>"
         . "</div>"
         . "<ul class='blocked-user-list'>" . $rows . "</ul>"
         . "<script>(function(){"
         . "var ep='/TCGEngine/SWUSim/BlockedUsers.php';"
         . "function post(p,cb){var x=new XMLHttpRequest();x.open('POST',ep,true);x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');x.onreadystatechange=function(){if(x.readyState===4){try{cb(JSON.parse(x.responseText));}catch(e){}}};x.send(p);}"
         . "function render(d){location.reload();}"
         . "var addBtn=document.getElementById('blocked-user-add-btn');"
         . "if(addBtn)addBtn.onclick=function(){var u=document.getElementById('blocked-user-add').value.trim();if(u)post('action=add&username='+encodeURIComponent(u),render);};"
         . "var ul=document.querySelector('.blocked-user-list');"
         . "if(ul)ul.addEventListener('click',function(e){if(e.target&&e.target.classList.contains('bu-unblock')){post('action=remove&blockedId='+encodeURIComponent(e.target.getAttribute('data-id')),render);}});"
         . "})();</script>"
         . "</div>";
}
