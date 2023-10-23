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
                    $reference = !empty($_POST['reference']) ? htmlspecialchars(strip_tags(trim($_POST['reference']))) : null;

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


                    $activitiesReferences = [];
                    if ($reference) {
                        // remove attribution if the student is not in the array
                        $courseLinkUsers = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(['course' => $courseId, 'reference' => $reference]);

                        foreach ($courseLinkUsers as $clu) {
                            $activitiesReferences = json_decode($clu->getActivitiesReferences());
                            //get student classroom
                            $classroomLinkUser = $this->entityManager->getRepository(ClassroomLinkUser::class)->findOneBy(['user' => $clu->getUser()->getId()]);
                            if (!in_array($clu->getUser()->getId(), $studentsId) && $classroomLinkUser->getClassroom()->getId() == $classroomId) {
                                $this->entityManager->remove($clu);
                            }
                        }
                        $this->entityManager->flush();
                    }


                    $randomStr = strval(time());
                    foreach ($studentsId as $studentId) {
                        $user = $this->entityManager->getRepository(User::class)->find($studentId);
                        //$linkCourseToClassroomExists = $this->entityManager->getRepository(CourseLinkUser::class)->findOneBy(['user' => $user->getId(), 'course' => $course->getId(), 'reference' => $reference]);
                        $readingCount = 0;
                        $activitiesReference = [];
                        // step 2 => insert all activities of the course for each student
                        foreach ($courseActivities as $key => $courseActivity) {
                            $activity = $this->entityManager->getRepository(Activity::class)->find($courseActivity->getActivity()->getId());

                            // check if activity link user already exists
                            $activityLinkUser = $this->entityManager->getRepository(ActivityLinkUser::class)->findOneBy(['user' => $user->getId(), 'activity' => $activity->getId(), "course" => $course->getId()]);
                            if ($activityLinkUser) {
                                array_push($activitiesReference, $activityLinkUser->getReference());
                                continue;
                            }

                            $activityLinkUser = new ActivityLinkUser($activity, $user);
                            if ($activity->getType() == "reading" && $course->getFormat() == 1) {
                                $readingCount++;
                                $activityLinkUser->setCorrection(2);
                                $activityLinkUser->setNote(4);
                            }
                            $activityLinkUser->setCourse($course);
                            if ($reference) {
                                $activityLinkUser->setReference($activitiesReferences[$key]);
                            } else {
                                $activityLinkUser->setReference($randomStr . $key);
                            }

                            
                            $activityLinkUser->setDateBegin($dateTimeBegin);
                            $activityLinkUser->setDateEnd($dayeTimeEnd);
                            $activityLinkUser->setIsFromCourse(1);
                            $this->entityManager->persist($activityLinkUser);

                            array_push($activitiesReference, $activityLinkUser->getReference());
                        }

                        //check if course link user already exists
                        $courseLinkUser = $this->entityManager->getRepository(CourseLinkUser::class)->findOneBy(['user' => $user->getId(), 'course' => $course->getId(), 'reference' => $reference]);
                        if ($courseLinkUser) {
                            continue;
                        }

                        $linkCourseToUser = new CourseLinkUser();
                        $linkCourseToUser->setUser($user);
                        $linkCourseToUser->setCourse($course);
                        $linkCourseToUser->setActivitiesData(null);

                        if ($course->getFormat() == 1) {
                            $linkCourseToUser->setCourseState($readingCount);
                        } else {
                            $linkCourseToUser->setCourseState(0);
                        }
                        $linkCourseToUser->setDateBegin($dateTimeBegin);
                        $linkCourseToUser->setDateEnd($dayeTimeEnd);
                        if ($reference) {
                            $linkCourseToUser->setReference($reference);
                        } else {
                            $linkCourseToUser->setReference($randomStr . $courseId);
                        }
                        $linkCourseToUser->setActivitiesReferences(json_encode($activitiesReference));
                        $this->entityManager->persist($linkCourseToUser);

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

                    foreach ($myCourses as $key => $course) {
                        $courseArray = $course->jsonSerialize();
                        $courseArray['activities'] = [];
                        $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['course' => $course->getId()]);

                        // order activities by position
                        usort($courseLinkActivities, function ($a, $b) {
                            return $a->getIndexOrder() <=> $b->getIndexOrder();
                        });


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
                    $coursesLinkUser = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(['user' => $loggedUser->getId()]);
                    $myCoursesArray = [];

                    foreach ($coursesLinkUser as $course) {
                        // get activities linked to the course
                        $courseArray = $course->jsonSerialize();
                        $courseArray['activities'] = [];
                        $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['course' => $course->getCourse()->getId()]);

                        $courseCheck = $this->entityManager->getRepository(Course::class)->findOneBy(['id' => $course->getCourse()->getId()]);
                        if (!$courseCheck) {
                            $this->deleteCourseReferences($course->getCourse()->getId());
                            continue;
                        };

                        // order activities by index order
                        usort($courseLinkActivities, function ($a, $b) {
                            return $a->getIndexOrder() <=> $b->getIndexOrder();
                        });

                        $activitiesReferences = json_decode($course->getActivitiesReferences());

                        foreach ($courseLinkActivities as $activity) {
                            $activityLinkUser = $this->entityManager->getRepository(ActivityLinkUser::class)->findBy(['user' => $loggedUser->getId(), 'activity' => $activity->getActivity()->getId(), "course" => $course->getCourse()->getId()]);
                            $acti = null;
                            foreach ($activityLinkUser as $alu) {
                                if (in_array($alu->getReference(), $activitiesReferences)) {
                                    $acti = $alu;
                                    break;
                                }
                            }

                            if (empty($acti)) {
                                $acti = $activityLinkUser[0];
                            }
                            
                            if (!$activityLinkUser) {
                                $activityLinkUser = $this->attributeActivityForCourse($activity->getActivity()->getId(), $course->getCourse(), $loggedUser->getId(), $courseLinkActivities);
                            }

                            $activityRestriction = $this->entityManager->getRepository(Applications::class)->findOneBy(['name' => $activity->getActivity()->getType()]);
                            $serializedActivity = $acti->jsonSerialize();
                            $serializedActivity["activity"]["isLti"] = $activityRestriction ? $activityRestriction->getIsLti() : false;
                            array_push($courseArray['activities'], $serializedActivity);
                        }
                        array_push($myCoursesArray, $courseArray);
                    }
                    return $myCoursesArray;
                },
                "unlink_course_to_users" => function () {
                    // accept only POST request
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
                    // accept only connected user
                    if (empty($_SESSION['id'])) return ["errorType" => "addUsersNotRetrievedNotAuthenticated"];
                    // bind and sanitize incoming data to check if the logged user is the teacher
                    $courseId = !empty($_POST['courseId']) ? intval($_POST['courseId']) : 0;
                    $classId = !empty($_POST['classId']) ? intval($_POST['classId']) : null;
                    $references = !empty($_POST['references']) ? $_POST['references'] : null;

                    if (empty($courseId)) return array("errorType" => "courseIdNotRetrieved");
                    if (empty($classId)) return array("errorType" => "classIdNotRetrieved");

                    $userId = intval($_SESSION['id']);
                    $course = $this->entityManager->getRepository(Course::class)->findOneBy(['id' => $courseId, 'user' => $userId]);

                    if (!$course) {
                        return array("errorType" => "courseNotFound");
                    }

                    $courseLinkUsers = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(['course' => $courseId, 'reference' => $references]);
                    $classroomLinkUser = $this->entityManager->getRepository(ClassroomLinkUser::class)->findBy(['classroom' => $classId]);

                    foreach ($courseLinkUsers as $clu) {
                        foreach ($classroomLinkUser as $classlu) {
                            if ($clu->getUser()->getId() == $classlu->getUser()->getId()) {
                                $this->entityManager->remove($clu);
                            }
                        }
                    }

                    $activitiesReferences = json_decode($courseLinkUsers[0]->getActivitiesReferences());
                    $activityLinkUsers = $this->entityManager->getRepository(ActivityLinkUser::class)->findBy(['course' => $courseId]);
                    foreach ($activityLinkUsers as $alu) {
                        foreach ($classroomLinkUser as $classlu) {
                            if ($alu->getUser()->getId() == $classlu->getUser()->getId() && in_array($alu->getReference(), $activitiesReferences)) {
                                $this->entityManager->remove($alu);
                            }
                        }
                    }
                    
                    $this->entityManager->flush();

                    return ["success" => true, "message" => "Course unlinked"];
                }
            );
        }
    }

    private function deleteCourseReferences($courseId) {
        $courseLinkUsers = $this->entityManager->getRepository(CourseLinkUser::class)->findBy(['course' => $courseId]);
        foreach ($courseLinkUsers as $clu) {
            $this->entityManager->remove($clu);
        }

        // activitylink user search from course
        $activityLinkUsers = $this->entityManager->getRepository(ActivityLinkUser::class)->findBy(['course' => $courseId]);
        foreach ($activityLinkUsers as $alu) {
            $this->entityManager->remove($alu);
        }

        $courseLinkActivities = $this->entityManager->getRepository(CourseLinkActivity::class)->findBy(['course' => $courseId]);
        foreach ($courseLinkActivities as $cla) {
            $this->entityManager->remove($cla);
        }
        $this->entityManager->flush();
    }
    
    private function attributeActivityForCourse($activityId, $course, $userId, $courseLinkUser) {

        $acti = $this->entityManager->getRepository(Activity::class)->findOneBy(["id" => $activityId]);
        $userN = $this->entityManager->getRepository(User::class)->findOneBy(["id" => $userId]);
        
        if ($acti) {

            $activityLinkUser = new ActivityLinkUser($acti, $userN);
            if ($acti->getType() == "reading" && $course->getFormat() == 1) {
                $activityLinkUser->setCorrection(2);
                $activityLinkUser->setNote(4);
                if ($course->getFormat() == 1) {
                    $courseLinkUser->setCourseState($courseLinkUser->getCourseState() + 1);
                }
            }

            $randomStr = strval(time());
            $dateTimeBegin = new \DateTime();
            $dayeTimeEnd = new \DateTime(date('Y-m-d H:i:s', strtotime('+1 year')));
            // get my class id 
            $classroomLinkUser = $this->entityManager->getRepository(ClassroomLinkUser::class)->findOneBy(['user' => $userId]);
            $classroomId = $classroomLinkUser->getClassroom()->getId();

            
            // get students in classroom
            $students = $this->entityManager->getRepository(ClassroomLinkUser::class)->findBy(['classroom' => $classroomId, 'rights' => 0]);
            if (count($students) > 0) {
                foreach ($students as $student) {
                    if ($student->getUser()->getId() != $userId) {
                        $actlinkuser = $this->entityManager->getRepository(ActivityLinkUser::class)->findOneBy(['course' => $course, 'activity' => $activityId, 'user' => $student->getUser()->getId()]);
                        if ($actlinkuser) {
                            $randomStr = $actlinkuser->getReference();
                            $dateTimeBegin = $actlinkuser->getDateBegin();
                            $dayeTimeEnd = $actlinkuser->getDateEnd();
                            break;
                        }
                    }
                }
            }
            $activityLinkUser->setCourse($course);
            $activityLinkUser->setReference($randomStr);
            $activityLinkUser->setDateBegin($dateTimeBegin);
            $activityLinkUser->setDateEnd($dayeTimeEnd);
            $activityLinkUser->setIsFromCourse(1);
            $this->entityManager->persist($activityLinkUser);
            $this->entityManager->flush();

            return $activityLinkUser;
        }
    }
}
