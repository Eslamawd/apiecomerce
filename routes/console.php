<?php

use App\Models\Order;
use App\Models\User;
use App\Notifications\VendorOrderPlacedNotification;
use App\Services\DemoCatalogImportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:import-catalog {provider=dummyjson} {--limit=1000}', function (DemoCatalogImportService $service) {
    $provider = (string) $this->argument('provider');
    $limit = (int) $this->option('limit');

    $this->info("Importing demo catalog from [{$provider}]...");

    try {
        $stats = $service->import($provider, $limit);

        $this->newLine();
        $this->info('Import completed successfully.');
        $this->table(['Metric', 'Count'], [
            ['Target', $stats['target'] ?? '-'],
            ['Source Pool', $stats['source_pool'] ?? '-'],
            ['Categories', $stats['categories']],
            ['Products', $stats['products']],
            ['Reviews', $stats['reviews']],
            ['Images', $stats['images'] ?? 0],
            ['Videos', $stats['videos'] ?? 0],
        ]);
    } catch (\Throwable $e) {
        $this->error('Import failed: ' . $e->getMessage());
        return 1;
    }

    return 0;
})->purpose('Import demo categories/products/reviews/images from a provider (default: dummyjson).');

Artisan::command('mail:verify {to?}', function () {
    $to = (string) ($this->argument('to') ?: config('mail.from.address'));

    if ($to === '') {
        $this->error('No recipient email provided. Pass {to} or set MAIL_FROM_ADDRESS.');
        return 1;
    }

    try {
        Mail::raw('Stone SMTP verification test email from Laravel.', function ($message) use ($to) {
            $message->to($to)->subject('Stone | SMTP Verify');
        });

        $this->info("Verification email sent successfully to: {$to}");
    } catch (\Throwable $e) {
        $this->error('SMTP verification failed: ' . $e->getMessage());
        return 1;
    }

    return 0;
})->purpose('Send a test email to verify SMTP configuration.');

Artisan::command('mail:send-verification {email}', function () {
    $email = (string) $this->argument('email');

    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("User not found for email: {$email}");
        return 1;
    }

    $user->sendEmailVerificationNotification();

    $this->info("Verification email sent to: {$email}");
    return 0;
})->purpose('Send the account verification email to an existing user.');

Artisan::command('mail:send-vendor-order {email} {orderNumber?}', function () {
    $email = (string) $this->argument('email');
    $orderNumber = $this->argument('orderNumber');

    $vendor = User::where('email', $email)->first();

    if (! $vendor) {
        $this->error("User not found for email: {$email}");
        return 1;
    }

    $orderQuery = Order::query()->with(['items.product', 'user']);

    if (is_string($orderNumber) && $orderNumber !== '') {
        $orderQuery->where('order_number', $orderNumber);
    }

    $order = $orderQuery->latest('id')->first();

    if (! $order) {
        $order = new Order([
            'order_number' => 'PREVIEW-' . now()->format('YmdHis'),
            'total' => 129.99,
            'shipping_name' => 'Preview Customer',
        ]);

        $vendorItems = [
            [
                'product_id' => 0,
                'product_name' => 'Preview Product A',
                'product_price' => 49.99,
                'quantity' => 1,
                'subtotal' => 49.99,
            ],
            [
                'product_id' => 0,
                'product_name' => 'Preview Product B',
                'product_price' => 40.00,
                'quantity' => 2,
                'subtotal' => 80.00,
            ],
        ];

        $customerName = 'Preview Customer';
        $vendor->notify(new VendorOrderPlacedNotification($order, $vendorItems, $customerName));
        $this->info("Vendor order email sent to: {$email} (preview data)");
        return 0;
    }

    $vendorItems = $order->items
        ->filter(fn ($item) => (int) ($item->product?->vendor_id ?? 0) === (int) $vendor->id)
        ->map(fn ($item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'product_price' => (float) $item->product_price,
            'quantity' => (int) $item->quantity,
            'subtotal' => (float) $item->subtotal,
        ])
        ->values()
        ->all();

    if (count($vendorItems) === 0) {
        // Fallback for preview mode when latest order items do not belong to this vendor.
        $vendorItems = $order->items
            ->take(3)
            ->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_price' => (float) $item->product_price,
                'quantity' => (int) $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])
            ->values()
            ->all();
    }

    $customerName = (string) ($order->shipping_name ?: ($order->user?->name ?: 'Customer'));

    $vendor->notify(new VendorOrderPlacedNotification($order, $vendorItems, $customerName));

    $this->info("Vendor order email sent to: {$email} (order: {$order->order_number})");
    return 0;
})->purpose('Send a vendor order notification email preview.');
