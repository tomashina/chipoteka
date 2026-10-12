<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

/**
 * Creates Croatian payment references for webshop bank transfers.
 */
class PaymentReference
{
    public const HUB3_MODEL = '01';


    /**
     * Return the HR01 reference for a bank-transfer order, or an empty string
     * for every other payment method.
     */
    public static function forOrder(array $order): string
    {
        if (($order['payment_code'] ?? '') !== 'bank_transfer') {
            return '';
        }

        $order_id = (string) ((int) ($order['order_id'] ?? 0));

        if ($order_id === '0') {
            return '';
        }

        $warehouse = static::resolveWarehouse($order);
        $year_month = static::resolveYearMonth($order);
        $base = $order_id . $warehouse . $year_month;

        return $order_id . '-' . $warehouse . '-' . $year_month . static::hr01CheckDigit($base);
    }


    /**
     * Resolve the storage of the actual main Luceed order.
     */
    public static function resolveWarehouse(array $order): string
    {
        $shipping_code = (string) ($order['shipping_code'] ?? '');
        $pickup_prefix = 'pickup.pickup_';

        if (strpos($shipping_code, $pickup_prefix) === 0) {
            $pickup_code = substr($shipping_code, strlen($pickup_prefix));

            if ($pickup_code !== '' && ctype_digit($pickup_code)) {
                return str_pad($pickup_code, 3, '0', STR_PAD_LEFT);
            }
        }

        $is_b2b = trim((string) ($order['oib'] ?? '')) !== ''
            && (int) ($order['customer_group_id'] ?? 0) > 2;

        return $is_b2b ? '101' : '099';
    }


    /**
     * Use the creation month so resend operations cannot change the reference.
     */
    public static function resolveYearMonth(array $order): string
    {
        $date_added = trim((string) ($order['date_added'] ?? ''));

        if ($date_added !== '') {
            $timestamp = strtotime($date_added);

            if ($timestamp !== false) {
                return date('ym', $timestamp);
            }
        }

        return date('ym');
    }


    /**
     * Croatian HR01 / ISO 7064 MOD 11,10 control digit.
     */
    public static function hr01CheckDigit(string $base): int
    {
        if ($base === '' || ! ctype_digit($base)) {
            throw new \InvalidArgumentException('HR01 reference base must contain only digits.');
        }

        $product = 10;

        foreach (str_split($base) as $digit) {
            $product = ($product + (int) $digit) % 10;
            $product = $product === 0 ? 10 : $product;
            $product = ($product * 2) % 11;
        }

        $check_digit = 11 - $product;

        return $check_digit === 10 ? 0 : $check_digit;
    }
}
