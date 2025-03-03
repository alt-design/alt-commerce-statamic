<?php

namespace AltDesign\AltCommerceStatamic\CP\Actions;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderFactory;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use Statamic\Actions\Action;

abstract class OrderAction extends Action
{

    protected bool $allowMultiple = true;

    protected array $callbackData = [];

    public function __construct(protected StatamicOrderFactory $orderFactory, protected StatamicOrderRepository $orderRepository)
    {
        parent::__construct();
    }

    public function run($items, $values)
    {
        foreach ($items as $item) {

            $order = $this->orderRepository->find($item->id());
            if (empty($order)) {
                throw new \Exception('Unable to obtain order with id '.$item->id());
            }

            $this->runOnOrder($order, $values);
        }

        return [
            'callback' => ['orderActionRan', $this->callbackData],
            ...$this->result()
        ];
    }

    public function visibleTo($item)
    {
        if (empty($this->items)) {
            return false;
        }

        if (!$this->allowMultiple && count($this->items) !== 1) {
            return false;
        }

        $order = app(StatamicOrderRepository::class)->find($item->id());

        if (empty($order)) {
            return false;
        }

        return $this->visibleToOrder($order);
    }

    abstract protected function runOnOrder(StatamicOrder $order, $values): void;

    abstract protected function visibleToOrder(StatamicOrder $order): bool;

    abstract protected function result(): array;

}