<?php
/**
 * Created by PhpStorm.
 * User: Denys
 * Date: 09.12.2017
 * Time: 17:46
 */

namespace WorkerBundle\Controller;


use Doctrine\Common\Util\Debug;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use WorkerBundle\Entity\Absence;
use WorkerBundle\Entity\Position;
use WorkerBundle\Entity\Worker;
use WorkerBundle\Forms\AbsenceForm;
use WorkerBundle\Forms\DetailsModelForm;
use WorkerBundle\Forms\Models\AbsenceModel;
use WorkerBundle\Forms\Models\DetailsModel;
use WorkerBundle\Forms\Models\PositionModel;
use WorkerBundle\Forms\Models\WorkerEditModel;
use WorkerBundle\Forms\PositionForm;
use WorkerBundle\Forms\WorkerEditForm;

class WorkerController extends Controller
{
    /**
     * страница со списком всех сотрудников
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function usersAction(Request $request)
    {//проверяем залогинен ли
        $securityContext = $this->container->get('security.authorization_checker');
        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $em = $this->getDoctrine()->getManager();
            $usersList = $em->getRepository('WorkerBundle:Worker')->findAll(); // все сотрудники
            /**
             * @var $paginator \Knp\Component\Pager\Paginator
             */
            $paginator = $this->get('knp_paginator'); // пагинация
            $result =  $paginator->paginate(
                $usersList,
                $request->query->getInt('page', 1),
                $request->query->getInt('limit', 5)
            );

