<?php

declare(strict_types=1);

namespace Sammyjo20\SaloonPagination\Tests\Fixtures;

use Saloon\Contracts\PendingRequest;
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
     *
     * @return string
     */
    public function resolveBaseUrl(): string
    {
        return 'https://tests.saloon.dev/api';
    }

    public function paginate(Request $request): PagedPaginator
    {
        return new TestPagedPaginator($this, $request);
    }

    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->middleware()->onRequest(function () {
            ray('hi');
        });
    }

    /**
     * Todo: This is to serialize the paginator
     *
     * @return array
     */
    public function __sleep(): array
    {
        $ignore = ['sender', 'middlewarePipeline'];

        return array_filter(
            array_keys(get_object_vars($this)),
            static fn(string $item) => !in_array($item, $ignore, true)
        );
    }
}
