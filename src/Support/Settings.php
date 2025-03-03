<?php

namespace AltDesign\AltCommerceStatamic\Support;


use Statamic\Facades\YAML;
use Statamic\Fields\BlueprintRepository;
use Statamic\Filesystem\Manager;


class Settings implements \AltDesign\AltCommerce\Contracts\Settings
{
    protected array $settings = [];

    public function __construct(
        protected Manager $fileSystemManager,
        protected BlueprintRepository $blueprintRepository,
    )
    {
        $this->load();
    }

    public function tradingName(): string
    {
        return $this->settings['trading_name'] ?? 'Alt Commerce';
    }

    public function statementDescriptor(): string
    {
        return $this->settings['statement_descriptor'] ?? 'Alt Commerce';
    }

    public function defaultCountryCode(): string
    {
        return $this->settings['default_country_code'] ?? 'gb';
    }

    public function defaultCurrency(): string
    {
        return $this->settings['default_currency'] ?? 'GBP';
    }

    public function supportedCurrencies(): array
    {
        return $this->settings['supported_currencies'] ?? ['GBP'];
    }

    public function orderNumberStartSequence(): string
    {
        return $this->settings['order_number_start_sequence'] ?? '00001';
    }

    public function orderNumberPrefix(): null|string
    {
        return $this->settings['order_number_prefix'];
    }

    public function currentOrderNumber(): int
    {
        return $this->settings['current_order_number'] ?? 0;
    }

    public function taxRules(): array
    {
        return $this->settings['tax_rules'] ?? [];
    }

    public function setAll(array $settings): void
    {
        $this->settings = $settings;
        $this->save();
    }

    public function set(string $name, mixed $value): void
    {
        if (!in_array($name, ['current_order_number'])) {
            throw new \InvalidArgumentException("$name is not a valid setting");
        }
        $this->settings[$name] = $value;
        $this->save();
    }

    public function all(): array
    {
        return $this->settings;
    }

    public function blueprint(): mixed
    {
        return $this->blueprintRepository->setDirectory(__DIR__ . '/../../resources/blueprints')->find('settings');
    }

    protected function load()
    {
        if (empty($this->settings)) {
            $filePath = $this->fileSystemManager->disk()->get($this->settingsFilePath());
            $this->settings = Yaml::parse($filePath);
        }
    }

    protected function save()
    {
        $this->fileSystemManager->disk()->put($this->settingsFilePath(), Yaml::dump($this->settings));
    }

    protected function settingsFilePath(): string
    {
        return 'content/alt-commerce/settings.yaml';
    }
}