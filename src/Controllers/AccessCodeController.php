<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\RedirectResponse;
use Railroad\Ecommerce\Providers\UserProviderInterface;
use Railroad\Ecommerce\Requests\AccessCodeClaimRequest;
use Railroad\Ecommerce\Services\AccessCodeService;
use Throwable;

class AccessCodeController extends BaseController
{
    /**
     * @var AccessCodeService
     */
    private $accessCodeService;

     /**
     * @var Hasher
     */
    private $hasher;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * AccessCodeController constructor.
     *
     * @param AccessCodeService $accessCodeService
     * @param Hasher $hasher
     */
    public function __construct(
        AccessCodeService $accessCodeService,
        Hasher $hasher
    ) {
        parent::__construct();

        $this->accessCodeService = $accessCodeService;
        $this->hasher = $hasher;
        $this->userProvider = app()->make(UserProviderInterface::class);
    }

    /**
     * Claim an access code
     *
     * @param AccessCodeClaimRequest $request
     *
     * @return RedirectResponse
     *
     * @throws Throwable
     */
    public function claim(AccessCodeClaimRequest $request)
    {
        $userId = auth()->id() ?? null;

        if ($request->has('email')) {
            // add new user

            $password = $this->hasher->make($request->get('password'));

            $userId = $this->userProvider->create(
                $request->get('email'),
                $password,
                $request->get('email')
            );

            auth()->loginUsingId($userId, true);

        }

        $accessCode = $this->accessCodeService
            ->claim($request->get('access_code'), $userId);

        return reply()->form(
            [true],
            null,
            [],
            ['access_code' => true]
        );
    }
}
