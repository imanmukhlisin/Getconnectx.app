const fs = require('fs');
require('dotenv').config();

const API_TOKEN = process.env.APIFY_API_TOKEN;
const ACTOR_ID = 'harvestapi~linkedin-profile-scraper';

// Endpoint to run the actor synchronously and get dataset items
const API_URL = `https://api.apify.com/v2/acts/${ACTOR_ID}/run-sync-get-dataset-items?token=${API_TOKEN}`;

async function runScraper() {
    console.log('Memulai scraper LinkedIn melalui Apify API...');

    // Payload input untuk scraper (sesuaikan dengan format input dari harvestapi/linkedin-profile-scraper)
    // Biasanya menerima array URL profile LinkedIn yang akan di-scrape
    const inputPayload = {
        "urls": [
            "https://www.linkedin.com/in/ananda-dimas-octavian-prasetyo/" // Ganti dengan URL profil target
        ]
    };

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(inputPayload)
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
        }

        // Apify run-sync-get-dataset-items mengembalikan hasil scraping (dataset) secara langsung dalam bentuk JSON
        const data = await response.json();
        console.log('\n✅ Scraping berhasil! Berikut adalah hasilnya:\n');
        console.log(JSON.stringify(data, null, 2));

        // Menyimpan data ke dalam file JSON secara dinamis
        const date = new Date();
        const months = ["januari", "februari", "maret", "april", "mei", "juni", "juli", "agustus", "september", "oktober", "november", "desember"];
        const formattedDate = `${date.getDate()}-${months[date.getMonth()]}-${date.getFullYear()}`;
        const fileName = `scraper-[${formattedDate}].json`;
        
        fs.writeFileSync(fileName, JSON.stringify(data, null, 2));
        console.log(`\n💾 Hasil scraping berhasil disimpan ke dalam file: ${fileName}\n`);

    } catch (error) {
        console.error('❌ Terjadi kesalahan saat menjalankan scraper:', error.message);
    }
}

runScraper();
