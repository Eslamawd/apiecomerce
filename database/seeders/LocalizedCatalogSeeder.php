<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class LocalizedCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $guardName = config('auth.defaults.guard', 'web');
        Role::findOrCreate('vendor', $guardName);

        $vendor = User::firstOrCreate(
            ['email' => 'vendor.catalog@example.com'],
            [
                'name' => 'Catalog Vendor',
                'phone' => null,
                'password' => Hash::make('password1234'),
                'is_active' => true,
            ]
        );

        if (! $vendor->hasRole('vendor')) {
            $vendor->assignRole('vendor');
        }

        $parents = [
            'fashion' => $this->upsertCategory([
                'name' => 'ملابس',
                'name_en' => 'Fashion',
                'slug' => 'fashion',
                'description' => 'منتجات ملابس رجالي وحريمي بأحجام وألوان متعددة.',
                'description_en' => 'Men and women fashion products with multiple sizes and colors.',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => 1,
            ]),
            'automotive' => $this->upsertCategory([
                'name' => 'سيارات',
                'name_en' => 'Automotive',
                'slug' => 'automotive',
                'description' => 'قسم السيارات يشمل قطع الغيار والكماليات.',
                'description_en' => 'Automotive section includes spare parts and accessories.',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => 2,
            ]),
        ];

        $children = [
            'mens-clothing' => $this->upsertCategory([
                'name' => 'ملابس رجالي',
                'name_en' => 'Men Clothing',
                'slug' => 'mens-clothing',
                'description' => 'تيشيرتات وبناطيل وجواكيت رجالي.',
                'description_en' => 'Men t-shirts, pants, and jackets.',
                'parent_id' => $parents['fashion']->id,
                'is_active' => true,
                'sort_order' => 1,
            ]),
            'womens-clothing' => $this->upsertCategory([
                'name' => 'ملابس حريمي',
                'name_en' => 'Women Clothing',
                'slug' => 'womens-clothing',
                'description' => 'فساتين وبلوزات وبناطيل حريمي.',
                'description_en' => 'Women dresses, blouses, and pants.',
                'parent_id' => $parents['fashion']->id,
                'is_active' => true,
                'sort_order' => 2,
            ]),
            'car-spare-parts' => $this->upsertCategory([
                'name' => 'قطع غيار سيارات',
                'name_en' => 'Car Spare Parts',
                'slug' => 'car-spare-parts',
                'description' => 'قطع غيار أصلية ومتوافقة لمعظم الموديلات.',
                'description_en' => 'OEM and compatible spare parts for common car models.',
                'parent_id' => $parents['automotive']->id,
                'is_active' => true,
                'sort_order' => 1,
            ]),
            'car-accessories' => $this->upsertCategory([
                'name' => 'كماليات سيارات',
                'name_en' => 'Car Accessories',
                'slug' => 'car-accessories',
                'description' => 'كماليات داخلية وخارجية لتحسين تجربة السيارة.',
                'description_en' => 'Interior and exterior accessories to upgrade your car.',
                'parent_id' => $parents['automotive']->id,
                'is_active' => true,
                'sort_order' => 2,
            ]),
        ];

        $this->upsertProduct([
            'name' => 'تيشيرت قطن رجالي أساسي',
            'name_en' => 'Basic Men Cotton T-Shirt',
            'description' => 'تيشيرت قطن 100% مناسب للاستخدام اليومي.',
            'description_en' => '100% cotton t-shirt suitable for daily wear.',
            'product_type' => 'clothing',
            'price' => 249.00,
            'old_price' => 299.00,
            'cost_price' => 150.00,
            'sku' => 'MEN-TSHIRT-BASIC',
            'quantity' => 80,
            'category_id' => $children['mens-clothing']->id,
            'vendor_id' => $vendor->id,
            'is_active' => true,
            'is_featured' => true,
            'specifications' => [
                'fabric' => 'cotton',
                'fit' => 'regular',
            ],
            'variants' => [
                ['sku' => 'MEN-TSHIRT-BASIC-BLK-M', 'price' => 249, 'quantity' => 20, 'attributes' => ['color' => 'black', 'size' => 'M']],
                ['sku' => 'MEN-TSHIRT-BASIC-BLK-L', 'price' => 249, 'quantity' => 20, 'attributes' => ['color' => 'black', 'size' => 'L']],
                ['sku' => 'MEN-TSHIRT-BASIC-WHT-M', 'price' => 249, 'quantity' => 20, 'attributes' => ['color' => 'white', 'size' => 'M']],
            ],
        ]);

        $this->upsertProduct([
            'name' => 'بلوزة صيفي حريمي',
            'name_en' => 'Women Summer Blouse',
            'description' => 'بلوزة خفيفة بتصميم مريح للموسم الصيفي.',
            'description_en' => 'Lightweight blouse with a comfy summer design.',
            'product_type' => 'clothing',
            'price' => 319.00,
            'old_price' => 379.00,
            'cost_price' => 190.00,
            'sku' => 'WMN-BLOUSE-SUMMER',
            'quantity' => 60,
            'category_id' => $children['womens-clothing']->id,
            'vendor_id' => $vendor->id,
            'is_active' => true,
            'is_featured' => true,
            'specifications' => [
                'fabric' => 'viscose',
                'season' => 'summer',
            ],
            'variants' => [
                ['sku' => 'WMN-BLOUSE-SUMMER-BLU-S', 'price' => 319, 'quantity' => 15, 'attributes' => ['color' => 'blue', 'size' => 'S']],
                ['sku' => 'WMN-BLOUSE-SUMMER-BLU-M', 'price' => 319, 'quantity' => 15, 'attributes' => ['color' => 'blue', 'size' => 'M']],
                ['sku' => 'WMN-BLOUSE-SUMMER-PNK-M', 'price' => 329, 'quantity' => 10, 'attributes' => ['color' => 'pink', 'size' => 'M']],
            ],
        ]);

        $this->upsertProduct([
            'name' => 'تيل فرامل أمامي تويوتا كورولا',
            'name_en' => 'Front Brake Pads Toyota Corolla',
            'description' => 'طقم تيل فرامل أمامي عالي التحمل.',
            'description_en' => 'Durable front brake pads set.',
            'product_type' => 'automotive',
            'price' => 950.00,
            'old_price' => 1090.00,
            'cost_price' => 700.00,
            'sku' => 'AUTO-BRAKEPAD-COROLLA',
            'quantity' => 40,
            'category_id' => $children['car-spare-parts']->id,
            'vendor_id' => $vendor->id,
            'is_active' => true,
            'is_featured' => true,
            'specifications' => [
                'position' => 'front',
                'material' => 'ceramic',
            ],
            'variants' => [
                ['sku' => 'AUTO-BRAKEPAD-COROLLA-2018', 'price' => 950, 'quantity' => 12, 'attributes' => ['make' => 'Toyota', 'model' => 'Corolla', 'year' => '2018']],
                ['sku' => 'AUTO-BRAKEPAD-COROLLA-2019', 'price' => 970, 'quantity' => 10, 'attributes' => ['make' => 'Toyota', 'model' => 'Corolla', 'year' => '2019']],
                ['sku' => 'AUTO-BRAKEPAD-COROLLA-2020', 'price' => 990, 'quantity' => 8, 'attributes' => ['make' => 'Toyota', 'model' => 'Corolla', 'year' => '2020']],
            ],
        ]);

        $this->upsertProduct([
            'name' => 'فلتر زيت محرك هيونداي النترا',
            'name_en' => 'Engine Oil Filter Hyundai Elantra',
            'description' => 'فلتر زيت بجودة عالية لحماية المحرك.',
            'description_en' => 'High-quality oil filter for engine protection.',
            'product_type' => 'automotive',
            'price' => 220.00,
            'old_price' => 260.00,
            'cost_price' => 140.00,
            'sku' => 'AUTO-OILFILTER-ELANTRA',
            'quantity' => 120,
            'category_id' => $children['car-spare-parts']->id,
            'vendor_id' => $vendor->id,
            'is_active' => true,
            'is_featured' => false,
            'specifications' => [
                'type' => 'oil filter',
                'brand' => 'OEM Compatible',
            ],
            'variants' => [
                ['sku' => 'AUTO-OILFILTER-ELANTRA-2017', 'price' => 220, 'quantity' => 30, 'attributes' => ['make' => 'Hyundai', 'model' => 'Elantra', 'year' => '2017']],
                ['sku' => 'AUTO-OILFILTER-ELANTRA-2018', 'price' => 220, 'quantity' => 30, 'attributes' => ['make' => 'Hyundai', 'model' => 'Elantra', 'year' => '2018']],
            ],
        ]);

        $this->upsertProduct([
            'name' => 'طقم أغطية مقاعد جلد',
            'name_en' => 'Leather Seat Cover Set',
            'description' => 'طقم أغطية مقاعد جلد صناعي مقاوم للاهلاك.',
            'description_en' => 'Wear-resistant synthetic leather seat cover set.',
            'product_type' => 'automotive',
            'price' => 1450.00,
            'old_price' => 1690.00,
            'cost_price' => 1000.00,
            'sku' => 'AUTO-SEATCOVER-SET',
            'quantity' => 35,
            'category_id' => $children['car-accessories']->id,
            'vendor_id' => $vendor->id,
            'is_active' => true,
            'is_featured' => true,
            'specifications' => [
                'material' => 'synthetic leather',
                'pieces' => 9,
            ],
            'variants' => [
                ['sku' => 'AUTO-SEATCOVER-SET-BLK', 'price' => 1450, 'quantity' => 12, 'attributes' => ['color' => 'black']],
                ['sku' => 'AUTO-SEATCOVER-SET-BGE', 'price' => 1450, 'quantity' => 8, 'attributes' => ['color' => 'beige']],
            ],
        ]);

        $this->upsertProduct([
            'name' => 'إضاءة LED داخلية للسيارة',
            'name_en' => 'Car Interior LED Light Kit',
            'description' => 'إضاءة ديكورية داخلية متعددة الألوان مع ريموت.',
            'description_en' => 'Multi-color decorative interior lights with remote.',
            'product_type' => 'automotive',
            'price' => 399.00,
            'old_price' => 479.00,
            'cost_price' => 250.00,
            'sku' => 'AUTO-LED-INTERIOR-KIT',
            'quantity' => 70,
            'category_id' => $children['car-accessories']->id,
            'vendor_id' => $vendor->id,
            'is_active' => true,
            'is_featured' => false,
            'specifications' => [
                'voltage' => '12V',
                'control' => 'remote',
            ],
            'variants' => [
                ['sku' => 'AUTO-LED-INTERIOR-KIT-4PC', 'price' => 399, 'quantity' => 25, 'attributes' => ['model' => '4pcs']],
                ['sku' => 'AUTO-LED-INTERIOR-KIT-6PC', 'price' => 449, 'quantity' => 20, 'attributes' => ['model' => '6pcs']],
            ],
        ]);
    }

    private function upsertCategory(array $data): Category
    {
        return Category::updateOrCreate(
            ['slug' => $data['slug']],
            $data,
        );
    }

    private function upsertProduct(array $data): Product
    {
        $data['slug'] = Str::slug($data['name_en']);

        return Product::updateOrCreate(
            ['sku' => $data['sku']],
            $data,
        );
    }
}
