<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Requests\AccessCodeClaimRequest;
use Railroad\Ecommerce\Services\AccessCodeService;
use Throwable;

class AccessCodeController extends Controller
{
    /**
     * @var AccessCodeService
     */
    private $accessCodeService;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * AccessCodeController constructor.
     *
     * @param AccessCodeService $accessCodeService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AccessCodeService $accessCodeService,
        UserProviderInterface $userProvider
    )
    {
        $this->accessCodeService = $accessCodeService;
        $this->userProvider = $userProvider;
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
        $user = null;

        if ($request->has('email')) {
            // create new user
            $user = $this->userProvider->createUser($request->get('email'), $request->get('password'));

            auth()->loginUsingId($user->getId(), true);
        }
        else {
            // use existing user
            $user = $this->userProvider->getCurrentUser();
        }

        $this->accessCodeService->claim($request->get('access_code'), $user);

        $message = ['success' => true];

        return $request->has('redirect') ?
            redirect()
                ->away($request->get('redirect'))
                ->with($message) :
            redirect()
                ->back()
                ->with($message);
    }
}
