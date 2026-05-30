<?php
$directory = 'c:\xampp\htdocs\hospital-bengo\app\views';

$replacements = [
    'bg-[#007aff]' => 'bg-primary',
    'text-[#007aff]' => 'text-primary',
    'border-[#007aff]' => 'border-primary',
    'hover:bg-[#007aff]' => 'hover:bg-primary',
    'hover:text-[#007aff]' => 'hover:text-primary',
    'hover:border-[#007aff]' => 'hover:border-primary',
    'focus:border-[#007aff]' => 'focus:border-primary',
    'bg-[#f3f4f6]' => 'bg-surface-container-low',
    'bg-[#f9f9f9]' => 'bg-surface-container-low'
];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
$filesModified = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file->getPathname(), $content);
            echo "Modified: " . $file->getPathname() . "\n";
            $filesModified++;
        }
    }
}

echo "\nDone. Modified $filesModified files.\n";
