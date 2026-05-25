import os

def fix_body_scroll():
    base_dir = r"c:\xampp\htdocs\hospital-bengo\app\views"
    
    for root, dirs, files in os.walk(base_dir):
        for file in files:
            if file.endswith('.php'):
                path = os.path.join(root, file)
                with open(path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                original = content
                content = content.replace('h-screen overflow-hidden bg-[#f3f4f6]', 'bg-[#f3f4f6]')
                content = content.replace('h-screen overflow-hidden', '')
                
                if content != original:
                    with open(path, 'w', encoding='utf-8') as f:
                        f.write(content)
                        
    # Update scratch configuracao script
    config_script = r"c:\xampp\htdocs\hospital-bengo\scratch\generate_configuracao.py"
    if os.path.exists(config_script):
        with open(config_script, 'r', encoding='utf-8') as f:
            c = f.read()
        c = c.replace('h-screen overflow-hidden ', '')
        with open(config_script, 'w', encoding='utf-8') as f:
            f.write(c)

if __name__ == "__main__":
    fix_body_scroll()