            return $this->render('@Worker/list.html.twig', [
                'users' => $result
            ]);
        } else {
            return $this->render('@Worker/access_deny.html.twig');
        }

    }

    /**страница добавления сотрудника
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function createAction(Request $request)
    {
        $workerModel = new WorkerEditModel(); // создаем модель
        $worker_form = $this->createForm(WorkerEditForm::class, $workerModel); // создаем форму и ассоциируем с моделью
        $worker_form->handleRequest($request); // обрабатываем запрос
        if ($worker_form->isSubmitted() && $worker_form->isValid()) { // добавляем в БД
            $worker = $workerModel->getWorkerHandler();
            $em = $this->getDoctrine()->getManager();
            $em->persist($worker);
            $em->flush();
            return $this->redirectToRoute('users');
        }

        $validator = $this->get('validator');
        $errors = $validator->validate($worker_form);
        return $this->render('@Worker/worker.html.twig',
            [
                'form' => $worker_form->createView(),
                'btnL' => 'Добавить',
                'errors' => $errors
            ]);
    }

    public function editAction(Request $request, Worker $worker)
    {
        $workerModel = new WorkerEditModel();
        if ($worker != null)
            $workerModel->createModelFromWorker($worker);
        $worker_form = $this->createForm(WorkerEditForm::class, $workerModel);
        $worker_form->handleRequest($request);
        if ($worker_form->isSubmitted() && $worker_form->isValid()) {
            $worker = $workerModel->changeWorker($worker);
            $em = $this->getDoctrine()->getManager();
            $em->persist($worker);
            $em->flush();
            return $this->redirectToRoute('details', [
                'id' => $worker->getId(),
            ]);
        }
        $validator = $this->get('validator');
        $errors = $validator->validate($worker_form);
        return $this->render('@Worker/worker.html.twig',
            [
                'form' => $worker_form->createView(),
                'errors' => $errors
            ]);
    }

    /**
     * удаление пользователя
     * @param Request $request
     * @param Worker $worker
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $id)
    {
        $worker = $this->getDoctrine()->getManager()->getRepository(Worker::class)->find($id);
        //если есть пользователь, то удаляем его из БД
        if ($worker != null) {
            $em = $this->getDoctrine()->getManager();
            $absences = $worker->getAbsences();
            foreach ($absences as $absence) {
                $em->remove($absence);
                $em->flush();
            }
            $em->remove($worker);
            $em->flush();
            return $this->redirectToRoute('users');
        }
    }

    /**
     * страница просмотра инфо о пользователе
     * @param Request $request
     * @param Worker $worker
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detailsAction(Request $request, Worker $worker)
    {
        $datemodel = new DetailsModel(); // создаем модель, форму, ассоциируем с ней работника, обрабатываем запрос
        $datemodel->setWorker($worker);
        $dateForm = $this->createForm(DetailsModelForm::class, $datemodel);
        $dateForm->handleRequest($request);
        $salary = $datemodel->calculateAbsence();
        return $this->render('@Worker/worker_details.html.twig', [
            'worker' => $worker,
            'form' => $dateForm->createView(),
            'salary' => $salary,
        ]);
    }

    /**
     * страница пропусков
     * @param Request $request
     * @param Worker $worker
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function absenceAction(Request $request, Worker $worker)
    {
        $absenceModel = new AbsenceModel($worker); // модель + форма + ассоциация + пагинация + добавление в БД, если
        $wAbsences = $worker->getAbsences();
        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator = $this->get('knp_paginator');
        $result =  $paginator->paginate(
            $wAbsences,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', 10)
        );
        $alert = null;
        $absenceForm = $this->createForm(AbsenceForm::class, $absenceModel); // расчет пропусков - от включая до - не включая
        $absenceForm->handleRequest($request);
        if ($absenceForm->isSubmitted()) { // добавление пропусков
            $absences = $absenceModel->getAbsenceHandler(); // получаем список пропусков

            if(sizeof($absences) != 0)
                $alert = 'Пропуски добавлены!';
            $em = $this->getDoctrine()->getManager();
            foreach ($absences as $absence) {
                $em->persist($absence);
            }
            $em->flush();
            return $this->redirectToRoute('absence', [
                'id' => $worker->getId(),
                'alert' => $alert
            ]);

        }
        return $this->render('@Worker/absence.html.twig', [
            'worker' => $worker,
            'form' => $absenceForm->createView(),
            'absences' => $result,
            'alert' => $alert
        ]);
    }

    /**список должностей и добавление
     * @param Request $request
     */
    public function positionsListAction(Request $request)
    {
        $positionModel = new PositionModel(); // модель + форма + ассоциация +  добавление в БД
        $em = $this->getDoctrine()->getManager();
        $alert = null;
        $positionForm = $this->createForm(PositionForm::class, $positionModel);
        $positionForm->handleRequest($request);
        $positions = $em->getRepository(Position::class)->findAll();
        if ($positionForm->isSubmitted()) { // добавление должности
            $position = $positionModel->getPositionHandler(); // получаем должность

            $positionExist = false;
            foreach ($positions as $pos) {
                /** @var Position $pos */
                $str1 = mb_strtoupper($position->getPositionName());
                $str2 = mb_strtoupper($pos->getPositionName());
                if($str1 === $str2)
                {
                    $positionExist = true;
                    break;
                }
            }
            if(!$positionExist) {
                $alert = 'Должность добавлена!';

                $em->persist($position);
                $em->flush();
            }
            else{
                $alert = 'Должность есть уже в списке';
            }

            return $this->redirectToRoute('positionlist', ['alert' => $alert, 'exist' => $positionExist]);

        }

        return $this->render('@Worker/positionsList.html.twig', [
            'form' => $positionForm->createView(),
            'positions' => $positions,
            'alert' => $alert
        ]);
    }

    /**удаление пропуска
     * @param Request $request
     * @param $idWorker
     * @param $idAbsence
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAbsenceAction(Request $request, $idWorker, $idAbsence)
    {
        var_dump($idWorker);
        var_dump($idAbsence);
        $worker = $this->getDoctrine()->getRepository(Worker::class)->find($idWorker);
        $absence = $this->getDoctrine()->getRepository(Absence::class)->find($idAbsence);
        if ($worker != null && $absence != null)
        {
            $em = $this->getDoctrine()->getManager();
            $worker->removeAbsence($absence);
            $em->remove($absence);
            $em->flush();
            return $this->redirectToRoute('absence', ['id' => $worker->getId()]);

        }
    }
}