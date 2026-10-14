<?php
namespace app\system\transformer;


/**
 * Transformer Abstract
 *
 * All Transformer classes should extend this to utilize the convenience methods
 * collection() and item(), and make the self::$availableIncludes property available.
 * Extend it and add a `transform()` method to transform any default or included data
 * into a basic array.
 */
abstract class TransformerAbstract
{
    abstract public function transform($data,$param = []);
}
