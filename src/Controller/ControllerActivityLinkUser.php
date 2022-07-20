<?php

namespace Classroom\Controller;

use User\Entity\User;
use Learn\Entity\Activity;
use Classroom\Entity\Classroom;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\ClassroomLinkUser;
use Classroom\Entity\ActivityRestrictions;
use Classroom\Entity\ActivityLinkClassroom;
use Classroom\Entity\Applications;

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
                /**
                 * This method is used on the student activity panel 
                 */
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "getStudentActivitiesNotRetrievedNotAuthenticated"];

                // bind and sanitize incoming data to check if the logged user is the teacher
                $userId = intval($_SESSION['id']);
                $arrayData = array();
               
                $arrayData['newActivities'] = $this->entityManager
                    ->getRepository(ActivityLinkUser::class)
                    ->getNewActivities($userId);
              
                $arrayData['currentActivities'] = $this->entityManager
                    ->getRepository(ActivityLinkUser::class)
                    ->getCurrentActivities($userId);

                $arrayData['doneActivities'] = $this->entityManager
                    ->getRepository(ActivityLinkUser::class)
                    ->getDoneActivities($userId);

                $arrayData['savedActivities'] = $this->entityManager
                    ->getRepository(ActivityLinkUser::class)
                    ->getSavedActivities($userId);

                // convert doctrine object to php object in order to add a custom property
                $dataToSend = json_decode(json_encode($arrayData));

                // loop through activities type
                foreach ($dataToSend as $MainActivitiesType) {
                    // loop through single activity
                    foreach ($MainActivitiesType as $activityLinkUser) {

                        // get the activity restriction by type

                        $activityRestriction = $this->entityManager
                            ->getRepository(Applications::class)
                            ->findOneBy(array(
                                'name' => $activityLinkUser->activity->type
                            ));

                        // bind isLti property to $dataToSend
                        $activityLinkUser->activity->isLti = $activityRestriction ? $activityRestriction->getIsLti() : false;
                    }
                }
                return $dataToSend;
            },
            'add_users' => function () {
                /**
                 * This method is used on the teacher activity panel 
                 * to attribute an activity for the first time by clicking on the activity cog => attribute
                 * Or to update the activity attribution inside a classroom when clicking on the activity cog => modify attribution
                 */
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "addUsersNotRetrievedNotAuthenticated"];

                // bind and sanitize incoming data to check if the logged user is the teacher
                $userId = intval($_SESSION['id']);

                $incomingClassroomsId = $_POST['classrooms'];
                $classroomIds = [];
                foreach ($incomingClassroomsId as $incomingClassroomId) {
                    if (intval($incomingClassroomId) == 0) continue;
                    array_push($classroomIds, $incomingClassroomId);
                }

                // get the current logged user and initiate an error flag to be false at the start
                $loggedUser = $this->entityManager->getRepository(User::class)->find($userId);
                $notTeacherErrorFlag = false;
                foreach ($classroomIds as $classroomId) {
                    // get the classroom or return an error if the classroom is not found
                    $classroom = $this->entityManager->getRepository(Classroom::class)->find($classroomId);
                    if (!$classroom) return array('errorType' => 'classroomNotExists');

                    // get the classroom teacher using the $loggedUser id
                    $teacher = $this->entityManager
                        ->getRepository(ClassroomLinkUser::class)
                        ->findOneBy(array(
                            'user' => $loggedUser->getId(),
                            'classroom' => $classroom->getId(),
                            'rights' => 2
                        ));

                    // the logged user is not the teacher, set error flag to true and exit the loop
                    if (!$teacher) {
                        $notTeacherErrorFlag = true;
                        break;
                    }
                }
                if ($notTeacherErrorFlag == true) return array("errorType" => "notTeacherErrorFlagTrue");

                // bind and sanitize the students id array
                $incomingStudentsId = $_POST['students'];
                $studentsId = [];
                foreach ($incomingStudentsId as $incomingStudentId) {
                    // ignore invalid id and add valid id onto the $studentsId
                    if (intval($incomingStudentId) == 0) continue;
                    array_push($studentsId, intval($incomingStudentId));
                }

                // bind and sanitize the rest of incoming data
                $activityId = !empty($_POST['activity']['id']) ? intval($_POST['activity']['id']) : 0;
                $dateBegin = !empty($_POST['dateBegin']) ? htmlspecialchars(strip_tags(trim($_POST['dateBegin']))) : '';
                $dateEnd = !empty($_POST['dateEnd']) ? htmlspecialchars(strip_tags(trim($_POST['dateEnd']))) : '';
                $evaluation = !empty($_POST['evaluation']) ? htmlspecialchars(strip_tags(trim($_POST['evaluation']))) : '';
                $autocorrection = !empty($_POST['autocorrection']) ? htmlspecialchars(strip_tags(trim($_POST['autocorrection']))) : '';
                $introduction = !empty($_POST['introduction']) ? htmlspecialchars(strip_tags(trim($_POST['introduction']))) : '';
                $retroAttribution = !empty($_POST['retroAttribution']) ? htmlspecialchars(strip_tags(trim($_POST['retroAttribution']))) : '';
                $reference = !empty($_POST['ref']) ? htmlspecialchars(strip_tags(trim($_POST['ref']))) : '';

                // a reference has been received, we are in an update context
                if (!empty($reference)) {
                    $activity = $this->entityManager
                        ->getRepository(Activity::class)
                        ->find($activityId);

                    // step 1 => insert all new students
                    foreach ($studentsId as $studentId) {
                        $user = $this->entityManager
                            ->getRepository(User::class)
                            ->find($studentId);

                        $linkActivityToClassroomExists = $this->entityManager
                            ->getRepository(ActivityLinkUser::class)
                            ->findOneBy(array(
                                'user' => $user->getId(),
                                'activity' => $activity->getId(),
                                'reference' => $reference
                            ));

                        if (!$linkActivityToClassroomExists) {
                            $linkActivityToUser = new ActivityLinkUser($activity, $user, new \DateTime($dateBegin),  new \DateTime($dateEnd), $evaluation, $autocorrection, "", $introduction, $reference);
                            $this->entityManager->persist($linkActivityToUser);
                            $this->entityManager->flush();
                        }
                    }

                    // step 2 => remove students when they are not part of the updated list of $studentsId
                    $studentActivities = $this->entityManager
                        ->getRepository(ActivityLinkUser::class)
                        ->findBy(array("reference" => $reference));

                    foreach ($studentActivities as $studentActivity) {
                        if (!in_array($studentActivity->getUser()->getId(), $studentsId)) {
                            $this->entityManager->remove($studentActivity);
                            $this->entityManager->flush();
                        }
                    }

                    // get all retor attributions if any
                    $linkedActivityToClassrooms = $this->entityManager
                        ->getRepository(ActivityLinkClassroom::class)
                        ->findBy(array(
                            'activity' => $activity,
                            'reference' => $reference
                        ));

                    // step 1 remove them all by default to handle the case when retro attribution is set to false
                    if ($linkedActivityToClassrooms) {
                        foreach ($linkedActivityToClassrooms as $linkedActivityToClassroom) {
                            $this->entityManager->remove($linkedActivityToClassroom);
                            $this->entityManager->flush();
                        }
                    }

                    // step 2 create the record and save them to handle the case when retro attribution to true, 
                    if ($retroAttribution == 'true') {
                        foreach ($classroomIds as $classroomId) {
                            $classroom = $this->entityManager
                                ->getRepository('Classroom\Entity\Classroom')
                                ->findOneBy(array("id" => $classroomId));
                            if ($classroom) {
                                $linkActivityToClassroom = new ActivityLinkClassroom($activity, $classroom, new \DateTime($dateBegin),  new \DateTime($dateEnd), $evaluation, $autocorrection, $introduction, $reference);
                                $this->entityManager->persist($linkActivityToClassroom);
                                $this->entityManager->flush();
                            }
                        }
                    }

                    return true;
                } else {
                    // no reference provided, we are in a create context
                    // create the reference and get the activity
                    $reference = strval(time());

                    $activity = $this->entityManager
                        ->getRepository('Learn\Entity\Activity')
                        ->find($activityId);

                    // step 1 => insert students
                    foreach ($studentsId as $studentId) {
                        $user = $this->entityManager
                            ->getRepository('User\Entity\User')
                            ->findOneBy(array("id" => $studentId));

                        if ($user) {
                            $linkActivityToUser = new ActivityLinkUser($activity, $user, new \DateTime($dateBegin),  new \DateTime($dateEnd), $evaluation, $autocorrection, "", $introduction, $reference);
                            $this->entityManager->persist($linkActivityToUser);
                        }
                    }
                    $this->entityManager->flush();

                    // step 2 loop through the classrooms ids and get the classrooms
                    foreach ($classroomIds as $classroomId) {
                        $classroom = $this->entityManager
                            ->getRepository('Classroom\Entity\Classroom')
                            ->findOneBy(array(
                                "id" => $classroomId
                            ));

                        if ($classroom) {
                            if ($retroAttribution == 'true') {
                                // the classroom was found 
                                // and the activity has to attributed to all future students joining the classroom
                                // check if there is already a record in classroom_activities_link_classroom
                                $linkActivityToClassroomExists = $this->entityManager
                                    ->getRepository(ActivityLinkClassroom::class)
                                    ->findOneBy(array(
                                        'classroom' => $classroom,
                                        'activity' => $activity,
                                        'reference' => $reference
                                    ));

                                // a record was found, do nothing
                                if ($linkActivityToClassroomExists) continue;

                                // no record found, save a new entry in classroom_activities_link_classroom
                                $linkActivityToClassroom = new ActivityLinkClassroom($activity, $classroom, new \DateTime($dateBegin),  new \DateTime($dateEnd), $evaluation, $autocorrection, $introduction, $reference);
                                $this->entityManager->persist($linkActivityToClassroom);
                            }
                        }
                    }
                    $this->entityManager->flush();
                    return true;
                    /*  return $linkActivityToClassroom; //synchronized */
                }
            },
            "update" => function () {
                /**
                 * This method is used by the student to save its work
                 * => my activities => click on an activity => submit the activity
                 * and by teacher to give a note to the student's work
                 * => inside a classroom => click on a student activity
                 */

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "updateNotRetrievedNotAuthenticated"];

                $incomingProjectId = $_POST['project']['id'] ?? $_POST['project'] ?? null;
                // bind incoming data
                $activityId = !empty($_POST['id']) ? intval($_POST['id']) : 0;
                $correction = isset($_POST['correction']) ? intval($_POST['correction']) : null;
                $commentary = !empty($_POST['commentary']) ? htmlspecialchars(strip_tags(trim($_POST['commentary']))) : '';
                $note = !empty($_POST['note']) ? intval($_POST['note']) : 0;
                $projectId = !empty($incomingProjectId) ? intval($incomingProjectId) : null;
                /**
                 * @ToBeRemoved duplicate behavior with Thomas's sendBeacon
                 * $timePassed = !empty($_POST['timePassed']) ? intval($_POST['timePassed']) : 0;
                 * March 2022
                 */

                // initiate an empty errors array 
                $errors = [];
                if (empty($activityId)) $errors['invalidActivityId'] = true;
                if (!is_numeric($correction)) $errors['invalidCorrection'] = true;
                if ($correction < 0 || $correction > 999) {
                    $errors['invalidCorrection'] = true;
                }
                // some errors found, return them
                if (!empty($errors)) return array('errors' => $errors);

                // no errors, get the activity
                $activity = $this->entityManager
                    ->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->findOneBy(array("id" => $activityId));

                $activity->setCorrection($correction);
                $activity->setNote($note);
                $activity->setCommentary($commentary);
                if (isset($projectId) && $projectId != null) {
                    $project = $this->entityManager
                        ->getRepository('Interfaces\Entity\Project')
                        ->findOneBy(array("id" => $projectId));

                    $activity->setProject($project);
                    $activity->setTries($activity->getTries() + 1);
                    $activity->setDateSend(new \DateTime());
                    /* 
                    * @ToBeRemoved duplicate behavior with Thomas's sendBeacon
                    * $activity->setTimePassed(intval($activity->getTimePassed()) + $timePassed); 
                    * March 2022
                    */
                }

                /*  $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findOneBy(array("link" => $data['classroomLink']));
                 $classroom->setIsChanged(true); 
                $this->entityManager->persist($classroom); */
                $this->entityManager->persist($activity);
                $this->entityManager->flush();

                return  $activity;
            },
            "get_one" => function () {
                /**
                 * This method is used by the teacher inside a classroom
                 * when cliking on a student activity to see the details
                 */
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "getOneNotRetrievedNotAuthenticated"];

                // bind and sanitize incoming data to check if the logged user is the teacher
                $activityId = !empty($_POST['id']) ? intval($_POST['id']) : 0;

                if (empty($activityId)) return array('errorType' => 'activityIdInvalid');

                $activity = $this->entityManager->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->findOneBy(array("id" => $activityId));

                $activityToSend = json_decode(json_encode($activity));

                // get the activity restriction by type
                $activityRestriction = $this->entityManager
                    ->getRepository(Applications::class)
                    ->findOneBy(array(
                        'name'=> $activityToSend->activity->type
                    ));
               
                // bind isLti property to $dataToSend
                $activityToSend->activity->isLti = $activityRestriction
                    ? $activityRestriction->getIsLti()
                    : false;

                return $activityToSend;
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

                // bind and sanitize incoming data
                $reference = !empty($_POST['reference']) ? intval($_POST['reference']) : 0;
                $classroomId = !empty($_POST['classroomId']) ? intval($_POST['classroomId']) : 0;

                // check for errors
                $errors = [];
                if (empty($reference)) {
                    array_push($errors, array('errorType' => 'activityReferenceInvalid'));
                }
                if (empty($classroomId)) {
                    array_push($errors, array('errorType' => 'classroomIdInvalid'));
                }

                // return errors if any
                if (!empty($errors)) return array('errors' => $errors);


                // no errors, get all records in classroom_activities_link_classroom with the reference provided
                $classroom = $this->entityManager->getRepository(Classroom::class)->find($classroomId);
                $classroomActivitiesRetroAttributed = $this->entityManager
                    ->getRepository(ActivityLinkClassroom::class)
                    ->findOneBy(array(
                        'reference' => $reference,
                        'classroom' => $classroom
                    ));

                // the current activity has been retro attributed , delete the record from classroom_activities_link_classroom
                if ($classroomActivitiesRetroAttributed) {
                    $this->entityManager->remove($classroomActivitiesRetroAttributed);
                    $this->entityManager->flush();
                }

                // get all students activity per classroom and activity ref
                $classroomStudentsActivitiesByReference = $this->entityManager
                    ->getRepository(ActivityLinkUser::class)
                    ->getStudentsActivityByClassroomAndActivityRef($classroomId, $reference);

                // no activity found, return an error
                if (!$classroomStudentsActivitiesByReference) return array('errorType' => 'activityReferenceInvalid');

                // delete each student activity from classroom_activities_link_classroom_users
                foreach ($classroomStudentsActivitiesByReference as $classroomStudentsActivity) {
                    $this->entityManager->remove($classroomStudentsActivity);
                    $this->entityManager->flush();
                }

                return true;
            },
            "update_time_passed" => function () {
                /**
                 * This method is called to update the time passed on an ansctivity (activityLinkUser) by a student.
                 */
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "removeByReferenceNotRetrievedNotAuthenticated"];

                $reference = !empty($_POST['reference']) ? intval($_POST['reference']) : 0;
                if (empty($reference)) return array('errorType' => 'activityReferenceInvalid');

                $timePassed = !empty($_POST['time_passed']) ? intval($_POST['time_passed']) : 0;
                if (empty($timePassed)) return array('errorType' => 'activityTimePassedInvalid');

                $user = $this->entityManager
                    ->getRepository(User::class)
                    ->find($_SESSION["id"]);

                $classroomStudentActivity = $this->entityManager
                    ->getRepository('Classroom\Entity\ActivityLinkUser')
                    ->findOneBy(array("reference" => $reference, "user" => $user));

                $previousTimePassed = $classroomStudentActivity->getTimePassed();
                $classroomStudentActivity->setTimePassed($previousTimePassed + $timePassed);
                $this->entityManager->flush();
            }
        );
    }
}
