<?php
$dir = __DIR__;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$extensions = ['php', 'js', 'vue', 'md', 'html'];
$replacements = [
    'EcoShare' => 'EcoShare',
    'EcoAnálisis' => 'EcoAnálisis',
    'EcoAnálisiss' => 'publicaciones',
    'EcoAnálisis' => 'EcoAnálisis',
    'EcoAnálisiss' => 'Publicaciones',
    'sostenibilidad' => 'sostenibilidad',
    'sostenible' => 'sostenible',
    'sostenible' => 'sostenible',
    'prácticas sostenibles' => 'prácticas sostenibles',
    'práctica' => 'práctica',
    'impacto' => 'impacto',
    'iniciativa' => 'iniciativa'
];

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    
    $path = $file->getPathname();
    
    // Skip vendor, node_modules, storage, etc.
    if (strpos($path, 'vendor') !== false || strpos($path, 'node_modules') !== false || strpos($path, 'storage') !== false || strpos($path, '.git') !== false) {
        continue;
    }
    
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), $extensions)) continue;
    
    $content = file_get_contents($path);
    $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
    
    if ($content !== $newContent) {
        file_put_contents($path, $newContent);
        echo "Updated: $path\n";
    }
}
echo "Done replacing.\n";
