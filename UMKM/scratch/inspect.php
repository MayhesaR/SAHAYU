<?php
$lines = file('resources/views/ManajemenBahanBaku.blade.php');
foreach ($lines as $i => $line) {
    if (strpos($line, 'group-hover') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
