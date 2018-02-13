<?php

namespace Railroad\Ecommerce\Responses;

use Illuminate\Contracts\Support\Responsable;


class JsonResponse implements Responsable
{
    public $results;
    public $code;

    /**
     * JsonPaginatedResponse constructor.
     *
     * @param $results
     * @param $code
     */
    public function __construct($results, $code)
    {
        $this->results = $results;
        $this->code = $code;
    }


    public function toResponse($request)
    {
        return response()
            ->json($this->transformResult())
            ->setStatusCode($this->code);
    }

    public function transformResult()
    {
        return [
            'status' => 'ok',
            'code' => $this->code,
            'results' => $this->results,
        ];
    }
}