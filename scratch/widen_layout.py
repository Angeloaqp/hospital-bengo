import os
import re

def widen_layout():
    base_dir = r"c:\xampp\htdocs\hospital-bengo\app\views"
    
    # Update header.php
    header_path = os.path.join(base_dir, "comum", "header.php")
    with open(header_path, 'r', encoding='utf-8') as f:
        header_content = f.read()
    
    # Remove max-w-[1500px] mx-auto
    header_content = header_content.replace('max-w-[1500px] mx-auto', '')
    # Just in case:
    header_content = header_content.replace('max-w-[1500px]', '')
    
    with open(header_path, 'w', encoding='utf-8') as f:
        f.write(header_content)

    # Update all other views
    for root, dirs, files in os.walk(base_dir):
        for file in files:
            if file.endswith('.php'):
                path = os.path.join(root, file)
                with open(path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                original = content
                
                # Replace the main wrappers
                # From: <div class="ml-[17rem] mr-6 mt-28 py-8 flex justify-center">
                #       <main class="w-full max-w-[1500px]">
                content = content.replace('flex justify-center">\n<main class="w-full max-w-[1500px]">', '">\n<main class="w-full">')
                content = content.replace('flex justify-center">\r\n<main class="w-full max-w-[1500px]">', '">\n<main class="w-full">')
                
                # Also just in case they are single line:
                content = content.replace('flex justify-center">', '">')
                content = content.replace('<main class="w-full max-w-[1500px]">', '<main class="w-full">')
                
                if content != original:
                    with open(path, 'w', encoding='utf-8') as f:
                        f.write(content)
                        
    # Update scratch script so future generation doesn't use max-w
    config_script = r"c:\xampp\htdocs\hospital-bengo\scratch\generate_configuracao.py"
    if os.path.exists(config_script):
        with open(config_script, 'r', encoding='utf-8') as f:
            c = f.read()
        c = c.replace('flex justify-center', '')
        c = c.replace('max-w-[1500px]', '')
        with open(config_script, 'w', encoding='utf-8') as f:
            f.write(c)

if __name__ == "__main__":
    widen_layout()
