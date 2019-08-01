<?php

use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\Codec\OrderedTimeCodec;

/**
 * Get a hashid.
 *
 * @param  int  $value
 * @return string
 */
function hashid_encode($value)
{
    $hashids = new Hashids\Hashids(config('app.key'), 36);

    return $hashids->encode($value);
}

/**
 * Decode a hashid.
 *
 * @param  string  $value
 * @return int
 */
function hashid_decode($value)
{
    $hashids = new Hashids\Hashids(config('app.key'), 36);

    return $hashids->decode($value)[0];
}

/**
 * Get a new UUID.
 *
 * @var string
 */
function uuid()
{
    $orderedTimeFactory = new UuidFactory;
    $orderedTimeFactory->setCodec(new OrderedTimeCodec($orderedTimeFactory->getUuidBuilder()));
    $orderedTimeUuid = $orderedTimeFactory->uuid1();
    return (string) $orderedTimeUuid;
}
