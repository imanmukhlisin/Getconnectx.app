const fs = require("fs");
const rawData = JSON.parse(fs.readFileSync("scraper-[3-mei-2026].json", "utf-8"));
function formatToApiContract(apifyData) {
    if (!apifyData || apifyData.length === 0) return null;
    const profile = Array.isArray(apifyData) ? apifyData[0] : apifyData;
    const skillsItems = (profile.skills || []).slice(0, 10).map((skill, index) => ({ id: "sk_" + (index + 1), name: skill.name }));
    const highlightsItems = [];
    if (profile.experience && profile.experience.length > 0) {
        const exp = profile.experience[0];
        if (exp.position && exp.companyName) highlightsItems.push(exp.position + " at " + exp.companyName);
    }
    if (profile.education && profile.education.length > 0) {
        const edu = profile.education[0];
        if (edu.degree && edu.schoolName) highlightsItems.push(edu.degree + ", " + edu.schoolName);
    }
    const city = profile.location?.parsed?.city || "";
    const country = profile.location?.parsed?.country || "";
    const displayLoc = profile.location?.linkedinText || "";
    return {
        success: true,
        message: "Profile fetched successfully",
        data: {
            id: profile.id || "usr_default",
            teamId: null,
            profileType: "builder",
            name: `${profile.firstName || ""} ${profile.lastName || ""}`.trim(),
            headline: profile.headline || "",
            photoUrl: profile.profilePicture?.url || profile.photo || null,
            location: { city, country, display: displayLoc },
            stats: { connections: profile.connectionsCount || 0, teamsJoined: 0, matches: 0 },
            badges: [],
            sections: {
                about: { kind: "personalDescription", title: "Description", value: profile.about || "" },
                skills: { title: "Skills", items: skillsItems },
                highlights: { items: highlightsItems }
            },
            createdAt: profile.registeredAt || new Date().toISOString(),
            updatedAt: new Date().toISOString()
        }
    };
}
const formattedData = formatToApiContract(rawData);
fs.writeFileSync("scraper-[3-mei-2026].json", JSON.stringify(formattedData, null, 2));
console.log("File scraper-[3-mei-2026].json has been reformatted.");
