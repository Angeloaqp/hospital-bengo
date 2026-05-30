<?php
$directory = 'c:\xampp\htdocs\hospital-bengo\app\views';

$classReplacements = [
    'bg-[#10b981]' => 'bg-[var(--cor-success)]',
    'bg-[#6b7280]' => 'bg-[var(--cor-inactive-dot)]',
    'bg-[#FFEBEE]' => 'bg-[var(--cor-priority-urgente-bg)]',
    'bg-[#FFF8E1]' => 'bg-[var(--cor-priority-idoso-bg)]',
    'bg-[#E3F2FD]' => 'bg-[var(--cor-priority-normal-bg)]',
    'bg-[#FF8F00]' => 'bg-[var(--cor-priority-idoso)]',
    'text-[#D32F2F]' => 'text-[var(--cor-priority-urgente)]',
    'text-[#FF8F00]' => 'text-[var(--cor-priority-idoso)]',
    'text-[#1976D2]' => 'text-[var(--cor-priority-normal)]',
    'border-[#fecdd3]' => 'border-[var(--cor-danger-icon-bg)]',
    'focus:border-[#111]' => 'focus:border-primary',
    'from-[#f3f4f6]' => 'from-[var(--cor-surface-container-low)]',
];

$regexReplacements = [
    '/#d4d4d8/i' => 'var(--cor-outline-variant)',
    '/#ff0000/i' => '#DC2626', // Keep raw hex if it's in a string comparison like `$rawColor === '#ff0000'`? Actually let's map it.
    '/#ffcc00/i' => '#F59E0B',
    '/#00cc00/i' => '#10B981',
    '/#e5e7eb/i' => 'var(--cor-scrollbar-light)',
    '/#111827/i' => 'var(--cor-chart-dark)',
    '/#4b5563/i' => 'var(--cor-inactive-text)',
    '/#F59E0B/i' => 'var(--cor-priority-idoso)',
    '/#8B5CF6/i' => 'var(--cor-priority-gravida)',
    '/#3B82F6/i' => 'var(--cor-priority-normal)',
    '/#10B981/i' => 'var(--cor-success)',
    '/#EF4444/i' => 'var(--cor-priority-urgente)',
    '/#DC2626/i' => 'var(--cor-priority-urgente)',
    '/#7C3AED/i' => 'var(--cor-priority-gravida)',
    '/#2563EB/i' => 'var(--cor-priority-normal)',
    '/#1a1a1a/i' => 'var(--cor-on-background)',
    '/#000000/i' => 'var(--cor-chart-tooltip)',
    '/#ffffff/i' => 'var(--cor-surface-container-lowest)',
    '/#fafafa/i' => 'var(--cor-surface-container-lowest)',
    '/#f1f5f9/i' => 'var(--cor-surface-container-low)',
    '/#f0f1f3/i' => 'var(--cor-surface-container-low)',
    '/#fff\b/i' => 'var(--cor-surface-container-lowest)',
    '/#374151/i' => 'var(--cor-outline)',
    '/#000\b/i' => 'var(--cor-chart-tooltip)',
    '/#f3f4f6/i' => 'var(--cor-surface-container-low)',
    '/#9ca3af/i' => 'var(--cor-scrollbar)',
    '/#6b7280/i' => 'var(--cor-scrollbar-hover)',
    '/#68D391/i' => 'var(--cor-success)',
    '/#eeeeee/i' => 'var(--cor-surface-container)',
    '/#3c3b3b/i' => 'var(--cor-primary-container)',
];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
$filesModified = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        foreach ($classReplacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        foreach ($regexReplacements as $search => $replace) {
            // More aggressive replacement: target any hex string that looks like a color assignment.
            // E.g. 'color' => '#FFF' or color: #FFF
            // We use lookbehind and lookahead to catch ' or " or : or space
            $pattern = '/([:\'"]\s*)' . substr($search, 1, -2) . '([\'";\s])/i';
            $content = preg_replace($pattern, '$1' . $replace . '$2', $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file->getPathname(), $content);
            echo "Modified: " . $file->getPathname() . "\n";
            $filesModified++;
        }
    }
}

echo "\nDone. Modified $filesModified files.\n";
