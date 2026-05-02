<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OnboardingSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('TRUNCATE TABLE onboarding_flows RESTART IDENTITY CASCADE');
        $now = Carbon::now();

        // ════════════════════════════════════════════════════════════════
        // MASTER DATA LISTS
        // ════════════════════════════════════════════════════════════════

        $masterRoles = [
            'Founders & Leadership' => [
                'Founder',
                'Co-Founder',
                'CEO',
                'COO',
                'CTO',
                'CPO (Chief Product Officer)',
                'CMO (Chief Marketing Officer)',
                'CFO',
                'Managing Director',
                'General Manager',
            ],
            'Engineering - Software' => [
                'Frontend Engineer',
                'Backend Engineer',
                'Full Stack Engineer',
                'Mobile Engineer (iOS/Android)',
                'Web Developer',
            ],
            'Engineering - Specialized' => [
                'Machine Learning Engineer',
                'AI Engineer',
                'Prompt Engineer',
                'Data Engineer',
                'Embedded Engineer',
                'Systems Engineer',
                'DevOps Engineer',
                'Blockchain Engineer',
                'Security Engineer',
            ],
            'Engineering - Hardware' => [
                'Hardware Engineer',
                'Mechanical Engineer',
                'Electrical Engineer',
            ],
            'Product & Strategy' => [
                'Product Manager',
                'Product Owner',
                'Technical Product Manager',
                'Product Designer',
                'UX Researcher',
                'Business Analyst',
                'Strategy Associate',
            ],
            'Design & Creative' => [
                'UI Designer',
                'UX Designer',
                'UI/UX Designer',
                'Graphic Designer',
                'Brand Designer',
                'Motion Designer',
                '3D Designer',
                'Creative Director',
                'Content Designer',
            ],
            'Marketing & Growth' => [
                'Growth Marketer',
                'Digital Marketer',
                'Performance Marketer',
                'Social Media Manager',
                'Content Creator',
                'Content Strategist',
                'SEO Specialist',
                'Copywriter',
                'Brand Manager',
                'Community Manager',
                'Influencer Marketing Manager',
            ],
            'Sales & Business Dev' => [
                'Sales Executive',
                'Account Executive',
                'Business Development Manager',
                'Partnerships Manager',
                'Account Manager',
                'Customer Success Manager',
                'Revenue Operations',
            ],
            'Data & Analytics' => [
                'Data Analyst',
                'Data Scientist',
                'Business Intelligence Analyst',
                'Quantitative Analyst',
            ],
            'Operations' => [
                'Operations Manager',
                'Project Manager',
                'Program Manager',
                'Supply Chain Manager',
                'Logistics Manager',
            ],
            'Finance & Legal' => [
                'Financial Analyst',
                'Accountant',
                'Finance Manager',
                'Investment Analyst',
                'Venture Capital Associate',
                'Legal Counsel',
                'Compliance Officer',
            ],
            'People & HR' => [
                'HR Manager',
                'Talent Acquisition',
                'Recruiter',
                'People Operations',
                'HR Business Partner',
            ],
            'Web3 / Crypto' => [
                'Smart Contract Developer',
                'Web3 Developer',
                'Crypto Trader',
                'Tokenomics Analyst',
                'Community Lead (Web3)',
                'DAO Contributor',
            ],
            'Creator & Non-Traditional' => [
                'Creator / Influencer',
                'Indie Hacker',
                'No-Code Builder',
                'Freelancer',
                'Consultant',
                'Advisor / Mentor',
            ],
        ];

        $masterIndustries = [
            'Core Technology' => [
                'AI',
                'Analytics',
                'AR/VR',
                'Cloud Infrastructure',
                'Data Services',
                'DeepTech',
                'Developer Tools',
                'Generative Tech/AI',
                'IoT',
                'Robotics',
                'Security',
                'Semiconductors',
            ],
            'Software & Digital Products' => [
                'Enterprise',
                'Messaging',
                'Productivity Tools',
                'SaaS',
                'Sales & CRM',
                'SMB Software',
                'Social Networks',
            ],
            'Consumer & Marketplace' => [
                'Cosmetics',
                'Creator/Passion Economy',
                'Direct-to-Consumer (DTC)',
                'E-commerce',
                'Fashion',
                'Food and Beverage',
                'Marketplaces',
                'Retail',
            ],
            'Finance & Business Infrastructure' => [
                'FinTech',
                'Human Capital/HRTech',
                'Insurance',
                'LegalTech',
                'Payments',
            ],
            'Industry-Specific Solutions' => [
                'AgTech',
                'ClimateTech/CleanTech',
                'ConstructionTech',
                'Education',
                'EnergyTech',
                'GovTech',
                'Healthcare',
                'Logistics',
                'Manufacturing',
                'Medical Devices',
                'Pharmaceuticals',
                'Real Estate/PropTech',
                'Supply Chain Tech',
                'TransportationTech',
            ],
            'Media, Lifestyle & Experience' => [
                'Entertainment & Sports',
                'Gaming',
                'Lodging/Hospitality',
                'Media/Content',
                'Mental Health',
                'Parenting/Families',
                'Travel',
                'Wellness & Fitness',
            ],
            'Emerging & Future' => [
                'Future of Work',
                'Gig Economy',
                'Hardware',
                'Material Science',
                'Smart Cities/UrbanTech',
                'Social Impact',
                'Space',
                'Web3/Blockchain',
            ],
        ];

        $masterSkills = [
            'Engineering, IT & Technical' => [
                'React / Angular / Vue',
                'Node.js / Java / Python / Go',
                'API Design & Integration',
                'System Architecture',
                'Microservices',
                'AWS / GCP / Azure',
                'CI/CD & DevOps pipelines',
                'Kubernetes / Docker',
                'Database Design (SQL/NoSQL)',
                'Cybersecurity',
                'Smart Contracts (Solidity)',
            ],
            'Construction & Property' => [
                'AutoCAD / SketchUp / Revit',
                'Building Design & Planning',
                'Interior Styling & Space Planning',
                'Construction Management',
                'Cost Estimation & Budgeting',
                'Site Supervision',
                'Property Development Strategy',
            ],
            'F&B (Food & Beverage)' => [
                'Menu Development',
                'Food Costing',
                'Kitchen Operations',
                'Food Safety & Hygiene',
                'Supply Chain (ingredients sourcing)',
                'Restaurant Branding',
                'Customer Experience',
            ],
            'Design & Creative' => [
                'Figma / Adobe Suite',
                'Branding & Identity',
                'Prototyping',
                'Visual Design',
                'Motion Graphics',
                '3D Rendering',
                'Fashion Design & Production',
            ],
            'Marketing & Growth' => [
                'Paid Ads (Meta, Google, TikTok)',
                'SEO / SEM',
                'Copywriting',
                'Social Media Growth',
                'Influencer Marketing',
                'Email Marketing',
                'Analytics (GA, Mixpanel)',
                'Campaign Strategy',
            ],
            'Sales & Partnerships' => [
                'Lead Generation',
                'Sales Closing',
                'Negotiation',
                'CRM Tools',
                'B2B / B2C Sales',
                'Deal Structuring',
                'Client Relationship Management',
            ],
            'Operations & Supply Chain' => [
                'Process Optimization',
                'SOP Creation',
                'Inventory Management',
                'Logistics & Distribution',
                'Vendor Management',
                'Operational Scaling',
            ],
            'Finance & Legal' => [
                'Financial Modeling',
                'Fundraising',
                'Investor Relations',
                'Budgeting',
                'Accounting',
                'Legal Structuring',
                'Contracts & Compliance',
            ],
            'Data, AI & Analytics' => [
                'Data Analysis',
                'Python / R',
                'Machine Learning',
                'Data Visualization',
                'Predictive Analytics',
                'AI Model Development',
            ],
            'Media & Content' => [
                'Video Production',
                'Editing (Premiere, CapCut)',
                'Storytelling',
                'Content Strategy',
                'Social Media Content',
            ],
            'HR & People' => [
                'Hiring & Recruitment',
                'Talent Management',
                'Employer Branding',
                'HR Strategy',
                'Performance Management',
            ],
            'Emerging & Specialized' => [
                'Smart Contracts',
                'Automation Tools (Zapier, Make)',
                'No-Code Platforms',
                'Crypto / DeFi Systems',
            ],
        ];

        $masterCFTypes = [
            ['Technical Co-Founder', 'tech', 
                ['me' => 'Aku membangun produk & teknologi', 'need' => 'Engineering & arsitektur'], 
                ['me' => 'I build the product & tech', 'need' => 'Engineering & architecture'], 
                'cofounder_technical'],
            ['Product Co-Founder', 'product', 
                ['me' => 'Aku memimpin produk & desain', 'need' => 'Visi produk & desain'], 
                ['me' => 'I lead product & design', 'need' => 'Product vision & design'], 
                'cofounder_product'],
            ['Business Co-Founder', 'business', 
                ['me' => 'Aku menangani strategi & operasional', 'need' => 'Strategi & operasional'], 
                ['me' => 'I handle strategy & ops', 'need' => 'Strategy & operations'], 
                'cofounder_business'],
            ['Growth Co-Founder', 'growth', 
                ['me' => 'Aku menggerakkan marketing & growth', 'need' => 'Marketing & distribusi'], 
                ['me' => 'I drive marketing & growth', 'need' => 'Marketing & distribution'], 
                'cofounder_growth'],
            ['AI / Data Co-Founder', 'ai_data', 
                ['me' => 'Aku membangun AI, data & intelligence', 'need' => 'Sistem AI & data intelligence'], 
                ['me' => 'I build AI, data & intelligence', 'need' => 'AI systems & data intelligence'], 
                'cofounder_ai'],
            ['Operations Co-Founder', 'operations', 
                ['me' => 'Aku mengeksekusi & menskalakan operasional', 'need' => 'Eksekusi & scaling operasional'], 
                ['me' => 'I execute & scale operations', 'need' => 'Execution & scaling operations'], 
                'cofounder_operations'],
            ['Finance Co-Founder', 'finance', 
                ['me' => 'Aku mengelola fundraising & keuangan', 'need' => 'Fundraising & strategi keuangan'], 
                ['me' => 'I manage fundraising & finance', 'need' => 'Fundraising & financial strategy'], 
                'cofounder_finance'],
            ['Partnerships Co-Founder', 'partnerships', 
                ['me' => 'Aku membangun deal & partnership', 'need' => 'Partnership strategis & pertumbuhan bisnis'], 
                ['me' => 'I build deals & partnerships', 'need' => 'Strategic partnerships & business growth'], 
                'cofounder_partnerships'],
        ];

        $masterBizModels = [
            'Digital & Software' => [
                ['SaaS (Subscription software)', 'saas'],
                ['Marketplace (2-sided platform)', 'marketplace'],
                ['E-commerce (Online store)', 'ecommerce'],
                ['Direct-to-Consumer (DTC brand)', 'dtc'],
                ['Mobile App (freemium / paid)', 'mobile_app'],
                ['API / Infrastructure (B2B tech)', 'api_infra'],
            ],
            'Financial & Transactional' => [
                ['FinTech (payments, lending, etc.)', 'fintech'],
                ['Transaction Fees (per use / commission)', 'transaction_fees'],
                ['Brokerage / Commission-based', 'brokerage'],
                ['Subscription + Transaction Hybrid', 'sub_transaction_hybrid'],
            ],
            'Media & Attention' => [
                ['Advertising-based', 'advertising'],
                ['Content / Media Platform', 'content_media'],
                ['Creator Economy (subscriptions, tips, content)', 'creator_economy'],
            ],
            'Services & Offline' => [
                ['Service-based (agency, consulting)', 'service'],
                ['F&B (restaurant, cafe, cloud kitchen)', 'fnb'],
                ['Retail (offline / omnichannel)', 'retail'],
                ['Hospitality (hotel, lodging)', 'hospitality'],
                ['Events / Experiences', 'events'],
            ],
            'Asset-Heavy / Industry' => [
                ['Real Estate / Property', 'real_estate'],
                ['Construction / Infrastructure', 'construction'],
                ['Manufacturing', 'manufacturing'],
                ['Logistics / Supply Chain', 'logistics'],
                ['Energy / Climate', 'energy_climate'],
            ],
            'Emerging / Tech-Forward' => [
                ['Web3 / Blockchain', 'web3'],
                ['Token-based / Crypto economy', 'token_crypto'],
                ['AI-first product', 'ai_first'],
                ['DeepTech / R&D', 'deeptech'],
            ],
            'Hybrid / Other' => [
                ['Franchise Model', 'franchise'],
                ['Licensing', 'licensing'],
                ['Aggregator', 'aggregator'],
                ['Platform + Service hybrid', 'platform_service'],
            ],
        ];

        $locations = [
            // ── INDONESIA (Major & Secondary Cities) ──
            ['Jakarta, Indonesia', 'jakarta', 'Indonesia'],
            ['Surabaya, Indonesia', 'surabaya', 'Indonesia'],
            ['Bandung, Indonesia', 'bandung', 'Indonesia'],
            ['Medan, Indonesia', 'medan', 'Indonesia'],
            ['Semarang, Indonesia', 'semarang', 'Indonesia'],
            ['Makassar, Indonesia', 'makassar', 'Indonesia'],
            ['Palembang, Indonesia', 'palembang', 'Indonesia'],
            ['Tangerang, Indonesia', 'tangerang', 'Indonesia'],
            ['Depok, Indonesia', 'depok', 'Indonesia'],
            ['Bekasi, Indonesia', 'bekasi', 'Indonesia'],
            ['Bogor, Indonesia', 'bogor', 'Indonesia'],
            ['Batam, Indonesia', 'batam', 'Indonesia'],
            ['Pekanbaru, Indonesia', 'pekanbaru', 'Indonesia'],
            ['Bandar Lampung, Indonesia', 'bandar_lampung', 'Indonesia'],
            ['Padang, Indonesia', 'padang', 'Indonesia'],
            ['Denpasar (Bali), Indonesia', 'denpasar', 'Indonesia'],
            ['Malang, Indonesia', 'malang', 'Indonesia'],
            ['Samarinda, Indonesia', 'samarinda', 'Indonesia'],
            ['Balikpapan, Indonesia', 'balikpapan', 'Indonesia'],
            ['Banjarmasin, Indonesia', 'banjarmasin', 'Indonesia'],
            ['Yogyakarta, Indonesia', 'yogyakarta', 'Indonesia'],
            ['Surakarta (Solo), Indonesia', 'surakarta', 'Indonesia'],
            ['Pontianak, Indonesia', 'pontianak', 'Indonesia'],
            ['Manado, Indonesia', 'manado', 'Indonesia'],
            ['Mataram, Indonesia', 'mataram', 'Indonesia'],
            ['Kupang, Indonesia', 'kupang', 'Indonesia'],
            ['Jayapura, Indonesia', 'jayapura', 'Indonesia'],
            ['Ambon, Indonesia', 'ambon', 'Indonesia'],
            ['Bengkulu, Indonesia', 'bengkulu', 'Indonesia'],
            ['Jambi, Indonesia', 'jambi', 'Indonesia'],
            ['Palu, Indonesia', 'palu', 'Indonesia'],
            ['Kendari, Indonesia', 'kendari', 'Indonesia'],
            ['Gorontalo, Indonesia', 'gorontalo', 'Indonesia'],
            ['Pangkal Pinang, Indonesia', 'pangkal_pinang', 'Indonesia'],
            ['Tanjung Pinang, Indonesia', 'tanjung_pinang', 'Indonesia'],
            ['Banda Aceh, Indonesia', 'banda_aceh', 'Indonesia'],
            ['Serang, Indonesia', 'serang', 'Indonesia'],
            ['Palangka Raya, Indonesia', 'palangka_raya', 'Indonesia'],
            ['Tanjung Selor, Indonesia', 'tanjung_selor', 'Indonesia'],
            ['Mamuju, Indonesia', 'mamuju', 'Indonesia'],
            ['Ternate, Indonesia', 'ternate', 'Indonesia'],
            ['Manokwari, Indonesia', 'manokwari', 'Indonesia'],
            // Java additional
            ['Cirebon, Indonesia', 'cirebon', 'Indonesia'],
            ['Sukabumi, Indonesia', 'sukabumi', 'Indonesia'],
            ['Tasikmalaya, Indonesia', 'tasikmalaya', 'Indonesia'],
            ['Garut, Indonesia', 'garut', 'Indonesia'],
            ['Purwokerto, Indonesia', 'purwokerto', 'Indonesia'],
            ['Cilacap, Indonesia', 'cilacap', 'Indonesia'],
            ['Magelang, Indonesia', 'magelang', 'Indonesia'],
            ['Salatiga, Indonesia', 'salatiga', 'Indonesia'],
            ['Kudus, Indonesia', 'kudus', 'Indonesia'],
            ['Tegal, Indonesia', 'tegal', 'Indonesia'],
            ['Pekalongan, Indonesia', 'pekalongan', 'Indonesia'],
            ['Madiun, Indonesia', 'madiun', 'Indonesia'],
            ['Kediri, Indonesia', 'kediri', 'Indonesia'],
            ['Jember, Indonesia', 'jember', 'Indonesia'],
            ['Banyuwangi, Indonesia', 'banyuwangi', 'Indonesia'],
            ['Gresik, Indonesia', 'gresik', 'Indonesia'],
            ['Sidoarjo, Indonesia', 'sidoarjo', 'Indonesia'],
            ['Mojokerto, Indonesia', 'mojokerto', 'Indonesia'],
            ['Blitar, Indonesia', 'blitar', 'Indonesia'],
            ['Probolinggo, Indonesia', 'probolinggo', 'Indonesia'],
            ['Pasuruan, Indonesia', 'pasuruan', 'Indonesia'],
            ['Batu, Indonesia', 'batu', 'Indonesia'],
            ['Cikarang, Indonesia', 'cikarang', 'Indonesia'],
            ['Karawang, Indonesia', 'karawang', 'Indonesia'],
            // Sumatra additional
            ['Binjai, Indonesia', 'binjai', 'Indonesia'],
            ['Pematangsiantar, Indonesia', 'pematangsiantar', 'Indonesia'],
            ['Deli Serdang, Indonesia', 'deli_serdang', 'Indonesia'],
            ['Bukittinggi, Indonesia', 'bukittinggi', 'Indonesia'],
            ['Payakumbuh, Indonesia', 'payakumbuh', 'Indonesia'],
            ['Dumai, Indonesia', 'dumai', 'Indonesia'],
            ['Metro, Indonesia', 'metro', 'Indonesia'],
            ['Lubuklinggau, Indonesia', 'lubuklinggau', 'Indonesia'],
            ['Prabumulih, Indonesia', 'prabumulih', 'Indonesia'],
            ['Lhokseumawe, Indonesia', 'lhokseumawe', 'Indonesia'],
            ['Langsa, Indonesia', 'langsa', 'Indonesia'],
            ['Meulaboh, Indonesia', 'meulaboh', 'Indonesia'],
            // Kalimantan additional
            ['Bontang, Indonesia', 'bontang', 'Indonesia'],
            ['Tarakan, Indonesia', 'tarakan', 'Indonesia'],
            ['Singkawang, Indonesia', 'singkawang', 'Indonesia'],
            ['Banjarbaru, Indonesia', 'banjarbaru', 'Indonesia'],
            ['Sampit, Indonesia', 'sampit', 'Indonesia'],
            ['Pangkalan Bun, Indonesia', 'pangkalan_bun', 'Indonesia'],
            // Sulawesi additional
            ['Bitung, Indonesia', 'bitung', 'Indonesia'],
            ['Tomohon, Indonesia', 'tomohon', 'Indonesia'],
            ['Kotamobagu, Indonesia', 'kotamobagu', 'Indonesia'],
            ['Parepare, Indonesia', 'parepare', 'Indonesia'],
            ['Palopo, Indonesia', 'palopo', 'Indonesia'],
            ['Baubau, Indonesia', 'baubau', 'Indonesia'],
            // Others additional
            ['Sorong, Indonesia', 'sorong', 'Indonesia'],
            ['Merauke, Indonesia', 'merauke', 'Indonesia'],
            ['Timika, Indonesia', 'timika', 'Indonesia'],
            ['Biak, Indonesia', 'biak', 'Indonesia'],
            ['Tual, Indonesia', 'tual', 'Indonesia'],
            ['Bima, Indonesia', 'bima', 'Indonesia'],
            ['Sumbawa Besar, Indonesia', 'sumbawa_besar', 'Indonesia'],
            ['Labuan Bajo, Indonesia', 'labuan_bajo', 'Indonesia'],

            // ── ASIA TENGGARA ──
            ['Singapore', 'singapore', 'Asia Tenggara'],
            ['Kuala Lumpur, Malaysia', 'kuala_lumpur', 'Asia Tenggara'],
            ['Penang (George Town), Malaysia', 'penang', 'Asia Tenggara'],
            ['Johor Bahru, Malaysia', 'johor_bahru', 'Asia Tenggara'],
            ['Ipoh, Malaysia', 'ipoh', 'Asia Tenggara'],
            ['Kuching, Malaysia', 'kuching', 'Asia Tenggara'],
            ['Kota Kinabalu, Malaysia', 'kota_kinabalu', 'Asia Tenggara'],
            ['Melaka, Malaysia', 'melaka', 'Asia Tenggara'],
            ['Shah Alam, Malaysia', 'shah_alam', 'Asia Tenggara'],
            ['Petaling Jaya, Malaysia', 'petaling_jaya', 'Asia Tenggara'],
            ['Cyberjaya, Malaysia', 'cyberjaya', 'Asia Tenggara'],
            ['Bangkok, Thailand', 'bangkok', 'Asia Tenggara'],
            ['Chiang Mai, Thailand', 'chiang_mai', 'Asia Tenggara'],
            ['Phuket, Thailand', 'phuket', 'Asia Tenggara'],
            ['Pattaya, Thailand', 'pattaya', 'Asia Tenggara'],
            ['Khon Kaen, Thailand', 'khon_kaen', 'Asia Tenggara'],
            ['Hat Yai, Thailand', 'hat_yai', 'Asia Tenggara'],
            ['Nakhon Ratchasima, Thailand', 'nakhon_ratchasima', 'Asia Tenggara'],
            ['Ho Chi Minh City, Vietnam', 'ho_chi_minh', 'Asia Tenggara'],
            ['Hanoi, Vietnam', 'hanoi', 'Asia Tenggara'],
            ['Da Nang, Vietnam', 'da_nang', 'Asia Tenggara'],
            ['Hai Phong, Vietnam', 'hai_phong', 'Asia Tenggara'],
            ['Can Tho, Vietnam', 'can_tho', 'Asia Tenggara'],
            ['Nha Trang, Vietnam', 'nha_trang', 'Asia Tenggara'],
            ['Hue, Vietnam', 'hue', 'Asia Tenggara'],
            ['Manila, Philippines', 'manila', 'Asia Tenggara'],
            ['Cebu City, Philippines', 'cebu', 'Asia Tenggara'],
            ['Davao, Philippines', 'davao', 'Asia Tenggara'],
            ['Quezon City, Philippines', 'quezon_city', 'Asia Tenggara'],
            ['Makati, Philippines', 'makati', 'Asia Tenggara'],
            ['Taguig (BGC), Philippines', 'taguig', 'Asia Tenggara'],
            ['Iloilo, Philippines', 'iloilo', 'Asia Tenggara'],
            ['Phnom Penh, Cambodia', 'phnom_penh', 'Asia Tenggara'],
            ['Siem Reap, Cambodia', 'siem_reap', 'Asia Tenggara'],
            ['Vientiane, Laos', 'vientiane', 'Asia Tenggara'],
            ['Luang Prabang, Laos', 'luang_prabang', 'Asia Tenggara'],
            ['Yangon, Myanmar', 'yangon', 'Asia Tenggara'],
            ['Mandalay, Myanmar', 'mandalay', 'Asia Tenggara'],
            ['Bandar Seri Begawan, Brunei', 'brunei', 'Asia Tenggara'],
            ['Dili, Timor-Leste', 'dili', 'Asia Tenggara'],

            // ── ASIA SELATAN ──
            ['Bangalore, India', 'bangalore', 'Asia Selatan'],
            ['Mumbai, India', 'mumbai', 'Asia Selatan'],
            ['Delhi / NCR, India', 'delhi', 'Asia Selatan'],
            ['Hyderabad, India', 'hyderabad', 'Asia Selatan'],
            ['Chennai, India', 'chennai', 'Asia Selatan'],
            ['Pune, India', 'pune', 'Asia Selatan'],
            ['Kolkata, India', 'kolkata', 'Asia Selatan'],
            ['Ahmedabad, India', 'ahmedabad', 'Asia Selatan'],
            ['Jaipur, India', 'jaipur', 'Asia Selatan'],
            ['Lucknow, India', 'lucknow', 'Asia Selatan'],
            ['Chandigarh, India', 'chandigarh', 'Asia Selatan'],
            ['Thiruvananthapuram, India', 'thiruvananthapuram', 'Asia Selatan'],
            ['Kochi, India', 'kochi', 'Asia Selatan'],
            ['Indore, India', 'indore', 'Asia Selatan'],
            ['Nagpur, India', 'nagpur', 'Asia Selatan'],
            ['Coimbatore, India', 'coimbatore', 'Asia Selatan'],
            ['Visakhapatnam, India', 'visakhapatnam', 'Asia Selatan'],
            ['Bhopal, India', 'bhopal', 'Asia Selatan'],
            ['Karachi, Pakistan', 'karachi', 'Asia Selatan'],
            ['Lahore, Pakistan', 'lahore', 'Asia Selatan'],
            ['Islamabad, Pakistan', 'islamabad', 'Asia Selatan'],
            ['Rawalpindi, Pakistan', 'rawalpindi', 'Asia Selatan'],
            ['Faisalabad, Pakistan', 'faisalabad', 'Asia Selatan'],
            ['Colombo, Sri Lanka', 'colombo', 'Asia Selatan'],
            ['Kandy, Sri Lanka', 'kandy', 'Asia Selatan'],
            ['Dhaka, Bangladesh', 'dhaka', 'Asia Selatan'],
            ['Chittagong, Bangladesh', 'chittagong', 'Asia Selatan'],
            ['Kathmandu, Nepal', 'kathmandu', 'Asia Selatan'],
            ['Pokhara, Nepal', 'pokhara', 'Asia Selatan'],

            // ── ASIA TIMUR ──
            ['Tokyo, Japan', 'tokyo', 'Asia Timur'],
            ['Osaka, Japan', 'osaka', 'Asia Timur'],
            ['Kyoto, Japan', 'kyoto', 'Asia Timur'],
            ['Nagoya, Japan', 'nagoya', 'Asia Timur'],
            ['Fukuoka, Japan', 'fukuoka', 'Asia Timur'],
            ['Yokohama, Japan', 'yokohama', 'Asia Timur'],
            ['Sapporo, Japan', 'sapporo', 'Asia Timur'],
            ['Kobe, Japan', 'kobe', 'Asia Timur'],
            ['Seoul, South Korea', 'seoul', 'Asia Timur'],
            ['Busan, South Korea', 'busan', 'Asia Timur'],
            ['Incheon, South Korea', 'incheon', 'Asia Timur'],
            ['Daejeon, South Korea', 'daejeon', 'Asia Timur'],
            ['Daegu, South Korea', 'daegu', 'Asia Timur'],
            ['Pangyo (Seongnam), South Korea', 'pangyo', 'Asia Timur'],
            ['Beijing, China', 'beijing', 'Asia Timur'],
            ['Shanghai, China', 'shanghai', 'Asia Timur'],
            ['Shenzhen, China', 'shenzhen', 'Asia Timur'],
            ['Guangzhou, China', 'guangzhou', 'Asia Timur'],
            ['Hangzhou, China', 'hangzhou', 'Asia Timur'],
            ['Chengdu, China', 'chengdu', 'Asia Timur'],
            ['Wuhan, China', 'wuhan', 'Asia Timur'],
            ['Nanjing, China', 'nanjing', 'Asia Timur'],
            ['Chongqing, China', 'chongqing', 'Asia Timur'],
            ['Suzhou, China', 'suzhou_cn', 'Asia Timur'],
            ['Xi\'an, China', 'xian', 'Asia Timur'],
            ['Hong Kong', 'hong_kong', 'Asia Timur'],
            ['Macau', 'macau', 'Asia Timur'],
            ['Taipei, Taiwan', 'taipei', 'Asia Timur'],
            ['Taichung, Taiwan', 'taichung', 'Asia Timur'],
            ['Kaohsiung, Taiwan', 'kaohsiung', 'Asia Timur'],
            ['Hsinchu, Taiwan', 'hsinchu', 'Asia Timur'],
            ['Ulaanbaatar, Mongolia', 'ulaanbaatar', 'Asia Timur'],

            // ── TIMUR TENGAH ──
            ['Dubai, UAE', 'dubai', 'Timur Tengah'],
            ['Abu Dhabi, UAE', 'abu_dhabi', 'Timur Tengah'],
            ['Sharjah, UAE', 'sharjah', 'Timur Tengah'],
            ['Riyadh, Saudi Arabia', 'riyadh', 'Timur Tengah'],
            ['Jeddah, Saudi Arabia', 'jeddah', 'Timur Tengah'],
            ['Dammam, Saudi Arabia', 'dammam', 'Timur Tengah'],
            ['Mecca, Saudi Arabia', 'mecca', 'Timur Tengah'],
            ['NEOM, Saudi Arabia', 'neom', 'Timur Tengah'],
            ['Tel Aviv, Israel', 'tel_aviv', 'Timur Tengah'],
            ['Jerusalem, Israel', 'jerusalem', 'Timur Tengah'],
            ['Haifa, Israel', 'haifa', 'Timur Tengah'],
            ['Amman, Jordan', 'amman', 'Timur Tengah'],
            ['Doha, Qatar', 'doha', 'Timur Tengah'],
            ['Manama, Bahrain', 'manama', 'Timur Tengah'],
            ['Muscat, Oman', 'muscat', 'Timur Tengah'],
            ['Kuwait City, Kuwait', 'kuwait_city', 'Timur Tengah'],
            ['Beirut, Lebanon', 'beirut', 'Timur Tengah'],
            ['Istanbul, Turkey', 'istanbul', 'Timur Tengah'],
            ['Ankara, Turkey', 'ankara', 'Timur Tengah'],
            ['Izmir, Turkey', 'izmir', 'Timur Tengah'],
            ['Tehran, Iran', 'tehran', 'Timur Tengah'],
            ['Baghdad, Iraq', 'baghdad', 'Timur Tengah'],
            ['Erbil, Iraq', 'erbil', 'Timur Tengah'],

            // ── EROPA BARAT ──
            ['London, UK', 'london', 'Eropa Barat'],
            ['Manchester, UK', 'manchester', 'Eropa Barat'],
            ['Birmingham, UK', 'birmingham', 'Eropa Barat'],
            ['Edinburgh, UK', 'edinburgh', 'Eropa Barat'],
            ['Bristol, UK', 'bristol', 'Eropa Barat'],
            ['Cambridge, UK', 'cambridge', 'Eropa Barat'],
            ['Oxford, UK', 'oxford', 'Eropa Barat'],
            ['Leeds, UK', 'leeds', 'Eropa Barat'],
            ['Berlin, Germany', 'berlin', 'Eropa Barat'],
            ['Munich, Germany', 'munich', 'Eropa Barat'],
            ['Hamburg, Germany', 'hamburg', 'Eropa Barat'],
            ['Frankfurt, Germany', 'frankfurt', 'Eropa Barat'],
            ['Cologne, Germany', 'cologne', 'Eropa Barat'],
            ['Stuttgart, Germany', 'stuttgart', 'Eropa Barat'],
            ['Düsseldorf, Germany', 'dusseldorf', 'Eropa Barat'],
            ['Amsterdam, Netherlands', 'amsterdam', 'Eropa Barat'],
            ['Rotterdam, Netherlands', 'rotterdam', 'Eropa Barat'],
            ['Eindhoven, Netherlands', 'eindhoven', 'Eropa Barat'],
            ['The Hague, Netherlands', 'the_hague', 'Eropa Barat'],
            ['Utrecht, Netherlands', 'utrecht', 'Eropa Barat'],
            ['Paris, France', 'paris', 'Eropa Barat'],
            ['Lyon, France', 'lyon', 'Eropa Barat'],
            ['Marseille, France', 'marseille', 'Eropa Barat'],
            ['Toulouse, France', 'toulouse', 'Eropa Barat'],
            ['Nice, France', 'nice', 'Eropa Barat'],
            ['Bordeaux, France', 'bordeaux', 'Eropa Barat'],
            ['Brussels, Belgium', 'brussels', 'Eropa Barat'],
            ['Antwerp, Belgium', 'antwerp', 'Eropa Barat'],
            ['Luxembourg City, Luxembourg', 'luxembourg', 'Eropa Barat'],
            ['Zurich, Switzerland', 'zurich', 'Eropa Barat'],
            ['Geneva, Switzerland', 'geneva', 'Eropa Barat'],
            ['Basel, Switzerland', 'basel', 'Eropa Barat'],
            ['Dublin, Ireland', 'dublin', 'Eropa Barat'],
            ['Cork, Ireland', 'cork', 'Eropa Barat'],
            ['Vienna, Austria', 'vienna', 'Eropa Barat'],
            ['Graz, Austria', 'graz', 'Eropa Barat'],

            // ── EROPA UTARA ──
            ['Stockholm, Sweden', 'stockholm', 'Eropa Utara'],
            ['Gothenburg, Sweden', 'gothenburg', 'Eropa Utara'],
            ['Malmö, Sweden', 'malmo', 'Eropa Utara'],
            ['Helsinki, Finland', 'helsinki', 'Eropa Utara'],
            ['Espoo, Finland', 'espoo', 'Eropa Utara'],
            ['Tampere, Finland', 'tampere', 'Eropa Utara'],
            ['Copenhagen, Denmark', 'copenhagen', 'Eropa Utara'],
            ['Aarhus, Denmark', 'aarhus', 'Eropa Utara'],
            ['Oslo, Norway', 'oslo', 'Eropa Utara'],
            ['Bergen, Norway', 'bergen_no', 'Eropa Utara'],
            ['Tallinn, Estonia', 'tallinn', 'Eropa Utara'],
            ['Riga, Latvia', 'riga', 'Eropa Utara'],
            ['Vilnius, Lithuania', 'vilnius', 'Eropa Utara'],
            ['Reykjavik, Iceland', 'reykjavik', 'Eropa Utara'],

            // ── EROPA SELATAN ──
            ['Lisbon, Portugal', 'lisbon', 'Eropa Selatan'],
            ['Porto, Portugal', 'porto', 'Eropa Selatan'],
            ['Barcelona, Spain', 'barcelona', 'Eropa Selatan'],
            ['Madrid, Spain', 'madrid', 'Eropa Selatan'],
            ['Valencia, Spain', 'valencia', 'Eropa Selatan'],
            ['Seville, Spain', 'seville', 'Eropa Selatan'],
            ['Malaga, Spain', 'malaga', 'Eropa Selatan'],
            ['Bilbao, Spain', 'bilbao', 'Eropa Selatan'],
            ['Rome, Italy', 'rome', 'Eropa Selatan'],
            ['Milan, Italy', 'milan', 'Eropa Selatan'],
            ['Turin, Italy', 'turin', 'Eropa Selatan'],
            ['Florence, Italy', 'florence', 'Eropa Selatan'],
            ['Bologna, Italy', 'bologna', 'Eropa Selatan'],
            ['Naples, Italy', 'naples', 'Eropa Selatan'],
            ['Athens, Greece', 'athens', 'Eropa Selatan'],
            ['Thessaloniki, Greece', 'thessaloniki', 'Eropa Selatan'],

            // ── EROPA TIMUR ──
            ['Warsaw, Poland', 'warsaw', 'Eropa Timur'],
            ['Kraków, Poland', 'krakow', 'Eropa Timur'],
            ['Wrocław, Poland', 'wroclaw', 'Eropa Timur'],
            ['Gdańsk, Poland', 'gdansk', 'Eropa Timur'],
            ['Prague, Czech Republic', 'prague', 'Eropa Timur'],
            ['Brno, Czech Republic', 'brno', 'Eropa Timur'],
            ['Budapest, Hungary', 'budapest', 'Eropa Timur'],
            ['Bucharest, Romania', 'bucharest', 'Eropa Timur'],
            ['Cluj-Napoca, Romania', 'cluj_napoca', 'Eropa Timur'],
            ['Sofia, Bulgaria', 'sofia', 'Eropa Timur'],
            ['Belgrade, Serbia', 'belgrade', 'Eropa Timur'],
            ['Novi Sad, Serbia', 'novi_sad', 'Eropa Timur'],
            ['Zagreb, Croatia', 'zagreb', 'Eropa Timur'],
            ['Ljubljana, Slovenia', 'ljubljana', 'Eropa Timur'],
            ['Bratislava, Slovakia', 'bratislava', 'Eropa Timur'],
            ['Kyiv, Ukraine', 'kyiv', 'Eropa Timur'],
            ['Lviv, Ukraine', 'lviv', 'Eropa Timur'],
            ['Minsk, Belarus', 'minsk', 'Eropa Timur'],
            ['Tbilisi, Georgia', 'tbilisi', 'Eropa Timur'],
            ['Yerevan, Armenia', 'yerevan', 'Eropa Timur'],

            // ── AMERIKA UTARA ──
            ['San Francisco Bay Area, USA', 'san_francisco', 'Amerika Utara'],
            ['New York, USA', 'new_york', 'Amerika Utara'],
            ['Los Angeles, USA', 'los_angeles', 'Amerika Utara'],
            ['Austin, USA', 'austin', 'Amerika Utara'],
            ['Seattle, USA', 'seattle', 'Amerika Utara'],
            ['Boston, USA', 'boston', 'Amerika Utara'],
            ['Chicago, USA', 'chicago', 'Amerika Utara'],
            ['Miami, USA', 'miami', 'Amerika Utara'],
            ['Denver, USA', 'denver', 'Amerika Utara'],
            ['Atlanta, USA', 'atlanta', 'Amerika Utara'],
            ['Dallas, USA', 'dallas', 'Amerika Utara'],
            ['Houston, USA', 'houston', 'Amerika Utara'],
            ['Washington D.C., USA', 'washington_dc', 'Amerika Utara'],
            ['San Diego, USA', 'san_diego', 'Amerika Utara'],
            ['Portland, USA', 'portland', 'Amerika Utara'],
            ['Phoenix, USA', 'phoenix', 'Amerika Utara'],
            ['Minneapolis, USA', 'minneapolis', 'Amerika Utara'],
            ['Nashville, USA', 'nashville', 'Amerika Utara'],
            ['Raleigh-Durham, USA', 'raleigh', 'Amerika Utara'],
            ['Salt Lake City, USA', 'salt_lake_city', 'Amerika Utara'],
            ['Toronto, Canada', 'toronto', 'Amerika Utara'],
            ['Vancouver, Canada', 'vancouver', 'Amerika Utara'],
            ['Montreal, Canada', 'montreal', 'Amerika Utara'],
            ['Ottawa, Canada', 'ottawa', 'Amerika Utara'],
            ['Calgary, Canada', 'calgary', 'Amerika Utara'],
            ['Edmonton, Canada', 'edmonton', 'Amerika Utara'],
            ['Waterloo, Canada', 'waterloo', 'Amerika Utara'],

            // ── AMERIKA LATIN ──
            ['São Paulo, Brazil', 'sao_paulo', 'Amerika Latin'],
            ['Rio de Janeiro, Brazil', 'rio_de_janeiro', 'Amerika Latin'],
            ['Belo Horizonte, Brazil', 'belo_horizonte', 'Amerika Latin'],
            ['Curitiba, Brazil', 'curitiba', 'Amerika Latin'],
            ['Porto Alegre, Brazil', 'porto_alegre', 'Amerika Latin'],
            ['Florianópolis, Brazil', 'florianopolis', 'Amerika Latin'],
            ['Recife, Brazil', 'recife', 'Amerika Latin'],
            ['Brasília, Brazil', 'brasilia', 'Amerika Latin'],
            ['Mexico City, Mexico', 'mexico_city', 'Amerika Latin'],
            ['Monterrey, Mexico', 'monterrey', 'Amerika Latin'],
            ['Guadalajara, Mexico', 'guadalajara', 'Amerika Latin'],
            ['Querétaro, Mexico', 'queretaro', 'Amerika Latin'],
            ['Puebla, Mexico', 'puebla', 'Amerika Latin'],
            ['Buenos Aires, Argentina', 'buenos_aires', 'Amerika Latin'],
            ['Córdoba, Argentina', 'cordoba_ar', 'Amerika Latin'],
            ['Rosario, Argentina', 'rosario', 'Amerika Latin'],
            ['Bogotá, Colombia', 'bogota', 'Amerika Latin'],
            ['Medellín, Colombia', 'medellin', 'Amerika Latin'],
            ['Cali, Colombia', 'cali', 'Amerika Latin'],
            ['Barranquilla, Colombia', 'barranquilla', 'Amerika Latin'],
            ['Santiago, Chile', 'santiago', 'Amerika Latin'],
            ['Valparaíso, Chile', 'valparaiso', 'Amerika Latin'],
            ['Lima, Peru', 'lima', 'Amerika Latin'],
            ['Arequipa, Peru', 'arequipa', 'Amerika Latin'],
            ['Quito, Ecuador', 'quito', 'Amerika Latin'],
            ['Guayaquil, Ecuador', 'guayaquil', 'Amerika Latin'],
            ['Montevideo, Uruguay', 'montevideo', 'Amerika Latin'],
            ['Panama City, Panama', 'panama_city', 'Amerika Latin'],
            ['San José, Costa Rica', 'san_jose_cr', 'Amerika Latin'],
            ['Santo Domingo, Dominican Republic', 'santo_domingo', 'Amerika Latin'],

            // ── AFRIKA ──
            ['Lagos, Nigeria', 'lagos', 'Afrika'],
            ['Abuja, Nigeria', 'abuja', 'Afrika'],
            ['Port Harcourt, Nigeria', 'port_harcourt', 'Afrika'],
            ['Ibadan, Nigeria', 'ibadan', 'Afrika'],
            ['Nairobi, Kenya', 'nairobi', 'Afrika'],
            ['Mombasa, Kenya', 'mombasa', 'Afrika'],
            ['Cairo, Egypt', 'cairo', 'Afrika'],
            ['Alexandria, Egypt', 'alexandria', 'Afrika'],
            ['Cape Town, South Africa', 'cape_town', 'Afrika'],
            ['Johannesburg, South Africa', 'johannesburg', 'Afrika'],
            ['Durban, South Africa', 'durban', 'Afrika'],
            ['Pretoria, South Africa', 'pretoria', 'Afrika'],
            ['Accra, Ghana', 'accra', 'Afrika'],
            ['Kumasi, Ghana', 'kumasi', 'Afrika'],
            ['Kigali, Rwanda', 'kigali', 'Afrika'],
            ['Casablanca, Morocco', 'casablanca', 'Afrika'],
            ['Rabat, Morocco', 'rabat', 'Afrika'],
            ['Addis Ababa, Ethiopia', 'addis_ababa', 'Afrika'],
            ['Dar es Salaam, Tanzania', 'dar_es_salaam', 'Afrika'],
            ['Kampala, Uganda', 'kampala', 'Afrika'],
            ['Lusaka, Zambia', 'lusaka', 'Afrika'],
            ['Harare, Zimbabwe', 'harare', 'Afrika'],
            ['Dakar, Senegal', 'dakar', 'Afrika'],
            ['Abidjan, Ivory Coast', 'abidjan', 'Afrika'],
            ['Tunis, Tunisia', 'tunis', 'Afrika'],
            ['Algiers, Algeria', 'algiers', 'Afrika'],
            ['Maputo, Mozambique', 'maputo', 'Afrika'],
            ['Windhoek, Namibia', 'windhoek', 'Afrika'],

            // ── OSEANIA ──
            ['Sydney, Australia', 'sydney', 'Oseania'],
            ['Melbourne, Australia', 'melbourne', 'Oseania'],
            ['Brisbane, Australia', 'brisbane', 'Oseania'],
            ['Perth, Australia', 'perth', 'Oseania'],
            ['Adelaide, Australia', 'adelaide', 'Oseania'],
            ['Canberra, Australia', 'canberra', 'Oseania'],
            ['Gold Coast, Australia', 'gold_coast', 'Oseania'],
            ['Hobart, Australia', 'hobart', 'Oseania'],
            ['Darwin, Australia', 'darwin', 'Oseania'],
            ['Auckland, New Zealand', 'auckland', 'Oseania'],
            ['Wellington, New Zealand', 'wellington', 'Oseania'],
            ['Christchurch, New Zealand', 'christchurch', 'Oseania'],
            ['Suva, Fiji', 'suva', 'Oseania'],
            ['Port Moresby, Papua New Guinea', 'port_moresby', 'Oseania'],

            // ── REMOTE ──
            ['Remote (Mana Saja)', 'remote', 'Remote'],
        ];

        // ════════════════════════════════════════════════════════════════
        // HELPER FUNCTIONS
        // ════════════════════════════════════════════════════════════════

        $jl = fn($id, $en) => json_encode(['id' => $id, 'en' => $en]);

        // Generate options from flat list for a question
        $genFlatOpts = function (string $prefix, string $qid, array $items) use ($now) {
            $opts = [];
            foreach ($items as $i => $item) {
                $opts[] = [
                    'id' => $prefix . '_' . ($i + 1),
                    'question_id' => $qid,
                    'order_index' => $i + 1,
                    'label' => json_encode(['id' => $item, 'en' => $item]),
                    'value' => \Illuminate\Support\Str::slug($item, '_'),
                    'sub_label' => null,
                    'icon' => null,
                    'group_name' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            return $opts;
        };

        // Generate options from grouped list for a question
        $genGroupedOpts = function (string $prefix, string $qid, array $groups) use ($now) {
            $opts = [];
            $i = 0;

            // Sort categories alphabetically (A-Z)
            ksort($groups);

            foreach ($groups as $group => $items) {
                // Sort items within category alphabetically (A-Z)
                sort($items);

                foreach ($items as $item) {
                    $i++;
                    $opts[] = [
                        'id' => $prefix . '_' . $i,
                        'question_id' => $qid,
                        'order_index' => $i,
                        'label' => json_encode(['id' => $item, 'en' => $item]),
                        'value' => \Illuminate\Support\Str::slug($item, '_'),
                        'sub_label' => null,
                        'icon' => null,
                        'group_name' => $group,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            return $opts;
        };

        // Simple option helper (no group)
        $o = function ($id, $qid, $order, $labelId, $labelEn, $value, $subId = null, $subEn = null, $icon = null) use ($now) {
            return [
                'id' => $id,
                'question_id' => $qid,
                'order_index' => $order,
                'label' => json_encode(['id' => $labelId, 'en' => $labelEn]),
                'value' => $value,
                'sub_label' => ($subId && $subEn) ? json_encode(['id' => $subId, 'en' => $subEn]) : null,
                'icon' => $icon,
                'group_name' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        // Co-founder type options generator (reused in multiple questions)
        $genCFOpts = function (string $prefix, string $qid) use ($masterCFTypes, $now) {
            $opts = [];
            $type = ($qid === 'q_cf_type') ? 'me' : 'need';
            foreach ($masterCFTypes as $i => [$label, $value, $subId, $subEn, $icon]) {
                $finalSubId = is_array($subId) ? $subId[$type] : $subId;
                $finalSubEn = is_array($subEn) ? $subEn[$type] : $subEn;
                $opts[] = [
                    'id' => $prefix . '_' . ($i + 1),
                    'question_id' => $qid,
                    'order_index' => $i + 1,
                    'label' => json_encode(['id' => $label, 'en' => $label]),
                    'value' => $value,
                    'sub_label' => json_encode(['id' => $finalSubId, 'en' => $finalSubEn]),
                    'icon' => $icon,
                    'group_name' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            return $opts;
        };

        // ════════════════════════════════════════════════════════════════
        // 1. FLOWS (18 Flows)
        // ════════════════════════════════════════════════════════════════
        DB::table('onboarding_flows')->insert([
            // Common → Data Diri (Entry Point)
            ['id' => 'flow_common', 'name' => 'Data Diri', 'description' => 'Informasi dasar pengguna', 'is_entry' => true, 'created_at' => $now, 'updated_at' => $now],
            // Builder Common (Role + Experience)
            ['id' => 'flow_builder_common', 'name' => 'Builder - Profil', 'description' => 'Peran dan pengalaman Builder', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Founder (Looking for? + Industries)
            ['id' => 'flow_founder', 'name' => 'Founder', 'description' => 'Tujuan dan industri Founder', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Founder Sub-Flows
            ['id' => 'flow_fdr_cf', 'name' => 'Founder → Cari Co-Founder', 'description' => 'Founder mencari co-founder', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_fdr_team', 'name' => 'Founder → Cari Team', 'description' => 'Founder mencari anggota tim', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_fdr_both', 'name' => 'Founder → Cari Keduanya', 'description' => 'Founder mencari CF + team', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Co-Founder Joining
            ['id' => 'flow_cofounder', 'name' => 'Co-Founder (Bergabung)', 'description' => 'Profil co-founder yang bergabung', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Team Member Joining
            ['id' => 'flow_team', 'name' => 'Anggota Tim (Bergabung)', 'description' => 'Profil anggota tim', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup Main
            ['id' => 'flow_startup', 'name' => 'Startup', 'description' => 'Profil startup', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup Traction by Stage
            ['id' => 'flow_su_tr_idea', 'name' => 'Traction - Idea', 'description' => 'Traction stage Idea', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_tr_mvp', 'name' => 'Traction - MVP', 'description' => 'Traction stage MVP', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_tr_live', 'name' => 'Traction - Live', 'description' => 'Traction stage Live', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_tr_scale', 'name' => 'Traction - Scale', 'description' => 'Traction stage Scale', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup Finish (Presence + Team + What You Need)
            ['id' => 'flow_su_finish', 'name' => 'Startup - Detail', 'description' => 'Detail startup lanjutan', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup Need Sub-Flows
            ['id' => 'flow_su_need_cf', 'name' => 'Startup → Cari CF', 'description' => 'Startup mencari co-founder', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_need_team', 'name' => 'Startup → Cari Team', 'description' => 'Startup mencari anggota tim', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_need_both', 'name' => 'Startup → Cari Keduanya', 'description' => 'Startup mencari CF + team', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup End (Commitment + Equity)
            ['id' => 'flow_su_end', 'name' => 'Startup - Final', 'description' => 'Komitmen dan kompensasi startup', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ════════════════════════════════════════════════════════════════
        // 2. STEPS (53 Steps)
        // ════════════════════════════════════════════════════════════════
        $s = function ($id, $flowId, $order, $section, $titleId, $titleEn, $autoAdvance = false, $subtitleId = null, $subtitleEn = null) use ($now) {
            return [
                'id' => $id,
                'flow_id' => $flowId,
                'order_index' => $order,
                'section' => $section,
                'title' => json_encode(['id' => $titleId, 'en' => $titleEn]),
                'subtitle' => ($subtitleId && $subtitleEn) ? json_encode(['id' => $subtitleId, 'en' => $subtitleEn]) : null,
                'cta_label' => null,
                'auto_advance' => $autoAdvance,
                'can_go_back' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        DB::table('onboarding_steps')->insert([
            // ── COMMON: Data Diri (5 steps) ──
            $s('step_personal_name', 'flow_common', 1, 'Data Diri', 'Ceritakan tentang dirimu', 'Tell us about yourself', false, 'Data dasar yang kami butuhkan untuk mempersonalisasi koneksi kamu.', 'The basics we need to personalize your connections'),
            $s('step_personal_dob', 'flow_common', 2, 'Data Diri', 'Kapan tanggal lahir Anda?', 'When is your date of birth?'),
            $s('step_personal_location', 'flow_common', 3, 'Data Diri', 'Kamu sedang tinggal dimana?', 'Where are you based?'),
            $s('step_personal_gender', 'flow_common', 4, 'Data Diri', 'Bagaimana kamu mengidentifikasi diri?', 'How do you identify?', true, 'Biar kami bisa menyesuaikan pengalamanmu.', 'To help us personalize your experience.'),
            $s('step_role_selection', 'flow_common', 5, 'Tipe Akun', 'Kamu mau pakai ConnectX buat apa?', 'How do you want to use ConnectX?', true, 'Ini bakal nentuin pengalaman kamu di sini.', 'This shapes your entire experience'),

            // ── BUILDER COMMON (2 steps) ──
            $s('step_bld_type', 'flow_builder_common', 1, 'Profil Builder', 'Tolong deskripsikan dirimu', 'What best describes you?', true, 'ini akan menentukan apa yang akan muncul di feed-mu', 'This determines what you\'ll see in your feed'),
            $s('step_bld_role', 'flow_builder_common', 2, 'Profil Builder', 'Peran mana yang paling menggambarkanmu saat ini?', 'Which role best describes you primarily?'),

            // ── FOUNDER (3 steps) ──
            $s('step_fdr_exp', 'flow_founder', 0, 'Profil Builder', 'Seberapa besar pengalaman startupmu?', 'How much startup experience do you have?', true, 'Mari bangun profil talentamu', 'Let\'s build your talent profile'),
            $s('step_fdr_looking', 'flow_founder', 1, 'Tujuan Founder', 'Apa yang sedang kamu cari?', 'What are you looking for?', true, 'Pilih kebutuhan tim paling urgent untuk startup kamu', 'Pick the most urgent hiring need for your startup'),
            $s('step_fdr_industry', 'flow_founder', 2, 'Minat & Industri', 'Industri apa yang menarik minatmu?', 'What industries interest you?', false, 'Pilih hingga 5 industri untuk membentuk feed kamu.', 'Pick up to 5 industries to shape your feed.'),

            // ── FOUNDER → CF (4 steps) ──
            $s('step_fdr_cf_type', 'flow_fdr_cf', 1, 'Cari Co-Founder', 'Co-Founder seperti apa yang kamu butuhkan?', 'What kind of co-founder do you need?', false, 'Pilih semua yang sesuai', 'Select all that apply'),
            $s('step_fdr_cf_avail', 'flow_fdr_cf', 2, 'Ketersediaan', 'Availability seperti apa yang kamu harapkan?', 'What availability do you expect?', true, 'Tingkat komitmen apa yang seharusnya dimiliki kandidat?', 'What commitment level should candidates have?'),
            $s('step_fdr_cf_remote', 'flow_fdr_cf', 3, 'Lokasi Kerja', 'Preferensi kerja', 'Work preferences'),
            $s('step_fdr_cf_linkedin', 'flow_fdr_cf', 4, 'Profil Online', 'Connect LinkedIn', 'Connect LinkedIn'),

            // ── FOUNDER → TEAM (4 steps) ──
            $s('step_fdr_tm_roles', 'flow_fdr_team', 1, 'Cari Anggota Tim', 'peran apa yang saat ini kamu butuhkan?', 'What roles do you need?', false, 'Pilih role yang mau kamu tambahin ke tim kamu', 'Pick the roles you want to add to your team.'),
            $s('step_fdr_tm_avail', 'flow_fdr_team', 2, 'Ketersediaan', 'Availability seperti apa yang kamu harapkan?', 'What availability do you expect?', true, 'Tingkat komitmen apa yang seharusnya dimiliki kandidat?', 'What commitment level should candidates have?'),
            $s('step_fdr_tm_remote', 'flow_fdr_team', 3, 'Lokasi Kerja', 'Preferensi kerja', 'Work preferences'),
            $s('step_fdr_tm_linkedin', 'flow_fdr_team', 4, 'Profil Online', 'Connect LinkedIn', 'Connect LinkedIn'),

            // ── FOUNDER → BOTH (5 steps) ──
            $s('step_fdr_bt_cf', 'flow_fdr_both', 1, 'Cari Co-Founder', 'Co-Founder seperti apa yang kamu butuhkan?', 'What kind of co-founder do you need?', false, 'Pilih semua yang sesuai', 'Select all that apply'),
            $s('step_fdr_bt_roles', 'flow_fdr_both', 2, 'Cari Anggota Tim', 'peran apa yang saat ini kamu butuhkan?', 'What roles do you need?', false, 'Pilih role yang mau kamu tambahin ke tim kamu', 'Pick the roles you want to add to your team.'),
            $s('step_fdr_bt_avail', 'flow_fdr_both', 3, 'Ketersediaan', 'Availability seperti apa yang kamu harapkan?', 'What availability do you expect?', true, 'Tingkat komitmen apa yang seharusnya dimiliki kandidat?', 'What commitment level should candidates have?'),
            $s('step_fdr_bt_remote', 'flow_fdr_both', 4, 'Lokasi Kerja', 'Preferensi kerja', 'Work preferences'),
            $s('step_fdr_bt_linkedin', 'flow_fdr_both', 5, 'Profil Online', 'Connect LinkedIn', 'Connect LinkedIn'),

            // ── CO-FOUNDER JOINING (8 steps) ──
            $s('step_cf_exp', 'flow_cofounder', 0, 'Profil Builder', 'Seberapa besar pengalaman startupmu?', 'How much startup experience do you have?', true, 'Mari bangun profil talentamu', 'Let\'s build your talent profile'),
            $s('step_cf_industry', 'flow_cofounder', 1, 'Minat & Industri', 'Industri apa yang menarik minatmu?', 'What industries interest you?', false, 'Pilih hingga 5 industri untuk membentuk feed kamu.', 'Pick up to 5 industries to shape your feed.'),
            $s('step_cf_type', 'flow_cofounder', 2, 'Tipe Co-Founder', 'Kamu tipe co-founder yang seperti apa?', 'What type of co-founder are you?', false, 'Pilih peran yang bisa kamu kerjakan dari awal', 'Pick the area you can own from day one.'),
            $s('step_cf_avail', 'flow_cofounder', 3, 'Ketersediaan', 'Availability seperti apa yang kamu harapkan?', 'What availability do you expect?', true, 'Tingkat komitmen apa yang seharusnya dimiliki kandidat?', 'What commitment level should candidates have?'),
            $s('step_cf_comp', 'flow_cofounder', 4, 'Ekspektasi Kompensasi', 'Bagaimana ekspektasimu untuk cash dan equity?', 'What are your cash & equity expectations?'),
            $s('step_cf_remote', 'flow_cofounder', 5, 'Lokasi Kerja', 'Preferensi kerja', 'Work preferences'),
            $s('step_cf_linkedin', 'flow_cofounder', 6, 'Profil Online', 'Connect LinkedIn', 'Connect LinkedIn'),

            // ── TEAM MEMBER JOINING (8 steps) ──
            $s('step_tm_exp', 'flow_team', 0, 'Profil Builder', 'Seberapa besar pengalaman startupmu?', 'How much startup experience do you have?', true, 'Mari bangun profil talentamu', 'Let\'s build your talent profile'),
            $s('step_tm_industry', 'flow_team', 1, 'Minat & Industri', 'Industri apa yang menarik minatmu?', 'What industries interest you?', false, 'Pilih hingga 5 industri untuk membentuk feed kamu.', 'Pick up to 5 industries to shape your feed.'),
            $s('step_tm_skills', 'flow_team', 2, 'Skill & Keahlian', 'Skill apa yang kamu miliki?', 'What skills do you have?'),
            $s('step_tm_avail', 'flow_team', 3, 'Ketersediaan', 'Availability seperti apa yang kamu harapkan?', 'What availability do you expect?', true, 'Tingkat komitmen apa yang seharusnya dimiliki kandidat?', 'What commitment level should candidates have?'),
            $s('step_tm_comp', 'flow_team', 4, 'Ekspektasi Kompensasi', 'Bagaimana ekspektasimu untuk cash dan equity?', 'What are your cash & equity expectations?'),
            $s('step_tm_remote', 'flow_team', 5, 'Lokasi Kerja', 'Preferensi kerja', 'Work preferences'),
            $s('step_tm_linkedin', 'flow_team', 6, 'Profil Online', 'Connect LinkedIn', 'Connect LinkedIn'),

            // ── STARTUP (3 steps) ──
            $s('step_su_about', 'flow_startup', 1, 'Profil Startup', 'Ceritakan tentang startup kamu secara singkat', 'Tell us about your startup', false, 'Kenalkan kami dengan startupmu secara singkat.', 'Give us a quick introduction to your company.'),
            $s('step_su_problem', 'flow_startup', 2, 'Masalah & Solusi', 'Apa yang sedang kamu bangun?', 'What are you building?', false, 'Visi kamu', 'Your vision'),
            $s('step_su_biz', 'flow_startup', 3, 'Industri & Model', 'Industri apa yang kamu geluti?', 'Which industries are you in?', false, 'Pilih industri dan model bisnis yang menggambarkan startup kamu.', 'Pick the industries and business model that describe your startup.'),

            // ── TRACTION (1 step each, 4 flows) ──
            $s('step_su_tr_idea', 'flow_su_tr_idea', 1, 'Traction & Milestones', 'Traction kamu sejauh ini', 'Your traction so far', false, 'Ceritakan sejauh mana progress startup kamu.', 'Share how far your startup has come.'),
            $s('step_su_tr_mvp', 'flow_su_tr_mvp', 1, 'Traction & Milestones', 'Traction kamu sejauh ini', 'Your traction so far', false, 'Ceritakan sejauh mana progress startup kamu.', 'Share how far your startup has come.'),
            $s('step_su_tr_live', 'flow_su_tr_live', 1, 'Traction & Milestones', 'Traction kamu sejauh ini', 'Your traction so far', false, 'Ceritakan sejauh mana progress startup kamu.', 'Share how far your startup has come.'),
            $s('step_su_tr_scale', 'flow_su_tr_scale', 1, 'Traction & Milestones', 'Traction kamu sejauh ini', 'Your traction so far', false, 'Ceritakan sejauh mana progress startup kamu.', 'Share how far your startup has come.'),

            // ── STARTUP FINISH (4 steps) ──
            $s('step_su_presence', 'flow_su_finish', 1, 'Online Presence', 'Di mana orang bisa menemukanmu?', 'Where can people find you?', false, 'Biar orang mudah nemuin startup kamu secara online.', 'Help people discover your startup online.'),
            $s('step_su_founders', 'flow_su_finish', 2, 'Tim Founder', 'Berapa jumlah foundermu?', 'How many founders are you?', false, 'Setup founder', 'Founder setup'),
            $s('step_su_team', 'flow_su_finish', 3, 'Status Tim', 'Apakah kamu punya tim di luar founder?', 'Do you have a team beyond founders?', false, 'Status tim', 'Team presence'),
            $s('step_su_need', 'flow_su_finish', 4, 'Kebutuhan', 'Apa yang sedang kamu cari?', 'What are you looking for?', true, 'Pilih kebutuhan tim paling urgent untuk startup kamu', 'Pick the most urgent hiring need for your startup'),

            // ── STARTUP NEED SUB-FLOWS ──
            $s('step_su_need_cf', 'flow_su_need_cf', 1, 'Cari Co-Founder', 'Co-Founder seperti apa yang kamu butuhkan?', 'What kind of co-founder do you need?', false, 'Pilih semua yang sesuai', 'Select all that apply'),
            $s('step_su_need_tm', 'flow_su_need_team', 1, 'Cari Anggota Tim', 'Skill apa yang belum kamu punya?', 'What skills are you missing?', false, 'Skill yang kamu butuhkan', 'Skills you need'),
            $s('step_su_need_bt_cf', 'flow_su_need_both', 1, 'Cari Co-Founder', 'Co-Founder seperti apa yang kamu butuhkan?', 'What kind of co-founder do you need?', false, 'Pilih semua yang sesuai', 'Select all that apply'),
            $s('step_su_need_bt_tm', 'flow_su_need_both', 2, 'Cari Anggota Tim', 'Skill apa yang belum kamu punya?', 'What skills are you missing?', false, 'Skill yang kamu butuhkan', 'Skills you need'),

            // ── STARTUP END (2 steps) ──
            $s('step_su_commit', 'flow_su_end', 1, 'Komitmen', 'Kandidat seperti apa yang kamu inginkan?', 'What kind of commitment do you expect?', true, 'Pilih tingkat komitmen yang paling cocok untuk startup kamu saat ini.', 'Choose the commitment level that fits your startup right now.'),
            $s('step_su_equity', 'flow_su_end', 2, 'Kompensasi', 'Equity & kompensasi yang ditawarkan', 'Equity & compensation offered'),
        ]);

        // ════════════════════════════════════════════════════════════════
        // 3. QUESTIONS
        // ════════════════════════════════════════════════════════════════
        $q = function ($id, $step, $order, $type, $labelId, $labelEn, $required = true, $extra = []) use ($now) {
            return array_merge([
                'id' => $id,
                'step_id' => $step,
                'order_index' => $order,
                'type' => $type,
                'label' => json_encode(['id' => $labelId, 'en' => $labelEn]),
                'sub_label' => null,
                'helper_text' => null,
                'placeholder' => null,
                'required' => $required,
                'validation' => null,
                'depends_on' => null,
                'meta' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $extra);
        };

        DB::table('onboarding_questions')->insert([
            // ── COMMON: Data Diri ──
            $q('q_first_name', 'step_personal_name', 1, 'text', 'Nama Depan', 'First Name', true, ['validation' => json_encode(['min_length' => 1, 'max_length' => 50]), 'placeholder' => json_encode(['id' => 'Nama depan kamu', 'en' => 'Your first name'])]),
            $q('q_last_name', 'step_personal_name', 2, 'text', 'Nama Belakang', 'Last Name', false, ['placeholder' => json_encode(['id' => 'Nama belakang kamu', 'en' => 'Your last name'])]),
            $q('q_dob', 'step_personal_dob', 1, 'date', 'Tanggal Lahir', 'Date of Birth', true, ['placeholder' => json_encode(['id' => 'Tahun-Bulan-tanggal', 'en' => 'YYYY-MM-DD'])]),
            $q('q_location', 'step_personal_location', 1, 'searchable_dropdown', 'Pilih Kota/Negara', 'Select City/Country', true, ['placeholder' => json_encode(['id' => 'Cari kota', 'en' => 'Search a city'])]),
            $q('q_open_remote', 'step_personal_location', 2, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_remote_pref', 'step_personal_location', 3, 'dropdown', 'Preferensi remote', 'Remote preference', true, ['depends_on' => json_encode(['question_id' => 'q_open_remote', 'operator' => 'equals', 'value' => 'yes'])]),
            $q('q_gender', 'step_personal_gender', 1, 'single_select_card', '', ''),
            $q('q_use_connectx', 'step_role_selection', 1, 'single_select_card', '', ''),

            // ── BUILDER COMMON ──
            $q('q_bld_type', 'step_bld_type', 1, 'single_select_card', '', ''),
            $q('q_bld_role', 'step_bld_role', 1, 'searchable_dropdown', 'Pilih peran utama Anda*', 'Select your primary role*', true, ['placeholder' => json_encode(['id' => 'Cari peran utama kamu', 'en' => 'Search your primary role'])]),
            $q('q_bld_years', 'step_bld_role', 2, 'number', 'Tahun Pengalaman', 'Years of Experience', true, ['placeholder' => json_encode(['id' => '3', 'en' => '3'])]),
            $q('q_fdr_exp', 'step_fdr_exp', 1, 'single_select_card', '', ''),
            $q('q_cf_exp', 'step_cf_exp', 1, 'single_select_card', '', ''),
            $q('q_tm_exp', 'step_tm_exp', 1, 'single_select_card', '', ''),

            // ── FOUNDER ──
            $q('q_fdr_looking', 'step_fdr_looking', 1, 'single_select_card', '', ''),
            $q('q_fdr_industry', 'step_fdr_industry', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', 'Select Industries (Max 5)', true, ['validation' => json_encode(['min_selections' => 1, 'max_selections' => 5]), 'placeholder' => json_encode(['id' => 'Cari industri', 'en' => 'Search industries'])]),

            // ── FOUNDER → CF ──
            $q('q_fdr_cf_type', 'step_fdr_cf_type', 1, 'multi_select_card', '', ''),
            $q('q_fdr_cf_avail', 'step_fdr_cf_avail', 1, 'single_select_card', '', ''),
            $q('q_fdr_cf_remote', 'step_fdr_cf_remote', 1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_fdr_cf_relocate', 'step_fdr_cf_remote', 2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_fdr_cf_linkedin', 'step_fdr_cf_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id' => 'https://linkedin.com/in/namamu', 'en' => 'https://linkedin.com/in/your-name'])]),

            // ── FOUNDER → TEAM ──
            $q('q_fdr_tm_roles', 'step_fdr_tm_roles', 1, 'multi_select_chip', 'Peran yang dibutuhkan', 'Roles needed', true, ['placeholder' => json_encode(['id' => 'cari role', 'en' => 'Search roles'])]),
            $q('q_fdr_tm_avail', 'step_fdr_tm_avail', 1, 'single_select_card', '', ''),
            $q('q_fdr_tm_remote', 'step_fdr_tm_remote', 1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_fdr_tm_relocate', 'step_fdr_tm_remote', 2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_fdr_tm_linkedin', 'step_fdr_tm_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id' => 'https://linkedin.com/in/namamu', 'en' => 'https://linkedin.com/in/your-name'])]),

            // ── FOUNDER → BOTH ──
            $q('q_fdr_bt_cf', 'step_fdr_bt_cf', 1, 'multi_select_card', '', ''),
            $q('q_fdr_bt_roles', 'step_fdr_bt_roles', 1, 'multi_select_chip', 'Peran yang dibutuhkan', 'Roles needed', true, ['placeholder' => json_encode(['id' => 'cari role', 'en' => 'Search roles'])]),
            $q('q_fdr_bt_avail', 'step_fdr_bt_avail', 1, 'single_select_card', '', ''),
            $q('q_fdr_bt_remote', 'step_fdr_bt_remote', 1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_fdr_bt_relocate', 'step_fdr_bt_remote', 2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_fdr_bt_linkedin', 'step_fdr_bt_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id' => 'https://linkedin.com/in/namamu', 'en' => 'https://linkedin.com/in/your-name'])]),

            // ── CO-FOUNDER JOINING ──
            $q('q_cf_industry', 'step_cf_industry', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', 'Select Industries (Max 5)', true, ['validation' => json_encode(['min_selections' => 1, 'max_selections' => 5]), 'placeholder' => json_encode(['id' => 'Cari industri', 'en' => 'Search industries'])]),
            $q('q_cf_type', 'step_cf_type', 1, 'multi_select_card', '', ''),
            $q('q_cf_avail', 'step_cf_avail', 1, 'single_select_card', '', ''),
            // Compensation
            $q('q_cf_equity', 'step_cf_comp', 1, 'single_select_card', 'Ekspektasi equity', 'Equity expectation'),
            $q('q_cf_salary_type', 'step_cf_comp', 2, 'single_select_card', 'Apakah kamu punya ekspektasi minimum gaji?', 'Do you have a minimum salary expectation?'),
            $q('q_cf_salary_period', 'step_cf_comp', 3, 'dropdown', 'Periode gaji', 'Salary period', true, ['depends_on' => json_encode(['question_id' => 'q_cf_salary_type', 'operator' => 'in', 'value' => ['strict', 'flexible']])]),
            $q('q_cf_salary_currency', 'step_cf_comp', 4, 'dropdown', 'Mata uang', 'Currency', true, ['depends_on' => json_encode(['question_id' => 'q_cf_salary_type', 'operator' => 'in', 'value' => ['strict', 'flexible']])]),
            $q('q_cf_salary_amount', 'step_cf_comp', 5, 'number', 'Berapa minimum gaji?', 'Minimum salary amount?', true, ['depends_on' => json_encode(['question_id' => 'q_cf_salary_type', 'operator' => 'in', 'value' => ['strict', 'flexible']]), 'placeholder' => json_encode(['id' => '5000', 'en' => '5000'])]),
            // Remote + LinkedIn
            $q('q_cf_remote', 'step_cf_remote', 1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_cf_relocate', 'step_cf_remote', 2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_cf_linkedin', 'step_cf_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id' => 'https://linkedin.com/in/namamu', 'en' => 'https://linkedin.com/in/your-name'])]),

            // ── TEAM MEMBER JOINING ──
            $q('q_tm_industry', 'step_tm_industry', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', 'Select Industries (Max 5)', true, ['validation' => json_encode(['min_selections' => 1, 'max_selections' => 5]), 'placeholder' => json_encode(['id' => 'Cari industri', 'en' => 'Search industries'])]),
            $q('q_tm_skills', 'step_tm_skills', 1, 'multi_select_chip', '', '', true, ['placeholder' => json_encode(['id' => 'Cari skill', 'en' => 'Search skills'])]),
            $q('q_tm_avail', 'step_tm_avail', 1, 'single_select_card', '', ''),
            // Compensation
            $q('q_tm_equity', 'step_tm_comp', 1, 'single_select_card', 'Ekspektasi equity', 'Equity expectation'),
            $q('q_tm_salary_type', 'step_tm_comp', 2, 'single_select_card', 'Apakah kamu punya ekspektasi minimum gaji?', 'Do you have a minimum salary expectation?'),
            $q('q_tm_salary_period', 'step_tm_comp', 3, 'dropdown', 'Periode gaji', 'Salary period', true, ['depends_on' => json_encode(['question_id' => 'q_tm_salary_type', 'operator' => 'in', 'value' => ['strict', 'flexible']])]),
            $q('q_tm_salary_currency', 'step_tm_comp', 4, 'dropdown', 'Mata uang', 'Currency', true, ['depends_on' => json_encode(['question_id' => 'q_tm_salary_type', 'operator' => 'in', 'value' => ['strict', 'flexible']])]),
            $q('q_tm_salary_amount', 'step_tm_comp', 5, 'number', 'Berapa minimum gaji?', 'Minimum salary amount?', true, ['depends_on' => json_encode(['question_id' => 'q_tm_salary_type', 'operator' => 'in', 'value' => ['strict', 'flexible']]), 'placeholder' => json_encode(['id' => '5000', 'en' => '5000'])]),
            // Remote + LinkedIn
            $q('q_tm_remote', 'step_tm_remote', 1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_tm_relocate', 'step_tm_remote', 2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_tm_linkedin', 'step_tm_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id' => 'https://linkedin.com/in/namamu', 'en' => 'https://linkedin.com/in/your-name'])]),

            // ── STARTUP: About ──
            $q('q_su_name', 'step_su_about', 1, 'text', 'Nama Startup', 'Startup Name', true, ['validation' => json_encode(['min_length' => 2, 'max_length' => 100]), 'placeholder' => json_encode(['id' => 'Nama startup', 'en' => 'Startup name'])]),
            $q('q_su_tagline', 'step_su_about', 2, 'text', 'Tagline (1 kalimat)', 'Tagline (1 sentence)', true, ['validation' => json_encode(['max_length' => 150]), 'placeholder' => json_encode(['id' => 'cont: Cara paling cepat buat cari co-founder', 'en' => 'e.g. The fastest way to find co-founders'])]),
            $q('q_su_stage', 'step_su_about', 3, 'dropdown', 'Tahap Startup', 'Startup Stage'),

            // ── STARTUP: Problem & Solution ──
            $q('q_su_problem', 'step_su_problem', 1, 'textarea', 'Masalah yang kamu selesaikan', 'Problem you\'re solving', true, ['placeholder' => json_encode(['id' => 'Siapa yang merasakan kesusahannya dan kenapa?', 'en' => 'Who hurts, and why?'])]),
            $q('q_su_solution', 'step_su_problem', 2, 'textarea', 'Solusi kamu', 'Your solution', true, ['placeholder' => json_encode(['id' => 'Bagaimana produkmu bisa mengatasi masalah ini?', 'en' => 'How does your product solve it?'])]),
            $q('q_su_target', 'step_su_problem', 3, 'textarea', 'Target pengguna', 'Target users', true, ['placeholder' => json_encode(['id' => 'Siapa target penggunamu?', 'en' => 'Describe the people you are building for'])]),

            // ── STARTUP: Industry & Biz Model ──
            $q('q_su_industry', 'step_su_biz', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', 'Select Industries (Max 5)', true, ['validation' => json_encode(['min_selections' => 1, 'max_selections' => 5]), 'placeholder' => json_encode(['id' => 'Cari industri', 'en' => 'Search industries'])]),
            $q('q_su_biz_model', 'step_su_biz', 2, 'multi_select_chip', 'Model Bisnis', 'Business Model', true, ['placeholder' => json_encode(['id' => 'Cari model bisnis', 'en' => 'Search a business model'])]),

            // ── TRACTION: Idea ──
            $q('q_su_tri_prototype', 'step_su_tr_idea', 1, 'single_select_card', 'Apakah kamu punya prototype?', 'Do you have a prototype?'),
            $q('q_su_tri_prototype_link', 'step_su_tr_idea', 2, 'url', 'Link Prototype', 'Prototype Link', false, ['depends_on' => json_encode(['question_id' => 'q_su_tri_prototype', 'operator' => 'equals', 'value' => 'yes']), 'placeholder' => json_encode(['id' => 'https://figma.com/...', 'en' => 'https://figma.com/...'])]),
            $q('q_su_tri_waitlist', 'step_su_tr_idea', 3, 'number', 'Ukuran Waitlist', 'Waitlist Size', false, ['placeholder' => json_encode(['id' => '5000', 'en' => '5000'])]),
            $q('q_su_tri_validation', 'step_su_tr_idea', 4, 'text', 'Validasi (interview, survey, dll)', 'Validation (interviews, surveys, etc.)', false, ['placeholder' => json_encode(['id' => 'Wawancara, survei, landing page...', 'en' => 'Interviews, surveys, landing page tests...'])]),

            // ── TRACTION: MVP ──
            $q('q_su_trm_users', 'step_su_tr_mvp', 1, 'number', 'Jumlah Users', 'Number of Users', false, ['placeholder' => json_encode(['id' => '5000', 'en' => '5000'])]),
            $q('q_su_trm_mau', 'step_su_tr_mvp', 2, 'number', 'Monthly Active Users (MAU)', 'Monthly Active Users (MAU)', false, ['placeholder' => json_encode(['id' => '5000', 'en' => '5000'])]),
            $q('q_su_trm_revenue', 'step_su_tr_mvp', 3, 'number', 'Revenue (jika ada)', 'Revenue (if any)', false, ['placeholder' => json_encode(['id' => '5000', 'en' => '5000'])]),
            $q('q_su_trm_growth', 'step_su_tr_mvp', 4, 'text', 'Growth Rate', 'Growth Rate', false, ['placeholder' => json_encode(['id' => 'misalnya 20% MoM', 'en' => 'e.g. 20% MoM'])]),

            // ── TRACTION: Live ──
            $q('q_su_trl_mrr', 'step_su_tr_live', 1, 'number', 'Monthly Recurring Revenue (MRR)', 'MRR', false, ['placeholder' => json_encode(['id' => 'misalnya $500 MRR', 'en' => 'e.g. $500 MRR'])]),
            $q('q_su_trl_customers', 'step_su_tr_live', 2, 'number', 'Jumlah Pelanggan', 'Number of Customers', false, ['placeholder' => json_encode(['id' => '5000', 'en' => '5000'])]),
            $q('q_su_trl_retention', 'step_su_tr_live', 3, 'text', 'Retention Rate', 'Retention Rate', false, ['placeholder' => json_encode(['id' => 'misalnya retensi 80% di bulan ke-3', 'en' => 'e.g. 80% 3-month retention'])]),
            $q('q_su_trl_metrics', 'step_su_tr_live', 4, 'text', 'Key Metrics (GMV, dll)', 'Key Metrics (GMV, etc.)', false, ['placeholder' => json_encode(['id' => 'Hal lain yang penting disorot?', 'en' => 'Anything else worth highlighting?'])]),

            // ── TRACTION: Scale ──
            $q('q_su_trs_funding', 'step_su_tr_scale', 1, 'text', 'Funding yang sudah didapat', 'Funding raised', false, ['placeholder' => json_encode(['id' => 'misalnya $2M seed', 'en' => 'e.g. $2M seed'])]),
            $q('q_su_trs_investors', 'step_su_tr_scale', 2, 'text', 'Investor (opsional)', 'Investors (optional)', false, ['placeholder' => json_encode(['id' => 'misalnya East Ventures, Alpha JWC', 'en' => 'e.g. East Ventures, Alpha JWC'])]),
            $q('q_su_trs_teamsize', 'step_su_tr_scale', 3, 'number', 'Ukuran Tim', 'Team Size', false, ['placeholder' => json_encode(['id' => '5000', 'en' => '5000'])]),
            $q('q_su_trs_arr', 'step_su_tr_scale', 4, 'number', 'Annual Recurring Revenue (ARR)', 'ARR', false, ['placeholder' => json_encode(['id' => 'misalnya $1.2M ARR', 'en' => 'e.g. $1.2M ARR'])]),

            // ── STARTUP FINISH: Online Presence ──
            $q('q_su_website', 'step_su_presence', 1, 'url', 'Website', 'Website', false, ['placeholder' => json_encode(['id' => 'https://startupkamu.com', 'en' => 'https://yourstartup.com'])]),
            $q('q_su_linkedin', 'step_su_presence', 2, 'url', 'LinkedIn', 'LinkedIn', false, ['placeholder' => json_encode(['id' => 'https://linkedin.com/company/...', 'en' => 'https://linkedin.com/company/...'])]),
            $q('q_su_twitter', 'step_su_presence', 3, 'url', 'Twitter / X', 'Twitter / X', false, ['placeholder' => json_encode(['id' => 'https://x.com/...', 'en' => 'https://x.com/...'])]),
            $q('q_su_instagram', 'step_su_presence', 4, 'url', 'Instagram', 'Instagram', false, ['placeholder' => json_encode(['id' => 'https://instagram.com/...', 'en' => 'https://instagram.com/...'])]),
            $q('q_su_pitchdeck', 'step_su_presence', 5, 'url', 'Pitch Deck', 'Pitch Deck', false, ['placeholder' => json_encode(['id' => 'https://pitch.com/...', 'en' => 'https://pitch.com/...']), 'helper_text' => json_encode(['id' => 'Opsional, bisa share pitch deck kamu — founder lain suka lihat deck kamu!', 'en' => 'Optional, but founders love seeing your deck!'])]),

            // ── STARTUP FINISH: Founder Setup ──
            $q('q_su_founder_count', 'step_su_founders', 1, 'single_select_card', '', ''),
            $q('q_su_founder_roles', 'step_su_founders', 2, 'multi_select_chip', 'Peran apa yang sudah terisi?', 'What roles are already covered?', false, ['depends_on' => json_encode(['question_id' => 'q_su_founder_count', 'operator' => 'not_equals', 'value' => 'solo']), 'placeholder' => json_encode(['id' => 'cari role', 'en' => 'Search roles'])]),

            // ── STARTUP FINISH: Team Status ──
            $q('q_su_have_team', 'step_su_team', 1, 'single_select_card', '', ''),
            $q('q_su_team_size', 'step_su_team', 2, 'single_select_card', 'Ukuran tim', 'Team size', true, ['depends_on' => json_encode(['question_id' => 'q_su_have_team', 'operator' => 'equals', 'value' => 'yes'])]),
            $q('q_su_team_roles', 'step_su_team', 3, 'multi_select_chip', 'Departemen/peran di tim', 'Team departments/roles', true, ['depends_on' => json_encode(['question_id' => 'q_su_have_team', 'operator' => 'equals', 'value' => 'yes']), 'placeholder' => json_encode(['id' => 'cari role', 'en' => 'Search roles'])]),

            // ── STARTUP FINISH: What You Need ──
            $q('q_su_need', 'step_su_need', 1, 'single_select_card', '', ''),

            // ── STARTUP NEED: CF ──
            $q('q_su_need_cf_type', 'step_su_need_cf', 1, 'multi_select_card', '', ''),
            // ── STARTUP NEED: Team ──
            $q('q_su_need_tm_skills', 'step_su_need_tm', 1, 'multi_select_chip', '', '', true, ['placeholder' => json_encode(['id' => 'Cari skill', 'en' => 'Search skills'])]),
            // ── STARTUP NEED: Both ──
            $q('q_su_need_bt_cf', 'step_su_need_bt_cf', 1, 'multi_select_card', '', ''),
            $q('q_su_need_bt_tm', 'step_su_need_bt_tm', 1, 'multi_select_chip', '', '', true, ['placeholder' => json_encode(['id' => 'Cari skill', 'en' => 'Search skills'])]),

            // ── STARTUP END: Commitment ──
            $q('q_su_commitment', 'step_su_commit', 1, 'single_select_card', '', ''),

            // ── STARTUP END: Equity & Comp ──
            $q('q_su_equity_range', 'step_su_equity', 1, 'text', 'Equity yang ditawarkan (% range)', 'Equity offered (% range)', true, ['placeholder' => json_encode(['id' => 'misalnya 5-15%', 'en' => 'e.g. 5-15%'])]),
            $q('q_su_paid', 'step_su_equity', 2, 'single_select_card', 'Apakah posisi ini dibayar?', 'Is this a paid position?'),
            $q('q_su_salary_range', 'step_su_equity', 3, 'text', 'Range gaji per tahun', 'Annual salary range', false, ['depends_on' => json_encode(['question_id' => 'q_su_paid', 'operator' => 'equals', 'value' => 'paid']), 'placeholder' => json_encode(['id' => 'misalnya IDR 100-200jt/thn', 'en' => 'e.g. USD 30-60k/yr'])]),
        ]);

        // ════════════════════════════════════════════════════════════════
        // 4. OPTIONS
        // ════════════════════════════════════════════════════════════════
        $opts = [];

        // ── Locations (68 cities with groups) ──
        $groupedLocations = [];
        foreach ($locations as $loc) {
            $groupedLocations[$loc[2]][] = $loc;
        }

        uksort($groupedLocations, function ($a, $b) {
            if ($a === 'Indonesia') return -1;
            if ($b === 'Indonesia') return 1;
            if ($a === 'Remote') return 1;
            if ($b === 'Remote') return -1;
            return strcmp($a, $b);
        });

        $sortedLocations = [];
        foreach ($groupedLocations as $groupName => $locs) {
            usort($locs, function ($a, $b) {
                return strcmp($a[0], $b[0]);
            });
            foreach ($locs as $loc) {
                $sortedLocations[] = $loc;
            }
        }

        foreach ($sortedLocations as $i => [$label, $value, $group]) {
            $opts[] = [
                'id' => 'opt_loc_' . ($i + 1),
                'question_id' => 'q_location',
                'order_index' => $i + 1,
                'label' => json_encode(['id' => $label, 'en' => $label]),
                'value' => $value,
                'sub_label' => null,
                'icon' => null,
                'group_name' => $group,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // ── Common Simple Options ──
        $opts[] = $o('opt_rem_yes', 'q_open_remote', 1, 'Ya', 'Yes', 'yes', null, null, 'yes');
        $opts[] = $o('opt_rem_no', 'q_open_remote', 2, 'Tidak', 'No', 'no', null, null, 'no');
        $opts[] = $o('opt_rp_1', 'q_remote_pref', 1, 'Hybrid', 'Hybrid', 'hybrid');
        $opts[] = $o('opt_rp_2', 'q_remote_pref', 2, 'Hanya Remote', 'Remote Only', 'remote_only');
        $opts[] = $o('opt_gen_m', 'q_gender', 1, 'Pria', 'Male', 'male');
        $opts[] = $o('opt_gen_f', 'q_gender', 2, 'Wanita', 'Female', 'female');

        // ── Role Selection (Builder vs Startup) ──
        $opts[] = $o('opt_uc_1', 'q_use_connectx', 1, 'Saya seorang Builder', 'I\'m a Builder', 'builder', 'Founder, co-founder, atau anggota tim', 'Founder, co-founder, or team member', 'team');
        $opts[] = $o('opt_uc_2', 'q_use_connectx', 2, 'Saya mewakili Startup', 'I represent a Startup', 'startup', 'Membangun tim atau mencari co-founder', 'Building a team or hiring co-founders', 'rocket');

        // ── Builder Sub-Type (Founder / Co-Founder / Team Member) ──
        $opts[] = $o('opt_bt_1', 'q_bld_type', 1, 'Founder', 'Founder', 'founder', 'Saya sedang membangun sesuatu dan mencari orang', 'I\'m building something and looking for people', 'founder_rocket');
        $opts[] = $o('opt_bt_2', 'q_bld_type', 2, 'Co-Founder', 'Co-Founder', 'cofounder', 'Saya ingin bergabung ke startup sebagai co-founder', 'I want to join a startup as a co-founder', 'cofounder_handshake');
        $opts[] = $o('opt_bt_3', 'q_bld_type', 3, 'Anggota Tim', 'Team Member', 'team', 'Saya ingin bergabung ke tim startup', 'I want to join a startup team', 'team_member_group');

        // ── Primary Roles (grouped, for q_bld_role) ──
        $opts = array_merge($opts, $genGroupedOpts('opt_role', 'q_bld_role', $masterRoles));

        // ── Experience Level (Dynamic per Role) ──
        // Founder Experience
        $opts[] = $o('opt_fdr_exp_1', 'q_fdr_exp', 1, 'Pernah mendirikan startup sebelumnya', 'Founded a startup before', 'founded', null, null, 'exp_founded');
        $opts[] = $o('opt_fdr_exp_2', 'q_fdr_exp', 2, 'Pernah menjual startup', 'Sold a startup', 'sold', null, null, 'exp_sold');
        $opts[] = $o('opt_fdr_exp_3', 'q_fdr_exp', 3, 'Pernah bekerja di startup', 'Worked in a startup', 'worked', null, null, 'exp_worked');
        $opts[] = $o('opt_fdr_exp_4', 'q_fdr_exp', 4, 'Pernah membangun produk di startup', 'Built a product at a startup', 'built', null, null, 'exp_built');
        $opts[] = $o('opt_fdr_exp_5', 'q_fdr_exp', 5, 'Tidak ada pengalaman startup sebelumnya', 'No Prior startup experience', 'none', null, null, 'exp_none');

        // Co-Founder Experience
        $opts[] = $o('opt_cf_exp_1', 'q_cf_exp', 1, 'Mendirikan / co-founded sebuah perusahaan', 'Founder / co-founded a company', 'founded', null, null, 'exp_founded');
        $opts[] = $o('opt_cf_exp_2', 'q_cf_exp', 2, 'Pernah menjual startup', 'Sold a startup', 'sold', null, null, 'exp_sold');
        $opts[] = $o('opt_cf_exp_3', 'q_cf_exp', 3, 'Pernah bekerja di startup', 'Worked in a startup', 'worked', null, null, 'exp_worked');
        $opts[] = $o('opt_cf_exp_4', 'q_cf_exp', 4, 'Pernah membangun produk di startup', 'Built a product at a startup', 'built', null, null, 'exp_built');
        $opts[] = $o('opt_cf_exp_5', 'q_cf_exp', 5, 'Tidak ada pengalaman startup sebelumnya', 'No Prior startup experience', 'none', null, null, 'exp_none');

        // Team Experience
        $opts[] = $o('opt_tm_exp_1', 'q_tm_exp', 1, 'Mendirikan / co-founded sebuah perusahaan', 'Founder / co-founded a company', 'founded', null, null, 'exp_founded');
        $opts[] = $o('opt_tm_exp_2', 'q_tm_exp', 2, 'Pernah bekerja di startup', 'Worked in a startup', 'worked', null, null, 'exp_worked');
        $opts[] = $o('opt_tm_exp_3', 'q_tm_exp', 3, 'Pernah membangun produk di startup', 'Built a product at a startup', 'built', null, null, 'exp_built');
        $opts[] = $o('opt_tm_exp_4', 'q_tm_exp', 4, 'Tidak ada pengalaman startup sebelumnya', 'No Prior startup experience', 'none', null, null, 'exp_none');

        // ── Founder: Looking For ──
        $opts[] = $o('opt_fdr_look_1', 'q_fdr_looking', 1, 'Co-Founder', 'Co-Founder', 'cofounder', 'Mencari partner untuk membangun bersama', 'Looking for a partner to build together', 'goal_cofounder');
        $opts[] = $o('opt_fdr_look_2', 'q_fdr_looking', 2, 'Anggota Tim', 'Team Members', 'team', 'Mencari anggota tim untuk startup saya', 'Looking for team members for my startup', 'goal_team_members');
        $opts[] = $o('opt_fdr_look_3', 'q_fdr_looking', 3, 'Keduanya', 'Both', 'both', 'Mencari co-founder dan anggota tim', 'Looking for both co-founder and team members', 'goal_both');

        // ── Industries (for all industry questions) ──
        $industryQuestions = ['q_fdr_industry', 'q_cf_industry', 'q_tm_industry', 'q_su_industry'];
        foreach ($industryQuestions as $qid) {
            $opts = array_merge($opts, $genGroupedOpts('opt_ind_' . str_replace('q_', '', $qid), $qid, $masterIndustries));
        }

        // ── Co-Founder Types (reused across multiple questions) ──
        $cfTypeQuestions = ['q_fdr_cf_type', 'q_fdr_bt_cf', 'q_cf_type', 'q_su_need_cf_type', 'q_su_need_bt_cf'];
        foreach ($cfTypeQuestions as $qid) {
            $opts = array_merge($opts, $genCFOpts('opt_cft_' . str_replace('q_', '', $qid), $qid));
        }

        // ── Roles (for founder team needs + startup needs) ──
        $teamRoleQuestions = ['q_fdr_tm_roles', 'q_fdr_bt_roles'];
        foreach ($teamRoleQuestions as $qid) {
            $opts = array_merge($opts, $genGroupedOpts('opt_tmr_' . str_replace('q_', '', $qid), $qid, $masterRoles));
        }

        // ── Skills (for team member + startup needs) ──
        $skillQuestions = ['q_tm_skills', 'q_su_need_tm_skills', 'q_su_need_bt_tm'];
        foreach ($skillQuestions as $qid) {
            $opts = array_merge($opts, $genGroupedOpts('opt_sk_' . str_replace('q_', '', $qid), $qid, $masterSkills));
        }

        // ── Availability Options (reused for multiple questions) ──
        $availQuestions = ['q_fdr_cf_avail', 'q_fdr_tm_avail', 'q_fdr_bt_avail', 'q_cf_avail', 'q_tm_avail'];
        foreach ($availQuestions as $qid) {
            $p = str_replace('q_', 'opt_av_', $qid);
            $opts[] = $o($p . '_1', $qid, 1, 'Full-time', 'Full-time', 'full_time', 'Kandidat harus komitmen penuh', 'Candidates must be fully committed', 'availability_full_time');
            $opts[] = $o($p . '_2', $qid, 2, 'Part-time', 'Part-time', 'part_time', 'Terbuka untuk kandidat dengan komitmen lain', 'Open to candidates with other commitments', 'availability_part_time');
            $opts[] = $o($p . '_3', $qid, 3, 'Flexible / Hybrid', 'Flexible / Hybrid', 'flexible', 'Terbuka untuk mendiskusikan pengaturan', 'Open to discuss arrangement', 'availability_flexible');
        }

        // ── Remote Options (reused) ──
        $remoteQuestions = ['q_fdr_cf_remote', 'q_fdr_tm_remote', 'q_fdr_bt_remote', 'q_cf_remote', 'q_tm_remote'];
        foreach ($remoteQuestions as $qid) {
            $p = str_replace('q_', 'opt_rm_', $qid);
            $opts[] = $o($p . '_1', $qid, 1, 'Ya', 'Yes', 'yes', null, null, 'yes');
            $opts[] = $o($p . '_2', $qid, 2, 'Tidak', 'No', 'no', null, null, 'no');
        }

        // ── Relocate Options (reused) ──
        $relocateQuestions = ['q_fdr_cf_relocate', 'q_fdr_tm_relocate', 'q_fdr_bt_relocate', 'q_cf_relocate', 'q_tm_relocate'];
        foreach ($relocateQuestions as $qid) {
            $p = str_replace('q_', 'opt_rl_', $qid);
            $opts[] = $o($p . '_1', $qid, 1, 'Ya', 'Yes', 'yes', null, null, 'yes');
            $opts[] = $o($p . '_2', $qid, 2, 'Tidak', 'No', 'no', null, null, 'no');
        }

        // ── Equity Expectation (Co-Founder + Team Member) ──
        $equityQuestions = ['q_cf_equity', 'q_tm_equity'];
        foreach ($equityQuestions as $qid) {
            $p = str_replace('q_', 'opt_eq_', $qid);
            $opts[] = $o($p . '_1', $qid, 1, 'Equity sangat penting', 'Equity is very important', 'equity_heavy');
            $opts[] = $o($p . '_2', $qid, 2, 'Tertarik dengan sebagian equity', 'Interested in some equity', 'partial_equity');
            $opts[] = $o($p . '_3', $qid, 3, 'Kompensasi berat di cash', 'Compensation heavy on cash', 'cash_heavy');
        }

        // ── Salary Type (Co-Founder + Team Member) ──
        $salaryTypeQuestions = ['q_cf_salary_type', 'q_tm_salary_type'];
        foreach ($salaryTypeQuestions as $qid) {
            $p = str_replace('q_', 'opt_st_', $qid);
            $opts[] = $o($p . '_1', $qid, 1, 'Ya, saya punya minimum yang tegas', 'Yes, I have a strict minimum', 'strict');
            $opts[] = $o($p . '_2', $qid, 2, 'Ya, tapi saya bisa turun tergantung peluang', 'Yes, but flexible depending on opportunity', 'flexible');
            $opts[] = $o($p . '_3', $qid, 3, 'Tidak, saya fleksibel soal gaji', 'No, I\'m flexible on salary', 'no_minimum');
        }

        // ── Salary Period (Co-Founder + Team Member) ──
        $salaryPeriodQuestions = ['q_cf_salary_period', 'q_tm_salary_period'];
        foreach ($salaryPeriodQuestions as $qid) {
            $p = str_replace('q_', 'opt_sp_', $qid);
            $opts[] = $o($p . '_1', $qid, 1, 'Per Tahun (Annual)', 'Annual', 'annual');
            $opts[] = $o($p . '_2', $qid, 2, 'Per Jam (Hourly)', 'Hourly', 'hourly');
        }

        // ── Salary Currency ──
        $salaryCurrencyQuestions = ['q_cf_salary_currency', 'q_tm_salary_currency'];
        foreach ($salaryCurrencyQuestions as $qid) {
            $p = str_replace('q_', 'opt_sc_', $qid);
            $opts[] = $o($p . '_1', $qid, 1, 'IDR', 'IDR', 'IDR');
            $opts[] = $o($p . '_2', $qid, 2, 'USD', 'USD', 'USD');
            $opts[] = $o($p . '_3', $qid, 3, 'SGD', 'SGD', 'SGD');
        }

        // ── Startup Stage ──
        $opts[] = $o('opt_stage_1', 'q_su_stage', 1, 'Idea', 'Idea', 'idea');
        $opts[] = $o('opt_stage_2', 'q_su_stage', 2, 'MVP', 'MVP', 'mvp');
        $opts[] = $o('opt_stage_3', 'q_su_stage', 3, 'Live (Sudah Launching)', 'Live (Already Launched)', 'live');
        $opts[] = $o('opt_stage_4', 'q_su_stage', 4, 'Scale (Seed / Series A)', 'Scale (Seed / Series A)', 'scale');

        // ── Business Models (grouped, for q_su_biz_model) ──
        $bizIdx = 0;
        ksort($masterBizModels);
        foreach ($masterBizModels as $group => $models) {
            usort($models, fn($a, $b) => strcmp($a[0], $b[0]));
            foreach ($models as [$label, $value]) {
                $bizIdx++;
                $opts[] = [
                    'id' => 'opt_biz_' . $bizIdx,
                    'question_id' => 'q_su_biz_model',
                    'order_index' => $bizIdx,
                    'label' => json_encode(['id' => $label, 'en' => $label]),
                    'value' => $value,
                    'sub_label' => null,
                    'icon' => null,
                    'group_name' => $group,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // ── Traction: Prototype Yes/No ──
        $opts[] = $o('opt_proto_1', 'q_su_tri_prototype', 1, 'Ya', 'Yes', 'yes', null, null, 'yes');
        $opts[] = $o('opt_proto_2', 'q_su_tri_prototype', 2, 'Belum', 'No', 'no', null, null, 'no');

        // ── Founder Count ──
        $opts[] = $o('opt_fc_1', 'q_su_founder_count', 1, 'Solo Founder', 'Solo Founder', 'solo', null, null, 'founder_solo');
        $opts[] = $o('opt_fc_2', 'q_su_founder_count', 2, '2 Founders', '2 Founders', '2_founders', null, null, 'founder_two');
        $opts[] = $o('opt_fc_3', 'q_su_founder_count', 3, '3+ Founders', '3+ Founders', '3plus_founders', null, null, 'founder_three_plus');

        // ── Founder Roles Covered ──
        $founderRolesCovered = ['Technical', 'Product', 'Business', 'Growth', 'Operations', 'Finance', 'Design', 'Other'];
        foreach ($founderRolesCovered as $i => $r) {
            $opts[] = $o('opt_frc_' . ($i + 1), 'q_su_founder_roles', $i + 1, $r, $r, \Illuminate\Support\Str::slug($r, '_'));
        }

        // ── Have Team ──
        $opts[] = $o('opt_ht_1', 'q_su_have_team', 1, 'Tidak, hanya founder', 'No, just founders', 'no', null, null, 'no');
        $opts[] = $o('opt_ht_2', 'q_su_have_team', 2, 'Ya', 'Yes', 'yes', null, null, 'yes');

        // ── Team Size ──
        $opts[] = $o('opt_ts_1', 'q_su_team_size', 1, '1-3 orang', '1-3 people', '1_3', null, null, 'team_size_small');
        $opts[] = $o('opt_ts_2', 'q_su_team_size', 2, '4-10 orang', '4-10 people', '4_10', null, null, 'team_size_medium');
        $opts[] = $o('opt_ts_3', 'q_su_team_size', 3, '10+ orang', '10+ people', '10_plus', null, null, 'team_size_large');

        // ── Team Departments ──
        $teamDepts = ['Engineering', 'Marketing', 'Sales', 'Operations', 'Design', 'Finance', 'Other'];
        foreach ($teamDepts as $i => $d) {
            $opts[] = $o('opt_td_' . ($i + 1), 'q_su_team_roles', $i + 1, $d, $d, \Illuminate\Support\Str::slug($d, '_'));
        }

        // ── Startup: What You Need ──
        $opts[] = $o('opt_sn_1', 'q_su_need', 1, 'Co-Founder', 'Co-Founder', 'cofounder', null, null, 'goal_cofounder');
        $opts[] = $o('opt_sn_2', 'q_su_need', 2, 'Anggota Tim', 'Team Members', 'team', null, null, 'goal_team_members');
        $opts[] = $o('opt_sn_3', 'q_su_need', 3, 'Keduanya', 'Both', 'both', null, null, 'goal_both');

        // ── Commitment Level ──
        $opts[] = $o('opt_cl_1', 'q_su_commitment', 1, 'Full-time only', 'Full-time only', 'full_time', null, null, 'availability_full_time');
        $opts[] = $o('opt_cl_2', 'q_su_commitment', 2, 'Part-time', 'Part-time', 'part_time', null, null, 'availability_part_time');
        $opts[] = $o('opt_cl_3', 'q_su_commitment', 3, 'Open / Fleksibel', 'Open / Flexible', 'flexible', null, null, 'availability_flexible');

        // ── Paid/Unpaid ──
        $opts[] = $o('opt_paid_1', 'q_su_paid', 1, 'Berbayar (Paid)', 'Paid', 'paid');
        $opts[] = $o('opt_paid_2', 'q_su_paid', 2, 'Tidak Dibayar (Unpaid)', 'Unpaid', 'unpaid');
        $opts[] = $o('opt_paid_3', 'q_su_paid', 3, 'Open to Discussion', 'Open to Discussion', 'open');

        // ── Batch Insert Options (chunk to avoid memory issues) ──
        foreach (array_chunk($opts, 200) as $chunk) {
            DB::table('onboarding_options')->insert($chunk);
        }

        // ════════════════════════════════════════════════════════════════
        // 5. TRANSITIONS (Branching Logic)
        // ════════════════════════════════════════════════════════════════
        $t = function ($from, $condition, $toStep, $toFlow, $priority = 0) use ($now) {
            return [
                'from_step_id' => $from,
                'condition' => $condition ? json_encode($condition) : null,
                'to_step_id' => $toStep,
                'to_flow_id' => $toFlow,
                'priority' => $priority,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        DB::table('onboarding_transitions')->insert([
            // ── step_role_selection: startup → flow_startup, else → flow_builder_common ──
            $t('step_role_selection', ['question_id' => 'q_use_connectx', 'operator' => 'equals', 'value' => 'startup'], null, 'flow_startup', 10),
            $t('step_role_selection', null, null, 'flow_builder_common', 0),  // default: founder/cofounder/team all go to builder common

            // ── step_bld_role: branch by original role selection ──
            $t('step_bld_role', ['question_id' => 'q_bld_type', 'operator' => 'equals', 'value' => 'founder'], null, 'flow_founder', 10),
            $t('step_bld_role', ['question_id' => 'q_bld_type', 'operator' => 'equals', 'value' => 'cofounder'], null, 'flow_cofounder', 10),
            $t('step_bld_role', ['question_id' => 'q_bld_type', 'operator' => 'equals', 'value' => 'team'], null, 'flow_team', 10),

            // ── step_fdr_industry: branch by what founder is looking for ──
            $t('step_fdr_industry', ['question_id' => 'q_fdr_looking', 'operator' => 'equals', 'value' => 'cofounder'], null, 'flow_fdr_cf', 10),
            $t('step_fdr_industry', ['question_id' => 'q_fdr_looking', 'operator' => 'equals', 'value' => 'team'], null, 'flow_fdr_team', 10),
            $t('step_fdr_industry', ['question_id' => 'q_fdr_looking', 'operator' => 'equals', 'value' => 'both'], null, 'flow_fdr_both', 10),

            // ── Startup: step_su_biz → Traction by stage ──
            $t('step_su_biz', ['question_id' => 'q_su_stage', 'operator' => 'equals', 'value' => 'idea'], null, 'flow_su_tr_idea', 10),
            $t('step_su_biz', ['question_id' => 'q_su_stage', 'operator' => 'equals', 'value' => 'mvp'], null, 'flow_su_tr_mvp', 10),
            $t('step_su_biz', ['question_id' => 'q_su_stage', 'operator' => 'equals', 'value' => 'live'], null, 'flow_su_tr_live', 10),
            $t('step_su_biz', ['question_id' => 'q_su_stage', 'operator' => 'equals', 'value' => 'scale'], null, 'flow_su_tr_scale', 10),

            // ── Traction → flow_su_finish (unconditional) ──
            $t('step_su_tr_idea', null, null, 'flow_su_finish', 0),
            $t('step_su_tr_mvp', null, null, 'flow_su_finish', 0),
            $t('step_su_tr_live', null, null, 'flow_su_finish', 0),
            $t('step_su_tr_scale', null, null, 'flow_su_finish', 0),

            // ── step_su_need: branch by what startup needs ──
            $t('step_su_need', ['question_id' => 'q_su_need', 'operator' => 'equals', 'value' => 'cofounder'], null, 'flow_su_need_cf', 10),
            $t('step_su_need', ['question_id' => 'q_su_need', 'operator' => 'equals', 'value' => 'team'], null, 'flow_su_need_team', 10),
            $t('step_su_need', ['question_id' => 'q_su_need', 'operator' => 'equals', 'value' => 'both'], null, 'flow_su_need_both', 10),

            // ── Startup Need sub-flows → flow_su_end (unconditional) ──
            $t('step_su_need_cf', null, null, 'flow_su_end', 0),
            $t('step_su_need_tm', null, null, 'flow_su_end', 0),
            $t('step_su_need_bt_tm', null, null, 'flow_su_end', 0),
        ]);
    }
}
