<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Тестовый каталог. Можно запускать повторно — записи обновляются по slug/sku.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'motors'      => ['Моторы', 'Motorlar', null],
            'batteries'   => ['Батареи', 'Batareyalar', null],
            'electronics' => ['Электроника', 'Elektronika', null],
            'controllers' => ['Контроллеры', 'Kontrollerlar', 'electronics'],
            'displays'    => ['Дисплеи', 'Displeylar', 'electronics'],
            'brakes'      => ['Тормоза', 'Tormozlar', null],
            'accessories' => ['Аксессуары', 'Aksessuarlar', null],
        ];

        $ids = [];
        foreach ($categories as $slug => [$ru, $uz, $parent]) {
            $ids[$slug] = Category::updateOrCreate(
                ['slug' => $slug],
                ['name_ru' => $ru, 'name_uz' => $uz, 'parent_id' => $parent ? $ids[$parent] : null],
            )->id;
        }

        // [sku, категория, name_ru, name_uz, цена (UZS), остаток, активен]
        $products = [
            ['MT-250',  'motors',      'Мотор-колесо 36V 250W',            'Motor-gʻildirak 36V 250W',          1_450_000, 8,  true],
            ['MT-500',  'motors',      'Мотор-колесо 48V 500W',            'Motor-gʻildirak 48V 500W',          2_300_000, 5,  true],
            ['MT-1000', 'motors',      'Мотор-колесо 48V 1000W',           'Motor-gʻildirak 48V 1000W',         3_900_000, 0,  true],
            ['BT-10',   'batteries',   'Аккумулятор 36V 10Ah',             'Akkumulyator 36V 10Ah',             2_800_000, 6,  true],
            ['BT-15',   'batteries',   'Аккумулятор 48V 15Ah',             'Akkumulyator 48V 15Ah',             4_200_000, 3,  true],
            ['CH-48',   'batteries',   'Зарядное устройство 48V 2A',       'Zaryadlovchi qurilma 48V 2A',         350_000, 15, true],
            ['CT-17',   'controllers', 'Контроллер 36/48V 17A',            'Kontroller 36/48V 17A',               520_000, 10, true],
            ['CT-30',   'controllers', 'Контроллер 48V 30A синусный',      'Kontroller 48V 30A sinusli',          890_000, 4,  true],
            ['DS-LCD3', 'displays',    'Дисплей LCD3',                     'LCD3 displey',                        450_000, 7,  true],
            ['DS-TFT',  'displays',    'Цветной дисплей TFT 750C',         'Rangli TFT 750C displey',             780_000, 0,  true],
            ['BR-HYD',  'brakes',      'Гидравлические тормоза (комплект)', 'Gidravlik tormozlar (toʻplam)',      650_000, 9,  true],
            ['BR-LEV',  'brakes',      'Тормозная ручка с отсечкой',       'Motor oʻchirgichli tormoz dastasi',   120_000, 20, true],
            ['AC-LIGHT', 'accessories', 'Фара LED 48V',                    'LED fara 48V',                        180_000, 12, true],
            ['AC-LOCK', 'accessories', 'Велозамок U-образный',             'U-shaklidagi velosiped qulfi',        160_000, 0,  false],
        ];

        foreach ($products as [$sku, $category, $ru, $uz, $price, $stock, $active]) {
            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id'    => $ids[$category],
                    'name_ru'        => $ru,
                    'name_uz'        => $uz,
                    'description_ru' => "{$ru}. Совместимо с большинством электровелосипедов.",
                    'description_uz' => "{$uz}. Koʻpchilik elektr velosipedlarga mos keladi.",
                    'price'          => $price,
                    'stock'          => $stock,
                    'images'         => [],
                    'is_active'      => $active,
                ],
            );
        }
    }
}
