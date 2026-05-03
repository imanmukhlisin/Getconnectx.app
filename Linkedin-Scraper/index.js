const fs = require('fs');
require('dotenv').config();

const API_TOKEN = process.env.APIFY_API_TOKEN;
const ACTOR_ID = 'harvestapi~linkedin-profile-scraper';

// Endpoint to run the actor synchronously and get dataset items
const API_URL = `https://api.apify.com/v2/acts/${ACTOR_ID}/run-sync-get-dataset-items?token=${API_TOKEN}`;

function formatToApiContract(apifyData) {
    if (!apifyData || apifyData.length === 0) return null;
    
    const profile = apifyData[0];
    
    // Extrak skills
    const skillsItems = (profile.skills || []).slice(0, 10).map((skill, index) => ({
        id: `sk_${index + 1}`,
        name: skill.name
    }));

    // Extrak highlights dari experience / education
    const highlightsItems = [];
    if (profile.experience && profile.experience.length > 0) {
        const exp = profile.experience[0];
        if (exp.position && exp.companyName) {
            highlightsItems.push(`${exp.position} at ${exp.companyName}`);
        }
    }
    if (profile.education && profile.education.length > 0) {
        const edu = profile.education[0];
        if (edu.degree && edu.schoolName) {
            highlightsItems.push(`${edu.degree}, ${edu.schoolName}`);
        }
    }
    
    // Fallback location parsing
    const city = profile.location?.parsed?.city || "";
    const country = profile.location?.parsed?.country || "";
    const displayLoc = profile.location?.linkedinText || "";

    const formattedData = {
        success: true,
        message: "Profile fetched successfully",
        data: {
            id: profile.id || "usr_default",
            teamId: null,
            profileType: "builder",
            name: `${profile.firstName || ''} ${profile.lastName || ''}`.trim(),
            headline: profile.headline || "",
            photoUrl: profile.profilePicture?.url || profile.photo || null,
            location: {
                city: city,
                country: country,
                display: displayLoc
            },
            stats: {
                connections: profile.connectionsCount || 0,
                teamsJoined: 0,
                matches: 0
            },
            badges: [],
            sections: {
                about: {
                    kind: "personalDescription",
                    title: "Description",
                    value: profile.about || ""
                },
                skills: {
                    title: "Skills",
                    items: skillsItems
                },
                highlights: {
                    items: highlightsItems
                }
            },
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        }
    };
    
    return formattedData;
}

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
        const rawData = await response.json();
        
        // Memformat data raw dari Apify menjadi format API Contract
        const formattedData = formatToApiContract(rawData);
        
        console.log('\n✅ Scraping berhasil! Berikut adalah hasilnya:\n');
        console.log(JSON.stringify(formattedData, null, 2));

        // Menyimpan data ke dalam file JSON secara dinamis
        const date = new Date();
        const months = ["januari", "februari", "maret", "april", "mei", "juni", "juli", "agustus", "september", "oktober", "november", "desember"];
        const formattedDate = `${date.getDate()}-${months[date.getMonth()]}-${date.getFullYear()}`;
        const fileName = `scraper-[${formattedDate}].json`;
        
        fs.writeFileSync(fileName, JSON.stringify(formattedData, null, 2));
        console.log(`\n💾 Hasil scraping berhasil disimpan ke dalam file: ${fileName}\n`);

    } catch (error) {
        console.error('❌ Terjadi kesalahan saat menjalankan scraper:', error.message);
    }
}

runScraper();
