<?php
// Get folder names from the Schemas directory
$directory = 'Schemas';
$items = array_filter(glob($directory . '/*'), 'is_dir');
$items = array_map('basename', $items);

// Function to create buttons with curl requests
function createButtons($item) {
    $button1 = "<button onclick=\"sendCurlRequest('$item', 'http://localhost/TCGEngine/zzGameCodeGenerator.php', 'response_$item')\">GameCodeGenerator</button>";
    $button2 = "<button onclick=\"sendCurlRequest('$item', 'http://localhost/TCGEngine/zzCardCodeGenerator.php', 'response_$item')\">CardCodeGenerator</button>";
    return $button1 . " " . $button2;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Generator</title>
    <script>
        function sendCurlRequest(item, url, responseElementId) {
            //display a loading gif because the card code generation can take a while
            document.getElementById(responseElementId).innerHTML = '<img src="Assets/Loading.gif" alt="Loading..." style="width: 50px; height: 50px;">';
            
            var xhr = new XMLHttpRequest();
            xhr.open("GET", url + "?rootName=" + item, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var responseLines = xhr.responseText.split('<BR>');
                    var clearButton = '<button onclick="document.getElementById(\'' + responseElementId + '\').innerHTML = \'\'">Clear</button>';
                    var responseHtml = clearButton + '<ul>';
                    //var responseHtml = '<ul>';
                    for (var i = 0; i < responseLines.length; i++) {
                        responseHtml += '<li>' + responseLines[i] + '</li>';
                    }
                    responseHtml += '</ul>';
                    document.getElementById(responseElementId).innerHTML = responseHtml;
                }
            };
            xhr.send();
        }
    </script>
</head>
<body>
    <?php foreach ($items as $item): ?>
        <div>
            <?php echo htmlspecialchars($item); ?>
            <?php echo createButtons($item); ?>
            <div id="response_<?php echo htmlspecialchars($item); ?>"></div>
        </div>
    <?php endforeach; ?>
</body>
</html>