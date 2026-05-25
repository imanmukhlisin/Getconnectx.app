<?php
// Script untuk test API OpenStreetMap (Nominatim) secara langsung
// Jalankan dengan: php test_osm.php

function getCoordinatesFromOSM($locationString) {
    echo "Mencari koordinat untuk: '{$locationString}'...\n";
    
    // Ganti koma dan spasi jadi format URL (URL Encoding)
    $searchQuery = urlencode($locationString);
    $url = "https://nominatim.openstreetmap.org/search?q={$searchQuery}&format=json&limit=1";
    
    // Menggunakan file_get_contents dengan header User-Agent (Wajib untuk Nominatim)
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: ConnectX-App/1.0\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    try {
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);
        
        if (!empty($data)) {
            echo "✅ Ditemukan!\n";
            echo "   Latitude  : " . $data[0]['lat'] . "\n";
            echo "   Longitude : " . $data[0]['lon'] . "\n";
            echo "   Nama Resmi: " . $data[0]['display_name'] . "\n\n";
        } else {
            echo "❌ Tidak ditemukan.\n\n";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "=== TEST AUTO-GEOCODING FALLBACK ===\n\n";

// Test 1: Kota besar di Indonesia
getCoordinatesFromOSM("Surabaya, Indonesia");

// Test 2: Kota kecil/kabupaten
getCoordinatesFromOSM("Banyuwangi, Indonesia");

// Test 3: Kota luar negeri
getCoordinatesFromOSM("Tokyo, Japan");

// Test 4: Nama kota doang (tanpa negara)
getCoordinatesFromOSM("Pekanbaru");
