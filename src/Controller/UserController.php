<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use \Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
class UserController extends AbstractController
{

    private $userRepository;
    private $entityManager;
    private $dispatcher;

    /**
     * UserController constructor.
     * @param $userRepository
     */
    public function __construct(EventDispatcherInterface $dispatcher,
                                EntityManagerInterface $entityManager,
                                UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;

    }

    /**
     * @Route("/profile",name="profile")
     *@IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function index(){

        return $this->render('user/profile.html.twig');
    }

    /**
     * @Route("/user_list", name="user")
     */
    public function usersList()
    {
        $userList = $this->userRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $userList,
        ]);
    }

    /**
     * @Route("/create_user", name="form")
     */
    public function newAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userData = $form->getData();
            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);
            dump($userData);
            $this->entityManager->persist($userData);
            $this->entityManager->flush();

            $this->addFlash('success', "new user has been created");

            return $this->redirectToRoute('user');
        }

        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
        ]);

    }

    /**
     * @Route("/delete_user/{id}", name="user_delete")
     * @IsGranted("ROLE_ADMIN")
     * @ParamConverter("user", options={"mapping"={"id"="id"}})
     */
    public function delete (User $user, EntityManagerInterface $entityManager)
    {
        $entityManager->remove($user);
        $entityManager->flush();
        $this->addFlash('success', "the user has been deleted");
        
        return $this->redirectToRoute('user');
    }
}