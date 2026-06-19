<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Data;

/** Recipient address details from the Shipment object. */
class Recipient
{
    /**
     * @param string|null $name        Recipient full name
     * @param string|null $address     Street address
     * @param string|null $postCode    Postal / ZIP code
     * @param string|null $city        City name
     * @param string|null $subdivision State, province, or region code
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $address,
        public readonly ?string $postCode,
        public readonly ?string $city,
        public readonly ?string $subdivision,
    ) {}

    /**
     * @param array<string, mixed> $item
     */
    public static function fromArray(array $item): self
    {
        return new self(
            name: isset($item['name']) ? (string) $item['name'] : null,
            address: isset($item['address']) ? (string) $item['address'] : null,
            postCode: isset($item['postCode']) ? (string) $item['postCode'] : null,
            city: isset($item['city']) ? (string) $item['city'] : null,
            subdivision: isset($item['subdivision']) ? (string) $item['subdivision'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'        => $this->name,
            'address'     => $this->address,
            'postCode'    => $this->postCode,
            'city'        => $this->city,
            'subdivision' => $this->subdivision,
        ];
    }
}
