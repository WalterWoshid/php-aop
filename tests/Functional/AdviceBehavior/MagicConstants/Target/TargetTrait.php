<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\MagicConstants\Target;

trait TargetTrait
{
    /** @var array<string, string> */
    public array $traitProperty = [
        'dir' => __DIR__,
        'file' => __FILE__,
        'function' => __FUNCTION__,
        'class' => __CLASS__,
        'trait' => __TRAIT__,
        'method' => __METHOD__,
        'namespace' => __NAMESPACE__,
        'targetClassClass' => TargetClass::class,
        'targetTraitClass' => TargetTrait::class,
        'targetParentClass' => TargetParent::class,
        'selfClass' => self::class,
    ];

    /** @var array<string, string> */
    public static array $traitStaticProperty = [
        'dir' => __DIR__,
        'file' => __FILE__,
        'function' => __FUNCTION__,
        'class' => __CLASS__,
        'trait' => __TRAIT__,
        'method' => __METHOD__,
        'namespace' => __NAMESPACE__,
        'targetClassClass' => TargetClass::class,
        'targetTraitClass' => TargetTrait::class,
        'targetParentClass' => TargetParent::class,
        'selfClass' => self::class,
    ];

    /** @return array<string, string> */
    public function traitMethod(): array
    {
        return [
            'dir' => __DIR__,
            'file' => __FILE__,
            'function' => __FUNCTION__,
            'class' => __CLASS__,
            'trait' => __TRAIT__,
            'method' => __METHOD__,
            'namespace' => __NAMESPACE__,
            'targetClassClass' => TargetClass::class,
            'targetTraitClass' => TargetTrait::class,
            'targetParentClass' => TargetParent::class,
            'selfClass' => self::class,
            'staticClass' => static::class,
        ];
    }

    /** @return array<string, string> */
    public static function traitStaticMethod(): array
    {
        return [
            'dir' => __DIR__,
            'file' => __FILE__,
            'function' => __FUNCTION__,
            'class' => __CLASS__,
            'trait' => __TRAIT__,
            'method' => __METHOD__,
            'namespace' => __NAMESPACE__,
            'targetClassClass' => TargetClass::class,
            'targetTraitClass' => TargetTrait::class,
            'targetParentClass' => TargetParent::class,
            'selfClass' => self::class,
            'staticClass' => static::class,
        ];
    }
}
