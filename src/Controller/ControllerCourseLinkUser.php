<?php

namespace Classroom\Controller;

use User\Entity\User;
use Learn\Entity\Course;
use User\Entity\Regular;
use Learn\Entity\Activity;
use Classroom\Entity\Classroom;
use Classroom\Entity\Applications;
use Classroom\Entity\CourseLinkUser;
use Learn\Entity\CourseLinkActivity;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\ClassroomLinkUser;

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
                    $dateBegin = !empty($_POST['dateBegin']) ? $_POST['dateBegin'] : '';
                    $dateEnd = !empty($_POST['dateEnd']) ? new \DateTime($_POST['dateEnd']) : '';
                    $activities = !empty($_POST['activities']) ? $_POST['activities'] : '';

                    $dateTimeBegin = new \DateTime($dateBegin);
                    $dayeTimeEnd = new \DateTime($dateEnd);
                    //$retroAttribution = !empty($_POST['retroAttribution']) ? htmlspecialchars(strip_tags(trim($_POST['retroAttribution']))) : '';

                    
                    $course = $this->entityManager->getRepository(Course::class)->findOneBy(["id" => $courseId]);
                    // get activities of the course
                    $courseActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(array('course' => $course->getId()));
                    // step 1 => insert all new students

                    $randomStr = strval(time());
                    foreach ($studentsId as $studentId) {
                        $user = $this->entityManager->getRepository(User::class)->find($studentId);

                        $linkCourseToClassroomExists = $this->entityManager->getRepository(CourseLinkUser::class)->findOneBy(array('user' => $user->getId(), 'course' => $course->getId()));

                        if (!$linkCourseToClassroomExists) {
                            $linkCourseToUser = new CourseLinkUser();
                            $linkCourseToUser->setUser($user);
                            $linkCourseToUser->setCourse($course);
                            $linkCourseToUser->setActivitiesData(null);
                            $linkCourseToUser->setCourseState(0);
                            $linkCourseToUser->setDateBegin($dateTimeBegin);
                            $linkCourseToUser->setDateEnd($dayeTimeEnd);
                            $this->entityManager->persist($linkCourseToUser);
                        }

                        // step 2 => insert all activities of the course for each student
                        foreach ($courseActivities as $key => $courseActivity) {
                            $activity = $this->entityManager->getRepository(Activity::class)->find($courseActivity->getActivity()->getId());
                            $activityLinkUser = new ActivityLinkUser($activity, $user);
                            $activityLinkUser->setCourse($course);
                            $activityLinkUser->setReference($randomStr . $key);
                            $activityLinkUser->setDateBegin($dateTimeBegin);
                            $activityLinkUser->setDateEnd($dayeTimeEnd);
                            $activityLinkUser->setIsFromCourse(1);
                            $this->entityManager->persist($activityLinkUser);
                        }
                        $this->entityManager->flush();
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
                    // check if regular 
                    $isRegular = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => htmlspecialchars($_SESSION['id'])]);

                    if (!$isRegular) {
                        return array("errorType" => "notTeacherErrorFlagTrue");
                    }

                    $myCourses = $this->entityManager->getRepository(Course::class)->findBy(['user' => $loggedUser->getId()]);
                    $myCoursesArray = [];

                    foreach($myCourses as $key => $course) {
                        $courseArray = $course->jsonSerialize();
                        $courseArray['activities'] = [];
                        $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['course' => $course->getId()]);
                        $toAdd = true;
                        foreach ($courseLinkActivities as $activity) {
                            if (!$activity->getActivity()->isFromClassroom()) {
                                $toAdd = false;
                                break;
                            }
                            array_push($courseArray['activities'], $activity->getActivity());
                        }
                        
                        if ($toAdd) {
                            array_push($myCoursesArray, $courseArray);
                        }
                    }
                    
                    return $myCoursesArray;
                },
                'get_my_courses_as_student' => function () {
                    // accept only POST request
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
                    // accept only connected user
                    if (empty($_SESSION['id'])) return ["errorType" => "addUsersNotRetrievedNotAuthenticated"];
                    // bind and sanitize incoming data to check if the logged user is the teacher
                    $userId = intval($_SESSION['id']);
                    $loggedUser = $this->entityManager->getRepository(User::class)->find($userId);
                    $courseLinkActivities = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(['user' => $loggedUser->getId()]);
                    $myCoursesArray = [];

                    foreach($courseLinkActivities as $course) {
                        // get activities linked to the course
                        $courseArray = $course->jsonSerialize();
                        $courseArray['activities'] = [];
                        $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['course' => $course->getCourse()->getId()]);
                        foreach ($courseLinkActivities as $activity) {
                            $activityLinkUser = $this->entityManager->getRepository(ActivityLinkUser::class)->findOneBy(['user' => $loggedUser->getId(), 'activity' => $activity->getActivity()->getId(), "course" => $course->getCourse()->getId()]);
                            $activityRestriction = $this->entityManager->getRepository(Applications::class)->findOneBy(['name' => $activity->getActivity()->getType()]);
                            $serializedActivity = $activityLinkUser->jsonSerialize();
                            $serializedActivity["activity"]["isLti"] = $activityRestriction ? $activityRestriction->getIsLti() : false;
                            array_push($courseArray['activities'], $serializedActivity);
                        }
                        array_push($myCoursesArray, $courseArray);
                    }
                    return $myCoursesArray;
                },
            );
        }
    }
}