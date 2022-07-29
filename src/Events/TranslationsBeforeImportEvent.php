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
        protected bool $replace,
        protected string $base,
        protected string $import_group
    ) {}
}
