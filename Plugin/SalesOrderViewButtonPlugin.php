<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

declare(strict_types=1);

namespace PagBank\PaymentMagento\Plugin;

use Magento\Sales\Block\Adminhtml\Order\View;

/**
 * Class Sales Order View Button - Add and Change Buttons in Order View.
 */
class SalesOrderViewButtonPlugin
{
    /**
     * Add Button for update payment.
     *
     * @param View $subject
     */
    public function beforeSetLayout(
        View $subject
    ) {
        $order = $subject->getOrder();
        $payment = $order->getPayment();
        $method = (string) $payment->getMethod();

        if (strpos($method, 'pagbank_paymentmagento') !== false) {
            if ($subject->getOrder()->getState() === 'new') {
                $subject->addButton(
                    'get_review_payment_update',
                    [
                        'label'   => __('Get Payment Update'),
                        'onclick' => 'setLocation(\''.$subject->getReviewPaymentUrl('update').'\')',
                    ]
                );
            }
        }
    }

    /**
     * Change Message Alert.
     *
     * @param View     $subject
     * @param \Closure $proceed
     * @param string   $buttonId
     * @param array    $data
     * @param int      $level
     * @param int      $sortOrder
     * @param string   $region
     *
     * @return View
     */
    public function aroundAddButton(
        View $subject,
        \Closure $proceed,
        string $buttonId,
        array $data,
        int $level = 0,
        int $sortOrder = 0,
        string $region = 'toolbar'
    ) {
        $order = $subject->getOrder();
        $payment = $order->getPayment();
        $method = (string) $payment->getMethod();

        if ($method === 'pagbank_paymentmagento_pix'
            || $method === 'pagbank_paymentmagento_boleto') {
            if ($buttonId === 'accept_payment') {
                $message = __('This decision will not change the status in PagBank.');
                $data = [
                    'label'   => __('Accept Offline Payment'),
                    'onclick' => "confirmSetLocation('{$message}', '{$subject->getReviewPaymentUrl('accept')}')",
                ];

                return $proceed($buttonId, $data, $level, $sortOrder, $region);
            }

            if ($buttonId === 'deny_payment') {
                $message = __('This decision will not change the status in PagBank.');
                $data = [
                    'label'   => __('Deny Offline Payment'),
                    'onclick' => "confirmSetLocation('{$message}', '{$subject->getReviewPaymentUrl('deny')}')",
                ];

                return $proceed($buttonId, $data, $level, $sortOrder, $region);
            }
        }

        return $proceed($buttonId, $data, $level, $sortOrder, $region);
    }
}
