<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\RedirectResponse;
use Railroad\Ecommerce\Contracts\UserInterface;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\Requests\AccessCodeClaimRequest;
use Railroad\Ecommerce\Services\AccessCodeService;
use Railroad\Usora\Entities\User;
use Throwable;

class AccessCodeController extends BaseController
{
    /**
     * @var AccessCodeService
     */
    private $accessCodeService;

    /**
     * @var EntityManager
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
     * @param AccessCodeService $accessCodeService
     * @param EntityManager $entityManager
     * @param Hasher $hasher
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        AccessCodeService $accessCodeService,
        EntityManager $entityManager,
        Hasher $hasher,
        UserProviderInterface $userProvider
    ) {
        parent::__construct();

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
             * @var $user \Railroad\Ecommerce\Contracts\UserInterface
             */
            $user = $this->userProvider
                        ->createUser($request->get('email'), $password);

            auth()->loginUsingId($user->getId(), true);

        } else {

            /**
             * @var $user \Railroad\Ecommerce\Contracts\UserInterface
             */
            $user = $this->userProvider->getCurrentUser();
        }

        $accessCodeRepository = $this->entityManager
                                    ->getRepository(AccessCode::class);

        $accessCode = $accessCodeRepository
                        ->findOneBy(['code' => $request->get('access_code')]);

        $claimedAccessCode = $this->accessCodeService
                                    ->claim($accessCode, $user);

        $message = ['success' => true];

        return $request->has('redirect') ?
            redirect()->away($request->get('redirect'))->with($message) :
            redirect()->back()->with($message);
    }
}
