<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 5/29/18
 * Time: 12:40 PM.
 */

namespace Barryvdh\TranslationManager\Events;

class TranslationsAfterExportEvent
{
    /**
     * Create a new event instance.
     * @param string[] $groups
     */
    public function __construct(
        public readonly array $groups,
    ) {}
}
