<?php
/**
 * PagBank Payment Magento Module.
 *
 * Copyright © 2023 PagBank. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * @license   See LICENSE for license details.
 */

namespace PagBank\PaymentMagento\Gateway\Request\Boleto;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use PagBank\PaymentMagento\Gateway\Config\ConfigBoleto;
use PagBank\PaymentMagento\Gateway\Request\ChargesDataRequest;

/**
 * Class Payment Method Data Request - Payment data structure for boleto.
 */
class PaymentMethodDataRequest implements BuilderInterface
{
    /**
     * Payment Method block name.
     */
    public const PAYMENT_METHOD = 'payment_method';

    /**
     * Payment Type block name.
     */
    public const PAYMENT_TYPE = 'type';

    /**
     * Method block name.
     */
    public const METHOD = 'BOLETO';

    /**
     * Boleto Due date block name.
     */
    public const BOLETO_DUE_DATE = 'due_date';

    /**
     * Boleto instruction lines block name.
     */
    public const BOLETO_INSTRUCTION_LINES = 'instruction_lines';

    /**
     * Boleto instruction lines line one block name.
     */
    public const BOLETO_INSTRUCTION_LINES_LINE_ONE = 'line_1';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var ConfigBoleto
     */
    protected $configBoleto;

    /**
     * @param ConfigBoleto $configBoleto
     */
    public function __construct(
        ConfigBoleto $configBoleto
    ) {
        $this->configBoleto = $configBoleto;
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
        $result = [];

        /** @var PaymentDataObject $paymentDO * */
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order $order * */
        $order = $paymentDO->getOrder();

        $storeId = $order->getStoreId();

        $result[ChargesDataRequest::CHARGES][] = $this->getDataPaymetBoleto($storeId);

        return $result;
    }

    /**
     * Data for Boleto.
     *
     * @param int $storeId
     *
     * @return array
     */
    public function getDataPaymetBoleto($storeId)
    {
        $instruction = [];
        $instruction[self::PAYMENT_METHOD] = [
            strtolower(self::METHOD)    => [
                self::BOLETO_DUE_DATE           => $this->configBoleto->getExpiration($storeId),
                self::BOLETO_INSTRUCTION_LINES  => [
                    self::BOLETO_INSTRUCTION_LINES_LINE_ONE => $this->configBoleto->getInstructionLine($storeId),
                ],
            ],
        ];

        return $instruction;
    }
}
