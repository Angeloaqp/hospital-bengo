import os
import re

dir_path = r'c:\xampp\htdocs\hospital-bengo\app\views'

# 1. Fix the inline tailwind configs
config_pattern = re.compile(r'\"?borderRadius\"?:\s*\{[^\}]+\}')
config_repl = '\"borderRadius\": { \"DEFAULT\": \"1rem\", \"lg\": \"1rem\", \"xl\": \"0.75rem\", \"2xl\": \"1rem\", \"3xl\": \"1.5rem\", \"full\": \"9999px\" }'

# 2. Fix the buttons. Replace 'rounded-full' with 'rounded-xl' inside class attributes of buttons/links
# Let's find tags like <button ... class="... rounded-full ...">
button_pattern = re.compile(r'(<(?:button|a)\b[^>]*class\s*=\s*(?:\"|\')[^\'\"]*?)rounded-full([^\'\"]*(?:\"|\')[^>]*>)', re.IGNORECASE)

changed_files = []

for root, _, files in os.walk(dir_path):
    for file in files:
        if file.endswith('.php') or file.endswith('.html'):
            filepath = os.path.join(root, file)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()
            except UnicodeDecodeError:
                continue
                
            original = content
            
            # fix config
            # this matches both quoted and unquoted borderRadius keys
            content = re.sub(r'(?P<q>\"?)borderRadius(?P=q)\s*:\s*\{[^}]*\}', config_repl, content)
            
            # fix buttons iteratively
            while True:
                new_content = button_pattern.sub(r'\1rounded-xl\2', content)
                if new_content == content:
                    break
                content = new_content
                
            if content != original:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)
                changed_files.append(filepath)

print('Changed files:', len(changed_files))
for f in changed_files:
    print(f)
