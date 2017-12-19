<?php
/**
 * Created by PhpStorm.
 * User: Denys
 * Date: 07.12.2017
 * Time: 19:11
 */

namespace UserBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Entity\User;
use UserBundle\Forms\Models\RegisterUserModel;
use UserBundle\Forms\RegisterUserForm;

class SecurityController extends Controller
{
    /**метод-действия для страницы login
     * @return Response
     */
    public function loginAction()
    {
        $authenticationUtils = $this->get('security.authentication_utils'); // получаем сервис аутентификации
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('@User/security/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

    /**метод-действия для страницы регистрации
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function registerAction(Request $request)
    { //задаем классы формы и соответствующую модель
        $registerModel = new RegisterUserModel();
        $register_form = $this->createForm(RegisterUserForm::class, $registerModel);
        $register_form->handleRequest($request);
        $userExist = false;
        $errors = array();
        if($register_form->isSubmitted() && $register_form->isValid()) // обрабатываем нажатие на кнопку submit и сохраняем данные в БД
        {
            $user = $registerModel->getUserHandler();
            $em = $this->getDoctrine()->getManager();
            $users = $em->getRepository(User::class)->findAll();
            foreach ($users as $u) {
                if(strtolower($u->getEmail()) == strtolower($user->getEmail()))
                {
                    $userExist = true;
                    array_push($errors, 'Пользователь с таким email уже существует!');
                    break;
                }
            }
            if(!$userExist)
            {

                $em->persist($user);
                $em->flush();
                return $this->redirectToRoute('login');
            }
        }
        return $this->render('UserBundle:security:register.html.twig',
            [
                'register_form' => $register_form->createView(),
                'errors' => $errors,
            ]);

    }
}