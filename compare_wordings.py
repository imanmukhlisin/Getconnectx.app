import csv
import re

def read_csv(file_path):
    wordings = []
    with open(file_path, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            wordings.append(row)
    return wordings

wordings = read_csv('/home/Rama_indonesia/codevits_projects/connectx-backend/ConnectX-Wordings.csv')

# Build mapping from English to (Indonesian Text, Revised Indonesian)
en_to_indo = {}
for row in wordings:
    en = row.get('English Text', '').strip()
    indo = row.get('Indonesian Text', '').strip()
    rev = row.get('Revised Indonesian', '').strip()
    if not en:
        continue
    
    final_indo = rev if (rev and rev.lower() != 'v' and not rev.startswith('ini')) else indo
    en_to_indo[en] = final_indo

# Now parse OnboardingSeeder.php and find masterCFTypes
with open('/home/Rama_indonesia/codevits_projects/connectx-backend/database/seeders/OnboardingSeeder.php', 'r', encoding='utf-8') as f:
    content = f.read()

print("Checking masterCFTypes:")
# Regex to match masterCFTypes
pattern = r"\$masterCFTypes\s*=\s*\[(.*?)\];"
match = re.search(pattern, content, re.DOTALL)
if match:
    block = match.group(1)
    # Extract each entry
    entries = re.findall(r"\['(.*?)',\s*'(.*?)',\s*\['me'\s*=>\s*'(.*?)',\s*'need'\s*=>\s*'(.*?)'\],\s*\['me'\s*=>\s*'(.*?)',\s*'need'\s*=>\s*'(.*?)'\],\s*'(.*?)'\]", block)
    
    for entry in entries:
        en_title = entry[0]
        indo_me_current = entry[2]
        indo_need_current = entry[3]
        en_me = entry[4]
        en_need = entry[5]
        
        expected_me = en_to_indo.get(en_me, indo_me_current)
        expected_need = en_to_indo.get(en_need, indo_need_current)
        
        print(f"Role: {en_title}")
        if indo_me_current != expected_me:
            print(f"  ME  : '{indo_me_current}' -> '{expected_me}'")
        if indo_need_current != expected_need:
            print(f"  NEED: '{indo_need_current}' -> '{expected_need}'")
