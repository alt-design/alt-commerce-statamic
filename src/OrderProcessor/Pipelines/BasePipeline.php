<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor\Pipelines;

use AltDesign\AltCommerce\Commerce\Order\Order;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Pipeline\Pipeline;

abstract class BasePipeline implements ShouldQueue
{

    public const TAG = '';

    use Dispatchable, Queueable;

    protected $afterCallback;

    protected $finallyCallback;

    public function __construct(protected string $orderNumber)
    {

    }

    protected function start()
    {
        $orderRepository = app(StatamicOrderRepository::class);
        $order = $orderRepository->findByOrderNumber($this->orderNumber);
        if (empty($order)) {
            throw new \Exception('Order not found for order number: '.$this->orderNumber);
        }

        $jobs = app()->tagged(static::TAG);

        if (!empty($jobs)) {
            app(Pipeline::class)
                ->send($order)
                ->through(...$jobs)
                ->then(function (Order $order) use ($orderRepository) {
                    if (is_callable($this->afterCallback)) {
                        call_user_func($this->afterCallback, $order);
                    }
                    $orderRepository->save($order);
                });
        }

        if (is_callable($this->finallyCallback)) {
            call_user_func($this->finallyCallback, $order);
        }

    }

    protected function after(callable $callback): static
    {
        $this->afterCallback = $callback;
        return $this;
    }

    protected function finally(callable $callback): static
    {
        $this->finallyCallback = $callback;
        return $this;
    }

    protected function jobs(): array
    {
        return app()->tagged(static::TAG);
    }
}