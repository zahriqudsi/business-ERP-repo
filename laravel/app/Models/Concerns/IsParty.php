<?php

namespace App\Models\Concerns;

/**
 * Shared behaviour of customers and suppliers: records found or created from the free-text
 * name typed on a document.
 *
 * @property int $id
 * @property string $name
 */
trait IsParty
{
    /**
     * The id of the party with this name, creating it when it is new. Null for a blank name.
     *
     * @param  array<string, mixed>  $details  Contact details used only when the party is created
     */
    public static function idForName(?string $name, array $details = []): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        return static::firstOrCreate(['name' => mb_substr($name, 0, 255)], array_filter($details))->id;
    }

    /**
     * Names for the autocomplete list on document forms.
     *
     * @return list<string>
     */
    public static function namesForPicker(): array
    {
        return static::query()->orderBy('name')->pluck('name')->all();
    }
}
