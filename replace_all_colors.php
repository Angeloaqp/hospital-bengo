<?php
$directory = 'c:\xampp\htdocs\hospital-bengo\app\views';

$classReplacements = [
    // Backgrounds
    'bg-[#f3f4f6]' => 'bg-surface-container-low',
    'bg-[#f3f3f3]' => 'bg-surface-container-low',
    'bg-[#f9f9f9]' => 'bg-surface-container-low',
    'bg-[#f4f5f7]' => 'bg-surface-container-low',
    'bg-[#e5e7eb]' => 'bg-surface-container-high',
    'bg-[#e8e8e8]' => 'bg-surface-container-high',
    'bg-[#ffffff]' => 'bg-surface-container-lowest',
    'bg-[#fff]' => 'bg-surface-container-lowest',
    'bg-[#000000]' => 'bg-inverse-surface',
    'bg-[#000]' => 'bg-inverse-surface',
    'bg-[#ffdad6]' => 'bg-[var(--cor-error-banner-bg)]',
    'bg-[#fecdd3]' => 'bg-[var(--cor-danger-icon-bg)]',
    'bg-[#fff1f2]' => 'bg-[var(--cor-danger-light)]',
    'bg-[#ecfdf5]' => 'bg-[var(--cor-success-light)]',
    'bg-[#F59E0B]' => 'bg-[var(--cor-priority-idoso)]',
    
    // Text
    'text-[#1a1c1c]' => 'text-on-surface',
    'text-[#111]' => 'text-on-surface',
    'text-[#000]' => 'text-on-surface',
    'text-[#000000]' => 'text-on-surface',
    'text-[#474747]' => 'text-on-surface-variant',
    'text-[#4b5563]' => 'text-[var(--cor-inactive-text)]',
    'text-[#71717a]' => 'text-[var(--cor-input-label)]',
    'text-[#a1a1aa]' => 'text-[var(--cor-input-placeholder)]',
    'text-[#410002]' => 'text-[var(--cor-error-banner-text)]',
    'text-[#be123c]' => 'text-[var(--cor-danger)]',
    'text-[#e11d48]' => 'text-[var(--cor-danger-subtitle)]',
    'text-[#881337]' => 'text-[var(--cor-danger-body)]',
    'text-[#059669]' => 'text-[var(--cor-success-dark)]',
    'text-[#10B981]' => 'text-success',
    
    // Priorities
    'text-[#3B82F6]' => 'text-[var(--cor-priority-normal)]',
    'text-[#8B5CF6]' => 'text-[var(--cor-priority-gravida)]',
    'text-[#F59E0B]' => 'text-[var(--cor-priority-idoso)]',
    'text-[#EF4444]' => 'text-[var(--cor-priority-urgente)]',

    // Borders / Rings
    'border-[#007aff]' => 'border-primary',
    'ring-[#007aff]' => 'ring-primary',
    'border-[#ffe4e6]' => 'border-[var(--cor-danger-border)]',
    
    // Hover variants
    'hover:bg-[#f3f3f3]' => 'hover:bg-surface-container-low',
    'hover:bg-[#e8e8e8]' => 'hover:bg-surface-container-high',
    'hover:bg-[#f8fafc]' => 'hover:bg-[var(--cor-input-hover)]',
    'hover:bg-[#fafafa]' => 'hover:bg-surface-container-lowest',
    'hover:bg-[#93000a]' => 'hover:bg-[var(--cor-danger-hover)]',
];

$regexReplacements = [
    // CSS blocks and inline styles (replace literal hex with vars)
    '/#007aff/i' => 'var(--cor-primary)',
    '/#1a1c1c/i' => 'var(--cor-on-surface)',
    '/#111\b/i' => 'var(--cor-on-surface)',
    '/#000000/i' => 'var(--cor-toast-bg)',
    '/#000\b/i' => 'var(--cor-toast-bg)',
    '/#ffffff/i' => 'var(--cor-surface-container-lowest)',
    '/#fff\b/i' => 'var(--cor-surface-container-lowest)',
    '/#f3f4f6/i' => 'var(--cor-surface-container-low)',
    '/#f3f3f3/i' => 'var(--cor-surface-container-low)',
    '/#f4f5f7/i' => 'var(--cor-input-bg)',
    '/#f8fafc/i' => 'var(--cor-input-hover)',
    '/#e5e7eb/i' => 'var(--cor-scrollbar-light)',
    '/#d1d5db/i' => 'var(--cor-scrollbar-light-hover)',
    '/#9ca3af/i' => 'var(--cor-scrollbar)',
    '/#6b7280/i' => 'var(--cor-scrollbar-hover)',
    '/#a1a1aa/i' => 'var(--cor-input-placeholder)',
    '/#71717a/i' => 'var(--cor-input-label)',
    '/#d4d4d8/i' => 'var(--cor-outline-variant)',
    '/#10b981/i' => 'var(--cor-success)',
    '/#3c3b3b/i' => 'var(--cor-primary-container)'
];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
$filesModified = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $originalContent = $content;
        
        // 1. Replace tailwind arbitrary classes
        foreach ($classReplacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        
        // 2. Replace raw hex codes inside <style> tags and inline style="" attributes
        // Only target # followed by hex in CSS contexts, not arbitrary anchor tags.
        // We'll replace hex codes specifically used as CSS property values.
        foreach ($regexReplacements as $search => $replace) {
            // Regex to replace hex only if it's after a colon (CSS property) or inside a style block/attribute
            // E.g. `color: #000;` or `background: #fff`
            $pattern = '/(:\s*)' . substr($search, 1, -2) . '([^a-zA-Z0-9_-])/i';
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
