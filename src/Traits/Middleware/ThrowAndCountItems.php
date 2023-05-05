<?php

namespace Sammyjo20\SaloonPagination\Traits\Middleware;

use Closure;
use Saloon\Contracts\Response;
use Saloon\Contracts\ResponseMiddleware;
use Sammyjo20\SaloonPagination\Paginators\Paginator;

class ThrowAndCountItems implements ResponseMiddleware
{
    /**
     * Constructor
     *
     * @param Paginator $paginator
     * @param Closure $getPageItems
     */
    public function __construct(protected Paginator $paginator, protected Closure $getPageItems)
    {
        //
    }

    /**
     * Register a response middleware
     *
     * @param Response $response
     * @return void
     */
    public function __invoke(Response $response): void
    {
        // This middleware will do two things. Firstly, it will force any requests to throw an exception
        // if the request fails. This will prevent the rest of our paginator to keep on iterating if
        // something goes wrong. Secondly, we will increment the total results which can be used to
        // check if we are at the end of a page.

        $response->throw();

        $pageItems = call_user_func($this->getPageItems, $response);

        $this->paginator->setTotalResults(
            $this->paginator->getTotalResults() + count($pageItems),
        );
    }
}
