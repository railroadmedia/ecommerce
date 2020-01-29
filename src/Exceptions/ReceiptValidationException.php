<?php

namespace Railroad\Ecommerce\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use ReceiptValidator\GooglePlay\SubscriptionResponse;
use ReceiptValidator\iTunes\ResponseInterface;

class ReceiptValidationException extends Exception
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var SubscriptionResponse|null
     */
    protected $googleSubscriptionResponse;

    /**
     * @var ResponseInterface|null
     */
    private $appleResponse;

    /**
     * NotFoundException constructor.
     *
     * @param string $message
     * @param SubscriptionResponse|null $googleSubscriptionResponse
     * @param ResponseInterface|null $appleResponse
     */
    public function __construct(
        $message,
        SubscriptionResponse $googleSubscriptionResponse = null,
        ResponseInterface $appleResponse = null
    ) {
        parent::__construct($message);

        $this->message = $message;
        $this->googleSubscriptionResponse = $googleSubscriptionResponse;
        $this->appleResponse = $appleResponse;
    }

    /**
     * @return JsonResponse
     */
    public function render()
    {
        return response()->json(
            [
                'errors' => [
                    [
                        'title' => 'Receipt validation failed.',
                        'detail' => $this->message,
                    ]
                ],
            ],
            404
        );
    }

    /**
     * @return SubscriptionResponse|null
     */
    public function getGoogleSubscriptionResponse(): ?SubscriptionResponse
    {
        return $this->googleSubscriptionResponse;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getAppleResponse(): ?ResponseInterface
    {
        return $this->appleResponse;
    }
}
