<?php

namespace Railroad\Ecommerce\Responses;

use Illuminate\Contracts\Support\Responsable;

class JsonPaginatedResponse implements Responsable
{
    public $results;
    public $totalResults;
    public $code;

    /**
     * JsonPaginatedResponse constructor.
     *
     * @param $results
     * @param $totalResults
     * @param $code
     */
    public function __construct($results, $totalResults, $code)
    {
        $this->results = $results;
        $this->totalResults = $totalResults;
        $this->code = $code;
    }


    /**
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function toResponse($request)
    {

        return response()->json(
            $this->transformResult($request),
            $this->code
        );
    }

    public function transformResult($request)
    {
        return [
            'status' => 'ok',
            'code' => $this->code,
            'page' => $request->get('page', 1),
            'limit' => $request->get('limit', 10),
            'total_results' => $this->totalResults,
            'results' => $this->results,
        ];
    }
}