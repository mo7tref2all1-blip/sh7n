<?php

namespace App\Support;

use App\Models\Shipment;

/** Arabic labels + Bootstrap badge color for every shipment/financial status (docs/05-Workflows.md). */
class StatusLabels
{
    private const SHIPMENT = [
        Shipment::STATUS_CREATED => ['تم إنشاء الشحنة', 'secondary'],
        Shipment::STATUS_READY_FOR_PICKUP => ['جاهزة للاستلام', 'info'],
        Shipment::STATUS_PICKED_UP => ['تم الاستلام من التاجر', 'info'],
        Shipment::STATUS_ARRIVED_BRANCH => ['وصلت الفرع', 'info'],
        Shipment::STATUS_IN_TRANSIT => ['قيد النقل', 'primary'],
        Shipment::STATUS_ARRIVED_DESTINATION_GOV => ['وصلت محافظة الوجهة', 'primary'],
        Shipment::STATUS_HANDED_TO_AGENT => ['تم تسليمها للوكيل', 'primary'],
        Shipment::STATUS_HANDED_TO_DRIVER => ['تم تسليمها للمندوب', 'primary'],
        Shipment::STATUS_OUT_FOR_DELIVERY => ['خرجت للتسليم', 'warning'],
        Shipment::STATUS_DELIVERED => ['تم التسليم', 'success'],
        Shipment::STATUS_NO_ANSWER => ['لا يرد', 'danger'],
        Shipment::STATUS_POSTPONED => ['مؤجل', 'warning'],
        Shipment::STATUS_REFUSED_RECEIPT => ['رفض الاستلام', 'danger'],
        Shipment::STATUS_REFUSED_PRICE => ['رفض السعر', 'danger'],
        Shipment::STATUS_WRONG_ADDRESS => ['عنوان خاطئ', 'danger'],
        Shipment::STATUS_RETURNED => ['مرتجع', 'dark'],
        Shipment::STATUS_RETURNED_TO_MERCHANT => ['تم إرجاعها للتاجر', 'dark'],
        Shipment::STATUS_CANCELLED => ['ملغاة', 'secondary'],
    ];

    private const FINANCIAL = [
        Shipment::FIN_UNCOLLECTED => ['غير محصَّل', 'secondary'],
        Shipment::FIN_COLLECTED => ['محصَّل (بعهدة المندوب)', 'warning'],
        Shipment::FIN_RECEIVED_BY_BRANCH => ['وصل للفرع', 'info'],
        Shipment::FIN_RECEIVED_BY_COMPANY => ['وصل للشركة', 'primary'],
        Shipment::FIN_SETTLED_TO_MERCHANT => ['تم تسويته للتاجر', 'success'],
    ];

    private const DELIVERY_RESULTS = [
        'no_answer' => 'لا يرد',
        'postponed' => 'مؤجل',
        'refused_receipt' => 'رفض الاستلام',
        'refused_receipt_fled' => 'رفض الاستلام وهروب',
        'wrong_address' => 'عنوان خاطئ',
        'refused_price' => 'رفض السعر',
    ];

    public static function shipment(string $status): array
    {
        return self::SHIPMENT[$status] ?? [$status, 'secondary'];
    }

    public static function financial(string $status): array
    {
        return self::FINANCIAL[$status] ?? [$status, 'secondary'];
    }

    public static function deliveryResult(string $result): string
    {
        return self::DELIVERY_RESULTS[$result] ?? $result;
    }
}
