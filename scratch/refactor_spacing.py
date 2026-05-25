import os
import re

def update_files():
    # Caminho das views
    base_dir = r"c:\xampp\htdocs\hospital-bengo\app\views"
    
    # Atualizar header.php
    header_path = os.path.join(base_dir, "comum", "header.php")
    with open(header_path, 'r', encoding='utf-8') as f:
        header = f.read()
    
    # Substituir class wrapper
    header = re.sub(r'left-56 pl-6', r'left-[17rem] right-6', header)
    # Substituir padding do header interior
    header = re.sub(r'<header class="h-16 mt-6 px-8 max-w-\[1500px\] mx-auto">', r'<header class="h-16 mt-6 w-full max-w-[1500px] mx-auto">', header)
    
    with open(header_path, 'w', encoding='utf-8') as f:
        f.write(header)

    # Atualizar sidebar.php
    sidebar_path = os.path.join(base_dir, "comum", "sidebar.php")
    with open(sidebar_path, 'r', encoding='utf-8') as f:
        sidebar = f.read()
    
    sidebar = sidebar.replace('.ml-56, .ml-64, .lg\:ml-56, .lg\:ml-64 { transition: margin-left 0.3s ease; }',
                              '.ml-56, .ml-64, .lg\:ml-56, .lg\:ml-64, .ml-\\[17rem\\] { transition: margin-left 0.3s ease; }')
    
    sidebar = sidebar.replace('html.sidebar-minimized .lg\:ml-64 { margin-left: 6.5rem !important; }',
                              'html.sidebar-minimized .lg\:ml-64,\n    html.sidebar-minimized .ml-\\[17rem\\] { margin-left: 7.5rem !important; }')
    
    sidebar = sidebar.replace('html.sidebar-minimized #header-wrapper { left: 6.5rem !important; }',
                              'html.sidebar-minimized #header-wrapper { left: 7.5rem !important; right: 1.5rem !important; }')
    
    sidebar = sidebar.replace('html.sidebar-minimized .lg\:ml-64 { margin-left: 0 !important; }',
                              'html.sidebar-minimized .lg\:ml-64,\n        html.sidebar-minimized .ml-\\[17rem\\] { margin-left: 0 !important; }')
                              
    with open(sidebar_path, 'w', encoding='utf-8') as f:
        f.write(sidebar)
        
    # Atualizar todas as páginas para ter ml-[17rem] mr-6
    for root, dirs, files in os.walk(base_dir):
        for file in files:
            if file.endswith('.php') and file not in ['header.php', 'sidebar.php', 'head_assets.php']:
                path = os.path.join(root, file)
                with open(path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                # ml-56 mt-28 -> ml-[17rem] mr-6 mt-28
                new_content = content.replace('class="ml-56 mt-28', 'class="ml-[17rem] mr-6 mt-28')
                if new_content != content:
                    with open(path, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                        
    # Atualizar scratch configuracao script
    config_script = r"c:\xampp\htdocs\hospital-bengo\scratch\generate_configuracao.py"
    if os.path.exists(config_script):
        with open(config_script, 'r', encoding='utf-8') as f:
            c = f.read()
        c = c.replace('class=\\"ml-56 mt-28', 'class=\\"ml-[17rem] mr-6 mt-28')
        with open(config_script, 'w', encoding='utf-8') as f:
            f.write(c)

if __name__ == "__main__":
    update_files()
