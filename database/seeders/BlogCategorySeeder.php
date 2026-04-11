<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (BlogCategory::count() > 0) {
            return;
        }

        $categories = [
            [
                'name' => 'Сватбени съвети',
                'slug' => 'wedding-tips',
                'description' => 'Всичко, което трябва да знаете, за да планирате сватбения си ден без стрес - от избор на фотограф до перфектен таймлайн.',
                'color' => '#d4a373',
                'display_order' => 1,
                'is_visible' => true,
            ],
            [
                'name' => 'Кръщенета',
                'slug' => 'baptism-tips',
                'description' => 'Практични съвети за подготовка, организация и заснемане на кръщенето на вашето дете.',
                'color' => '#a8c4e3',
                'display_order' => 2,
                'is_visible' => true,
            ],
            [
                'name' => 'Балове и абитуриенти',
                'slug' => 'prom-tips',
                'description' => 'Как да превърнете абитуриентския бал и пред-балната фотосесия в незабравими моменти.',
                'color' => '#c9a961',
                'display_order' => 3,
                'is_visible' => true,
            ],
            [
                'name' => 'Техника и зад кадъра',
                'slug' => 'behind-the-scenes',
                'description' => 'Как работим, с какво снимаме и защо резултатът ни прави разликата - погледи зад кулисите на студиото.',
                'color' => '#7a7a7a',
                'display_order' => 4,
                'is_visible' => true,
            ],
            [
                'name' => 'Фотосесии и стил',
                'slug' => 'photoshoot-style',
                'description' => 'Съвети за позиране, избор на дрехи, локации и настроение за вашата следваща фотосесия.',
                'color' => '#b08ba5',
                'display_order' => 5,
                'is_visible' => true,
            ],
        ];

        foreach ($categories as $data) {
            BlogCategory::create($data);
        }
    }
}
