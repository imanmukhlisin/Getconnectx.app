<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $industries = [
            'AIL', 'Fintech', 'Healthtech', 'EdTech', 'Web3', 'SaaS', 'Marketplace', 'Gaming',
            'Climate Tech', 'AgriTech', 'LegalTech', 'InsurTech', 'PropTech', 'FoodTech',
            'Logistics', 'E-Commerce', 'Media', 'Entertainment', 'Travel', 'Social', 'HRTech',
            'Cybersecurity', 'IoT', 'Robotics', 'Biotech', 'SpaceTech', 'Fashion', 'Sports',
            'Automotive', 'Energy', 'Construction', 'Telecom', 'GovTec'
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
            \App\Models\Tag::updateOrCreate(
                ['name' => $industry, 'type' => 'industry']
            );
        }

        foreach ($skills as $skill) {
            \App\Models\Tag::updateOrCreate(
                ['name' => $skill, 'type' => 'skill']
            );
        }
    }
}
