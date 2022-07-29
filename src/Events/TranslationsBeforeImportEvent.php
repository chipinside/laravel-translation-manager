<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 5/29/18
 * Time: 12:40 PM.
 */

namespace Barryvdh\TranslationManager\Events;

class TranslationsBeforeImportEvent
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly bool $replace,
        public readonly string $base,
        public readonly string $import_group
    ) {}
}
