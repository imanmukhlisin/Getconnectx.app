<?php

namespace App\Services\Discovery;

/**
 * Single source of truth for city catalog used in Discovery filter-options.
 * Mirrors the $locations array in OnboardingSeeder.
 * Format: [label, value, group]
 */
class CityCatalog
{
    /**
     * Returns flat list: [['label' => ..., 'value' => ..., 'group' => ...], ...]
     */
    public static function all(): array
    {
        $raw = self::raw();
        return array_map(fn($r) => [
            'id'    => 'opt_city_' . $r[1],
            'label' => $r[0],
            'value' => $r[1],
            'group' => $r[2],
        ], $raw);
    }

    /**
     * Returns flat list of valid city values for validation.
     */
    public static function values(): array
    {
        return array_column(self::raw(), 1);
    }

    /**
     * Raw data: [label, value, group]
     */
    private static function raw(): array
    {
        return [
            // ── INDONESIA ──
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
            ['Bontang, Indonesia', 'bontang', 'Indonesia'],
            ['Tarakan, Indonesia', 'tarakan', 'Indonesia'],
            ['Singkawang, Indonesia', 'singkawang', 'Indonesia'],
            ['Banjarbaru, Indonesia', 'banjarbaru', 'Indonesia'],
            ['Sampit, Indonesia', 'sampit', 'Indonesia'],
            ['Pangkalan Bun, Indonesia', 'pangkalan_bun', 'Indonesia'],
            ['Bitung, Indonesia', 'bitung', 'Indonesia'],
            ['Tomohon, Indonesia', 'tomohon', 'Indonesia'],
            ['Kotamobagu, Indonesia', 'kotamobagu', 'Indonesia'],
            ['Parepare, Indonesia', 'parepare', 'Indonesia'],
            ['Palopo, Indonesia', 'palopo', 'Indonesia'],
            ['Baubau, Indonesia', 'baubau', 'Indonesia'],
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
            ['Ho Chi Minh City, Vietnam', 'ho_chi_minh', 'Asia Tenggara'],
            ['Hanoi, Vietnam', 'hanoi', 'Asia Tenggara'],
            ['Da Nang, Vietnam', 'da_nang', 'Asia Tenggara'],
            ['Manila, Philippines', 'manila', 'Asia Tenggara'],
            ['Cebu City, Philippines', 'cebu', 'Asia Tenggara'],
            ['Davao, Philippines', 'davao', 'Asia Tenggara'],
            ['Makati, Philippines', 'makati', 'Asia Tenggara'],
            ['Phnom Penh, Cambodia', 'phnom_penh', 'Asia Tenggara'],
            ['Yangon, Myanmar', 'yangon', 'Asia Tenggara'],
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
            ['Karachi, Pakistan', 'karachi', 'Asia Selatan'],
            ['Lahore, Pakistan', 'lahore', 'Asia Selatan'],
            ['Islamabad, Pakistan', 'islamabad', 'Asia Selatan'],
            ['Colombo, Sri Lanka', 'colombo', 'Asia Selatan'],
            ['Dhaka, Bangladesh', 'dhaka', 'Asia Selatan'],
            ['Kathmandu, Nepal', 'kathmandu', 'Asia Selatan'],

            // ── ASIA TIMUR ──
            ['Tokyo, Japan', 'tokyo', 'Asia Timur'],
            ['Osaka, Japan', 'osaka', 'Asia Timur'],
            ['Fukuoka, Japan', 'fukuoka', 'Asia Timur'],
            ['Seoul, South Korea', 'seoul', 'Asia Timur'],
            ['Busan, South Korea', 'busan', 'Asia Timur'],
            ['Beijing, China', 'beijing', 'Asia Timur'],
            ['Shanghai, China', 'shanghai', 'Asia Timur'],
            ['Shenzhen, China', 'shenzhen', 'Asia Timur'],
            ['Guangzhou, China', 'guangzhou', 'Asia Timur'],
            ['Hangzhou, China', 'hangzhou', 'Asia Timur'],
            ['Hong Kong', 'hong_kong', 'Asia Timur'],
            ['Taipei, Taiwan', 'taipei', 'Asia Timur'],

            // ── TIMUR TENGAH ──
            ['Dubai, UAE', 'dubai', 'Timur Tengah'],
            ['Abu Dhabi, UAE', 'abu_dhabi', 'Timur Tengah'],
            ['Riyadh, Saudi Arabia', 'riyadh', 'Timur Tengah'],
            ['Jeddah, Saudi Arabia', 'jeddah', 'Timur Tengah'],
            ['Tel Aviv, Israel', 'tel_aviv', 'Timur Tengah'],
            ['Doha, Qatar', 'doha', 'Timur Tengah'],
            ['Istanbul, Turkey', 'istanbul', 'Timur Tengah'],
            ['Tehran, Iran', 'tehran', 'Timur Tengah'],

            // ── EROPA BARAT ──
            ['London, UK', 'london', 'Eropa Barat'],
            ['Manchester, UK', 'manchester', 'Eropa Barat'],
            ['Berlin, Germany', 'berlin', 'Eropa Barat'],
            ['Munich, Germany', 'munich', 'Eropa Barat'],
            ['Amsterdam, Netherlands', 'amsterdam', 'Eropa Barat'],
            ['Paris, France', 'paris', 'Eropa Barat'],
            ['Brussels, Belgium', 'brussels', 'Eropa Barat'],
            ['Zurich, Switzerland', 'zurich', 'Eropa Barat'],
            ['Dublin, Ireland', 'dublin', 'Eropa Barat'],
            ['Vienna, Austria', 'vienna', 'Eropa Barat'],

            // ── EROPA UTARA ──
            ['Stockholm, Sweden', 'stockholm', 'Eropa Utara'],
            ['Helsinki, Finland', 'helsinki', 'Eropa Utara'],
            ['Copenhagen, Denmark', 'copenhagen', 'Eropa Utara'],
            ['Oslo, Norway', 'oslo', 'Eropa Utara'],
            ['Tallinn, Estonia', 'tallinn', 'Eropa Utara'],

            // ── EROPA SELATAN ──
            ['Lisbon, Portugal', 'lisbon', 'Eropa Selatan'],
            ['Barcelona, Spain', 'barcelona', 'Eropa Selatan'],
            ['Madrid, Spain', 'madrid', 'Eropa Selatan'],
            ['Rome, Italy', 'rome', 'Eropa Selatan'],
            ['Milan, Italy', 'milan', 'Eropa Selatan'],
            ['Athens, Greece', 'athens', 'Eropa Selatan'],

            // ── EROPA TIMUR ──
            ['Warsaw, Poland', 'warsaw', 'Eropa Timur'],
            ['Prague, Czech Republic', 'prague', 'Eropa Timur'],
            ['Budapest, Hungary', 'budapest', 'Eropa Timur'],
            ['Bucharest, Romania', 'bucharest', 'Eropa Timur'],
            ['Kyiv, Ukraine', 'kyiv', 'Eropa Timur'],

            // ── AMERIKA UTARA ──
            ['San Francisco Bay Area, USA', 'san_francisco', 'Amerika Utara'],
            ['New York, USA', 'new_york', 'Amerika Utara'],
            ['Los Angeles, USA', 'los_angeles', 'Amerika Utara'],
            ['Austin, USA', 'austin', 'Amerika Utara'],
            ['Seattle, USA', 'seattle', 'Amerika Utara'],
            ['Boston, USA', 'boston', 'Amerika Utara'],
            ['Chicago, USA', 'chicago', 'Amerika Utara'],
            ['Miami, USA', 'miami', 'Amerika Utara'],
            ['Toronto, Canada', 'toronto', 'Amerika Utara'],
            ['Vancouver, Canada', 'vancouver', 'Amerika Utara'],
            ['Montreal, Canada', 'montreal', 'Amerika Utara'],

            // ── AMERIKA LATIN ──
            ['São Paulo, Brazil', 'sao_paulo', 'Amerika Latin'],
            ['Rio de Janeiro, Brazil', 'rio_de_janeiro', 'Amerika Latin'],
            ['Mexico City, Mexico', 'mexico_city', 'Amerika Latin'],
            ['Buenos Aires, Argentina', 'buenos_aires', 'Amerika Latin'],
            ['Bogotá, Colombia', 'bogota', 'Amerika Latin'],
            ['Santiago, Chile', 'santiago', 'Amerika Latin'],
            ['Lima, Peru', 'lima', 'Amerika Latin'],

            // ── AFRIKA ──
            ['Lagos, Nigeria', 'lagos', 'Afrika'],
            ['Nairobi, Kenya', 'nairobi', 'Afrika'],
            ['Cairo, Egypt', 'cairo', 'Afrika'],
            ['Cape Town, South Africa', 'cape_town', 'Afrika'],
            ['Johannesburg, South Africa', 'johannesburg', 'Afrika'],
            ['Accra, Ghana', 'accra', 'Afrika'],
            ['Kigali, Rwanda', 'kigali', 'Afrika'],

            // ── OSEANIA ──
            ['Sydney, Australia', 'sydney', 'Oseania'],
            ['Melbourne, Australia', 'melbourne', 'Oseania'],
            ['Brisbane, Australia', 'brisbane', 'Oseania'],
            ['Perth, Australia', 'perth', 'Oseania'],
            ['Auckland, New Zealand', 'auckland', 'Oseania'],

            // ── REMOTE ──
            ['Remote (Mana Saja)', 'remote', 'Remote'],
        ];
    }
}
