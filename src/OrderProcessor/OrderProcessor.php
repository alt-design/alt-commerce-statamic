<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\DispatchNextStage;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
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

    public function process(StatamicOrder $order, string $profile = 'default', string $stage = null): void
    {

        $config = config('alt-commerce.order-pipelines.' . $profile, []);
        if (empty($config)) {
            throw new \Exception('No order pipeline found for profile: ' . $profile);
        }

        $stage = $stage ?? Arr::first(array_keys($config));
        $pipeline = $config[$stage] ?? throw new \Exception("Stage '$stage' does not exist in profile '$profile'");

        $this->runPipeline(
            order: $order,
            pipeline: $pipeline,
            profile: $profile,
            stage: $stage,
            nextStage: $this->determineNextStage($config, $stage)
        );

    }

    protected function runPipeline(StatamicOrder $order, array $pipeline, string $profile, string $stage, string|null $nextStage): void
    {
        $this->info($order, "Running stage '$stage' on pipeline '$profile'");

        if (!$this->checkCondition($order, $pipeline)) {
            $this->finished($order);
            return;
        }

        if (empty($pipeline['tasks'])) {
            $this->info($order, 'Pipeline has no tasks defined.');
            $this->finished($order);
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

        // Add a task to run the processor again on the order
        if ($nextStage) {
            $this->info($order, "Configuring next stage to be '$nextStage'");

            $tasks[] = new DispatchNextStage(
                orderId: $order->id,
                profile: $profile,
                stage: $nextStage,
            );
        }

        Bus::chain($tasks)
            ->onConnection($connection)
            ->onQueue($queue)
            ->dispatch();


        $this->finished($order);
    }

    protected function checkCondition(StatamicOrder $order, array $pipeline): bool
    {

        if (!empty($pipeline['condition'])) {
            $result = $this->container->make($pipeline['condition'])->validate($order);
            $this->info($order, 'Checking pipeline condition using '. $pipeline['condition']);
            $this->info($order, $result ? 'Condition passed' : 'Condition failed');
            return $result;
        }

        $this->info($order, 'Pipeline has no condition defined.');
        return true;
    }

    protected function determineNextStage(array $pipeline, string $current): string|null
    {
        $keys = array_keys($pipeline);
        $index = array_search($current, $keys);

        if ($index !== false && isset($keys[$index + 1])) {
            return $keys[$index + 1];
        }

        return null;
    }

    protected function finished(StatamicOrder $order): void
    {
        $this->info($order, 'Pipeline finished');
    }

    protected function info(StatamicOrder $order, string $message): void
    {
        $this->logger->info("$message", [
            'order_id' => $order->id
        ]);
    }
}