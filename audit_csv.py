import csv

rows = []
with open('ConnectX-Wordings.csv', encoding='utf-8') as f:
    reader = csv.reader(f)
    headers = next(reader)
    for row in reader:
        rows.append(row)

print('idx | context | type | EN | ID_revised')
print('-'*120)
for r in rows:
    if len(r) >= 5 and 'Onboarding' in r[1]:
        en = r[3].strip()
        id_rev = r[4].strip()
        print(f'{r[0]:>4} | {r[1]:<12} | {r[2]:<18} | {en[:55]:<57} | {id_rev}')
