<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cleanup existing tags to avoid confusion and duplication
        DB::statement('TRUNCATE TABLE tags RESTART IDENTITY CASCADE');

        $industries = [
            'AI','Generative Tech/AI','DeepTech','AR/VR','IoT','Robotics','Semiconductors','Cloud Infrastructure','Developer Tools','Security','Data Services','Analytics',
            'SaaS','SMB Software','Productivity Tools','Sales & CRM','Enterprise','Messaging','Social Networks',
            'E-commerce','Marketplaces','Direct-to-Consumer (DTC)','Retail','Fashion','Cosmetics','Food and Beverage','Creator/Passion Economy',
            'FinTech','Payments','Insurance','LegalTech','Human Capital/HRTech',
            'Healthcare','Medical Devices','Pharmaceuticals','Education','EnergyTech','ClimateTech/CleanTech','AgTech','ConstructionTech','Manufacturing','Logistics','Supply Chain Tech','TransportationTech','Real Estate/PropTech','GovTech',
            'Gaming','Entertainment & Sports','Media/Content','Travel','Lodging/Hospitality','Wellness & Fitness','Mental Health','Parenting/Families',
            'Web3/Blockchain','Space','Smart Cities/UrbanTech','Future of Work','Gig Economy','Social Impact','Hardware','Material Science',
        ];

        $skills = [
            'AI/ML', 'Full-Stack', 'Frontend', 'Backend', 'Mobile Dev', 'Data Science', 'Cloud/Infra',
            'DevOps', 'Blockchain', 'Cybersecurity', 'QA/Testing', 'Embedded Systems', 'Game Dev',
            'AR/VR', 'Robotics', 'NLP', 'Hardware', 'Product Management', 'UI/UX', 'Graphic Design',
            'UX Research', 'Digital Marketing', 'SEO/SEM', 'Social Media', 'Content Creation',
            'Copywriting', 'Brand Strategy', 'Email Marketing', 'Influencer Marketing', 'PR/Comms',
            'Sales Business', 'Dev Partnerships', 'Account Management', 'Customer Success',
            'Lead Generation', 'Operations', 'Supply Chain', 'Project Management', 'Strategy',
            'Process Optimization', 'Logistics', 'Finance', 'Accounting', 'Financial Modeling',
            'Fundraising', 'Investor Relations', 'Tax/Compliance', 'Bookkeeping', 'Legal',
            'HR/Recruiting', 'Talent Acquisition', 'People Ops', 'Compensation & Benefits',
            'Technical Writing', 'Community Management', 'Data Analytics', 'Market Research',
            'Public Speaking', 'Consulting'
        ];

        foreach ($industries as $industry) {
            Tag::create([
                'name' => $industry,
                'type' => 'industry'
            ]);
        }

        foreach ($skills as $skill) {
            Tag::create([
                'name' => $skill,
                'type' => 'skill'
            ]);
        }
    }
}
