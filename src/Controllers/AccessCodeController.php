<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\RedirectResponse;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Requests\AccessCodeClaimRequest;
use Railroad\Ecommerce\Services\AccessCodeService;
use Throwable;

class AccessCodeController extends BaseController
{
    /**
     * @var AccessCodeRepository
     */
    private $accessCodeRepository;

    /**
     * @var AccessCodeService
     */
    private $accessCodeService;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

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
     * @param AccessCodeRepository $accessCodeRepository
     * @param AccessCodeService $accessCodeService
     * @param EcommerceEntityManager $entityManager
     * @param Hasher $hasher
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AccessCodeRepository $accessCodeRepository,
        AccessCodeService $accessCodeService,
        EcommerceEntityManager $entityManager,
        Hasher $hasher,
        UserProviderInterface $userProvider
    ) {
        parent::__construct();

        $this->accessCodeRepository = $accessCodeRepository;
        $this->accessCodeService = $accessCodeService;
        $this->entityManager = $entityManager;
        $this->hasher = $hasher;
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
            // add new user

            $password = $this->hasher->make($request->get('password'));

            /**
             * @var $user User
             */
            $user = $this->userProvider
                        ->createUser($request->get('email'), $password);

            auth()->loginUsingId($user->getId(), true);

        } else {

            /**
             * @var $user User
             */
            $user = $this->userProvider->getCurrentUser();
        }

        $accessCode = $this->accessCodeRepository
                        ->findOneBy(['code' => $request->get('access_code')]);

        $this->accessCodeService->claim($accessCode, $user);

        $message = ['success' => true];

        return $request->has('redirect') ?
            redirect()->away($request->get('redirect'))->with($message) :
            redirect()->back()->with($message);
    }
}
