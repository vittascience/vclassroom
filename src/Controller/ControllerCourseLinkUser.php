<?php

namespace Classroom\Controller;

use Classroom\Entity\CourseLinkUser;


class ControllerCourseLinkUser extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);

        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return false;
        } else {
            $this->actions = array(
                'link_user_to_course' => function () {
                     
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
                            return array("errorType" => "notTeacherErrorFlagTrue");
                        }
                    }

                    // bind and sanitize the students id array
                    $incomingStudentsId = $_POST['students'];
                    $studentsId = [];
                    foreach ($incomingStudentsId as $incomingStudentId) {
                        // ignore invalid id and add valid id onto the $studentsId
                        if (intval($incomingStudentId) == 0) continue;
                        array_push($studentsId, intval($incomingStudentId));
                    }

                    // bind and sanitize the rest of incoming data
                    $courseId = !empty($_POST['courseId']) ? intval($_POST['courseId']) : 0;
                    $dateBegin = !empty($_POST['dateBegin']) ? new \DateTime($_POST['dateBegin']) : '';
                    $dateEnd = !empty($_POST['dateEnd']) ? new \DateTime($_POST['dateEnd']) : '';
                    $activities = !empty($_POST['activities']) ? $_POST['activities'] : '';
                    //$retroAttribution = !empty($_POST['retroAttribution']) ? htmlspecialchars(strip_tags(trim($_POST['retroAttribution']))) : '';

                    
                    $course = $this->entityManager->getRepository(Course::class)->findOneBy(["id" => $courseId]);
                    // step 1 => insert all new students
                    foreach ($studentsId as $studentId) {
                        $user = $this->entityManager->getRepository(User::class)->find($studentId);

                        $linkCourseToClassroomExists = $this->entityManager
                            ->getRepository(CourseLinkUser::class)
                            ->findOneBy(array(
                                'user' => $user->getId(),
                                'course' => $course->getId()
                            ));

                        if (!$linkCourseToClassroomExists) {
                            $linkCourseToUser = new CourseLinkUser();
                            $linkCourseToUser->setUser($user);
                            $linkCourseToUser->setCourse($course);
                            $linkCourseToUser->setActivitiesData(null);
                            $linkCourseToUser->setCourseState(0);
                            $linkCourseToUser->setDateBegin($dateBegin);
                            $linkCourseToUser->setDateEnd($dateEnd);

                            $this->entityManager->persist($linkCourseToUser);
                            $this->entityManager->flush();
                        }
                    }

                    return true;
                },
                'get_my_courses_as_teacher' => function () {
                    // accept only POST request
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
                    // accept only connected user
                    if (empty($_SESSION['id'])) return ["errorType" => "addUsersNotRetrievedNotAuthenticated"];
                    // bind and sanitize incoming data to check if the logged user is the teacher
                    $userId = intval($_SESSION['id']);
                    $loggedUser = $this->entityManager->getRepository(User::class)->find($userId);


                    // get the classroom teacher using the $loggedUser id
                    $teacher = $this->entityManager->getRepository(ClassroomLinkUser::class)->findOneBy(['user' => $loggedUser->getId(), 'rights' => 2]);
                    // the logged user is not the teacher, set error flag to true and exit the loop
                    if (!$teacher) {
                        return array("errorType" => "notTeacherErrorFlagTrue");
                    }
                    // get the courses linked to the teacher
                    $courses = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(['user' => $loggedUser->getId(), 'rights' => 2]);

                    // return the courses linked to the teacher
                    return $courses;
                },
            );
        }
    }
}


                    // get all retor attributions if any
        /*             $linkedActivityToClassrooms = $this->entityManager
                        ->getRepository(ActivityLinkClassroom::class)
                        ->findBy(array(
                            'activity' => $activity,
                            'reference' => $reference
                        )); */

                    // step 1 remove them all by default to handle the case when retro attribution is set to false
 /*                    if ($linkedActivityToClassrooms) {
                        foreach ($linkedActivityToClassrooms as $linkedActivityToClassroom) {
                            $this->entityManager->remove($linkedActivityToClassroom);
                            $this->entityManager->flush();
                        }
                    } */

                    // step 2 create the record and save them to handle the case when retro attribution to true, 
/*                     if ($retroAttribution == 'true') {
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
                    } */