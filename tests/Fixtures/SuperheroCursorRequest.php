<?php

declare(strict_types=1);

namespace Sammyjo20\SaloonPagination\Tests\Fixtures;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class SuperheroCursorRequest extends Request
{
    protected Method $method = Method::GET;

    /**
     * Define the endpoint for the request.
     */
    public function resolveEndpoint(): string
    {
        return '/superheroes/cursor';
    }
}
