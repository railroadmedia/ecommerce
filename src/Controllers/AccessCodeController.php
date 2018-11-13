<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Requests\AccessCodeClaimRequest;
use Railroad\Ecommerce\Services\AccessCodeService;
use Railroad\Usora\Repositories\UserRepository;
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
     * @var UserRepository
     */
    private $userRepository;

    /**
     * AccessCodeController constructor.
     *
     * @param AccessCodeService $accessCodeService
     * @param Hasher $hasher
     * @param UserRepository $userRepository
     */
    public function __construct(
        AccessCodeService $accessCodeService,
        Hasher $hasher,
        UserRepository $userRepository
    ) {
        parent::__construct();

        $this->accessCodeService = $accessCodeService;
        $this->hasher = $hasher;
        $this->userRepository = $userRepository;
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
        $user = auth()->user() ?? null;

        if ($request->has('email')) {
            // add new user

            $password = $this->hasher->make($request->get('password'));

            $user = $this->userRepository->create(
                [
                    'email' => $request->get('email'),
                    'password' => $password,
                    'display_name' => $request->get('email'),
                    'created_at' => Carbon::now()->toDateTimeString()
                ]
            );

            auth()->loginUsingId($user['id'], true);

        }

        $accessCode = $this->accessCodeService
            ->claim($request->get('access_code'), $user);

        return reply()->form(
            [true],
            null,
            [],
            ['access_code' => true]
        );
    }
}
