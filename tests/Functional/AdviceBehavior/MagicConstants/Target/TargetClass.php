<?php

namespace Okapi\Aop\Tests\Functional\AdviceBehavior\MagicConstants\Target;

class TargetClass extends TargetParent
{
    use TargetTrait;

    public const CONST = [
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
    public array $property = [
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
    public static array $staticProperty = [
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
    public function method(): array
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
    public static function staticMethod(): array
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
