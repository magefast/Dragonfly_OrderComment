<?php

namespace Dragonfly\OrderComment\Observer;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status\HistoryFactory;

class SaveOrderCommentObserver implements ObserverInterface
{
    private Session $checkoutSession;
    private HistoryFactory $historyFactory;

    /**
     * @param Session $checkoutSession
     * @param HistoryFactory $historyFactory
     */
    public function __construct(
        Session        $checkoutSession,
        HistoryFactory $historyFactory
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->historyFactory = $historyFactory;
    }

    /**
     * @param Observer $observer
     * @return $this
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        if (!$this->checkoutSession->getOrderComment()) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();

        $text = $this->checkoutSession->getOrderComment();

        if (empty($text)) {
            return;
        }

        if (!empty($text)) {
            $text = trim($text);
        }

        $this->addCommentToOrder($order, $text);

        $this->unsetSessionVar();
    }

    /**
     * @param $order
     * @param $comment
     * @return void
     * @throws LocalizedException
     */
    public function addCommentToOrder($order, $comment): void
    {
        try {
            $statusHistory = $this->historyFactory->create();
            $statusHistory->setComment(__('Client Order Comment: %1', $comment));
            $statusHistory->setEntityName(Order::ENTITY);
            $statusHistory->setStatus($order->getStatus());
            $statusHistory->setIsCustomerNotified(false)->setIsVisibleOnFront(true);
            $order->addStatusHistory($statusHistory);
            $order->save();
        } catch (Exception $e) {
            throw new LocalizedException(__("Failed to add the comment to the order: %1", $e->getMessage()));
        }
    }

    private function unsetSessionVar()
    {
        $this->checkoutSession->unsOrderComment();
    }
}
