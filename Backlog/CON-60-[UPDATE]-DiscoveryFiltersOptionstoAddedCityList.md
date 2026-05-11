> ✅ **STATUS: IMPLEMENTED**

Backend update needed for CON-60: add city to discovery filter options and candidate generation filters.
This is the only new contract change from this note.

1. Filter Options Response

Endpoint:

```
GET /api/v1/discovery/filter-options?mode=finding_cofounder
```

Add data.city to the response:
```
{
  "success": true,
  "message": "Discovery filter options fetched successfully",
  "data": {
    "mode": "finding_cofounder",
    "city": {
      "id": "q_city",
      "type": "searchable_dropdown",
      "placeholder": "Search a city",
      "required": true,
      "meta": { "searchable": true },
      "options": [
        { "id": "opt_city_jakarta", "label": "Jakarta", "value": "jakarta", "group": "Indonesia" },
        { "id": "opt_city_bandung", "label": "Bandung", "value": "bandung", "group": "Indonesia" },
        { "id": "opt_city_singapore", "label": "Singapore", "value": "singapore", "group": "Singapore" },
        { "id": "opt_city_bangalore", "label": "Bangalore", "value": "bangalore", "group": "India" },
        { "id": "opt_city_hcmc", "label": "Ho Chi Minh City", "value": "hcmc", "group": "Vietnam" },
        { "id": "opt_city_dubai", "label": "Dubai", "value": "dubai", "group": "United Arab Emirates" }
      ]
    }
  }
}
```

Important:

Do not include a city question label like Where are you based?.

options[].value is the canonical value the frontend sends back.

options[].group is used by the frontend to group cities by country.

2. Generate Candidates Request

Endpoint:
```
POST /api/v1/discovery/cards
```
When a user selects a city, frontend sends it here:
```
{
  "context": {
    "mode": "finding_cofounder"
  },
  "filters": {
    "goalId": "goal_finding_cofounder",
    "locationAvailability": {
      "workArrangementIds": ["wa_remote"],
      "city": "jakarta"
    }
  },
  "pagination": {
    "limit": 10,
    "cursor": null
  }
}
```
Backend should validate filters.locationAvailability.city against the supported city option values for the selected mode.

---

## Backend Implementation Status
**Status: ✅ Done**

City catalog sudah include 200+ kota di:
- Indonesia (lengkap semua ibu kota provinsi + kota besar)
- Asia Tenggara (SG, MY, TH, VN, PH, KH, MM, TL)
- Asia Selatan, Asia Timur, Timur Tengah
- Eropa Barat/Utara/Selatan/Timur
- Amerika Utara & Latin, Afrika, Oseania
- `remote` (opsi "Mana Saja")