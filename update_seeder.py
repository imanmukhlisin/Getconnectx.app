import re

with open('database/seeders/OnboardingSeeder.php', 'r') as f:
    content = f.read()

# Update $masterCFTypes
content = content.replace(
    "['Technical Co-Founder',     'tech',         'Saya membangun produk & teknologi',           'I build the product & tech'],",
    "['Technical Co-Founder',     'tech',         'Saya membangun produk & teknologi',           'I build the product & tech', 'cofounder_technical'],"
).replace(
    "['Product Co-Founder',       'product',      'Saya memimpin produk dan desain',             'I lead product & design'],",
    "['Product Co-Founder',       'product',      'Saya memimpin produk dan desain',             'I lead product & design', 'cofounder_product'],"
).replace(
    "['Business Co-Founder',      'business',     'Saya menangani strategi dan operasional',     'I handle strategy & ops'],",
    "['Business Co-Founder',      'business',     'Saya menangani strategi dan operasional',     'I handle strategy & ops', 'cofounder_business'],"
).replace(
    "['Growth Co-Founder',        'growth',       'Saya menggerakkan marketing dan growth',      'I drive marketing & growth'],",
    "['Growth Co-Founder',        'growth',       'Saya menggerakkan marketing dan growth',      'I drive marketing & growth', 'cofounder_growth'],"
).replace(
    "['AI / Data Co-Founder',     'ai_data',      'Saya membangun AI, data & intelligence',      'I build AI, data & intelligence'],",
    "['AI / Data Co-Founder',     'ai_data',      'Saya membangun AI, data & intelligence',      'I build AI, data & intelligence', 'cofounder_ai'],"
).replace(
    "['Operations Co-Founder',    'operations',   'Saya mengeksekusi dan menskalakan operasional','I execute & scale operations'],",
    "['Operations Co-Founder',    'operations',   'Saya mengeksekusi dan menskalakan operasional','I execute & scale operations', 'cofounder_operations'],"
).replace(
    "['Finance Co-Founder',       'finance',      'Saya mengelola fundraising dan keuangan',     'I manage fundraising & finance'],",
    "['Finance Co-Founder',       'finance',      'Saya mengelola fundraising dan keuangan',     'I manage fundraising & finance', 'cofounder_finance'],"
).replace(
    "['Partnerships Co-Founder',  'partnerships', 'Saya membangun deal dan partnership',         'I build deals & partnerships'],",
    "['Partnerships Co-Founder',  'partnerships', 'Saya membangun deal dan partnership',         'I build deals & partnerships', 'cofounder_partnerships'],"
)

# Update $o function definition
content = content.replace(
    "$o = function ($id, $qid, $order, $labelId, $labelEn, $value, $subId = null, $subEn = null) use ($now) {",
    "$o = function ($id, $qid, $order, $labelId, $labelEn, $value, $subId = null, $subEn = null, $icon = null) use ($now) {"
)
content = content.replace(
    "'sub_label' => ($subId && $subEn) ? json_encode(['id' => $subId, 'en' => $subEn]) : null,\n                'icon' => null, 'group_name' => null,",
    "'sub_label' => ($subId && $subEn) ? json_encode(['id' => $subId, 'en' => $subEn]) : null,\n                'icon' => $icon, 'group_name' => null,"
)

# Update $genCFOpts definition
content = content.replace(
    "foreach ($masterCFTypes as $i => [$label, $value, $subId, $subEn]) {",
    "foreach ($masterCFTypes as $i => [$label, $value, $subId, $subEn, $icon]) {"
)
content = content.replace(
    "'sub_label' => json_encode(['id' => $subId, 'en' => $subEn]),\n                    'icon' => null, 'group_name' => null,",
    "'sub_label' => json_encode(['id' => $subId, 'en' => $subEn]),\n                    'icon' => $icon, 'group_name' => null,"
)

