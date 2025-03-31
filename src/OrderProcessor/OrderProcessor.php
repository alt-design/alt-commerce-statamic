<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Bus;
use Psr\Log\LoggerInterface;

class OrderProcessor
{

    public function __construct(
        protected StatamicOrderRepository $orderRepository,
        protected Pipeline $pipeline,
        protected LoggerInterface $logger,
        protected Container $container,
    )
    {

    }

    public function process(StatamicOrder $order, string $profile = 'default'): void
    {

        $config = config('alt-commerce.order-pipelines.' . $profile, []);
        if (empty($config)) {
            throw new \Exception('No order pipeline found for profile: ' . $profile);
        }

        $this->info($order, "Processing order with '$profile' profile");

        foreach ($config as $name => $pipeline) {
            $this->runPipeline($order, $name, $pipeline);
        }
    }

    protected function runPipeline(StatamicOrder $order, string $name, array $pipeline): void
    {
        $this->info($order, "Running pipeline '$name'");

        if (empty($pipeline['tasks'])) {
            $this->info($order, 'Pipeline has no tasks defined.');
            $this->info($order, 'Pipeline finished');
            return;
        }

        $connection = $pipeline['connection'] ?? 'default';
        $queue = $pipeline['queue'] ?? 'default';

        $this->info($order, "Dispatching jobs to queue '$queue' using connection '$connection'");

        $args = [
            'orderId' => $order->id,
            'logger' => $this->logger
        ];

        $tasks = collect($pipeline['tasks'])->map(fn($task) => $this->container->makeWith($task, $args))->toArray();

        Bus::chain($tasks)
            ->onConnection($connection)
            ->onQueue($queue)
            ->dispatch();

        $this->info($order, 'Pipeline finished');
    }


    protected function info(StatamicOrder $order, string $message): void
    {
        $this->logger->info("$message", [
            'order_id' => $order->id
        ]);
    }
}