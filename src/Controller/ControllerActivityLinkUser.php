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
                /**
                 * This method is used on the student profil
                 */
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "getStudentDataNotRetrievedNotAuthenticated"];

                // bind and sanitize data
                $userId = intval($_SESSION['id']);

                $arrayData = array();
                $arrayData['todoActivities'] = $this->entityManager
                    ->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getTodoActivitiesCount($userId);

                $arrayData['doneActivities'] = $this->entityManager
                    ->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getDoneActivitiesCount($userId);

                $arrayData['todoCourses'] = $this->entityManager
                    ->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getTodoCoursesCount($userId);

                $arrayData['doneCourses'] = $this->entityManager
                    ->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getDoneCoursesCount($userId);

                return $arrayData;
            },
            'get_teacher_data' => function () {
                /**
                 * This method is used on the teacher profil
                 */
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "getTeacherDataNotRetrievedNotAuthenticated"];

                // bind and sanitize data
                $userId = intval($_SESSION['id']);

                $arrayData = array();
                $arrayData['ownedActivities'] = $this->entityManager
                    ->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->getOwnedActivitiesCount($userId);

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
                        $linkActivityToUser = new ActivityLinkUser($activity, $user, new \DateTime($data['dateBegin']),  new \DateTime($data['dateEnd']), $data['evaluation'], $data['autocorrection'], null, $data['introduction'], $reference);
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
                if (isset($data['project']) && $data['project'] != null) {
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
            "remove_by_reference" => function () {
                /**
                 * This method is used by the teacher inside a classroom
                 * to remove an activity by reference
                 * when clicking on the activity cog => remove attribution
                 */
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "removeByReferenceNotRetrievedNotAuthenticated"];

                $reference = !empty($_POST['reference']) ? intval($_POST['reference']) : 0;
                if(empty($reference)) return array('errorType' => 'activityRefenceInvalid');

                // get all records in classroom_activities_link_classroom with the reference provided
                $classroomActivitiesRetroAttributed = $this->entityManager
                    ->getRepository(ActivityLinkClassroom::class)
                    ->findBy(array(
                        'reference' => $reference
                    ));
                
                // some records found, delete them
                if($classroomActivitiesRetroAttributed){
                    foreach($classroomActivitiesRetroAttributed as $classroomActivityRetroAttributed){
                        $this->entityManager->remove($classroomActivityRetroAttributed);
                    }
                    $this->entityManager->flush();
                }
                

                // get the sudents activities
                $classroomStudentsActivities = $this->entityManager
                    ->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->findBy(array("reference" => $reference));

                // no activity found, return an error
                if(!$classroomStudentsActivities) return array('errorType' => 'activityRefenceInvalid');

                // delete each activity
                foreach ($classroomStudentsActivities as $classroomStudentsActivity) {
                    $this->entityManager->remove($classroomStudentsActivity);
                }
                $this->entityManager->flush();
                return true;
            }
        );
    }
}
