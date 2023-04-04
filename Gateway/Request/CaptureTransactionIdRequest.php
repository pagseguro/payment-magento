<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class Capture Trancation Id - Capture Transaction Id structure.
 */
class CaptureTransactionIdRequest implements BuilderInterface
{
    /**
     * @var string
     */
    public const PAGBANK_PAYMENT_ID = 'payment_id';

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transacRepository;

    /**
     * @param TransactionRepositoryInterface $transacRepository
     */
    public function __construct(
        TransactionRepositoryInterface $transacRepository
    ) {
        $this->transacRepository = $transacRepository;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var InfoInterface $payment * */
        $payment = $paymentDO->getPayment();

        $transaction = $this->transacRepository->getByTransactionType(
            Transaction::TYPE_CAPTURE,
            $payment->getId(),
            $payment->getOrder()->getId()
        );

        return [
            self::PAGBANK_PAYMENT_ID => str_replace('-capture', '', $transaction->getTxnId()),
        ];
    }
}
