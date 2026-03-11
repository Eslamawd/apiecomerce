<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use RuntimeException;

class DeepCategoryTreeSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Level 1 (Roots)
            $this->row('سيارات', 'Automotive', 'automotive', null, 1, 'تصنيفات السيارات وقطع الغيار والكماليات.', 'Automotive parts and accessories.'),
            $this->row('ملابس', 'Fashion', 'fashion', null, 2, 'تصنيفات الملابس الرجالي والحريمي والأطفال.', 'Men, women, and kids clothing.'),
            $this->row('العناية والجمال', 'Beauty', 'beauty', null, 3, 'العناية الشخصية والعطور.', 'Personal care and fragrances.'),
            $this->row('أثاث', 'Furniture', 'furniture', null, 4, 'أثاث منزلي ومكتبي.', 'Home and office furniture.'),
            $this->row('بقالة', 'Groceries', 'groceries', null, 5, 'منتجات غذائية واستهلاكية.', 'Food and household essentials.'),

            // Level 2 - Automotive
            $this->row('قطع غيار سيارات', 'Car Spare Parts', 'car-spare-parts', 'automotive', 1),
            $this->row('كماليات سيارات', 'Car Accessories', 'car-accessories', 'automotive', 2),

            // Level 3 -> 5 - Spare Parts (Brand/Model/System)
            $this->row('قطع غيار تويوتا', 'Toyota Parts', 'toyota-parts', 'car-spare-parts', 1),
            $this->row('قطع غيار هيونداي', 'Hyundai Parts', 'hyundai-parts', 'car-spare-parts', 2),
            $this->row('قطع غيار نيسان', 'Nissan Parts', 'nissan-parts', 'car-spare-parts', 3),

            $this->row('كورولا', 'Corolla', 'toyota-corolla-parts', 'toyota-parts', 1),
            $this->row('كامري', 'Camry', 'toyota-camry-parts', 'toyota-parts', 2),
            $this->row('يارس', 'Yaris', 'toyota-yaris-parts', 'toyota-parts', 3),

            $this->row('إلنترا', 'Elantra', 'hyundai-elantra-parts', 'hyundai-parts', 1),
            $this->row('أكسنت', 'Accent', 'hyundai-accent-parts', 'hyundai-parts', 2),
            $this->row('توسان', 'Tucson', 'hyundai-tucson-parts', 'hyundai-parts', 3),

            $this->row('صني', 'Sunny', 'nissan-sunny-parts', 'nissan-parts', 1),
            $this->row('سنترا', 'Sentra', 'nissan-sentra-parts', 'nissan-parts', 2),

            $this->row('نظام الفرامل', 'Brake System', 'corolla-brake-system', 'toyota-corolla-parts', 1),
            $this->row('فلاتر وزيوت', 'Filters and Oils', 'corolla-filters-oils', 'toyota-corolla-parts', 2),
            $this->row('نظام التعليق', 'Suspension System', 'corolla-suspension-system', 'toyota-corolla-parts', 3),

            $this->row('نظام الفرامل', 'Brake System', 'camry-brake-system', 'toyota-camry-parts', 1),
            $this->row('فلاتر وزيوت', 'Filters and Oils', 'camry-filters-oils', 'toyota-camry-parts', 2),

            $this->row('نظام الفرامل', 'Brake System', 'elantra-brake-system', 'hyundai-elantra-parts', 1),
            $this->row('فلاتر وزيوت', 'Filters and Oils', 'elantra-filters-oils', 'hyundai-elantra-parts', 2),
            $this->row('كهرباء السيارة', 'Auto Electrical', 'elantra-auto-electrical', 'hyundai-elantra-parts', 3),

            $this->row('نظام الفرامل', 'Brake System', 'sunny-brake-system', 'nissan-sunny-parts', 1),
            $this->row('فلاتر وزيوت', 'Filters and Oils', 'sunny-filters-oils', 'nissan-sunny-parts', 2),

            // Level 3 -> 5 - Car Accessories
            $this->row('إكسسوارات داخلية', 'Interior Accessories', 'interior-accessories', 'car-accessories', 1),
            $this->row('إكسسوارات خارجية', 'Exterior Accessories', 'exterior-accessories', 'car-accessories', 2),
            $this->row('إلكترونيات السيارة', 'Car Electronics', 'car-electronics', 'car-accessories', 3),

            $this->row('أغطية المقاعد', 'Seat Covers', 'seat-covers', 'interior-accessories', 1),
            $this->row('دواسات أرضية', 'Floor Mats', 'floor-mats', 'interior-accessories', 2),
            $this->row('منظمات وشنط', 'Organizers', 'car-organizers', 'interior-accessories', 3),

            $this->row('جلد', 'Leather Seat Covers', 'leather-seat-covers', 'seat-covers', 1),
            $this->row('قماش', 'Fabric Seat Covers', 'fabric-seat-covers', 'seat-covers', 2),

            $this->row('شاشات وكاميرات', 'Screens and Cameras', 'screens-cameras', 'car-electronics', 1),
            $this->row('إضاءة LED', 'LED Lighting', 'led-lighting', 'car-electronics', 2),
            $this->row('شواحن وهواتف', 'Chargers and Holders', 'chargers-holders', 'car-electronics', 3),

            $this->row('شاشات أندرويد', 'Android Screens', 'android-screens', 'screens-cameras', 1),
            $this->row('كاميرات خلفية', 'Rear Cameras', 'rear-cameras', 'screens-cameras', 2),

            // Level 2 - Fashion
            $this->row('ملابس رجالي', 'Men Clothing', 'mens-clothing', 'fashion', 1),
            $this->row('ملابس حريمي', 'Women Clothing', 'womens-clothing', 'fashion', 2),
            $this->row('ملابس أطفال', 'Kids Clothing', 'kids-clothing', 'fashion', 3),

            // Level 3 -> 5 - Men
            $this->row('ملابس علوية', 'Men Tops', 'men-tops', 'mens-clothing', 1),
            $this->row('ملابس سفلية', 'Men Bottoms', 'men-bottoms', 'mens-clothing', 2),
            $this->row('ملابس خارجية', 'Men Outerwear', 'men-outerwear', 'mens-clothing', 3),

            $this->row('تيشيرتات', 'Men T-Shirts', 'men-tshirts', 'men-tops', 1),
            $this->row('قمصان', 'Men Shirts', 'men-shirts', 'men-tops', 2),

            $this->row('تيشيرتات أساسية', 'Basic Men T-Shirts', 'men-basic-tshirts', 'men-tshirts', 1),
            $this->row('تيشيرتات أوفر سايز', 'Oversized Men T-Shirts', 'men-oversized-tshirts', 'men-tshirts', 2),

            $this->row('جينز', 'Men Jeans', 'men-jeans', 'men-bottoms', 1),
            $this->row('تشينو', 'Men Chino Pants', 'men-chino-pants', 'men-bottoms', 2),

            // Level 3 -> 5 - Women
            $this->row('ملابس علوية', 'Women Tops', 'women-tops', 'womens-clothing', 1),
            $this->row('ملابس سفلية', 'Women Bottoms', 'women-bottoms', 'womens-clothing', 2),
            $this->row('فساتين', 'Women Dresses', 'women-dresses', 'womens-clothing', 3),

            $this->row('بلوزات', 'Women Blouses', 'women-blouses', 'women-tops', 1),
            $this->row('تيشيرتات', 'Women T-Shirts', 'women-tshirts', 'women-tops', 2),

            $this->row('فساتين كاجوال', 'Casual Dresses', 'women-casual-dresses', 'women-dresses', 1),
            $this->row('فساتين سواريه', 'Evening Dresses', 'women-evening-dresses', 'women-dresses', 2),

            // Level 3 -> 5 - Kids
            $this->row('أولادي', 'Boys Clothing', 'boys-clothing', 'kids-clothing', 1),
            $this->row('بناتي', 'Girls Clothing', 'girls-clothing', 'kids-clothing', 2),

            $this->row('تيشيرتات أولادي', 'Boys T-Shirts', 'boys-tshirts', 'boys-clothing', 1),
            $this->row('فساتين بناتي', 'Girls Dresses', 'girls-dresses', 'girls-clothing', 1),

            // Level 2 - Beauty
            $this->row('عطور', 'Fragrances', 'fragrances', 'beauty', 1),
            $this->row('عناية بالبشرة', 'Skin Care', 'skin-care', 'beauty', 2),

            // Level 2 - Furniture
            $this->row('أثاث منزلي', 'Home Furniture', 'home-furniture', 'furniture', 1),
            $this->row('أثاث مكتبي', 'Office Furniture', 'office-furniture', 'furniture', 2),

            // Level 2 - Groceries
            $this->row('معلبات', 'Canned Food', 'canned-food', 'groceries', 1),
            $this->row('مشروبات', 'Beverages', 'beverages', 'groceries', 2),
            $this->row('منظفات', 'Cleaning Supplies', 'cleaning-supplies', 'groceries', 3),
        ];

        foreach ($categories as $category) {
            $this->upsertCategory($category);
        }
    }

    private function row(
        string $name,
        string $nameEn,
        string $slug,
        ?string $parentSlug,
        int $sortOrder,
        ?string $description = null,
        ?string $descriptionEn = null,
        ?string $image = null,
        ?string $video = null
    ): array {
        return [
            'name' => $name,
            'name_en' => $nameEn,
            'slug' => $slug,
            'parent_slug' => $parentSlug,
            'description' => $description,
            'description_en' => $descriptionEn,
            'image' => $image,
            'video' => $video,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ];
    }

    private function upsertCategory(array $data): Category
    {
        $parentId = null;

        if (! empty($data['parent_slug'])) {
            $parentId = Category::where('slug', $data['parent_slug'])->value('id');

            if (! $parentId) {
                throw new RuntimeException('Missing parent category for slug: ' . $data['parent_slug']);
            }
        }

        return Category::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'name_en' => $data['name_en'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'description_en' => $data['description_en'],
                'image' => $data['image'],
                'video' => $data['video'],
                'parent_id' => $parentId,
                'is_active' => $data['is_active'],
                'sort_order' => $data['sort_order'],
            ]
        );
    }
}
