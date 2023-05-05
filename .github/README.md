## Todo

- [x] Asynchronous Pagination
- [ ] Per-page logic
- [ ] Tests for the middleware (throw and total)
- [ ] Tests for exception handlers on pools & async requests
- [ ] Loop protection (if subsequent request is exactly the same)
- [ ] Tests for checking exact iteration count 
- [ ] Tests for `items()` method
- [ ] Test for `collect()` method without items
- [ ] Mocking/Fixture recording for paginators
- [ ] Iterating through items with async
- [ ] Consider adding default implementations of all types of paginator
- [ ] Test being able to use synchronous pagination with an async paginator (async = false)
- [ ] Test logic exception if trying to use non-async pagination on an asynchronous paginator
- [ ] Method to apply additional logic to the request before it is sent, like a delay
- [ ] Serializing paginators

## Docs

Welcome to Saloon's new pagination. This repo is just a nice place for me to build and test out how I would like the 
new pagination to work in Saloon v3.

### Summary of changes
- Paginators are now class based, so you define everything inside the paginator class
- You now need to define `getPageItems` which means you don't need to provide a key to the `items()` or `collect()` method
- Saloon will now throw exceptions if a paginated request fails, even if people don't add `AlwaysThrowOnErrors` trait
- The `json()` method has been renamed to `items()`
- Asynchronous support is not added by default but can be implemented by a trait
- Inside every paginator, you'll be able to access `$this->page` as well as `$this->totalItems` which is counted automatically this is useful

## Synchronous Pagination

I will be using Saloon's super-hero pages as examples for each of the different major pagination types, which are paged, offset and cursor.

### Paged Pagination

To create a paged paginator, create a class near your connector and extend the `PagedPaginator` abstract class.

```php
use Sammyjo20\SaloonPagination\Paginators\PagedPaginator;

class SuperheroPaginator extends PagedPaginator
{
    //
}
```

Next, you will be required to implement two methods: `isLastPage` and `getPageItems`. These methods will determine if the paginator
should get the next page and the array of items in each response respectively.

```php
use Sammyjo20\SaloonPagination\Paginators\PagedPaginator;
use Saloon\Contracts\Response;

class SuperheroPaginator extends PagedPaginator
{
    protected function isLastPage(Response $response): bool
    {
        return empty($response->json('next_page_url'));
    }
    
    protected function getPageItems(Response $response): array
    {
        return $response->json('data') ?? [];
    }
}
```

When using the paginator, you just need to pass in a connector and a request.

```php
$paginator = new SuperheroPaginator($connector, $request);
```

### Offset Pagination

To create an offset paginator, create a class near your connector and extend the `OffsetPaginator` abstract class.

```php
use Sammyjo20\SaloonPagination\Paginators\OffsetPaginator;

class SuperheroPaginator extends OffsetPaginator
{
    //
}
```

Next, you will be required to implement two methods: `isLastPage` and `getPageItems`. These methods will determine if the paginator
should get the next page and the array of items in each response respectively.

```php
use Sammyjo20\SaloonPagination\Paginators\OffsetPaginator;
use Saloon\Contracts\Response;

class SuperheroPaginator extends OffsetPaginator
{
    protected function isLastPage(Response $response): bool
    {
        return (int)$response->json('total') === $this->getOffset();
    }

    protected function getPageItems(Response $response): array
    {
        return $response->json('data') ?? [];
    }
}
```

When using the paginator, you will need to provide an additional argument to define the "per-page limit" of the paginator
this is so Saloon can calculate the limit/offset accordingly.

```php
$paginator = new SuperheroPaginator($connector, $request, perPageLimit: 100);
```

### Cursor Paginator

To create a cursor paginator, create a class near your connector and extend the `CursorPaginator` abstract class.

```php
use Sammyjo20\SaloonPagination\Paginators\CursorPaginator;

class SuperheroPaginator extends CursorPaginator
{
    //
}
```

Next, you will be required to implement three methods: `isLastPage` and `getPageItems` and `getNextCursor`. These methods will determine if the paginator
should get the next page, the array of items in each response and the next cursor respectively.

