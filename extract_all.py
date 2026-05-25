import json
log_path = r'C:\Users\aqp\.gemini\antigravity\brain\d0690273-4938-493e-bddd-22c694cae2f3\.system_generated\logs\transcript.jsonl'
with open(log_path, 'r', encoding='utf-8') as f, open(r'c:\xampp\htdocs\hospital-bengo\all_prompts.txt', 'w', encoding='utf-8') as out:
    for line in f:
        data = json.loads(line)
        if data.get('type') == 'USER_INPUT':
            out.write(data.get('content', '') + '\n' + ('-'*80) + '\n')
