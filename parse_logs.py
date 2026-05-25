import json
import sys
import re

log_path = r'C:\Users\aqp\.gemini\antigravity\brain\d0690273-4938-493e-bddd-22c694cae2f3\.system_generated\logs\transcript.jsonl'
try:
    with open(log_path, 'r', encoding='utf-8') as f:
        for line in f:
            try:
                data = json.loads(line)
                if data.get('source') == 'USER_EXPLICIT' and data.get('type') == 'USER_INPUT':
                    content = data.get('content', '')
                    time = data.get('created_at', '')
                    print(f"[{time}] {content[:100].replace(chr(10), ' ')}")
            except json.JSONDecodeError:
                pass
except Exception as e:
    print("Error:", e)
