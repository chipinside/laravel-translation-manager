<?php
/**
 * Created by PhpStorm.
 * User: kgbot
 * Date: 5/29/18
 * Time: 12:40 PM.
 */

namespace Barryvdh\TranslationManager\Events;

class TranslationsAfterPublish
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly ?string $group
    ) {}
}
