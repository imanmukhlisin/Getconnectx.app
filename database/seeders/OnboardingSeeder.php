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
                'Founder','Co-Founder','CEO','COO','CTO',
                'CPO (Chief Product Officer)','CMO (Chief Marketing Officer)',
                'CFO','Managing Director','General Manager',
            ],
            'Engineering - Software' => [
                'Frontend Engineer','Backend Engineer','Full Stack Engineer',
                'Mobile Engineer (iOS/Android)','Web Developer',
            ],
            'Engineering - Specialized' => [
                'Machine Learning Engineer','AI Engineer','Prompt Engineer',
                'Data Engineer','Embedded Engineer','Systems Engineer',
                'DevOps Engineer','Blockchain Engineer','Security Engineer',
            ],
            'Engineering - Hardware' => [
                'Hardware Engineer','Mechanical Engineer','Electrical Engineer',
            ],
            'Product & Strategy' => [
                'Product Manager','Product Owner','Technical Product Manager',
                'Product Designer','UX Researcher','Business Analyst','Strategy Associate',
            ],
            'Design & Creative' => [
                'UI Designer','UX Designer','UI/UX Designer','Graphic Designer',
                'Brand Designer','Motion Designer','3D Designer',
                'Creative Director','Content Designer',
            ],
            'Marketing & Growth' => [
                'Growth Marketer','Digital Marketer','Performance Marketer',
                'Social Media Manager','Content Creator','Content Strategist',
                'SEO Specialist','Copywriter','Brand Manager','Community Manager',
                'Influencer Marketing Manager',
            ],
            'Sales & Business Dev' => [
                'Sales Executive','Account Executive','Business Development Manager',
                'Partnerships Manager','Account Manager','Customer Success Manager',
                'Revenue Operations',
            ],
            'Data & Analytics' => [
                'Data Analyst','Data Scientist',
                'Business Intelligence Analyst','Quantitative Analyst',
            ],
            'Operations' => [
                'Operations Manager','Project Manager','Program Manager',
                'Supply Chain Manager','Logistics Manager',
            ],
            'Finance & Legal' => [
                'Financial Analyst','Accountant','Finance Manager',
                'Investment Analyst','Venture Capital Associate',
                'Legal Counsel','Compliance Officer',
            ],
            'People & HR' => [
                'HR Manager','Talent Acquisition','Recruiter',
                'People Operations','HR Business Partner',
            ],
            'Web3 / Crypto' => [
                'Smart Contract Developer','Web3 Developer','Crypto Trader',
                'Tokenomics Analyst','Community Lead (Web3)','DAO Contributor',
            ],
            'Creator & Non-Traditional' => [
                'Creator / Influencer','Indie Hacker','No-Code Builder',
                'Freelancer','Consultant','Advisor / Mentor',
            ],
        ];

        $masterIndustries = [
            'Core Technology' => [
                'AI','Analytics','AR/VR','Cloud Infrastructure','Data Services','DeepTech',
                'Developer Tools','Generative Tech/AI','IoT','Robotics','Security','Semiconductors',
            ],
            'Software & Digital Products' => [
                'Enterprise','Messaging','Productivity Tools','SaaS','Sales & CRM','SMB Software','Social Networks',
            ],
            'Consumer & Marketplace' => [
                'Cosmetics','Creator/Passion Economy','Direct-to-Consumer (DTC)','E-commerce',
                'Fashion','Food and Beverage','Marketplaces','Retail',
            ],
            'Finance & Business Infrastructure' => [
                'FinTech','Human Capital/HRTech','Insurance','LegalTech','Payments',
            ],
            'Industry-Specific Solutions' => [
                'AgTech','ClimateTech/CleanTech','ConstructionTech','Education',
                'EnergyTech','GovTech','Healthcare','Logistics','Manufacturing',
                'Medical Devices','Pharmaceuticals','Real Estate/PropTech',
                'Supply Chain Tech','TransportationTech',
            ],
            'Media, Lifestyle & Experience' => [
                'Entertainment & Sports','Gaming','Lodging/Hospitality','Media/Content',
                'Mental Health','Parenting/Families','Travel','Wellness & Fitness',
            ],
            'Emerging & Future' => [
                'Future of Work','Gig Economy','Hardware','Material Science',
                'Smart Cities/UrbanTech','Social Impact','Space','Web3/Blockchain',
            ],
        ];

        $masterSkills = [
            'Engineering, IT & Technical' => [
                'React / Angular / Vue','Node.js / Java / Python / Go',
                'API Design & Integration','System Architecture','Microservices',
                'AWS / GCP / Azure','CI/CD & DevOps pipelines','Kubernetes / Docker',
                'Database Design (SQL/NoSQL)','Cybersecurity','Smart Contracts (Solidity)',
            ],
            'Construction & Property' => [
                'AutoCAD / SketchUp / Revit','Building Design & Planning',
                'Interior Styling & Space Planning','Construction Management',
                'Cost Estimation & Budgeting','Site Supervision','Property Development Strategy',
            ],
            'F&B (Food & Beverage)' => [
                'Menu Development','Food Costing','Kitchen Operations',
                'Food Safety & Hygiene','Supply Chain (ingredients sourcing)',
                'Restaurant Branding','Customer Experience',
            ],
            'Design & Creative' => [
                'Figma / Adobe Suite','Branding & Identity','Prototyping',
                'Visual Design','Motion Graphics','3D Rendering','Fashion Design & Production',
            ],
            'Marketing & Growth' => [
                'Paid Ads (Meta, Google, TikTok)','SEO / SEM','Copywriting',
                'Social Media Growth','Influencer Marketing','Email Marketing',
                'Analytics (GA, Mixpanel)','Campaign Strategy',
            ],
            'Sales & Partnerships' => [
                'Lead Generation','Sales Closing','Negotiation','CRM Tools',
                'B2B / B2C Sales','Deal Structuring','Client Relationship Management',
            ],
            'Operations & Supply Chain' => [
                'Process Optimization','SOP Creation','Inventory Management',
                'Logistics & Distribution','Vendor Management','Operational Scaling',
            ],
            'Finance & Legal' => [
                'Financial Modeling','Fundraising','Investor Relations',
                'Budgeting','Accounting','Legal Structuring','Contracts & Compliance',
            ],
            'Data, AI & Analytics' => [
                'Data Analysis','Python / R','Machine Learning',
                'Data Visualization','Predictive Analytics','AI Model Development',
            ],
            'Media & Content' => [
                'Video Production','Editing (Premiere, CapCut)','Storytelling',
                'Content Strategy','Social Media Content',
            ],
            'HR & People' => [
                'Hiring & Recruitment','Talent Management','Employer Branding',
                'HR Strategy','Performance Management',
            ],
            'Emerging & Specialized' => [
                'Smart Contracts','Automation Tools (Zapier, Make)',
                'No-Code Platforms','Crypto / DeFi Systems',
            ],
        ];

        $masterCFTypes = [
            ['Technical Co-Founder',     'tech',         'Saya membangun produk & teknologi',           'I build the product & tech',       'cofounder_technical'],
            ['Product Co-Founder',       'product',      'Saya memimpin produk dan desain',             'I lead product & design',          'cofounder_product'],
            ['Business Co-Founder',      'business',     'Saya menangani strategi dan operasional',     'I handle strategy & ops',          'cofounder_business'],
            ['Growth Co-Founder',        'growth',       'Saya menggerakkan marketing dan growth',      'I drive marketing & growth',       'cofounder_growth'],
            ['AI / Data Co-Founder',     'ai_data',      'Saya membangun AI, data & intelligence',      'I build AI, data & intelligence',  'cofounder_ai'],
            ['Operations Co-Founder',    'operations',   'Saya mengeksekusi dan menskalakan operasional','I execute & scale operations',     'cofounder_operations'],
            ['Finance Co-Founder',       'finance',      'Saya mengelola fundraising dan keuangan',     'I manage fundraising & finance',   'cofounder_finance'],
            ['Partnerships Co-Founder',  'partnerships', 'Saya membangun deal dan partnership',         'I build deals & partnerships',     'cofounder_partnerships'],
        ];

        $masterBizModels = [
            'Digital & Software' => [
                ['SaaS (Subscription software)','saas'],
                ['Marketplace (2-sided platform)','marketplace'],
                ['E-commerce (Online store)','ecommerce'],
                ['Direct-to-Consumer (DTC brand)','dtc'],
                ['Mobile App (freemium / paid)','mobile_app'],
                ['API / Infrastructure (B2B tech)','api_infra'],
            ],
            'Financial & Transactional' => [
                ['FinTech (payments, lending, etc.)','fintech'],
                ['Transaction Fees (per use / commission)','transaction_fees'],
                ['Brokerage / Commission-based','brokerage'],
                ['Subscription + Transaction Hybrid','sub_transaction_hybrid'],
            ],
            'Media & Attention' => [
                ['Advertising-based','advertising'],
                ['Content / Media Platform','content_media'],
                ['Creator Economy (subscriptions, tips, content)','creator_economy'],
            ],
            'Services & Offline' => [
                ['Service-based (agency, consulting)','service'],
                ['F&B (restaurant, cafe, cloud kitchen)','fnb'],
                ['Retail (offline / omnichannel)','retail'],
                ['Hospitality (hotel, lodging)','hospitality'],
                ['Events / Experiences','events'],
            ],
            'Asset-Heavy / Industry' => [
                ['Real Estate / Property','real_estate'],
                ['Construction / Infrastructure','construction'],
                ['Manufacturing','manufacturing'],
                ['Logistics / Supply Chain','logistics'],
                ['Energy / Climate','energy_climate'],
            ],
            'Emerging / Tech-Forward' => [
                ['Web3 / Blockchain','web3'],
                ['Token-based / Crypto economy','token_crypto'],
                ['AI-first product','ai_first'],
                ['DeepTech / R&D','deeptech'],
            ],
            'Hybrid / Other' => [
                ['Franchise Model','franchise'],
                ['Licensing','licensing'],
                ['Aggregator','aggregator'],
                ['Platform + Service hybrid','platform_service'],
            ],
        ];

        $locations = [
            ['Jakarta, Indonesia','jakarta','Asia Tenggara'],
            ['Bandung, Indonesia','bandung','Asia Tenggara'],
            ['Surabaya, Indonesia','surabaya','Asia Tenggara'],
            ['Bali, Indonesia','bali','Asia Tenggara'],
            ['Yogyakarta, Indonesia','yogyakarta','Asia Tenggara'],
            ['Medan, Indonesia','medan','Asia Tenggara'],
            ['Singapore','singapore','Asia Tenggara'],
            ['Kuala Lumpur, Malaysia','kuala_lumpur','Asia Tenggara'],
            ['Penang, Malaysia','penang','Asia Tenggara'],
            ['Bangkok, Thailand','bangkok','Asia Tenggara'],
            ['Ho Chi Minh City, Vietnam','ho_chi_minh','Asia Tenggara'],
            ['Hanoi, Vietnam','hanoi','Asia Tenggara'],
            ['Manila, Philippines','manila','Asia Tenggara'],
            ['Cebu, Philippines','cebu','Asia Tenggara'],
            ['Phnom Penh, Cambodia','phnom_penh','Asia Tenggara'],
            ['Bangalore, India','bangalore','Asia Selatan'],
            ['Mumbai, India','mumbai','Asia Selatan'],
            ['Delhi, India','delhi','Asia Selatan'],
            ['Hyderabad, India','hyderabad','Asia Selatan'],
            ['Chennai, India','chennai','Asia Selatan'],
            ['Pune, India','pune','Asia Selatan'],
            ['Karachi, Pakistan','karachi','Asia Selatan'],
            ['Colombo, Sri Lanka','colombo','Asia Selatan'],
            ['Dhaka, Bangladesh','dhaka','Asia Selatan'],
            ['Tokyo, Japan','tokyo','Asia Timur'],
            ['Osaka, Japan','osaka','Asia Timur'],
            ['Seoul, South Korea','seoul','Asia Timur'],
            ['Beijing, China','beijing','Asia Timur'],
            ['Shanghai, China','shanghai','Asia Timur'],
            ['Shenzhen, China','shenzhen','Asia Timur'],
            ['Hong Kong','hong_kong','Asia Timur'],
            ['Taipei, Taiwan','taipei','Asia Timur'],
            ['Dubai, UAE','dubai','Timur Tengah'],
            ['Abu Dhabi, UAE','abu_dhabi','Timur Tengah'],
            ['Riyadh, Saudi Arabia','riyadh','Timur Tengah'],
            ['Tel Aviv, Israel','tel_aviv','Timur Tengah'],
            ['Amman, Jordan','amman','Timur Tengah'],
            ['London, UK','london','Eropa'],
            ['Berlin, Germany','berlin','Eropa'],
            ['Amsterdam, Netherlands','amsterdam','Eropa'],
            ['Paris, France','paris','Eropa'],
            ['Stockholm, Sweden','stockholm','Eropa'],
            ['Zurich, Switzerland','zurich','Eropa'],
            ['Lisbon, Portugal','lisbon','Eropa'],
            ['Barcelona, Spain','barcelona','Eropa'],
            ['Warsaw, Poland','warsaw','Eropa'],
            ['Tallinn, Estonia','tallinn','Eropa'],
            ['San Francisco, USA','san_francisco','Amerika'],
            ['New York, USA','new_york','Amerika'],
            ['Austin, USA','austin','Amerika'],
            ['Seattle, USA','seattle','Amerika'],
            ['Miami, USA','miami','Amerika'],
            ['Toronto, Canada','toronto','Amerika'],
            ['Vancouver, Canada','vancouver','Amerika'],
            ['São Paulo, Brazil','sao_paulo','Amerika'],
            ['Mexico City, Mexico','mexico_city','Amerika'],
            ['Buenos Aires, Argentina','buenos_aires','Amerika'],
            ['Lagos, Nigeria','lagos','Afrika & Oseania'],
            ['Nairobi, Kenya','nairobi','Afrika & Oseania'],
            ['Cairo, Egypt','cairo','Afrika & Oseania'],
            ['Johannesburg, South Africa','johannesburg','Afrika & Oseania'],
            ['Sydney, Australia','sydney','Afrika & Oseania'],
            ['Melbourne, Australia','melbourne','Afrika & Oseania'],
            ['Auckland, New Zealand','auckland','Afrika & Oseania'],
            ['Remote (Mana Saja)','remote','Remote'],
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
                    'sub_label' => null, 'icon' => null, 'group_name' => null,
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
            return $opts;
        };

        // Generate options from grouped list for a question
        $genGroupedOpts = function (string $prefix, string $qid, array $groups) use ($now) {
            $opts = [];
            $i = 0;
            foreach ($groups as $group => $items) {
                foreach ($items as $item) {
                    $i++;
                    $opts[] = [
                        'id' => $prefix . '_' . $i,
                        'question_id' => $qid,
                        'order_index' => $i,
                        'label' => json_encode(['id' => $item, 'en' => $item]),
                        'value' => \Illuminate\Support\Str::slug($item, '_'),
                        'sub_label' => null, 'icon' => null, 'group_name' => $group,
                        'created_at' => $now, 'updated_at' => $now,
                    ];
                }
            }
            return $opts;
        };

        // Simple option helper (no group)
        $o = function ($id, $qid, $order, $labelId, $labelEn, $value, $subId = null, $subEn = null, $icon = null) use ($now) {
            return [
                'id' => $id, 'question_id' => $qid, 'order_index' => $order,
                'label' => json_encode(['id' => $labelId, 'en' => $labelEn]),
                'value' => $value,
                'sub_label' => ($subId && $subEn) ? json_encode(['id' => $subId, 'en' => $subEn]) : null,
                'icon' => $icon, 'group_name' => null,
                'created_at' => $now, 'updated_at' => $now,
            ];
        };

        // Co-founder type options generator (reused in multiple questions)
        $genCFOpts = function (string $prefix, string $qid) use ($masterCFTypes, $now) {
            $opts = [];
            foreach ($masterCFTypes as $i => [$label, $value, $subId, $subEn, $icon]) {
                $opts[] = [
                    'id' => $prefix . '_' . ($i + 1),
                    'question_id' => $qid,
                    'order_index' => $i + 1,
                    'label' => json_encode(['id' => $label, 'en' => $label]),
                    'value' => $value,
                    'sub_label' => json_encode(['id' => $subId, 'en' => $subEn]),
                    'icon' => $icon, 'group_name' => null,
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
            return $opts;
        };

        // ════════════════════════════════════════════════════════════════
        // 1. FLOWS (18 Flows)
        // ════════════════════════════════════════════════════════════════
        DB::table('onboarding_flows')->insert([
            // Common → Data Diri (Entry Point)
            ['id' => 'flow_common',           'name' => 'Data Diri',                 'description' => 'Informasi dasar pengguna',          'is_entry' => true,  'created_at' => $now, 'updated_at' => $now],
            // Builder Common (Role + Experience)
            ['id' => 'flow_builder_common',   'name' => 'Builder - Profil',           'description' => 'Peran dan pengalaman Builder',      'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Founder (Looking for? + Industries)
            ['id' => 'flow_founder',          'name' => 'Founder',                    'description' => 'Tujuan dan industri Founder',       'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Founder Sub-Flows
            ['id' => 'flow_fdr_cf',           'name' => 'Founder → Cari Co-Founder',  'description' => 'Founder mencari co-founder',        'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_fdr_team',         'name' => 'Founder → Cari Team',        'description' => 'Founder mencari anggota tim',       'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_fdr_both',         'name' => 'Founder → Cari Keduanya',    'description' => 'Founder mencari CF + team',         'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Co-Founder Joining
            ['id' => 'flow_cofounder',        'name' => 'Co-Founder (Bergabung)',      'description' => 'Profil co-founder yang bergabung', 'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Team Member Joining
            ['id' => 'flow_team',             'name' => 'Anggota Tim (Bergabung)',     'description' => 'Profil anggota tim',               'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup Main
            ['id' => 'flow_startup',          'name' => 'Startup',                    'description' => 'Profil startup',                    'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup Traction by Stage
            ['id' => 'flow_su_tr_idea',       'name' => 'Traction - Idea',            'description' => 'Traction stage Idea',               'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_tr_mvp',        'name' => 'Traction - MVP',             'description' => 'Traction stage MVP',                'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_tr_live',       'name' => 'Traction - Live',            'description' => 'Traction stage Live',               'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_tr_scale',      'name' => 'Traction - Scale',           'description' => 'Traction stage Scale',              'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup Finish (Presence + Team + What You Need)
            ['id' => 'flow_su_finish',        'name' => 'Startup - Detail',           'description' => 'Detail startup lanjutan',           'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup Need Sub-Flows
            ['id' => 'flow_su_need_cf',       'name' => 'Startup → Cari CF',          'description' => 'Startup mencari co-founder',        'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_need_team',     'name' => 'Startup → Cari Team',        'description' => 'Startup mencari anggota tim',       'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 'flow_su_need_both',     'name' => 'Startup → Cari Keduanya',    'description' => 'Startup mencari CF + team',         'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
            // Startup End (Commitment + Equity)
            ['id' => 'flow_su_end',           'name' => 'Startup - Final',            'description' => 'Komitmen dan kompensasi startup',   'is_entry' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // ════════════════════════════════════════════════════════════════
        // 2. STEPS (53 Steps)
        // ════════════════════════════════════════════════════════════════
        $s = function ($id, $flowId, $order, $section, $titleId, $titleEn, $autoAdvance = false) use ($now) {
            return [
                'id' => $id, 'flow_id' => $flowId, 'order_index' => $order,
                'section' => $section,
                'title' => json_encode(['id' => $titleId, 'en' => $titleEn]),
                'subtitle' => null, 'cta_label' => null,
                'auto_advance' => $autoAdvance, 'can_go_back' => true,
                'created_at' => $now, 'updated_at' => $now,
            ];
        };

        DB::table('onboarding_steps')->insert([
            // ── COMMON: Data Diri (5 steps) ──
            $s('step_personal_name',     'flow_common', 1, 'Data Diri', 'Siapa nama Anda?',                          'What\'s your name?'),
            $s('step_personal_dob',      'flow_common', 2, 'Data Diri', 'Kapan tanggal lahir Anda?',                 'When\'s your date of birth?'),
            $s('step_personal_location', 'flow_common', 3, 'Data Diri', 'Di mana Anda berlokasi?',                   'Where are you based?'),
            $s('step_personal_gender',   'flow_common', 4, 'Data Diri', 'Jenis Kelamin',                             'Gender', true),
            $s('step_role_selection',    'flow_common', 5, 'Tipe Akun', 'Bagaimana Anda ingin menggunakan ConnectX?', 'How would you like to use ConnectX?', true),

            // ── BUILDER COMMON (3 steps) ──
            $s('step_bld_type', 'flow_builder_common', 1, 'Profil Builder', 'Apa yang paling menggambarkan kamu?', 'What best describes you?', true),
            $s('step_bld_role',  'flow_builder_common', 2, 'Profil Builder', 'Apa peran/jabatan utama Anda?',     'What is your primary role?'),
            $s('step_bld_exp',   'flow_builder_common', 3, 'Profil Builder', 'Seberapa besar pengalaman startup Anda?', 'How much startup experience do you have?', true),

            // ── FOUNDER (2 steps) ──
            $s('step_fdr_looking',  'flow_founder', 1, 'Tujuan Founder',    'Apa yang sedang kamu cari?',              'What are you looking for?', true),
            $s('step_fdr_industry', 'flow_founder', 2, 'Minat & Industri',  'Industri apa yang menarik minatmu?',      'What industries interest you?'),

            // ── FOUNDER → CF (4 steps) ──
            $s('step_fdr_cf_type',    'flow_fdr_cf', 1, 'Cari Co-Founder',  'Co-Founder seperti apa yang kamu butuhkan?', 'What kind of Co-Founder do you need?'),
            $s('step_fdr_cf_avail',   'flow_fdr_cf', 2, 'Ketersediaan',     'Availability seperti apa yang kamu harapkan?', 'What availability do you expect?', true),
            $s('step_fdr_cf_remote',  'flow_fdr_cf', 3, 'Lokasi Kerja',     'Preferensi kerja',                       'Work preferences'),
            $s('step_fdr_cf_linkedin','flow_fdr_cf', 4, 'Profil Online',    'Connect LinkedIn',                       'Connect LinkedIn'),

            // ── FOUNDER → TEAM (4 steps) ──
            $s('step_fdr_tm_roles',   'flow_fdr_team', 1, 'Cari Anggota Tim','Peran apa yang kamu butuhkan?',          'What roles do you need?'),
            $s('step_fdr_tm_avail',   'flow_fdr_team', 2, 'Ketersediaan',    'Availability seperti apa yang kamu harapkan?', 'What availability do you expect?', true),
            $s('step_fdr_tm_remote',  'flow_fdr_team', 3, 'Lokasi Kerja',    'Preferensi kerja',                       'Work preferences'),
            $s('step_fdr_tm_linkedin','flow_fdr_team', 4, 'Profil Online',   'Connect LinkedIn',                       'Connect LinkedIn'),

            // ── FOUNDER → BOTH (5 steps) ──
            $s('step_fdr_bt_cf',      'flow_fdr_both', 1, 'Cari Co-Founder', 'Co-Founder seperti apa yang kamu butuhkan?', 'What kind of Co-Founder do you need?'),
            $s('step_fdr_bt_roles',   'flow_fdr_both', 2, 'Cari Anggota Tim','Peran apa yang kamu butuhkan?',          'What roles do you need?'),
            $s('step_fdr_bt_avail',   'flow_fdr_both', 3, 'Ketersediaan',    'Availability seperti apa yang kamu harapkan?', 'What availability do you expect?', true),
            $s('step_fdr_bt_remote',  'flow_fdr_both', 4, 'Lokasi Kerja',    'Preferensi kerja',                       'Work preferences'),
            $s('step_fdr_bt_linkedin','flow_fdr_both', 5, 'Profil Online',   'Connect LinkedIn',                       'Connect LinkedIn'),

            // ── CO-FOUNDER JOINING (7 steps) ──
            $s('step_cf_industry', 'flow_cofounder', 1, 'Minat & Industri',   'Industri apa yang menarik minatmu?',        'What industries interest you?'),
            $s('step_cf_type',     'flow_cofounder', 2, 'Tipe Co-Founder',    'Kamu co-founder tipe apa?',                 'What kind of co-founder are you?', true),
            $s('step_cf_avail',    'flow_cofounder', 3, 'Ketersediaan',       'Availability seperti apa yang kamu harapkan?','What availability do you expect?', true),
            $s('step_cf_comp',     'flow_cofounder', 4, 'Ekspektasi Kompensasi','Bagaimana ekspektasimu untuk cash dan equity?','What are your cash & equity expectations?'),
            $s('step_cf_remote',   'flow_cofounder', 5, 'Lokasi Kerja',       'Preferensi kerja',                         'Work preferences'),
            $s('step_cf_linkedin', 'flow_cofounder', 6, 'Profil Online',      'Connect LinkedIn',                         'Connect LinkedIn'),

            // ── TEAM MEMBER JOINING (7 steps) ──
            $s('step_tm_industry', 'flow_team', 1, 'Minat & Industri',   'Industri apa yang menarik minatmu?',         'What industries interest you?'),
            $s('step_tm_skills',   'flow_team', 2, 'Skill & Keahlian',   'Skill apa yang kamu miliki?',                'What skills do you have?'),
            $s('step_tm_avail',    'flow_team', 3, 'Ketersediaan',       'Availability seperti apa yang kamu harapkan?','What availability do you expect?', true),
            $s('step_tm_comp',     'flow_team', 4, 'Ekspektasi Kompensasi','Bagaimana ekspektasimu untuk cash dan equity?','What are your cash & equity expectations?'),
            $s('step_tm_remote',   'flow_team', 5, 'Lokasi Kerja',       'Preferensi kerja',                          'Work preferences'),
            $s('step_tm_linkedin', 'flow_team', 6, 'Profil Online',      'Connect LinkedIn',                          'Connect LinkedIn'),

            // ── STARTUP (3 steps) ──
            $s('step_su_about',   'flow_startup', 1, 'Profil Startup',  'Ceritakan tentang startup kamu',             'Tell us about your startup'),
            $s('step_su_problem', 'flow_startup', 2, 'Masalah & Solusi','Apa yang sedang kamu bangun?',               'What are you building?'),
            $s('step_su_biz',     'flow_startup', 3, 'Industri & Model','Industri dan model bisnis',                  'Industry and business model'),

            // ── TRACTION (1 step each, 4 flows) ──
            $s('step_su_tr_idea',  'flow_su_tr_idea',  1, 'Traction', 'Validasi tahap Idea',       'Idea stage validation'),
            $s('step_su_tr_mvp',   'flow_su_tr_mvp',   1, 'Traction', 'Traction tahap MVP',        'MVP stage traction'),
            $s('step_su_tr_live',  'flow_su_tr_live',   1, 'Traction', 'Traction tahap Live',       'Live stage traction'),
            $s('step_su_tr_scale', 'flow_su_tr_scale',  1, 'Traction', 'Traction tahap Scale',      'Scale stage traction'),

            // ── STARTUP FINISH (4 steps) ──
            $s('step_su_presence', 'flow_su_finish', 1, 'Online Presence', 'Di mana orang bisa menemukan kamu?',       'Where can people find you?'),
            $s('step_su_founders', 'flow_su_finish', 2, 'Tim Founder',     'Setup founder',                           'Founder setup'),
            $s('step_su_team',     'flow_su_finish', 3, 'Status Tim',      'Status tim kamu',                         'Your team status'),
            $s('step_su_need',     'flow_su_finish', 4, 'Kebutuhan',       'Apa yang sedang kamu cari?',              'What are you looking for?', true),

            // ── STARTUP NEED SUB-FLOWS ──
            $s('step_su_need_cf',      'flow_su_need_cf',   1, 'Cari Co-Founder', 'Co-Founder seperti apa yang kamu butuhkan?', 'What kind of Co-Founder do you need?'),
            $s('step_su_need_tm',      'flow_su_need_team', 1, 'Cari Anggota Tim','Skill apa yang belum dipunyai di tim?',      'What skills are you missing?'),
            $s('step_su_need_bt_cf',   'flow_su_need_both', 1, 'Cari Co-Founder', 'Co-Founder yang dibutuhkan',                'Co-Founder needed'),
            $s('step_su_need_bt_tm',   'flow_su_need_both', 2, 'Cari Anggota Tim','Skill yang belum dipunyai di tim',           'Skills missing in team'),

            // ── STARTUP END (2 steps) ──
            $s('step_su_commit', 'flow_su_end', 1, 'Komitmen',    'Commitment level',                              'Commitment level', true),
            $s('step_su_equity', 'flow_su_end', 2, 'Kompensasi',  'Equity & kompensasi yang ditawarkan',            'Equity & compensation offered'),
        ]);

        // ════════════════════════════════════════════════════════════════
        // 3. QUESTIONS
        // ════════════════════════════════════════════════════════════════
        $q = function ($id, $step, $order, $type, $labelId, $labelEn, $required = true, $extra = []) use ($now) {
            return array_merge([
                'id' => $id, 'step_id' => $step, 'order_index' => $order,
                'type' => $type,
                'label' => json_encode(['id' => $labelId, 'en' => $labelEn]),
                'sub_label' => null, 'helper_text' => null, 'placeholder' => null,
                'required' => $required,
                'validation' => null, 'depends_on' => null, 'meta' => null,
                'created_at' => $now, 'updated_at' => $now,
            ], $extra);
        };

        DB::table('onboarding_questions')->insert([
            // ── COMMON: Data Diri ──
            $q('q_first_name', 'step_personal_name', 1, 'text', 'Nama Depan', 'First Name', true, ['validation' => json_encode(['min_length'=>1, 'max_length'=>50])]),
            $q('q_last_name',  'step_personal_name', 2, 'text', 'Nama Belakang', 'Last Name', false),
            $q('q_dob',        'step_personal_dob',  1, 'date', 'Tanggal Lahir', 'Date of Birth'),
            $q('q_location',   'step_personal_location', 1, 'searchable_dropdown', 'Pilih Kota/Negara', 'Select City/Country'),
            $q('q_open_remote','step_personal_location', 2, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_remote_pref','step_personal_location', 3, 'dropdown', 'Preferensi remote', 'Remote preference', true, ['depends_on' => json_encode(['question_id'=>'q_open_remote','operator'=>'equals','value'=>'yes'])]),
            $q('q_gender',     'step_personal_gender',   1, 'single_select_card', 'Jenis Kelamin', 'Gender'),
            $q('q_use_connectx','step_role_selection',    1, 'single_select_card', 'Bagaimana kamu ingin menggunakan ConnectX?', 'How do you want to use ConnectX?'),

            // ── BUILDER COMMON ──
            $q('q_bld_type',   'step_bld_type', 1, 'single_select_card', 'Apa yang paling menggambarkan kamu?', 'What best describes you?'),
            $q('q_bld_role',   'step_bld_role', 1, 'searchable_dropdown', 'Peran Utama', 'Primary Role'),
            $q('q_bld_years',  'step_bld_role', 2, 'number', 'Tahun Pengalaman', 'Years of Experience', true, ['placeholder' => json_encode(['id'=>'contoh: 3','en'=>'e.g. 3'])]),
            $q('q_bld_exp_fdr', 'step_bld_exp',  1, 'single_select_card', 'Apakah kamu memiliki pengalaman startup sebelumnya?', 'Do you have any prior startup experience?', true, ['depends_on' => json_encode(['question_id'=>'q_bld_type','operator'=>'equals','value'=>'founder'])]),
            $q('q_bld_exp_cf',  'step_bld_exp',  2, 'single_select_card', 'Apakah kamu memiliki pengalaman startup sebelumnya?', 'Do you have any prior startup experience?', true, ['depends_on' => json_encode(['question_id'=>'q_bld_type','operator'=>'equals','value'=>'cofounder'])]),
            $q('q_bld_exp_tm',  'step_bld_exp',  3, 'single_select_card', 'Apakah kamu memiliki pengalaman startup sebelumnya?', 'Do you have any prior startup experience?', true, ['depends_on' => json_encode(['question_id'=>'q_bld_type','operator'=>'equals','value'=>'team'])]),

            // ── FOUNDER ──
            $q('q_fdr_looking',  'step_fdr_looking',  1, 'single_select_card', 'Apa yang sedang kamu cari?', 'What are you looking for?'),
            $q('q_fdr_industry', 'step_fdr_industry', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', 'Select Industries (Max 5)', true, ['validation' => json_encode(['min_selections'=>1,'max_selections'=>5])]),

            // ── FOUNDER → CF ──
            $q('q_fdr_cf_type',    'step_fdr_cf_type',    1, 'multi_select_chip', 'Tipe Co-Founder yang dibutuhkan', 'Co-Founder type needed'),
            $q('q_fdr_cf_avail',   'step_fdr_cf_avail',   1, 'single_select_card', 'Availability', 'Availability'),
            $q('q_fdr_cf_remote',  'step_fdr_cf_remote',  1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_fdr_cf_relocate','step_fdr_cf_remote',  2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_fdr_cf_linkedin','step_fdr_cf_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id'=>'https://linkedin.com/in/...','en'=>'https://linkedin.com/in/...'])]),

            // ── FOUNDER → TEAM ──
            $q('q_fdr_tm_roles',   'step_fdr_tm_roles',   1, 'multi_select_chip', 'Peran yang dibutuhkan', 'Roles needed'),
            $q('q_fdr_tm_avail',   'step_fdr_tm_avail',   1, 'single_select_card', 'Availability', 'Availability'),
            $q('q_fdr_tm_remote',  'step_fdr_tm_remote',  1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_fdr_tm_relocate','step_fdr_tm_remote',  2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_fdr_tm_linkedin','step_fdr_tm_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id'=>'https://linkedin.com/in/...','en'=>'https://linkedin.com/in/...'])]),

            // ── FOUNDER → BOTH ──
            $q('q_fdr_bt_cf',      'step_fdr_bt_cf',    1, 'multi_select_chip', 'Tipe Co-Founder yang dibutuhkan', 'Co-Founder type needed'),
            $q('q_fdr_bt_roles',   'step_fdr_bt_roles', 1, 'multi_select_chip', 'Peran yang dibutuhkan', 'Roles needed'),
            $q('q_fdr_bt_avail',   'step_fdr_bt_avail', 1, 'single_select_card', 'Availability', 'Availability'),
            $q('q_fdr_bt_remote',  'step_fdr_bt_remote', 1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_fdr_bt_relocate','step_fdr_bt_remote', 2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_fdr_bt_linkedin','step_fdr_bt_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id'=>'https://linkedin.com/in/...','en'=>'https://linkedin.com/in/...'])]),

            // ── CO-FOUNDER JOINING ──
            $q('q_cf_industry',  'step_cf_industry', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', 'Select Industries (Max 5)', true, ['validation' => json_encode(['min_selections'=>1,'max_selections'=>5])]),
            $q('q_cf_type',      'step_cf_type',     1, 'single_select_card', 'Kamu co-founder tipe apa?', 'What kind of co-founder are you?'),
            $q('q_cf_avail',     'step_cf_avail',    1, 'single_select_card', 'Availability', 'Availability'),
            // Compensation
            $q('q_cf_equity',       'step_cf_comp', 1, 'single_select_card', 'Ekspektasi equity', 'Equity expectation'),
            $q('q_cf_salary_type',  'step_cf_comp', 2, 'single_select_card', 'Apakah kamu punya ekspektasi minimum gaji?', 'Do you have a minimum salary expectation?'),
            $q('q_cf_salary_period','step_cf_comp', 3, 'dropdown', 'Periode gaji', 'Salary period', true, ['depends_on' => json_encode(['question_id'=>'q_cf_salary_type','operator'=>'in','value'=>['strict','flexible']])]),
            $q('q_cf_salary_currency','step_cf_comp', 4, 'dropdown', 'Mata uang', 'Currency', true, ['depends_on' => json_encode(['question_id'=>'q_cf_salary_type','operator'=>'in','value'=>['strict','flexible']])]),
            $q('q_cf_salary_amount','step_cf_comp', 5, 'number', 'Berapa minimum gaji?', 'Minimum salary amount?', true, ['depends_on' => json_encode(['question_id'=>'q_cf_salary_type','operator'=>'in','value'=>['strict','flexible']]), 'placeholder' => json_encode(['id'=>'e.g. 10000000','en'=>'e.g. 10000000'])]),
            // Remote + LinkedIn
            $q('q_cf_remote',   'step_cf_remote',  1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_cf_relocate', 'step_cf_remote',  2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_cf_linkedin', 'step_cf_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id'=>'https://linkedin.com/in/...','en'=>'https://linkedin.com/in/...'])]),

            // ── TEAM MEMBER JOINING ──
            $q('q_tm_industry', 'step_tm_industry', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', 'Select Industries (Max 5)', true, ['validation' => json_encode(['min_selections'=>1,'max_selections'=>5])]),
            $q('q_tm_skills',   'step_tm_skills',   1, 'multi_select_chip', 'Skill yang kamu miliki', 'Skills you have'),
            $q('q_tm_avail',    'step_tm_avail',    1, 'single_select_card', 'Availability', 'Availability'),
            // Compensation
            $q('q_tm_equity',       'step_tm_comp', 1, 'single_select_card', 'Ekspektasi equity', 'Equity expectation'),
            $q('q_tm_salary_type',  'step_tm_comp', 2, 'single_select_card', 'Apakah kamu punya ekspektasi minimum gaji?', 'Do you have a minimum salary expectation?'),
            $q('q_tm_salary_period','step_tm_comp', 3, 'dropdown', 'Periode gaji', 'Salary period', true, ['depends_on' => json_encode(['question_id'=>'q_tm_salary_type','operator'=>'in','value'=>['strict','flexible']])]),
            $q('q_tm_salary_currency','step_tm_comp', 4, 'dropdown', 'Mata uang', 'Currency', true, ['depends_on' => json_encode(['question_id'=>'q_tm_salary_type','operator'=>'in','value'=>['strict','flexible']])]),
            $q('q_tm_salary_amount','step_tm_comp', 5, 'number', 'Berapa minimum gaji?', 'Minimum salary amount?', true, ['depends_on' => json_encode(['question_id'=>'q_tm_salary_type','operator'=>'in','value'=>['strict','flexible']]), 'placeholder' => json_encode(['id'=>'e.g. 10000000','en'=>'e.g. 10000000'])]),
            // Remote + LinkedIn
            $q('q_tm_remote',   'step_tm_remote',  1, 'single_select_card', 'Apakah kamu terbuka untuk kerja remote?', 'Are you open to remote work?'),
            $q('q_tm_relocate', 'step_tm_remote',  2, 'single_select_card', 'Apakah kamu bersedia relokasi?', 'Are you willing to relocate?'),
            $q('q_tm_linkedin', 'step_tm_linkedin', 1, 'url', 'LinkedIn URL', 'LinkedIn URL', false, ['placeholder' => json_encode(['id'=>'https://linkedin.com/in/...','en'=>'https://linkedin.com/in/...'])]),

            // ── STARTUP: About ──
            $q('q_su_name',    'step_su_about', 1, 'text', 'Nama Startup', 'Startup Name', true, ['validation' => json_encode(['min_length'=>2,'max_length'=>100])]),
            $q('q_su_tagline', 'step_su_about', 2, 'text', 'Tagline (1 kalimat)', 'Tagline (1 sentence)', true, ['validation' => json_encode(['max_length'=>150])]),
            $q('q_su_stage',   'step_su_about', 3, 'dropdown', 'Tahap Startup', 'Startup Stage'),

            // ── STARTUP: Problem & Solution ──
            $q('q_su_problem', 'step_su_problem', 1, 'textarea', 'Masalah yang kamu selesaikan', 'Problem you\'re solving'),
            $q('q_su_solution','step_su_problem', 2, 'textarea', 'Solusi kamu', 'Your solution'),
            $q('q_su_target',  'step_su_problem', 3, 'textarea', 'Target pengguna', 'Target users'),

            // ── STARTUP: Industry & Biz Model ──
            $q('q_su_industry', 'step_su_biz', 1, 'multi_select_chip', 'Pilih Industri (Maks 5)', 'Select Industries (Max 5)', true, ['validation' => json_encode(['min_selections'=>1,'max_selections'=>5])]),
            $q('q_su_biz_model','step_su_biz', 2, 'multi_select_chip', 'Model Bisnis', 'Business Model'),

            // ── TRACTION: Idea ──
            $q('q_su_tri_prototype',     'step_su_tr_idea', 1, 'single_select_card', 'Apakah kamu punya prototype?', 'Do you have a prototype?'),
            $q('q_su_tri_prototype_link','step_su_tr_idea', 2, 'url', 'Link Prototype', 'Prototype Link', false, ['depends_on' => json_encode(['question_id'=>'q_su_tri_prototype','operator'=>'equals','value'=>'yes'])]),
            $q('q_su_tri_waitlist',      'step_su_tr_idea', 3, 'number', 'Ukuran Waitlist', 'Waitlist Size', false),
            $q('q_su_tri_validation',    'step_su_tr_idea', 4, 'text', 'Validasi (interview, survey, dll)', 'Validation (interviews, surveys, etc.)', false),

            // ── TRACTION: MVP ──
            $q('q_su_trm_users',   'step_su_tr_mvp', 1, 'number', 'Jumlah Users', 'Number of Users', false),
            $q('q_su_trm_mau',     'step_su_tr_mvp', 2, 'number', 'Monthly Active Users (MAU)', 'Monthly Active Users (MAU)', false),
            $q('q_su_trm_revenue', 'step_su_tr_mvp', 3, 'number', 'Revenue (jika ada)', 'Revenue (if any)', false),
            $q('q_su_trm_growth',  'step_su_tr_mvp', 4, 'text', 'Growth Rate', 'Growth Rate', false),

            // ── TRACTION: Live ──
            $q('q_su_trl_mrr',       'step_su_tr_live', 1, 'number', 'Monthly Recurring Revenue (MRR)', 'MRR', false),
            $q('q_su_trl_customers', 'step_su_tr_live', 2, 'number', 'Jumlah Pelanggan', 'Number of Customers', false),
            $q('q_su_trl_retention', 'step_su_tr_live', 3, 'text', 'Retention Rate', 'Retention Rate', false),
            $q('q_su_trl_metrics',   'step_su_tr_live', 4, 'text', 'Key Metrics (GMV, dll)', 'Key Metrics (GMV, etc.)', false),

            // ── TRACTION: Scale ──
            $q('q_su_trs_funding',   'step_su_tr_scale', 1, 'text', 'Funding yang sudah didapat', 'Funding raised', false),
            $q('q_su_trs_investors', 'step_su_tr_scale', 2, 'text', 'Investor (opsional)', 'Investors (optional)', false),
            $q('q_su_trs_teamsize',  'step_su_tr_scale', 3, 'number', 'Ukuran Tim', 'Team Size', false),
            $q('q_su_trs_arr',       'step_su_tr_scale', 4, 'number', 'Annual Recurring Revenue (ARR)', 'ARR', false),

            // ── STARTUP FINISH: Online Presence ──
            $q('q_su_website',   'step_su_presence', 1, 'url', 'Website', 'Website', false),
            $q('q_su_linkedin',  'step_su_presence', 2, 'url', 'LinkedIn', 'LinkedIn', false),
            $q('q_su_twitter',   'step_su_presence', 3, 'url', 'Twitter / X', 'Twitter / X', false),
            $q('q_su_instagram', 'step_su_presence', 4, 'url', 'Instagram', 'Instagram', false),
            $q('q_su_pitchdeck', 'step_su_presence', 5, 'url', 'Pitch Deck (opsional 🔥)', 'Pitch Deck (optional 🔥)', false),

            // ── STARTUP FINISH: Founder Setup ──
            $q('q_su_founder_count',  'step_su_founders', 1, 'single_select_card', 'Berapa banyak founder?', 'How many founders?'),
            $q('q_su_founder_roles',  'step_su_founders', 2, 'multi_select_chip', 'Peran apa yang sudah terisi?', 'What roles are already covered?', false, ['depends_on' => json_encode(['question_id'=>'q_su_founder_count','operator'=>'not_equals','value'=>'solo'])]),

            // ── STARTUP FINISH: Team Status ──
            $q('q_su_have_team',   'step_su_team', 1, 'single_select_card', 'Apakah kamu punya tim selain founder?', 'Do you have a team beyond founders?'),
            $q('q_su_team_size',   'step_su_team', 2, 'single_select_card', 'Ukuran tim', 'Team size', true, ['depends_on' => json_encode(['question_id'=>'q_su_have_team','operator'=>'equals','value'=>'yes'])]),
            $q('q_su_team_roles',  'step_su_team', 3, 'multi_select_chip', 'Departemen/peran di tim', 'Team departments/roles', true, ['depends_on' => json_encode(['question_id'=>'q_su_have_team','operator'=>'equals','value'=>'yes'])]),

            // ── STARTUP FINISH: What You Need ──
            $q('q_su_need', 'step_su_need', 1, 'single_select_card', 'Apa yang sedang kamu cari?', 'What are you looking for?'),

            // ── STARTUP NEED: CF ──
            $q('q_su_need_cf_type',   'step_su_need_cf',    1, 'multi_select_chip', 'Co-Founder tipe apa yang dibutuhkan?', 'What Co-Founder type do you need?'),
            // ── STARTUP NEED: Team ──
            $q('q_su_need_tm_skills', 'step_su_need_tm',    1, 'multi_select_chip', 'Skill apa yang belum dipunyai di tim?', 'What skills are missing in the team?'),
            // ── STARTUP NEED: Both ──
            $q('q_su_need_bt_cf',     'step_su_need_bt_cf', 1, 'multi_select_chip', 'Co-Founder yang dibutuhkan', 'Co-Founder needed'),
            $q('q_su_need_bt_tm',     'step_su_need_bt_tm', 1, 'multi_select_chip', 'Skill yang belum dipunyai', 'Missing skills'),

            // ── STARTUP END: Commitment ──
            $q('q_su_commitment', 'step_su_commit', 1, 'single_select_card', 'Commitment Level', 'Commitment Level'),

            // ── STARTUP END: Equity & Comp ──
            $q('q_su_equity_range', 'step_su_equity', 1, 'text', 'Equity yang ditawarkan (% range)', 'Equity offered (% range)', true, ['placeholder' => json_encode(['id'=>'contoh: 5-15%','en'=>'e.g. 5-15%'])]),
            $q('q_su_paid',         'step_su_equity', 2, 'single_select_card', 'Apakah posisi ini dibayar?', 'Is this a paid position?'),
            $q('q_su_salary_range', 'step_su_equity', 3, 'text', 'Range gaji per tahun', 'Annual salary range', false, ['depends_on' => json_encode(['question_id'=>'q_su_paid','operator'=>'equals','value'=>'paid']), 'placeholder' => json_encode(['id'=>'contoh: IDR 100-200jt/thn','en'=>'e.g. USD 30-60k/yr'])]),
        ]);

        // ════════════════════════════════════════════════════════════════
        // 4. OPTIONS
        // ════════════════════════════════════════════════════════════════
        $opts = [];

        // ── Locations (68 cities with groups) ──
        foreach ($locations as $i => [$label, $value, $group]) {
            $opts[] = [
                'id' => 'opt_loc_' . ($i + 1), 'question_id' => 'q_location', 'order_index' => $i + 1,
                'label' => json_encode(['id' => $label, 'en' => $label]), 'value' => $value,
                'sub_label' => null, 'icon' => null, 'group_name' => $group,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }

        // ── Common Simple Options ──
        $opts[] = $o('opt_rem_yes', 'q_open_remote', 1, 'Ya', 'Yes', 'yes', null, null, 'yes');
        $opts[] = $o('opt_rem_no',  'q_open_remote', 2, 'Tidak', 'No', 'no', null, null, 'no');
        $opts[] = $o('opt_rp_1',  'q_remote_pref', 1, 'Hybrid', 'Hybrid', 'hybrid');
        $opts[] = $o('opt_rp_2',  'q_remote_pref', 2, 'Hanya Remote', 'Remote Only', 'remote_only');
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

        // ── Experience Level (Founder) ──
        $opts[] = $o('opt_exp_fdr_1', 'q_bld_exp_fdr', 1, 'Pernah mendirikan startup', 'Founded a startup before', 'founded', null, null, 'exp_founded');
        $opts[] = $o('opt_exp_fdr_2', 'q_bld_exp_fdr', 2, 'Pernah menjual startup', 'Sold a startup', 'sold', null, null, 'exp_sold');
        $opts[] = $o('opt_exp_fdr_3', 'q_bld_exp_fdr', 3, 'Pernah bekerja di startup', 'Worked in a startup', 'worked', null, null, 'exp_worked');
        $opts[] = $o('opt_exp_fdr_4', 'q_bld_exp_fdr', 4, 'Pernah membangun produk di startup', 'Built a product at a startup', 'built', null, null, 'exp_built');
        $opts[] = $o('opt_exp_fdr_5', 'q_bld_exp_fdr', 5, 'Tidak ada pengalaman startup sebelumnya', 'No Prior startup experience', 'none', null, null, 'exp_none');

        // ── Experience Level (Co-Founder) ──
        $opts[] = $o('opt_exp_cf_1', 'q_bld_exp_cf', 1, 'Pernah menjadi Founder / Co-Founder', 'Founder / co-founded a company', 'founded', null, null, 'exp_founded');
        $opts[] = $o('opt_exp_cf_2', 'q_bld_exp_cf', 2, 'Pernah menjual startup', 'Sold a startup', 'sold', null, null, 'exp_sold');
        $opts[] = $o('opt_exp_cf_3', 'q_bld_exp_cf', 3, 'Pernah bekerja di startup', 'Worked in a startup', 'worked', null, null, 'exp_worked');
        $opts[] = $o('opt_exp_cf_4', 'q_bld_exp_cf', 4, 'Pernah membangun produk di startup', 'Built a product at a startup', 'built', null, null, 'exp_built');
        $opts[] = $o('opt_exp_cf_5', 'q_bld_exp_cf', 5, 'Tidak ada pengalaman startup sebelumnya', 'No Prior startup experience', 'none', null, null, 'exp_none');

        // ── Experience Level (Team Member) ──
        $opts[] = $o('opt_exp_tm_1', 'q_bld_exp_tm', 1, 'Pernah menjadi Founder / Co-Founder', 'Founder / co-founded a company', 'founded', null, null, 'exp_founded');
        $opts[] = $o('opt_exp_tm_2', 'q_bld_exp_tm', 2, 'Pernah bekerja di startup', 'Worked in a startup', 'worked', null, null, 'exp_worked');
        $opts[] = $o('opt_exp_tm_3', 'q_bld_exp_tm', 3, 'Pernah membangun produk di startup', 'Built a product at a startup', 'built', null, null, 'exp_built');
        $opts[] = $o('opt_exp_tm_4', 'q_bld_exp_tm', 4, 'Tidak ada pengalaman startup sebelumnya', 'No Prior startup experience', 'none', null, null, 'exp_none');

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
            $opts[] = $o($p.'_1', $qid, 1, 'Full-time', 'Full-time', 'full_time', null, null, 'availability_full_time');
            $opts[] = $o($p.'_2', $qid, 2, 'Part-time', 'Part-time', 'part_time', null, null, 'availability_part_time');
            $opts[] = $o($p.'_3', $qid, 3, 'Fleksibel / Open', 'Flexible / Open', 'flexible', null, null, 'availability_flexible');
        }

        // ── Remote Options (reused) ──
        $remoteQuestions = ['q_fdr_cf_remote', 'q_fdr_tm_remote', 'q_fdr_bt_remote', 'q_cf_remote', 'q_tm_remote'];
        foreach ($remoteQuestions as $qid) {
            $p = str_replace('q_', 'opt_rm_', $qid);
            $opts[] = $o($p.'_1', $qid, 1, 'Ya', 'Yes', 'yes', null, null, 'yes');
            $opts[] = $o($p.'_2', $qid, 2, 'Tidak', 'No', 'no', null, null, 'no');
        }

        // ── Relocate Options (reused) ──
        $relocateQuestions = ['q_fdr_cf_relocate', 'q_fdr_tm_relocate', 'q_fdr_bt_relocate', 'q_cf_relocate', 'q_tm_relocate'];
        foreach ($relocateQuestions as $qid) {
            $p = str_replace('q_', 'opt_rl_', $qid);
            $opts[] = $o($p.'_1', $qid, 1, 'Ya', 'Yes', 'yes', null, null, 'yes');
            $opts[] = $o($p.'_2', $qid, 2, 'Tidak', 'No', 'no', null, null, 'no');
        }

        // ── Equity Expectation (Co-Founder + Team Member) ──
        $equityQuestions = ['q_cf_equity', 'q_tm_equity'];
        foreach ($equityQuestions as $qid) {
            $p = str_replace('q_', 'opt_eq_', $qid);
            $opts[] = $o($p.'_1', $qid, 1, 'Equity sangat penting', 'Equity is very important', 'equity_heavy');
            $opts[] = $o($p.'_2', $qid, 2, 'Tertarik dengan sebagian equity', 'Interested in some equity', 'partial_equity');
            $opts[] = $o($p.'_3', $qid, 3, 'Kompensasi berat di cash', 'Compensation heavy on cash', 'cash_heavy');
        }

        // ── Salary Type (Co-Founder + Team Member) ──
        $salaryTypeQuestions = ['q_cf_salary_type', 'q_tm_salary_type'];
        foreach ($salaryTypeQuestions as $qid) {
            $p = str_replace('q_', 'opt_st_', $qid);
            $opts[] = $o($p.'_1', $qid, 1, 'Ya, saya punya minimum yang tegas', 'Yes, I have a strict minimum', 'strict');
            $opts[] = $o($p.'_2', $qid, 2, 'Ya, tapi saya bisa turun tergantung peluang', 'Yes, but flexible depending on opportunity', 'flexible');
            $opts[] = $o($p.'_3', $qid, 3, 'Tidak, saya fleksibel soal gaji', 'No, I\'m flexible on salary', 'no_minimum');
        }

        // ── Salary Period (Co-Founder + Team Member) ──
        $salaryPeriodQuestions = ['q_cf_salary_period', 'q_tm_salary_period'];
        foreach ($salaryPeriodQuestions as $qid) {
            $p = str_replace('q_', 'opt_sp_', $qid);
            $opts[] = $o($p.'_1', $qid, 1, 'Per Tahun (Annual)', 'Annual', 'annual');
            $opts[] = $o($p.'_2', $qid, 2, 'Per Jam (Hourly)', 'Hourly', 'hourly');
        }

        // ── Salary Currency ──
        $salaryCurrencyQuestions = ['q_cf_salary_currency', 'q_tm_salary_currency'];
        foreach ($salaryCurrencyQuestions as $qid) {
            $p = str_replace('q_', 'opt_sc_', $qid);
            $opts[] = $o($p.'_1', $qid, 1, 'IDR', 'IDR', 'IDR');
            $opts[] = $o($p.'_2', $qid, 2, 'USD', 'USD', 'USD');
            $opts[] = $o($p.'_3', $qid, 3, 'SGD', 'SGD', 'SGD');
        }

        // ── Startup Stage ──
        $opts[] = $o('opt_stage_1', 'q_su_stage', 1, 'Idea', 'Idea', 'idea');
        $opts[] = $o('opt_stage_2', 'q_su_stage', 2, 'MVP', 'MVP', 'mvp');
        $opts[] = $o('opt_stage_3', 'q_su_stage', 3, 'Live (Sudah Launching)', 'Live (Already Launched)', 'live');
        $opts[] = $o('opt_stage_4', 'q_su_stage', 4, 'Scale (Seed / Series A)', 'Scale (Seed / Series A)', 'scale');

        // ── Business Models (grouped, for q_su_biz_model) ──
        $bizIdx = 0;
        foreach ($masterBizModels as $group => $models) {
            foreach ($models as [$label, $value]) {
                $bizIdx++;
                $opts[] = [
                    'id' => 'opt_biz_' . $bizIdx, 'question_id' => 'q_su_biz_model', 'order_index' => $bizIdx,
                    'label' => json_encode(['id' => $label, 'en' => $label]), 'value' => $value,
                    'sub_label' => null, 'icon' => null, 'group_name' => $group,
                    'created_at' => $now, 'updated_at' => $now,
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
        $founderRolesCovered = ['Technical','Product','Business','Growth','Operations','Finance','Design','Other'];
        foreach ($founderRolesCovered as $i => $r) {
            $opts[] = $o('opt_frc_'.($i+1), 'q_su_founder_roles', $i+1, $r, $r, \Illuminate\Support\Str::slug($r, '_'));
        }

        // ── Have Team ──
        $opts[] = $o('opt_ht_1', 'q_su_have_team', 1, 'Tidak, hanya founder', 'No, just founders', 'no', null, null, 'no');
        $opts[] = $o('opt_ht_2', 'q_su_have_team', 2, 'Ya', 'Yes', 'yes', null, null, 'yes');

        // ── Team Size ──
        $opts[] = $o('opt_ts_1', 'q_su_team_size', 1, '1-3 orang', '1-3 people', '1_3', null, null, 'team_size_small');
        $opts[] = $o('opt_ts_2', 'q_su_team_size', 2, '4-10 orang', '4-10 people', '4_10', null, null, 'team_size_medium');
        $opts[] = $o('opt_ts_3', 'q_su_team_size', 3, '10+ orang', '10+ people', '10_plus', null, null, 'team_size_large');

        // ── Team Departments ──
        $teamDepts = ['Engineering','Marketing','Sales','Operations','Design','Finance','Other'];
        foreach ($teamDepts as $i => $d) {
            $opts[] = $o('opt_td_'.($i+1), 'q_su_team_roles', $i+1, $d, $d, \Illuminate\Support\Str::slug($d, '_'));
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
                'created_at' => $now, 'updated_at' => $now,
            ];
        };

        DB::table('onboarding_transitions')->insert([
            // ── step_role_selection: startup → flow_startup, else → flow_builder_common ──
            $t('step_role_selection', ['question_id'=>'q_use_connectx','operator'=>'equals','value'=>'startup'], null, 'flow_startup', 10),
            $t('step_role_selection', null, null, 'flow_builder_common', 0),  // default: founder/cofounder/team all go to builder common

            // ── step_bld_exp: branch by original role selection ──
            $t('step_bld_exp', ['question_id'=>'q_bld_type','operator'=>'equals','value'=>'founder'],   null, 'flow_founder',   10),
            $t('step_bld_exp', ['question_id'=>'q_bld_type','operator'=>'equals','value'=>'cofounder'], null, 'flow_cofounder', 10),
            $t('step_bld_exp', ['question_id'=>'q_bld_type','operator'=>'equals','value'=>'team'],      null, 'flow_team',      10),

            // ── step_fdr_industry: branch by what founder is looking for ──
            $t('step_fdr_industry', ['question_id'=>'q_fdr_looking','operator'=>'equals','value'=>'cofounder'], null, 'flow_fdr_cf',   10),
            $t('step_fdr_industry', ['question_id'=>'q_fdr_looking','operator'=>'equals','value'=>'team'],      null, 'flow_fdr_team', 10),
            $t('step_fdr_industry', ['question_id'=>'q_fdr_looking','operator'=>'equals','value'=>'both'],      null, 'flow_fdr_both', 10),

            // ── Startup: step_su_biz → Traction by stage ──
            $t('step_su_biz', ['question_id'=>'q_su_stage','operator'=>'equals','value'=>'idea'],  null, 'flow_su_tr_idea',  10),
            $t('step_su_biz', ['question_id'=>'q_su_stage','operator'=>'equals','value'=>'mvp'],   null, 'flow_su_tr_mvp',   10),
            $t('step_su_biz', ['question_id'=>'q_su_stage','operator'=>'equals','value'=>'live'],  null, 'flow_su_tr_live',  10),
            $t('step_su_biz', ['question_id'=>'q_su_stage','operator'=>'equals','value'=>'scale'], null, 'flow_su_tr_scale', 10),

            // ── Traction → flow_su_finish (unconditional) ──
            $t('step_su_tr_idea',  null, null, 'flow_su_finish', 0),
            $t('step_su_tr_mvp',   null, null, 'flow_su_finish', 0),
            $t('step_su_tr_live',  null, null, 'flow_su_finish', 0),
            $t('step_su_tr_scale', null, null, 'flow_su_finish', 0),

            // ── step_su_need: branch by what startup needs ──
            $t('step_su_need', ['question_id'=>'q_su_need','operator'=>'equals','value'=>'cofounder'], null, 'flow_su_need_cf',   10),
            $t('step_su_need', ['question_id'=>'q_su_need','operator'=>'equals','value'=>'team'],      null, 'flow_su_need_team', 10),
            $t('step_su_need', ['question_id'=>'q_su_need','operator'=>'equals','value'=>'both'],      null, 'flow_su_need_both', 10),

            // ── Startup Need sub-flows → flow_su_end (unconditional) ──
            $t('step_su_need_cf',    null, null, 'flow_su_end', 0),
            $t('step_su_need_tm',    null, null, 'flow_su_end', 0),
            $t('step_su_need_bt_tm', null, null, 'flow_su_end', 0),
        ]);
    }
}
