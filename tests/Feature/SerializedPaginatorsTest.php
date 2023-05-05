<?php

use Sammyjo20\SaloonPagination\TestPagedPaginator;
use Sammyjo20\SaloonPagination\Tests\Fixtures\SuperheroPagedRequest;
use Sammyjo20\SaloonPagination\Tests\Fixtures\TestConnector;

test('you can serialize a paginator half way through and start it where it left off', function () {
    $connector = new TestConnector();
    $request = new SuperheroPagedRequest();
    $paginator = new TestPagedPaginator($connector, $request);

    $superheroes = [];
    $serialized = null;

    // Okay, so I've had to add a __sleep() method to the paginator as well as the TestConnector
    // I think I should write a serialization test in Saloon with Guzzle and see if it works.
    // I believe what we'll need to do is not serialize the sender with __sleep() like I am
    // doing. I believe our middleware etc should be fine.

    $iteratorCounter = 0;

    foreach ($paginator as $index => $response) {
        $iteratorCounter++;

        $superheroes = array_merge($superheroes, $response->json('data'));

        if ($index === 2) {
            $serialized = serialize($paginator);
            break;
        }
    }

    expect($superheroes)->toHaveCount(15);

    // Todo: It seems like PHP is rewinding at the start of every loop. We need to make sure
    // that we start from where we left off.

    foreach (unserialize($serialized) as $index => $response) {
        $iteratorCounter++;

        $superheroes = array_merge($superheroes, $response->json('data'));
    }

    expect($iteratorCounter)->toEqual(4);

    expect($paginator->getTotalResults())->toEqual(20);

    $mapped = array_map(static fn(array $superhero) => $superhero['id'], $superheroes);

    expect($mapped)->toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20,]);
})->skip();
