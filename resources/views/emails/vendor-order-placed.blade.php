<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order</title>
</head>
<body style="margin:0;padding:0;background:#f3f6fb;font-family:Tahoma,Arial,sans-serif;color:#111827;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6fb;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="padding:22px 24px;background:linear-gradient(135deg,#10b981 0%,#14b8a6 45%,#06b6d4 100%);color:#ffffff;">
                        <div style="font-size:13px;opacity:0.9;letter-spacing:0.08em;">Stone Vendor Alerts</div>
                        <h1 style="margin:8px 0 0;font-size:24px;line-height:1.35;">A New Order Has Arrived</h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 10px;font-size:16px;">Hi {{ $vendorName }},</p>
                        <p style="margin:0 0 16px;font-size:14px;color:#374151;line-height:1.8;">
                            A new order containing your products has been placed. Please review and prepare it for fulfillment.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
                            <tr>
                                <td style="padding:14px;">
                                    <div style="font-size:13px;color:#64748b;">Order Number</div>
                                    <div style="font-size:16px;font-weight:700;color:#0f172a;">{{ $order->order_number }}</div>
                                </td>
                                <td style="padding:14px;">
                                    <div style="font-size:13px;color:#64748b;">Customer</div>
                                    <div style="font-size:16px;font-weight:700;color:#0f172a;">{{ $customerName }}</div>
                                </td>
                                <td style="padding:14px;">
                                    <div style="font-size:13px;color:#64748b;">Total</div>
                                    <div style="font-size:16px;font-weight:700;color:#0f172a;">${{ number_format((float) $order->total, 2) }}</div>
                                </td>
                            </tr>
                        </table>

                        <div style="margin:0 0 10px;font-size:15px;font-weight:700;">Your Items in This Order:</div>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                            <thead>
                                <tr style="background:#f9fafb;">
                                    <th align="left" style="padding:10px;font-size:12px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Product</th>
                                    <th align="left" style="padding:10px;font-size:12px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Quantity</th>
                                    <th align="left" style="padding:10px;font-size:12px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Unit Price</th>
                                    <th align="left" style="padding:10px;font-size:12px;color:#6b7280;border-bottom:1px solid #e5e7eb;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendorItems as $item)
                                    <tr>
                                        <td style="padding:10px;font-size:13px;border-bottom:1px solid #f1f5f9;">{{ $item['product_name'] }}</td>
                                        <td style="padding:10px;font-size:13px;border-bottom:1px solid #f1f5f9;">{{ $item['quantity'] }}</td>
                                        <td style="padding:10px;font-size:13px;border-bottom:1px solid #f1f5f9;">${{ number_format((float) $item['product_price'], 2) }}</td>
                                        <td style="padding:10px;font-size:13px;border-bottom:1px solid #f1f5f9;">${{ number_format((float) $item['subtotal'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <p style="margin:16px 0 0;font-size:12px;color:#6b7280;line-height:1.7;">
                            This is an automated email sent by your store system when a new order is created.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
