import json
log_path = r'C:\Users\aqp\.gemini\antigravity\brain\d0690273-4938-493e-bddd-22c694cae2f3\.system_generated\logs\transcript.jsonl'
with open(log_path, 'r', encoding='utf-8') as f:
    for i, line in enumerate(f):
        if i == 1459:
            with open(r'c:\xampp\htdocs\hospital-bengo\step_1459.txt', 'w', encoding='utf-8') as out:
                data = json.loads(line)
                out.write(json.dumps(data, indent=2))
            break
