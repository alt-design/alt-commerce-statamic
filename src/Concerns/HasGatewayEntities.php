<?php

namespace AltDesign\AltCommerceStatamic\Concerns;

use AltDesign\AltCommerce\Support\GatewayEntity;
use Statamic\Entries\Entry;

trait HasGatewayEntities
{
    /**
     * @param GatewayEntity[] $gatewayEntities
     */
    protected function storeGatewayEntities(Entry $entry, string $type, string $id, array $gatewayEntities): void
    {
        $entities = $entry->get('gateway_entities') ?? [];
        foreach ($this->mapGatewayEntities($type, $id, $gatewayEntities) as $key => $value) {
            $entities[$key] = $value;
        }

        $entry->set('gateway_entities', $entities);
    }

    /**
     * @param GatewayEntity[] $gatewayEntities
     * @return array<string, string>
     */
    protected function mapGatewayEntities(string $type, string $id, array $gatewayEntities): array
    {
        $ar = [];
        foreach ($gatewayEntities as $entity) {

            $key = md5($type.':'.$entity->gateway.':'.$id.':'.json_encode($entity->context));
            $ar[$key] = [
                'gateway' => $entity->gateway,
                'type' => $type,
                'entity_id' => $id,
                'gateway_id' => $entity->gatewayId,
                'context' => $entity->context
            ];
        }
        return $ar;
    }

    protected function extractGatewayEntities(Entry $entry, string $type, string $id): array
    {
        $entities = [];
        foreach ($entry->get('gateway_entities') ?? [] as $details) {
            if ($details['type'] === $type && $details['entity_id'] === $id) {
                $entities[] = new GatewayEntity($details['gateway'], $details['gateway_id'], $details['context'] ?? []);
            }
        }
        return $entities;
    }
}