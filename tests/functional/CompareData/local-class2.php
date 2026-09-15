<?php

declare(strict_types=1);

class QueryBuilder
{
    public function where(string $condition): static
    {
        return $this;
    }

    public function build(): PDOStatement
    {
        // ...
    }
}

(new QueryBuilder())->where('x = 1')->build()->fetchAll();