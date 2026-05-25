import os
import re

def fix_all_pages():
    base_dir = r"c:\xampp\htdocs\hospital-bengo\app\views"
    
    for root, dirs, files in os.walk(base_dir):
        for file in files:
            if file.endswith('.php') and file not in ['header.php', 'sidebar.php', 'head_assets.php']:
                path = os.path.join(root, file)
                with open(path, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                original_content = content

                # Se a página usa <main class="ml-56..."> vamos trocar pelo layout ideal
                if '<main class="ml-56' in content:
                    # Encontrar a tag <main...> e substituí-la
                    content = re.sub(
                        r'<main class="ml-56.*?>\s*(<div class=".*?max-w-.*?>)?', 
                        r'<div class="ml-[17rem] mr-6 mt-28 py-8 flex justify-center">\n<main class="w-full max-w-[1500px]">\n', 
                        content
                    )
                    
                    # Substituir o final. O </div> extra se houvesse, agora não tem porque substituimos ou main ou main + div.
                    # Mas temos de garantir que fechamos com </main></div>
                    content = content.replace('</main>', '</main>\n</div>')
                    
                    # Garantir que não há mais do que um </div> após o main caso tenhamos removido o div interno do lado do open tag.
                    # Se substituimos o main + div por apenas main, então sobra um </div> a mais antes do </main>.
                    # Como resolver isso: podemos remover o "p-8 max-w-[...]" se ele existir dentro.
                    
                # Corrigir os que já têm ml-[17rem] mr-6 mt-28 mas com formatação extra:
                if 'class="ml-[17rem] mr-6 mt-28 h-[calc(100vh-7rem)] flex flex-col"' in content:
                    content = content.replace(
                        'class="ml-[17rem] mr-6 mt-28 h-[calc(100vh-7rem)] flex flex-col"', 
                        'class="ml-[17rem] mr-6 mt-28 py-8 flex justify-center"'
                    )
                    content = re.sub(r'<main class="w-full max-w-\[(.*?)\](.*?)">', r'<main class="w-full max-w-[1500px]">', content)
                    content = content.replace('<div class="flex-1 overflow-y-auto custom-scrollbar">', '')
                    content = content.replace('</main>\n        </div>\n    </div>', '</main>\n</div>')
                    content = content.replace('</main>\n    </div>\n</div>', '</main>\n</div>')

                if content != original_content:
                    with open(path, 'w', encoding='utf-8') as f:
                        f.write(content)

fix_all_pages()
