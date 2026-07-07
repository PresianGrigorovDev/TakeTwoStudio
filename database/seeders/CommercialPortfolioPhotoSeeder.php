<?php

namespace Database\Seeders;

use App\Models\CommercialPortfolioPhoto;
use Illuminate\Database\Seeder;

class CommercialPortfolioPhotoSeeder extends Seeder
{
    public function run(): void
    {
        // Пропускаме ако вече има записи, за да не дублираме при повторен seed
        if (CommercialPortfolioPhoto::count() > 0) {
            return;
        }

        $photos = [
            // --- Реклама (ads) ---
            [
                'image_path'   => 'css/img/ads/topa-na-banketa-momcheto-fyre.jpg',
                'alt_text'     => 'TOPA NA BANKETA',
                'sub_category' => 'ads',
                'sort_order'   => 1,
                'is_visible'   => true,
            ],
            [
                'image_path'   => 'css/img/ads/moonlight.jpg',
                'alt_text'     => 'Moonlight Event Center',
                'sub_category' => 'ads',
                'sort_order'   => 2,
                'is_visible'   => true,
            ],
            [
                'image_path'   => 'css/img/ads/razors.jpg',
                'alt_text'     => 'Razors',
                'sub_category' => 'ads',
                'sort_order'   => 3,
                'is_visible'   => true,
            ],
            [
                'image_path'   => 'css/img/ads/fyre.jpg',
                'alt_text'     => 'Fyre',
                'sub_category' => 'ads',
                'sort_order'   => 7,
                'is_visible'   => true,
            ],

            // --- Продуктова (product) ---
            [
                'image_path'   => 'css/img/ads/product.png',
                'alt_text'     => 'Maksuda',
                'sub_category' => 'product',
                'sort_order'   => 4,
                'is_visible'   => true,
            ],
            [
                'image_path'   => 'css/img/ads/dress.jpg',
                'alt_text'     => 'Tailor Boutique',
                'sub_category' => 'product',
                'sort_order'   => 5,
                'is_visible'   => true,
            ],
            [
                'image_path'   => 'css/img/ads/clothes.jpg',
                'alt_text'     => 'Calieate',
                'sub_category' => 'product',
                'sort_order'   => 6,
                'is_visible'   => true,
            ],
            [
                'image_path'   => 'css/img/ads/masterchef.jpg',
                'alt_text'     => 'Master Chef',
                'sub_category' => 'product',
                'sort_order'   => 8,
                'is_visible'   => true,
            ],

            // --- Имоти (imoti) ---
            [
                'image_path'   => 'css/img/ads/hotel.jpg',
                'alt_text'     => 'Hotel Continental',
                'sub_category' => 'imoti',
                'sort_order'   => 9,
                'is_visible'   => true,
            ],

            // --- Събития (events) ---
            [
                'image_path'   => 'css/img/ads/subitie.jpg',
                'alt_text'     => 'Каменица',
                'sub_category' => 'events',
                'sort_order'   => 10,
                'is_visible'   => true,
            ],

            // --- Дрон (drone) ---
            [
                'image_path'   => 'css/img/ads/drone.jpg',
                'alt_text'     => 'Дрон',
                'sub_category' => 'drone',
                'sort_order'   => 11,
                'is_visible'   => true,
            ],
        ];

        foreach ($photos as $photo) {
            CommercialPortfolioPhoto::create($photo);
        }
    }
}
