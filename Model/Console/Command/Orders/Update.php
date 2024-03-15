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

namespace PagBank\PaymentMagento\Model\Console\Command\Orders;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use PagBank\PaymentMagento\Model\Console\Command\AbstractModel;

/**
 * Class Update - Manually apply the PagBank order status.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Update extends AbstractModel
{
    /**
     * Time due for Pix.
     */
    public const TIME_DUE_PIX = 5;

    /**
     * Time due for Deep Link.
     */
    public const TIME_DUE_DEEP_LINK = 5;

    /**
     * Time due for Boleto.
     */
    public const TIME_DUE_BOLETO = 2880;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @param State                    $state
     * @param SearchCriteriaBuilder    $searchCriteria
     * @param OrderRepositoryInterface $orderRepository
     * @param DateTime                 $date
     * @param TimezoneInterface        $localeDate
     */
    public function __construct(
        State $state,
        SearchCriteriaBuilder $searchCriteria,
        OrderRepositoryInterface $orderRepository,
        DateTime $date,
        TimezoneInterface $localeDate
    ) {
        $this->state = $state;
        $this->searchCriteria = $searchCriteria;
        $this->orderRepository = $orderRepository;
        $this->date = $date;
        $this->localeDate = $localeDate;
    }

    /**
     * Command Preference.
     *
     * @param string $incrementId
     *
     * @return int
     */
    public function getUpdate($incrementId)
    {
        $this->writeln(__('Querying the status of the order at the gateway.'));

        /** @var OrderInterface|null $order */
        $order = $this->getMageOrder($incrementId);

        if ($order) {
            $currentState = $order->getState();

            /** @var InfoInterface $payment * */
            $payment = $order->getPayment();

            $payment->update(true);

            $expired = $this->hasExpired($payment);

            if ($currentState === \Magento\Sales\Model\Order::STATE_NEW) {
                $order->save();
            }

            if ($expired) {
                $this->setExpiredPayment($payment);
                $order->cancel(true);
                $comment = __('Order cancelled, payment deadline has expired.');
                $order->addStatusToHistory($order->getStatus(), $comment, true);
                $order->save();
            }

            $newState = $order->getState();

            $message = __(
                'Order %1 was in state %2 has been updated to state %3.',
                $incrementId,
                $currentState,
                $newState
            );
            $this->writeln('<info>'.$message.'</info>');
        }
        $this->writeln(__('Finished'));

        return 1;
    }

    /**
     * Get Mage Order.
     *
     * @param string $incrementId
     *
     * @return OrderInterface|null
     */
    public function getMageOrder($incrementId)
    {
        $order = null;
        /** @var SearchCriteriaBuilder $search */
        $search = $this->searchCriteria->addFilter('increment_id', $incrementId)->create();

        try {
            /** @var OrderInterface $order */
            $order = $this->orderRepository->getList($search)->getFirstItem();

            if (!$order->getId()) {
                $order = null;
                $this->writeln('<error>'.__('Order not found').'</error>');
            }

            $state = $order->getState();

            if ($state !== Order::STATE_NEW && $state !== Order::STATE_PAYMENT_REVIEW) {
                $this->writeln('<error>'.
                __('Update not available because the initial state is incompatible: %1', $order->getState())
                .'</error>');
                $order = null;
            }
        } catch (LocalizedException $exc) {
            $this->writeln('<error>'.$exc->getMessage().'</error>');
        }

        return $order;
    }

    /**
     * Has Expired - see if payment time has expired.
     *
     * @param InfoInterface $payment
     *
     * @return int
     */
    public function hasExpired($payment)
    {
        $method = $payment->getMethod();

        $due = '-10080';

        $initExpireIn = strtotime((string) $payment->getAdditionalInformation('expiration_date'));

        if ($method === 'pagbank_paymentmagento_pix') {
            $due = self::TIME_DUE_PIX * -1;
        }

        if ($method === 'pagbank_paymentmagento_boleto') {
            $due = self::TIME_DUE_BOLETO * -1;
        }

        if ($method === 'pagbank_paymentmagento_deep_link') {
            $due = self::TIME_DUE_DEEP_LINK * -1;
        }

        $initDateNow = $this->date->gmtDate('Y-m-d\TH:i:s.uP', strtotime("{$due} minutes"));

        $dateNow = $this->localeDate->date($initDateNow)->format('Y-m-d H:i:s');

        $expireIn = $this->localeDate->date($initExpireIn)->format('Y-m-d H:i:s');

        return ($dateNow > $expireIn) ? 1 : 0;
    }

    /**
     * Set Expired Payment - cancel order if expired.
     *
     * @param InfoInterface $payment
     *
     * @return void
     */
    public function setExpiredPayment($payment)
    {
        $payment->deny(false);
        $payment->registerVoidNotification();
    }
}
