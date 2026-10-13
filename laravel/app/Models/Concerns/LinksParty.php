<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * A document that prints a customer/supplier name and links to the matching record.
 * The link follows the name whenever the name is set or changed.
 */
trait LinksParty
{
    /**
     * @return array{0: class-string<Model>, 1: string, 2: string} [party model, name column, foreign key]
     */
    abstract protected static function partyLink(): array;

    /**
     * Contact details to give a party that is created from this document.
     *
     * @return array<string, mixed>
     */
    protected function partyDetails(): array
    {
        return [];
    }

    protected static function bootLinksParty(): void
    {
        static::saving(function (Model $document) {
            [$partyModel, $nameColumn, $foreignKey] = static::partyLink();

            if ($document->isDirty($nameColumn) || ($document->{$foreignKey} === null && filled($document->{$nameColumn}))) {
                $document->{$foreignKey} = $partyModel::idForName($document->{$nameColumn}, $document->partyDetails());
            }
        });
    }
}
