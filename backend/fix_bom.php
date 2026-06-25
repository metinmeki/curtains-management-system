<?php
$file = "app/Http/Controllers/Api/InventoryCategoryController.php";
$content = file_get_contents($file);
$content = preg_replace("/^\xEF\xBB\xBF/", "", $content);
$content = ltrim($content);
file_put_contents($file, $content);
echo "Fixed!";
