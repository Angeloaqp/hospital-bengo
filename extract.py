import json

log_path = r'C:\Users\aqp\.gemini\antigravity\brain\d0690273-4938-493e-bddd-22c694cae2f3\.system_generated\logs\transcript.jsonl'
with open(log_path, 'r', encoding='utf-8') as f:
    for line in f:
        data = json.loads(line)
        if data.get('type') == 'USER_INPUT':
            with open(r'c:\xampp\htdocs\hospital-bengo\first_prompt.txt', 'w', encoding='utf-8') as out:
                out.write(data.get('content', ''))
            break
