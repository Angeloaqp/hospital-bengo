<?php
$directories = ['admin', 'medico', 'paciente', 'recepcionista', 'enfermeiro', 'comum'];
$basePath = __DIR__ . '/app/views/';

foreach ($directories as $dir) {
    $dirPath = $basePath . $dir;
    if (is_dir($dirPath)) {
        $files = glob($dirPath . '/*.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            
            // Only replace backgrounds and borders, LEAVE TEXT ALONE
            $content = str_replace(['bg-black', 'bg-gray-900', 'bg-on-surface'], 'bg-primary', $content);
            $content = str_replace(['backdrop:bg-black', 'backdrop:bg-gray-900'], 'backdrop:bg-primary', $content);
            $content = str_replace(['border-black', 'border-gray-900'], 'border-primary', $content);
            
            // Note: we purposely omit text-black, text-on-surface, and hover:text-black 
            // because the user explicitly said "as letras nao" (not the letters).

            file_put_contents($file, $content);
            echo "Processed: " . basename($file) . "\n";
        }
    }
}
echo "All done!\n";
