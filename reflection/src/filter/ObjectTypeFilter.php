<?php

namespace kuiper\reflection\filter;

use kuiper\reflection\TypeFilterInterface;

class ObjectTypeFilter implements TypeFilterInterface
{
    /**
     * checks whether the value is valid.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function validate($value)
    {
        return is_object($value);
    }

    /**
     * Sanitizes input value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function sanitize($value)
    {
        return $value;
    }
}
