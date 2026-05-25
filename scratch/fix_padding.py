import os
import re

def fix_padding():
    base_dir = r"c:\xampp\htdocs\hospital-bengo\app\views"
    
    for root, dirs, files in os.walk(base_dir):
        for file in files:
            if file.endswith('.php') and file not in ['header.php', 'sidebar.php', 'head_assets.php']:
                path = os.path.join(root, file)
                with open(path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                # Substituir p-8 por py-8 no div principal
                content = content.replace('class="ml-[17rem] mr-6 mt-28 p-8', 'class="ml-[17rem] mr-6 mt-28 py-8')
                
                # Substituir px-8 na tag main se existir
                content = re.sub(r'<main class="(.*?)px-8(.*?)">', r'<main class="\1\2">', content)
                
                with open(path, 'w', encoding='utf-8') as f:
                    f.write(content)
                    
    # Atualizar scratch configuracao script
    config_script = r"c:\xampp\htdocs\hospital-bengo\scratch\generate_configuracao.py"
    if os.path.exists(config_script):
        with open(config_script, 'r', encoding='utf-8') as f:
            c = f.read()
        c = c.replace('class=\\"ml-[17rem] mr-6 mt-28 p-8', 'class=\\"ml-[17rem] mr-6 mt-28 py-8')
        c = re.sub(r'<main class=\\"(.*?)px-8(.*?)\\">', r'<main class=\"\1\2\">', c)
        with open(config_script, 'w', encoding='utf-8') as f:
            f.write(c)

if __name__ == "__main__":
    fix_padding()
