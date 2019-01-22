<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\RedirectResponse;
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
     * AccessCodeController constructor.
     *
     * @param AccessCodeService $accessCodeService
     * @param EntityManager $entityManager
     * @param Hasher $hasher
     */
    public function __construct(
        AccessCodeService $accessCodeService,
        EntityManager $entityManager,
        Hasher $hasher
    ) {
        parent::__construct();

        $this->accessCodeService = $accessCodeService;
        $this->entityManager = $entityManager;
        $this->hasher = $hasher;
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

            $user = new User();

            $user
                ->setEmail($request->get('email'))
                ->setPassword($password)
                ->setDisplayName($request->get('email'))
                ->setCreatedAt(Carbon::now());

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            auth()->loginUsingId($user->getId(), true);

        } else {

            $userRepository = $this->entityManager->getRepository(User::class);

            $user = $userRepository->find(auth()->id());
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
