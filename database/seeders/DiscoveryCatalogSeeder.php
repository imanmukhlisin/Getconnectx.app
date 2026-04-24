<?php

namespace Database\Seeders;

use App\Models\DiscoveryCatalog;
use Illuminate\Database\Seeder;

class DiscoveryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $allModes    = ['finding_cofounder', 'building_team', 'explore_startups', 'joining_startups'];
        $personModes = ['finding_cofounder', 'building_team'];
        $startupModes = ['explore_startups', 'joining_startups'];

        $catalogs = [
            // ═══════════════════════════════════════════════════════════════════
            //  INDUSTRIES (shared across all modes)
            // ═══════════════════════════════════════════════════════════════════
            ['id' => 'ind_ai',         'type' => 'industry', 'group_id' => 'grp_industry_core_technology', 'group_label' => 'Core Technology',    'label' => 'AI',         'modes' => $allModes, 'sort_order' => 1],
            ['id' => 'ind_fintech',    'type' => 'industry', 'group_id' => 'grp_industry_core_technology', 'group_label' => 'Core Technology',    'label' => 'Fintech',    'modes' => $allModes, 'sort_order' => 2],
            ['id' => 'ind_healthtech', 'type' => 'industry', 'group_id' => 'grp_industry_impact',         'group_label' => 'Impact & Health',    'label' => 'Healthtech', 'modes' => $allModes, 'sort_order' => 3],
            ['id' => 'ind_edtech',     'type' => 'industry', 'group_id' => 'grp_industry_impact',         'group_label' => 'Impact & Health',    'label' => 'Edtech',     'modes' => $allModes, 'sort_order' => 4],
            ['id' => 'ind_web3',       'type' => 'industry', 'group_id' => 'grp_industry_core_technology', 'group_label' => 'Core Technology',    'label' => 'Web3',       'modes' => $allModes, 'sort_order' => 5],
            ['id' => 'ind_saas',       'type' => 'industry', 'group_id' => 'grp_industry_business',       'group_label' => 'Business & SaaS',    'label' => 'SaaS',       'modes' => $allModes, 'sort_order' => 6],

            // ═══════════════════════════════════════════════════════════════════
            //  SKILLS (building_team mode, also useful for finding_cofounder)
            // ═══════════════════════════════════════════════════════════════════
            ['id' => 'skill_react',      'type' => 'skill', 'group_id' => 'grp_skill_engineering', 'group_label' => 'Engineering',     'label' => 'React',      'modes' => $personModes, 'sort_order' => 1],
            ['id' => 'skill_python',     'type' => 'skill', 'group_id' => 'grp_skill_engineering', 'group_label' => 'Engineering',     'label' => 'Python',     'modes' => $personModes, 'sort_order' => 2],
            ['id' => 'skill_figma',      'type' => 'skill', 'group_id' => 'grp_skill_design',      'group_label' => 'Design',          'label' => 'Figma',      'modes' => $personModes, 'sort_order' => 3],
            ['id' => 'skill_growth',     'type' => 'skill', 'group_id' => 'grp_skill_marketing',   'group_label' => 'Growth & Sales',  'label' => 'Growth',     'modes' => $personModes, 'sort_order' => 4],
            ['id' => 'skill_seo',        'type' => 'skill', 'group_id' => 'grp_skill_marketing',   'group_label' => 'Growth & Sales',  'label' => 'SEO',        'modes' => $personModes, 'sort_order' => 5],
            ['id' => 'skill_salesforce', 'type' => 'skill', 'group_id' => 'grp_skill_marketing',   'group_label' => 'Growth & Sales',  'label' => 'Salesforce', 'modes' => $personModes, 'sort_order' => 6],

            // ═══════════════════════════════════════════════════════════════════
            //  ROLES (building_team + explore/joining startups)
            // ═══════════════════════════════════════════════════════════════════
            ['id' => 'role_engineer',   'type' => 'role', 'group_id' => 'grp_role_product_engineering', 'group_label' => 'Product & Build', 'label' => 'Engineer',   'modes' => ['building_team', 'explore_startups'], 'sort_order' => 1],
            ['id' => 'role_product',    'type' => 'role', 'group_id' => 'grp_role_product_engineering', 'group_label' => 'Product & Build', 'label' => 'Product',    'modes' => ['building_team'],                     'sort_order' => 2],
            ['id' => 'role_designer',   'type' => 'role', 'group_id' => 'grp_role_product_engineering', 'group_label' => 'Product & Build', 'label' => 'Designer',   'modes' => ['building_team', 'explore_startups'], 'sort_order' => 3],
            ['id' => 'role_sales',      'type' => 'role', 'group_id' => 'grp_role_growth',              'group_label' => 'Growth',          'label' => 'Sales',      'modes' => ['building_team', 'explore_startups'], 'sort_order' => 4],
            ['id' => 'role_marketing',  'type' => 'role', 'group_id' => 'grp_role_growth',              'group_label' => 'Growth',          'label' => 'Marketing',  'modes' => ['building_team', 'explore_startups'], 'sort_order' => 5],
            ['id' => 'role_operations', 'type' => 'role', 'group_id' => 'grp_role_operations',          'group_label' => 'Operations',      'label' => 'Operations', 'modes' => ['building_team', 'explore_startups'], 'sort_order' => 6],
            ['id' => 'role_finance',    'type' => 'role', 'group_id' => 'grp_role_operations',          'group_label' => 'Operations',      'label' => 'Finance',    'modes' => ['building_team'],                     'sort_order' => 7],
            ['id' => 'role_growth',     'type' => 'role', 'group_id' => 'grp_role_growth',              'group_label' => 'Growth',          'label' => 'Growth',     'modes' => ['building_team'],                     'sort_order' => 8],
            ['id' => 'role_ai_ml',      'type' => 'role', 'group_id' => 'grp_role_product_engineering', 'group_label' => 'Product & Build', 'label' => 'AI/ML',      'modes' => ['building_team'],                     'sort_order' => 9],

            // ═══════════════════════════════════════════════════════════════════
            //  LANGUAGES
            // ═══════════════════════════════════════════════════════════════════
            ['id' => 'lang_en', 'type' => 'language', 'group_id' => 'grp_language_global', 'group_label' => 'Languages', 'label' => 'English',           'modes' => $allModes, 'sort_order' => 1],
            ['id' => 'lang_id', 'type' => 'language', 'group_id' => 'grp_language_global', 'group_label' => 'Languages', 'label' => 'Bahasa Indonesia',  'modes' => $allModes, 'sort_order' => 2],
            ['id' => 'lang_zh', 'type' => 'language', 'group_id' => 'grp_language_global', 'group_label' => 'Languages', 'label' => 'Mandarin',          'modes' => $allModes, 'sort_order' => 3],
            ['id' => 'lang_ja', 'type' => 'language', 'group_id' => 'grp_language_global', 'group_label' => 'Languages', 'label' => 'Japanese',          'modes' => $allModes, 'sort_order' => 4],
            ['id' => 'lang_ko', 'type' => 'language', 'group_id' => 'grp_language_global', 'group_label' => 'Languages', 'label' => 'Korean',            'modes' => $allModes, 'sort_order' => 5],
            ['id' => 'lang_es', 'type' => 'language', 'group_id' => 'grp_language_global', 'group_label' => 'Languages', 'label' => 'Spanish',           'modes' => $allModes, 'sort_order' => 6],
        ];

        foreach ($catalogs as $catalog) {
            DiscoveryCatalog::updateOrCreate(
                ['id' => $catalog['id']],
                $catalog
            );
        }

        $this->command->info('✅ Discovery catalogs seeded: ' . count($catalogs) . ' items.');
    }
}
