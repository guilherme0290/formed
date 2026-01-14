from pathlib import Path
text=Path('resources/views/comercial/agenda/index.blade.php').read_text(encoding='utf-8')
for i,line in enumerate(text.splitlines(),start=1):
    if 20<=i<=50:
        print(f"{i}: {line}")
