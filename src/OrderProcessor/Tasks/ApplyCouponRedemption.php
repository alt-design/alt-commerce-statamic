<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Tasks;


use AltDesign\AltCommerce\Enum\DiscountType;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderFactory;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Entry;
use Symfony\Component\Process\Process;


class ApplyCouponRedemption implements ShouldQueue
{
    use Queueable, CanDebug;

    public function __construct(protected string $orderId)
    {

    }

    public function handle(StatamicOrderRepository $orderRepository, StatamicOrderFactory $orderFactory): void
    {
        $order = $this->findOrder($orderRepository, $orderFactory);

        if (empty($order)) {
            Log::error('Order cannot be found '. $this->orderId);
            return;
        }

        foreach ($order->discountItems as $discountItem) {
            if ($discountItem->type !== DiscountType::PRODUCT_COUPON) {
                continue;
            }

            Entry::make()
                ->collection('coupon_redemptions')
                ->data([
                    'title' => $discountItem->name,
                    'code' => $discountItem->couponCode,
                    'amount' => $discountItem->amount,
                    'currency' => $order->currency,
                    'customer_id' => $order->customerId,
                    'customer_email' => $order->customerEmail,
                    'order_id' => $order->id,
                ])
                ->save();
        }
    }

    protected function findOrder(StatamicOrderRepository $orderRepository, StatamicOrderFactory $orderFactory): StatamicOrder|null
    {
        $this->debug('attempting to find order');
        $order = $orderRepository->find($this->orderId);

        if ($order) {
            $this->debug('order found');
            return $order;
        }

        $this->debug('sleeping for 1 second then trying again');
        sleep(1);

        $order = $orderRepository->find($this->orderId);
        if ($order) {
            $this->debug('order found after sleeping for 1 second');
            return $order;
        }

        $this->debug('attempting to find entry directly');
        $entry = Entry::query()
            ->where('collection', 'orders')
            ->where('id', $this->orderId)
            ->first();

        if ($entry) {
            $this->debug('order found using entry facade');
            return $orderFactory->fromEntry($entry);
        }


        $process  = new Process(['grep', '-r', $this->orderId, base_path('content/collections/orders')]);
        $process->run();
        $this->debug('running grep to see if the file actual exists', [
            'output' => $process->getOutput(),
        ]);

        return null;
    }
}