# Replacements for specific $opts[] = $o(...)
replacements = [
    (r"\$opts\[\] = \$o\('opt_rem_yes', 'q_open_remote', 1, 'Ya', 'Yes', 'yes'\);",
     r"\$opts[] = \$o('opt_rem_yes', 'q_open_remote', 1, 'Ya', 'Yes', 'yes', null, null, 'yes');"),
    
    (r"\$opts\[\] = \$o\('opt_rem_no',  'q_open_remote', 2, 'Tidak', 'No', 'no'\);",
     r"\$opts[] = \$o('opt_rem_no',  'q_open_remote', 2, 'Tidak', 'No', 'no', null, null, 'no');"),
     
    (r"\$opts\[\] = \$o\('opt_uc_1', 'q_use_connectx', 1, 'Saya seorang Builder — Founder', 'I\\'m a Builder — Founder', 'founder', 'Saya ingin membangun startup dan mencari partner', 'I want to build a startup and find partners'\);",
     r"\$opts[] = \$o('opt_uc_1', 'q_use_connectx', 1, 'Saya seorang Builder — Founder', 'I\\'m a Builder — Founder', 'founder', 'Saya ingin membangun startup dan mencari partner', 'I want to build a startup and find partners', 'founder_rocket');"),
     
    (r"\$opts\[\] = \$o\('opt_uc_2', 'q_use_connectx', 2, 'Saya seorang Builder — Co-Founder', 'I\\'m a Builder — Co-Founder', 'cofounder', 'Saya ingin bergabung sebagai co-founder', 'I want to join as a co-founder'\);",
     r"\$opts[] = \$o('opt_uc_2', 'q_use_connectx', 2, 'Saya seorang Builder — Co-Founder', 'I\\'m a Builder — Co-Founder', 'cofounder', 'Saya ingin bergabung sebagai co-founder', 'I want to join as a co-founder', 'cofounder_handshake');"),
     
    (r"\$opts\[\] = \$o\('opt_uc_3', 'q_use_connectx', 3, 'Saya seorang Builder — Anggota Tim', 'I\\'m a Builder — Team Member', 'team', 'Saya ingin bergabung dengan tim startup', 'I want to join a startup team'\);",
     r"\$opts[] = \$o('opt_uc_3', 'q_use_connectx', 3, 'Saya seorang Builder — Anggota Tim', 'I\\'m a Builder — Team Member', 'team', 'Saya ingin bergabung dengan tim startup', 'I want to join a startup team', 'team_member_group');"),
     
    (r"\$opts\[\] = \$o\('opt_uc_4', 'q_use_connectx', 4, 'Saya mewakili Startup', 'I represent a Startup', 'startup', 'Startup saya sedang mencari talent', 'My startup is looking for talent'\);",
     r"\$opts[] = \$o('opt_uc_4', 'q_use_connectx', 4, 'Saya mewakili Startup', 'I represent a Startup', 'startup', 'Startup saya sedang mencari talent', 'My startup is looking for talent', 'rocket');"),

    (r"\$opts\[\] = \$o\('opt_exp_1', 'q_bld_exp', 1, 'Pernah mendirikan startup', 'Founded a startup before', 'founder_exp'\);",
     r"\$opts[] = \$o('opt_exp_1', 'q_bld_exp', 1, 'Pernah mendirikan startup', 'Founded a startup before', 'founder_exp', null, null, 'exp_founded');"),
     
    (r"\$opts\[\] = \$o\('opt_exp_2', 'q_bld_exp', 2, 'Pernah membangun produk di startup', 'Built a product at a startup', 'product_exp'\);",
     r"\$opts[] = \$o('opt_exp_2', 'q_bld_exp', 2, 'Pernah membangun produk di startup', 'Built a product at a startup', 'product_exp', null, null, 'exp_built');"),
     
    (r"\$opts\[\] = \$o\('opt_exp_3', 'q_bld_exp', 3, 'Pernah bekerja di tim startup', 'Worked in a startup team', 'team_exp'\);",
     r"\$opts[] = \$o('opt_exp_3', 'q_bld_exp', 3, 'Pernah bekerja di tim startup', 'Worked in a startup team', 'team_exp', null, null, 'exp_worked');"),
     
    (r"\$opts\[\] = \$o\('opt_exp_4', 'q_bld_exp', 4, 'Baru di dunia startup', 'New to the startup world', 'no_exp'\);",
     r"\$opts[] = \$o('opt_exp_4', 'q_bld_exp', 4, 'Baru di dunia startup', 'New to the startup world', 'no_exp', null, null, 'exp_none');"),

    (r"\$opts\[\] = \$o\('opt_fdr_look_1', 'q_fdr_looking', 1, 'Co-Founder', 'Co-Founder', 'cofounder', 'Mencari partner untuk membangun bersama', 'Looking for a partner to build together'\);",
     r"\$opts[] = \$o('opt_fdr_look_1', 'q_fdr_looking', 1, 'Co-Founder', 'Co-Founder', 'cofounder', 'Mencari partner untuk membangun bersama', 'Looking for a partner to build together', 'goal_cofounder');"),
     
    (r"\$opts\[\] = \$o\('opt_fdr_look_2', 'q_fdr_looking', 2, 'Anggota Tim', 'Team Members', 'team', 'Mencari anggota tim untuk startup saya', 'Looking for team members for my startup'\);",
     r"\$opts[] = \$o('opt_fdr_look_2', 'q_fdr_looking', 2, 'Anggota Tim', 'Team Members', 'team', 'Mencari anggota tim untuk startup saya', 'Looking for team members for my startup', 'goal_team_members');"),
     
    (r"\$opts\[\] = \$o\('opt_fdr_look_3', 'q_fdr_looking', 3, 'Keduanya', 'Both', 'both', 'Mencari co-founder dan anggota tim', 'Looking for both co-founder and team members'\);",
     r"\$opts[] = \$o('opt_fdr_look_3', 'q_fdr_looking', 3, 'Keduanya', 'Both', 'both', 'Mencari co-founder dan anggota tim', 'Looking for both co-founder and team members', 'goal_both');"),

    (r"\$opts\[\] = \$o\(\$p.'_1', \$qid, 1, 'Full-time', 'Full-time', 'full_time'\);",
     r"\$opts[] = \$o(\$p.'_1', \$qid, 1, 'Full-time', 'Full-time', 'full_time', null, null, 'availability_full_time');"),
     
    (r"\$opts\[\] = \$o\(\$p.'_2', \$qid, 2, 'Part-time', 'Part-time', 'part_time'\);",
     r"\$opts[] = \$o(\$p.'_2', \$qid, 2, 'Part-time', 'Part-time', 'part_time', null, null, 'availability_part_time');"),
     
    (r"\$opts\[\] = \$o\(\$p.'_3', \$qid, 3, 'Fleksibel / Open', 'Flexible / Open', 'flexible'\);",
     r"\$opts[] = \$o(\$p.'_3', \$qid, 3, 'Fleksibel / Open', 'Flexible / Open', 'flexible', null, null, 'availability_flexible');"),

    (r"\$opts\[\] = \$o\(\$p.'_1', \$qid, 1, 'Ya', 'Yes', 'yes'\);",
     r"\$opts[] = \$o(\$p.'_1', \$qid, 1, 'Ya', 'Yes', 'yes', null, null, 'yes');"),
     
    (r"\$opts\[\] = \$o\(\$p.'_2', \$qid, 2, 'Tidak', 'No', 'no'\);",
     r"\$opts[] = \$o(\$p.'_2', \$qid, 2, 'Tidak', 'No', 'no', null, null, 'no');"),
     
    (r"\$opts\[\] = \$o\('opt_proto_1', 'q_su_tri_prototype', 1, 'Ya', 'Yes', 'yes'\);",
     r"\$opts[] = \$o('opt_proto_1', 'q_su_tri_prototype', 1, 'Ya', 'Yes', 'yes', null, null, 'yes');"),
     
    (r"\$opts\[\] = \$o\('opt_proto_2', 'q_su_tri_prototype', 2, 'Belum', 'No', 'no'\);",
     r"\$opts[] = \$o('opt_proto_2', 'q_su_tri_prototype', 2, 'Belum', 'No', 'no', null, null, 'no');"),
     
    (r"\$opts\[\] = \$o\('opt_fc_1', 'q_su_founder_count', 1, 'Solo Founder', 'Solo Founder', 'solo'\);",
     r"\$opts[] = \$o('opt_fc_1', 'q_su_founder_count', 1, 'Solo Founder', 'Solo Founder', 'solo', null, null, 'founder_solo');"),
     
    (r"\$opts\[\] = \$o\('opt_fc_2', 'q_su_founder_count', 2, '2 Founders', '2 Founders', '2_founders'\);",
     r"\$opts[] = \$o('opt_fc_2', 'q_su_founder_count', 2, '2 Founders', '2 Founders', '2_founders', null, null, 'founder_two');"),
     
    (r"\$opts\[\] = \$o\('opt_fc_3', 'q_su_founder_count', 3, '3\+ Founders', '3\+ Founders', '3plus_founders'\);",
     r"\$opts[] = \$o('opt_fc_3', 'q_su_founder_count', 3, '3+ Founders', '3+ Founders', '3plus_founders', null, null, 'founder_three_plus');"),
     
    (r"\$opts\[\] = \$o\('opt_ht_1', 'q_su_have_team', 1, 'Tidak, hanya founder', 'No, just founders', 'no'\);",
     r"\$opts[] = \$o('opt_ht_1', 'q_su_have_team', 1, 'Tidak, hanya founder', 'No, just founders', 'no', null, null, 'no');"),
     
    (r"\$opts\[\] = \$o\('opt_ht_2', 'q_su_have_team', 2, 'Ya', 'Yes', 'yes'\);",
     r"\$opts[] = \$o('opt_ht_2', 'q_su_have_team', 2, 'Ya', 'Yes', 'yes', null, null, 'yes');"),
     
    (r"\$opts\[\] = \$o\('opt_ts_1', 'q_su_team_size', 1, '1-3 orang', '1-3 people', '1_3'\);",
     r"\$opts[] = \$o('opt_ts_1', 'q_su_team_size', 1, '1-3 orang', '1-3 people', '1_3', null, null, 'team_size_small');"),
     
    (r"\$opts\[\] = \$o\('opt_ts_2', 'q_su_team_size', 2, '4-10 orang', '4-10 people', '4_10'\);",
     r"\$opts[] = \$o('opt_ts_2', 'q_su_team_size', 2, '4-10 orang', '4-10 people', '4_10', null, null, 'team_size_medium');"),
     
    (r"\$opts\[\] = \$o\('opt_ts_3', 'q_su_team_size', 3, '10\+ orang', '10\+ people', '10_plus'\);",
     r"\$opts[] = \$o('opt_ts_3', 'q_su_team_size', 3, '10+ orang', '10+ people', '10_plus', null, null, 'team_size_large');"),
     
    (r"\$opts\[\] = \$o\('opt_sn_1', 'q_su_need', 1, 'Co-Founder', 'Co-Founder', 'cofounder'\);",
     r"\$opts[] = \$o('opt_sn_1', 'q_su_need', 1, 'Co-Founder', 'Co-Founder', 'cofounder', null, null, 'goal_cofounder');"),
     
    (r"\$opts\[\] = \$o\('opt_sn_2', 'q_su_need', 2, 'Anggota Tim', 'Team Members', 'team'\);",
     r"\$opts[] = \$o('opt_sn_2', 'q_su_need', 2, 'Anggota Tim', 'Team Members', 'team', null, null, 'goal_team_members');"),
     
    (r"\$opts\[\] = \$o\('opt_sn_3', 'q_su_need', 3, 'Keduanya', 'Both', 'both'\);",
     r"\$opts[] = \$o('opt_sn_3', 'q_su_need', 3, 'Keduanya', 'Both', 'both', null, null, 'goal_both');")
]

for pat, rep in replacements:
    content = re.sub(pat, rep, content)

with open('database/seeders/OnboardingSeeder.php', 'w') as f:
    f.write(content)

print("Done")