```php
use Sammyjo20\SaloonPagination\Paginators\CursorPaginator;
use Saloon\Contracts\Response;

class SuperheroPaginator extends CursorPaginator
{
    protected function getNextCursor(Response $response): int|string
    {
        $nextPageUrl = $response->json('next_page_url');
        parse_str(parse_url($nextPageUrl, PHP_URL_QUERY), $queryParams);

        return $queryParams['cursor'];
    }

    protected function isLastPage(Response $response): bool
    {
        return empty($response->json('next_page_url'));
    }

    protected function getPageItems(Response $response): array
    {
        return $response->json('data');
    }
}
```

When using the paginator, you just need to pass in a connector and a request.

```php
$paginator = new SuperheroPaginator($connector, $request);
```

## Asynchronous Pagination

Asynchronous pagination is not provided by default, however adding it is a breeze. The only requirement to use async 
pagination is that you are able to calculate the **total number of pages** on the fist API response. This is because
the paginator does not know when the last response is provided.

First, add the `HasAsyncPagination` trait to your paginator.

```php
use Sammyjo20\SaloonPagination\Traits\HasAsyncPagination;
use Sammyjo20\SaloonPagination\Paginators\PagedPaginator;
use Saloon\Contracts\Response;

class SuperheroPaginator extends PagedPaginator
{
    use HasAsyncPagination;

    protected function isLastPage(Response $response): bool
    {
        return empty($response->json('next_page_url'));
    }
    
    protected function getPageItems(Response $response): array
    {
        return $response->json('data') ?? [];
    }
}
```

Next, you will be required to define a method `getTotalPages`. This method requires you to return the total
number of pages that Saloon needs to iterate over.

```php
class SuperheroPaginator extends PagedPaginator
{
    use HasAsyncPagination;

    protected function getPageItems(Response $response): array
    {
        return $response->json('data') ?? [];
    }

    protected function getTotalPages(Response $response): int
    {
        return $response->json('to');
    }
}
```

> **Note**
> You don't need to define the `isLastPage` when using the trait unless you're using synchronous pagination too.

Now, when using the paginator, make sure to set it in asynchronous mode. When you iterate over each item, you will retrieve a `PromiseInterface` instance and not a response.

```php
$paginator = new SuperheroPaginator($connector, $request);
$paginator->async();
```

## Using Paginators

You can use Saloon's paginators in many different ways. You can use it in a for-loop to get each response but you
can also use the `items()` method to iterate over each item, or even better - use Laravel's collections to map,
filter and change the collection of items.

### Iterating Over

```php
$paginator = new SuperheroPaginator($connector, $request);

foreach($paginator as $response) {
    // $response->json(...)
}
```

### Items Method
The items method will return each item instead of a response, saving you from traversing through multiple arrays

```php
$paginator = new SuperheroPaginator($connector, $request);

foreach($paginator->items() as $superhero) {
    // $superhero['name'] -> Batman
}
```

### Collect Method
The collect method requires `illuminate/collections` to be installed, but allows you to iterate through your 
items in a `LazyCollection`. You can specify the `throughItems` argument as `false` if you would like a 
collection of responses instead.

```php
$paginator = new SuperheroPaginator($connector, $request);
$collection = $paginator->collect();

// Or collection of responses

$collection = $paginator->collect(throughItems: false);
```

### Pools
Pools require your paginator to have the `HasAsyncPagination` trait, but they work just like typical Saloon pools

```php
$paginator = new SuperheroPaginator($connector, $request);

$paginator->pool(
    concurrentRequests: 5,
    responseHandler: fn () ...
    exceptionHandler: fn () ...
)->send();
```

## Customisation

Often times you will also need to configure the query parameters that Saloon sets to apply the pagination. On each
of the paginators, you may overwrite the `applyPagination` method and use any query parameter you prefer.

```php
class SuperheroPaginator extends PagedPaginator
{
    protected function applyPagination(Request $request): Request
    {
        $request->query()->add('currentPage', $this->page);

        return $request;
    }
}
```

## Your own paginators 👀

With Saloon's base paginator class you may create your own paginator based on the type of API that you are dealing
with. This is especially useful if the pagination you are working with isn't paged, offset or cursor pagination.

Just create a class and extend the base `Paginator` class. You will be required to define the following methods:

- `applyPagination`
- `isLastPage`
- `getPageItems`

```php
class CustomPaginator extends Paginator
{
    protected function applyPagination(Request $request): Request
    {
        //
    }

    protected function isLastPage(Response $response): bool
    {
        //
    }

    protected function getPageItems(Response $response): array
    {
        //
    }
}
```
