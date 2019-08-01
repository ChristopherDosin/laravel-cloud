<?php

namespace App;

class StackMetadata
{
    /**
     * The underlying metadata.
     *
     * @var array
     */
    public $meta;

    /**
     * Create a new metadata instance.
     *
     * @param  array  $meta
     * @return void
     */
    public function __construct(array $meta)
    {
        $this->meta = $meta;
    }

    /**
     * Create a new metadata instance.
     *
     * @param  array  $meta
     * @return static
     */
    public static function from(array $meta)
    {
        return new static($meta);
    }

    /**
     * Prepare the metadata.
     *
     * @return array
     */
    public function prepare()
    {
        return $this->meta;
    }
}
