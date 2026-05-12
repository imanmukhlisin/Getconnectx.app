import urllib.request
import csv
import json
import os

url = "https://raw.githubusercontent.com/datasets/world-cities/master/data/world-cities.csv"
print("Downloading world cities (approx 23,000 cities)...")
response = urllib.request.urlopen(url)
lines = [l.decode('utf-8') for l in response.readlines()]
reader = csv.reader(lines)
next(reader) # skip header

locations = []
# Tambahkan Remote sebagai pilihan pertama
locations.append({
    "label": "Remote (Mana Saja)",
    "value": "remote",
    "group": "Remote"
})

for row in reader:
    if len(row) >= 2:
        city = row[0]
        country = row[1]
        label = f"{city}, {country}"
        # Make a slug
        value = city.lower().replace(" ", "_").replace("'", "").replace("-", "_") + "_" + country.lower().replace(" ", "_").replace("'", "").replace("-", "_")
        locations.append({
            "label": label,
            "value": value,
            "group": country
        })

# Grouping logic agar Indonesia di atas, sisanya alphabet, dan Remote di bawah
def sort_key(x):
    group = x["group"]
    if group == "Indonesia":
        return (0, group, x["label"])
    elif group == "Remote":
        return (2, group, x["label"])
    else:
        return (1, group, x["label"])

locations.sort(key=sort_key)

os.makedirs("database/data", exist_ok=True)
with open("database/data/world_cities.json", "w", encoding="utf-8") as f:
    json.dump(locations, f, ensure_ascii=False, indent=2)

print(f"✅ Success! Saved {len(locations)} cities to database/data/world_cities.json")
