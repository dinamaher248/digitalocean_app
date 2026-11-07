<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\ServiceOrder;
use App\Models\User;

class MerchantOrderNotificationService
{
    public function notifyNewOrder(Merchant $merchant, ServiceOrder $order): void
    {
        $body = $order->order_type === 'custom'
            ? 'طلب مخصص — اطلب أي شيء'
            : 'المنتج: ' . $order->product_name . ' | الكمية: ' . $order->quantity;

        $this->notifyOwner(
            $merchant,
            'طلب جديد',
            $body,
            'new_service_order',
            $order->id,
        );
    }

    public function notifyCustomOffer(User $customer, ServiceOrder $order): void
    {
        $price = $order->merchant_offer_price;
        $body = $price !== null
            ? 'عرض بقيمة ' . $price . ' ج.م من ' . ($order->merchant?->name ?? 'المتجر')
            : 'رد من ' . ($order->merchant?->name ?? 'المتجر') . ' على طلبك المخصص';

        app(FcmService::class)->sendToUser(
            $customer,
            'عرض على طلبك',
            $body,
            'custom_order_offer',
            (string) $order->id,
        );
    }

    public function notifyCustomOfferAccepted(Merchant $merchant, ServiceOrder $order): void
    {
        $this->notifyOwner(
            $merchant,
            'قبول العرض',
            'وافق العميل على عرض الطلب المخصص #' . $order->id,
            'custom_offer_accepted',
            $order->id,
        );
    }

    public function notifyCustomOfferRejected(Merchant $merchant, ServiceOrder $order): void
    {
        $this->notifyOwner(
            $merchant,
            'رفض العرض',
            'رفض العميل عرض الطلب المخصص #' . $order->id,
            'custom_offer_rejected',
            $order->id,
        );
    }

    public function notifyOrderStatus(ServiceOrder $order, string $title, string $body): void
    {
        $merchant = $order->merchant;
        if (!$merchant) {
            return;
        }

        $this->notifyOwner(
            $merchant,
            $title,
            $body,
            'service_order_status',
            $order->id,
        );
    }

    public function notifyOwner(
        Merchant $merchant,
        string $title,
        string $body,
        string $action,
        ?int $refId = null,
    ): void {
        $owner = $merchant->owner;
        if (!$owner) {
            return;
        }

        app(FcmService::class)->sendToUser(
            $owner,
            $title,
            $body,
            $action,
            $refId !== null ? (string) $refId : null,
        );
    }

    public function notifyCustomerOrderSent(User $customer, ServiceOrder $order): void
    {
        app(FcmService::class)->sendToUser(
            $customer,
            'تم إرسال الطلب',
            'طلبك من ' . ($order->merchant?->name ?? 'المتجر') . ' بانتظار المراجعة',
            'service_order_sent',
            (string) $order->id,
        );
    }
}
