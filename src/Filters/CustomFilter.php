<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Filters;

use JimmyAhalpara\LaravelDatatable\Contracts\FilterInterface;

class CustomFilter implements FilterInterface
{
    /** @var callable */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public static function make(callable $callable): self
    {
        return new self($callable);
    }

    /**
     * Apply the custom filter using the provided callable.
     */
    public function apply($builder, $data): void
    {
        ($this->callable)($builder, $data);
    }

    /**
     * Get the callable function.
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }
}