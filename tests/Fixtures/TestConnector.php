<?php

declare(strict_types=1);

namespace Sammyjo20\SaloonPagination\Tests\Fixtures;

use Saloon\Http\Connector;
use Saloon\Contracts\Request;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Sammyjo20\SaloonPagination\TestPagedPaginator;
use Sammyjo20\SaloonPagination\Paginators\PagedPaginator;

class TestConnector extends Connector
{
    use AlwaysThrowOnErrors;

    /**
     * Define the base URL of the API.
     */
    public function resolveBaseUrl(): string
    {
        return 'https://tests.saloon.dev/api';
    }

    public function paginate(Request $request): PagedPaginator
    {
        return new TestPagedPaginator($this, $request);
    }
}
