<?php

namespace Classroom\Controller;

use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\ActivityLinkClassroom;
use User\Entity\User;

class ControllerActivityLinkUser extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_student_data' => function () {
                $arrayData = array();
                $arrayData['todoActivities'] = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getTodoActivitiesCount($this->user['id']);
                $arrayData['doneActivities'] = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getDoneActivitiesCount($this->user['id']);
                $arrayData['todoCourses'] = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getTodoCoursesCount($this->user['id']);
                $arrayData['doneCourses'] = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getDoneCoursesCount($this->user['id']);
                return $arrayData;
            },
            'get_teacher_data' => function () {
                $arrayData = array();
                $arrayData['ownedActivities'] = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getOwnedActivitiesCount($this->user['id']);
                return $arrayData;
            },
            'get_student_activities' => function () {
                $arrayData = array();
                $arrayData['newActivities'] = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getNewActivities($this->user['id']);
                $arrayData['currentActivities'] = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getCurrentActivities($this->user['id']);
                $arrayData['doneActivities'] = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getDoneActivities($this->user['id']);
                $arrayData['savedActivities'] = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getSavedActivities($this->user['id']);
                return $arrayData;
            },
            'add_users' => function ($data) {
                $reference = strval(time());
                $activity = $this->entityManager->getRepository('Learn\Entity\Activity')
                    ->find($data['activity']['id']);
                foreach ($data['students'] as $u) {
                    $user = $this->entityManager->getRepository('User\Entity\User')
                        ->findOneBy(array("id" => $u));
                    if ($user) {
                        $linkActivityToUser = new ActivityLinkUser($activity, $user, new \DateTime($data['dateBegin']),  new \DateTime($data['dateEnd']), $data['evaluation'], $data['autocorrection'], $data['introduction'], $reference);
                        $this->entityManager->persist($linkActivityToUser);
                    }
                }
                foreach ($data['classrooms'] as $c) {
                    $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                        ->findOneBy(array("id" => $c));
                    if ($classroom) {
                        $linkActivityToClassroom = new ActivityLinkClassroom($activity, $classroom, new \DateTime($data['dateBegin']),  new \DateTime($data['dateEnd']), $data['evaluation'], $data['autocorrection'], $data['introduction'], $reference);
                        $this->entityManager->persist($linkActivityToClassroom);
                    }
                }
                $this->entityManager->flush();
                return $linkActivityToClassroom; //synchronized

            },
            "update" => function ($data) {
                $activity = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->findOneBy(array("id" => $data['id']));
                $activity->setCorrection(intval($data['correction']));
                $activity->setNote(intval($data['note']));
                if (isset($data['commentary'])) {
                    $activity->setCommentary($data['commentary']);
                }
                if (isset($data['project'])) {
                    $project = $this->entityManager->getRepository('Interfaces\Entity\Project')
                        ->findOneBy(array("id" => $data['project']));
                    $activity->setProject($project);
                    $activity->setTries($activity->getTries() + 1);
                    $activity->setDateSend(new \DateTime());
                    $activity->setTimePassed(intval($activity->getTimePassed()) + intval($data['timePassed']));
                }

                /*  $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findOneBy(array("link" => $data['classroomLink']));
                 $classroom->setIsChanged(true); 
                $this->entityManager->persist($classroom); */
                $this->entityManager->persist($activity);
                $this->entityManager->flush();

                return  $activity;
            },
            "get_one" => function ($data) {
                return $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->findOneBy(array("id" => $data['id']));
            },
            "remove_by_reference" => function ($data) {
                $referenceArray = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->findBy(array("reference" => $data['reference']));
                foreach ($referenceArray as $r) {
                    $this->entityManager->remove($r);
                }
                $this->entityManager->flush();
                return true;
            }
        );
    }
}
