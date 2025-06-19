<?php

namespace AltDesign\AltCommerceStatamic\OrderProcessor;

use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrder;
use AltDesign\AltCommerceStatamic\Commerce\Order\StatamicOrderRepository;
use AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\DispatchNextStage;
use AltDesign\AltCommerceStatamic\OrderProcessor\Tasks\LogCompletedPipeline;
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

    public function process(StatamicOrder $order, string $profile = 'default', string $stage = null, string $connection = null, string $mode = null, \Closure $after = null): void
    {

        $config = config('alt-commerce.order-pipelines.' . $profile, []);
        if (empty($config)) {
            throw new \Exception('No order pipeline found for profile: ' . $profile);
        }

        $stageName = $stage ?? Arr::first(array_keys($config['stages']));
        $stage = $config['stages'][$stageName] ?? throw new \Exception("Stage '$stageName' does not exist in profile '$profile'");

        if ($after) {
            array_push($stage['tasks'], ...$after($order->id));
        }

        $this->runStage(
            order: $order,
            tasks: $stage['tasks'] ?? [],
            condition: $stage['condition'] ?? null,
            stage: $stageName,
            profile: $profile,
            nextStage: $this->determineNextStage($config, $stageName),
            connection: $connection ?? $stage['connection'] ?? 'default',
            queue: $stage['queue'] ?? 'default',
            mode: $mode ?? $config['mode'] ?? 'manual',
        );
    }

    protected function runStage(
        StatamicOrder $order,
        array $tasks,
        string|null $condition,
        string $stage,
        string $profile,
        string|null $nextStage,
        string $connection,
        string $queue,
        string $mode,
    ): void
    {
        $this->info($order, "Running stage '$stage' on pipeline '$profile'");

        if (!$this->checkCondition($order, $condition)) {
            $this->finished($order);
            return;
        }

        if (empty($tasks)) {
            $this->info($order, 'Pipeline has no tasks defined.');
            $this->finished($order);
            return;
        }

        $this->info($order, "Dispatching jobs to queue '$queue' using connection '$connection'");

        $args = [
            'orderId' => $order->id,
            'logger' => $this->logger
        ];

        $tasks = collect($tasks)->map(function($task) use($args) {
            if (is_object($task)) {
                return $task;
            }
            return $this->container->makeWith($task, $args);
        })->toArray();

        // Add a task to run the processor again on the order
        if ($nextStage && ($mode === 'sequential')) {
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

    }

    protected function checkCondition(StatamicOrder $order, string|null $condition): bool
    {

        if (!empty($condition)) {
            $result = $this->container->make($condition)->validate($order);
            $this->info($order, 'Checking pipeline condition using '. $condition);
            $this->info($order, $result ? 'Condition passed' : 'Condition failed');
            return $result;
        }

        $this->info($order, 'Pipeline has no condition defined.');
        return true;
    }

    protected function determineNextStage(array $pipeline, string $current): string|null
    {
        $keys = array_keys($pipeline['stages']);
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