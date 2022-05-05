<?php

namespace Classroom\Controller;

use DAO\RegularDAO;
use models\Regular;
use User\Entity\User;
use Classroom\Entity\Groups;
use User\Entity\ClassroomUser;
use Classroom\Entity\Applications;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\ClassroomLinkUser;
use Classroom\Entity\ActivityLinkClassroom;


class ControllerClassroomLinkUser extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'add_users' => function () {

                /**
                 * Limiting learner number @THOMAS MODIF
                 * Added premium and Admin check @NASER MODIF
                 * @var $data[users] is an array of users submitted
                 * @var $data[classroom] is a string containing the classroom link 
                 */

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "addUsersNotAuthenticated"];

                // use the same regex as in the User entity to avoid troubleshouting
                $regexForPseudo = "/^[a-zA-ZáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ]{1}[\w\sáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ'&@\-_.()]{0,98}[\wáàâäãåçéèêëíìîïñóòôöõúùûüýÿæœÁÀÂÄÃÅÇÉÈÊËÍÌÎÏÑÓÒÔÖÕÚÙÛÜÝŸÆŒ)]{0,1}$/";

                // get the currently logged user (professor, admin, premium,...) 
                $currentUserId = intval($_SESSION["id"]);

                // get the statuses for the current user
                $isPremium = RegularDAO::getSharedInstance()->isTester($currentUserId);
                $isAdmin = RegularDAO::getSharedInstance()->isAdmin($currentUserId);

                // bind and sanitize .env demoStudent
                $demoStudent = !empty($this->envVariables['VS_DEMOSTUDENT']) ? htmlspecialchars(strip_tags(trim(strtolower($this->envVariables['VS_DEMOSTUDENT'])))) : 'demostudent';

                // bind incoming users
                $incomingUsers = $_POST['users'];
                $usersToAdd = [];
                $usersToAddErrorFlag = false;
                foreach ($incomingUsers as $incomingUser) {
                    // bind and sanitize each incoming user
                    $student = preg_match($regexForPseudo, $incomingUser)
                        ? htmlspecialchars(strip_tags(trim($incomingUser)), ENT_QUOTES)
                        : '';

                    if (empty($student)) $usersToAddErrorFlag = true;
                    else array_push($usersToAdd, $student);
                }
                if ($usersToAddErrorFlag == true) return array('errorType' => "backendReplyPseudoMissingInUsersArray");

                // bind and sanitize incoming classroomLink
                $classroomLink = !empty($_POST['classroom']) ? htmlspecialchars(strip_tags(trim($_POST['classroom']))) : '';
                if (empty($classroomLink)) return array('errorType' => 'classroomLinkMissing');

                // get all classrooms for the current user
                $classrooms = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array("user" => $currentUserId));

                // initiate the $nbApprenants counter and loop through each classrooms
                $nbApprenants = 0;
                foreach ($classrooms as $c) {
                    $students = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                        ->getAllStudentsInClassroom($c->getClassroom()->getId(), 0, $demoStudent);

                    // add the current classroom users number and increase the total
                    $nbApprenants += count($students);
                }

                $learnerNumberCheck = [
                    "idUser" => $currentUserId,
                    "isPremium" => $isPremium,
                    "isAdmin" => $isAdmin,
                    "learnerNumber" => $nbApprenants
                ];

                // set the $isAllowed flag to true if the current user is admin or premium
                //$isAllowed = $learnerNumberCheck["isAdmin"] || $learnerNumberCheck["isPremium"];

                /**
                 * Update Rémi COINTE
                 * if the user is not admin =>
                 * we check how many students he can have
                 * if it has no apps = default number => in the folder "default-restrictions"
                 * otherwise the restrictions is set by the user apps or the group's apps he has
                 */
                if (!$learnerNumberCheck["isAdmin"]) {
                    //@Note : the isPremium check is not deleted to restrein the actual user with the isPremium method
                    // the restrictions by application is not implemented to every user
                    $addedLearnerNumber = count($usersToAdd);
                    if ($learnerNumberCheck["isPremium"]) {
                        // computer the total number of students registered +1 and return an error if > 50
                        $totalLearnerCount = $learnerNumberCheck["learnerNumber"] + $addedLearnerNumber;
                        // check if the 400 students limit is reached and return an error when it is reached
                        if ($totalLearnerCount > 400) {
                            return [
                                "isUsersAdded" => false,
                                "currentLearnerCount" => $learnerNumberCheck["learnerNumber"],
                                "addedLearnerNumber" => $addedLearnerNumber
                            ];
                        }
                    } else {
                        // Groups and teacher limitation per application
                        $limitationsReached = $this->entityManager->getRepository(Applications::class)->isStudentsLimitReachedForTeacher($currentUserId, $addedLearnerNumber);
                        if (!$limitationsReached['canAdd']) {
                            $groupInfo = array_key_exists("groupInfo", $limitationsReached) ? $limitationsReached["groupInfo"] : null;
                            $teacherInfo = array_key_exists("teacherInfo", $limitationsReached) ? $limitationsReached["teacherInfo"] : null;
                            return [
                                "isUsersAdded" => false,
                                "currentLearnerCount" => $limitationsReached["teacherInfo"]["actualStudents"],
                                "addedLearnerNumber" => $addedLearnerNumber,
                                "message" => $limitationsReached['message'],
                                "teacherInfo" => $teacherInfo,
                                "groupInfo" => $groupInfo
                            ];
                        }
                    }
                }

                /**
                 * check that teacher does not add a demoStudent user @MODIF naser
                 */

                if (in_array($demoStudent, array_map('strtolower', $usersToAdd))) {
                    return [
                        "isUsersAdded" => false,
                        "errorType" => "reservedNickname",
                        "currentNickname" => $demoStudent
                    ];
                }

                $passwords = [];
                foreach ($usersToAdd as $u) {
                    $user = new User();
                    $user->setSurname('surname');
                    $user->setFirstname('firstname');
                    $user->setPseudo($u);
                    $password = passwordGenerator();
                    $passwords[] = $password;
                    $user->setPassword($password);

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $classroomUser = new ClassroomUser($user);
                    $classroomUser->setGarId(null);
                    $classroomUser->setSchoolId(null);
                    $classroomUser->setIsTeacher(false);
                    $classroomUser->setMailTeacher(NULL);
                    $this->entityManager->persist($classroomUser);

                    $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                        ->findOneBy(array('link' => $classroomLink));
                    
                    // get retro attributed activities if any
                    $classroomRetroAttributedActivities = $this->entityManager
                        ->getRepository(ActivityLinkClassroom::class)
                        ->getRetroAttributedActivitiesByClassroom($classroom);
                
                    // some retro attributed activities found, add them to the student
                    if($classroomRetroAttributedActivities){
                        $this->entityManager->getRepository(ActivityLinkUser::class)
                            ->addRetroAttributedActivitiesToStudent($classroomRetroAttributedActivities,$user);
                    }

                    
                    $linkClassroomUserToGroup = new ClassroomLinkUser($user, $classroom);
                    $linkClassroomUserToGroup->setRights(0);
                    $this->entityManager->persist($linkClassroomUserToGroup);
                }
                if (isset($_POST['existingUsers']) && count($_POST['existingUsers']) > 0) {

                    // bind incoming users
                    $incomingUsersToUpdate = $_POST['existingUsers'];
                    $usersToUpdate = [];
                    $usersToUpdateErrorFlag = false;
                    foreach ($incomingUsersToUpdate as $incomingUserToUpdate) {

                        // bind and sanitize each incoming user
                        $studentPseudo = preg_match($regexForPseudo, $incomingUserToUpdate['pseudo'])
                            ? htmlspecialchars(strip_tags(trim($incomingUserToUpdate['pseudo'])), ENT_QUOTES)
                            : '';
                        $studentId = !empty($incomingUserToUpdate['id']) ? intval($incomingUserToUpdate['id']) : 0;
                        if (empty($studentPseudo) || empty($studentId)) $usersToUpdateErrorFlag = true;
                        else array_push(
                            $usersToUpdate,
                            array('pseudo' => $studentPseudo, 'id' => $studentId)
                        );
                    }

                    if ($usersToUpdateErrorFlag == true) return array('errorType' => "backendReplyPseudoMissingInUpdateUsersArray");


                    foreach ($usersToUpdate as $userToUpdate) {
                        $existingUser = $this->entityManager->getRepository('User\Entity\User')->findOneBy(['id' => $userToUpdate['id']]);
                        $existingUser->setPseudo($userToUpdate['pseudo']);
                        $this->entityManager->persist($existingUser);
                    }
                }

                $this->entityManager->flush();

                return ["isUsersAdded" => true, "passwords" => $passwords];
            },
            'add_users_by_csv' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "addUsersByCsvNotAuthenticated"];

                // bind incoming users, set empty student array to fill and set error flag to false
                $incomingUsers = $_POST['users'];
                $studentsToAdd = [];
                $errorPseudoMissingFlag = false;

                // bind and sanitize incoming users array
                foreach ($incomingUsers as $incomingUser) {

                    $studentPseudo = htmlspecialchars(strip_tags(trim($incomingUser['apprenant'])));
                    $studentPassword = !empty($incomingUser['mot_de_passe'])
                        ? htmlspecialchars(strip_tags(trim($incomingUser['mot_de_passe'])))
                        :  passwordGenerator();

                    // one of the pseudo is empty, set the error flag to true and stop the loop
                    if (empty($studentPseudo)) {
                        $errorPseudoMissingFlag = true;
                        break;
                    }

                    // no error found, fille the student array
                    array_push(
                        $studentsToAdd,
                        array('apprenant' => $studentPseudo, 'mot_de_passe' => $studentPassword)
                    );
                }

                // error flag = true, return an error
                if ($errorPseudoMissingFlag == true) return array('errorType' => "backendReplyPseudoMissingInCsv");

                // sanitize the others data
                $currentUserId = intval($_SESSION['id']);
                $classroomLink = htmlspecialchars(strip_tags(trim($_POST['classroom'])));

                // get the statuses for the current user
                $isPremium = RegularDAO::getSharedInstance()->isTester($currentUserId);
                $isAdmin = RegularDAO::getSharedInstance()->isAdmin($currentUserId);

                // bind and sanitize .env demoStudent
                $demoStudent = !empty($this->envVariables['VS_DEMOSTUDENT'])
                    ? htmlspecialchars(strip_tags(trim(strtolower($this->envVariables['VS_DEMOSTUDENT']))))
                    : 'demostudent';

                // retrieve all classrooms of the current user
                $teacherClassrooms = $this->entityManager
                    ->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array(
                        'user' => $currentUserId,
                        'rights' => 2
                    ));

                $learnerNumber = 0;
                foreach ($teacherClassrooms as $classroomObject) {
                    // retrieve all student for the current classroom
                    $studentsInClassroom = $this->entityManager
                        ->getRepository('Classroom\Entity\ClassroomLinkUser')
                        ->findBy(array(
                            'classroom' => $classroomObject->getClassroom()->getId(),
                            'rights' => 0
                        ));
                    // add classroom students to the total
                    $learnerNumber += count($studentsInClassroom);
                }

                $learnerNumberCheck = [
                    "idUser" => $currentUserId,
                    "isPremium" => $isPremium,
                    "isAdmin" => $isAdmin,
                    "learnerNumber" => $learnerNumber
                ];

                /**
                 * Update Rémi COINTE
                 * if the user is not admin =>
                 * we check how many students he can have
                 * if it has no apps = default number => in the folder "default-restrictions"
                 * otherwise the restrictions is set by the user apps or the group's apps he has
                 */
                if (!$learnerNumberCheck["isAdmin"]) {
                    //@Note : the isPremium check is not deleted to restrein the actual user with the isPremium method
                    // the restrictions by application is not implemented to every user
                    $addedLearnerNumber = count($studentsToAdd);
                    if ($learnerNumberCheck["isPremium"]) {
                        // computer the total number of students registered +1 and return an error if > 50
                        $totalLearnerCount = $learnerNumberCheck["learnerNumber"] + $addedLearnerNumber;
                        // check if the 400 students limit is reached and return an error when it is reached
                        if ($totalLearnerCount > 400) {
                            return [
                                "isUsersAdded" => false,
                                "currentLearnerCount" => $learnerNumberCheck["learnerNumber"],
                                "addedLearnerNumber" => $addedLearnerNumber
                            ];
                        }
                    } else {
                        // Groups and teacher limitation per application
                        $limitationsReached = $this->entityManager->getRepository(Applications::class)->isStudentsLimitReachedForTeacher($currentUserId, $addedLearnerNumber);
                        if (!$limitationsReached['canAdd']) {
                            return [
                                "isUsersAdded" => false,
                                "currentLearnerCount" => $limitationsReached["teacherInfo"]["actualStudents"],
                                "addedLearnerNumber" => $addedLearnerNumber,
                                "message" => $limitationsReached['message']
                            ];
                        }
                    }
                }

                // check that teacher does not add a demoStudent (ie: .env var)             
                for ($i = 0; $i < count($studentsToAdd); $i++) {
                    $currentUserName = strtolower($studentsToAdd[$i]['apprenant']);
                    $demoStudentNameToTest = strtolower($demoStudent);
                    if ($currentUserName == $demoStudentNameToTest) {
                        return [
                            "isUsersAdded" => false,
                            "errorType" => "reservedNickname",
                            "currentNickname" => $demoStudent
                        ];
                    }
                }

                foreach ($studentsToAdd as $studentToAdd) {
                    // extract and bind sanitized data
                    $studentPseudo = $studentToAdd['apprenant'];
                    $studentPassword = $studentToAdd['mot_de_passe'];

                    // create the user
                    $user = new User();
                    $user->setSurname('surname');
                    $user->setFirstname('firstname');
                    $user->setPseudo($studentPseudo);
                    $user->setPassword($studentPassword);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    // retrieve the las insert Id for next query
                    //$user->setId($user->getId());

                    // create the classroomUser to insert in user_classroom_users
                    $classroomUser = new ClassroomUser($user);
                    $classroomUser->setGarId(null);
                    $classroomUser->setSchoolId(null);
                    $classroomUser->setIsTeacher(false);
                    $classroomUser->setMailTeacher(NULL);
                    $this->entityManager->persist($classroomUser);

                    // retrieve the classroom by its link
                    $classroom = $this->entityManager
                        ->getRepository('Classroom\Entity\Classroom')
                        ->findOneBy(array('link' => $classroomLink));

                    // get retro attributed activities if any
                    $classroomRetroAttributedActivities = $this->entityManager
                        ->getRepository(ActivityLinkClassroom::class)
                        ->getRetroAttributedActivitiesByClassroom($classroom);
                
                    // some retro attributed activities found, add them to the student
                    if($classroomRetroAttributedActivities){
                        $this->entityManager->getRepository(ActivityLinkUser::class)
                            ->addRetroAttributedActivitiesToStudent($classroomRetroAttributedActivities,$user);
                    }

                    // create the link between the user and its classroom to be stored in classroom_activities_link_classroom_users
                    $classroomLinkUser = new ClassroomLinkUser($user, $classroom);
                    $classroomLinkUser->setRights(0);
                    $this->entityManager->persist($classroomLinkUser);
                }
                $this->entityManager->flush();
                return true;
            },
            'get_teachers_by_classroom' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "getTeachersByClassroomNotAuthenticated"];

                // bind incoming data
                $classroomLink = !empty($_POST['classroom']) ? htmlspecialchars(strip_tags(trim($_POST['classroom']))) : '';
                $userId = intval($_SESSION['id']);
                if (empty($classroomLink)) return array('errorType' => 'classroomLinkMissing');

                // check if the current student belong to the classroom or return an error
                $student = $this->entityManager
                    ->getRepository(ClassroomLinkUser::class)
                    ->findOneBy(array(
                        'user' => $userId,
                        'rights' => 0
                    ));
                if (!$student) return array('errorType' => 'studentDoesNotBelongToClassroom');

                // get the classroom
                $studyGroup = $this->entityManager
                    ->getRepository('Classroom\Entity\Classroom')
                    ->findOneBy(array('link' => $classroomLink));


                return $this->entityManager
                    ->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array(
                        "rights" => 2,
                        "classroom" => $studyGroup->getId()
                    ));
            },
            'get_by_user' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "getByUserNotAuthenticated"];

                $userId = intval($_SESSION['id']);

                $user = $this->entityManager->getRepository('User\Entity\User')
                    ->findOneBy(array("id" => $userId));
                return $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findOneBy(array("user" => $user->getId()));
            },
            /*
            // @ToBeDeleted
            // last check => october 2021
            'get_changes_for_teacher' => function () {
                $changedStudyGroups = false;
                $classrooms = [];
                $listNames = '';
                $studyGroups = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array('user' => $this->user));
                foreach ($studyGroups as $s) {
                    if ($s->getClassroom()->getIsChanged() == true) {
                        $changedStudyGroups = true;
                    }
                }
                if ($changedStudyGroups == true) {
                    $studyGroups = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                        ->findBy(array('user' => $this->user));
                    foreach ($studyGroups as $s) {
                        $students = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                            ->getAllStudentsInClassroom($s->getClassroom()->getId(), 0);
                        $classrooms[] = array("classroom" => $s->getClassroom(), "students" => $students);
                        if ($s->getClassroom()->getIsChanged() == true) {
                            $classroom = $s->getClassroom();
                            $classroom->setIsChanged(false);
                            $listNames .= $classroom->getName() . "\n";
                            $this->entityManager->persist($classroom);
                        }
                    }
                    $value = ['classrooms' => $classrooms, 'listNames' => $listNames];
                    $this->entityManager->flush();
                    return $value;
                }
                return false;
            }, */
            /* 
            // @ToBeDeleted
            // last check => october 2021
            'remove_users' => function ($data) {
                foreach ($data['users'] as $user) {
                    $linkClassroomUserToGroup = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                        ->findOneBy(array("user" => $user['id'], "studyGroup" => $data['studyGroupId']));

                    $linkClassroomUserToGroup = $this->entityManager->merge($linkClassroomUserToGroup);
                    $this->entityManager->remove($linkClassroomUserToGroup);
                }
                $this->entityManager->flush();
                return true; //synchronized

            },  */
            /* 
            // @ToBeDeleted
            // last check => october 2021
            'get_student_activities_by_classroom' => function ($data) {
                $activities = [];
                $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findBy(array("link" => $data['classroom']));
                $users = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array("id_classroom" => $classroom->getId(), "rights" => 0));
                foreach ($users as $u) {
                    $activities[] = ["user" => $u, "activities" => $this->entityManager->getRepository('Classroom\Entity\TutorialPartLinkClassroomUser')
                        ->findBy(array("id_classroom_user" => $u->getId()))];
                }
                return $activities;
            }, */
            /* 
            * @ToBeRemoved
            * this method no called                 
            'get_by_classroom' => function ($data) {
                
                
                 $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                     ->findBy(array("link" => $data['classroom']));
 
                 return $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                     ->findBy(array("classroom" => $classroom->getId(), "rights" => 0));
 
             },  */
        );
    }

    /* private function addRetroAttributedActivitiesToStudentIfAny($classroom,$user){
        $classroomRetroAttributedActivities = $this->entityManager
            ->getRepository(ActivityLinkClassroom::class)
            ->getRetroAttributedActivitiesByClassroom($classroom);
        
        if($classroomRetroAttributedActivities){
            foreach($classroomRetroAttributedActivities as $classroomRetroAttributedActivity){
                $activity = $classroomRetroAttributedActivity->getActivity();
                $dateBegin = $classroomRetroAttributedActivity->getDateBegin();
                $dateEnd = $classroomRetroAttributedActivity->getDateEnd();
                $evaluation = $classroomRetroAttributedActivity->getEvaluation();
                $autocorrection = $classroomRetroAttributedActivity->getAutocorrection();
                $introduction = $classroomRetroAttributedActivity->getIntroduction();
                $reference = $classroomRetroAttributedActivity->getReference();
                $commentary = $classroomRetroAttributedActivity->getCommentary();

                $course = $classroomRetroAttributedActivity->getCourse();
                $coefficient = $classroomRetroAttributedActivity->getCoefficient();
                $linkActivityToUser = new ActivityLinkUser(
                    $activity, 
                    $user, 
                    $dateBegin,  
                    $dateEnd, 
                    $evaluation, 
                    $autocorrection, 
                    "", 
                    $introduction, 
                    $reference,
                    $commentary
                );
                if($course){
                    $linkActivityToUser->setCourse($course);
                }
                $linkActivityToUser->setCoefficient($coefficient);

                $this->entityManager->persist($linkActivityToUser);
                $this->entityManager->flush();
                
            }
        }
            
    } */
}

function passwordGenerator()
{
    $password = '';
    for ($i = 0; $i < 4; $i++) {
        $password .= rand(0, 9);
    }
    return $password;
}


// maybe usefull ? 
// if not admin or premium
///////////////////////////////////
// remove the limitations for CABRI
/*                 if (!$isAllowed) {
    // get the number of students to add and compute the sum with the number of student already registered
    $addedLearnerNumber = count($data['users']);
    $totalLearnerCount = $learnerNumberCheck["learnerNumber"] + $addedLearnerNumber;

    // if the total exceed the limit max, return an error
    if ($totalLearnerCount > 50) {
        return [
            "isUsersAdded" => false,
            "currentLearnerCount" => $learnerNumberCheck["learnerNumber"],
            "addedLearnerNumber" => $addedLearnerNumber
        ];
    }
}

if ($learnerNumberCheck['isPremium']) {
    // get the number of students to add and compute the sum with the number of student already registered
    $addedLearnerNumber = count($data['users']);
    $totalLearnerCount = $learnerNumberCheck["learnerNumber"] + $addedLearnerNumber;

    // if the total exceed the limit max, return an error
    if ($totalLearnerCount > 400) {
        return [
            "isUsersAdded" => false,
            "currentLearnerCount" => $learnerNumberCheck["learnerNumber"],
            "addedLearnerNumber" => $addedLearnerNumber
        ];
    }
} */

// end remove the limitations for CABRI
/////////////////////////////////////////



// Groups and teacher limitation per application
// if not admin or premium
///////////////////////////////////
// remove the limitations for CABRI
/*                 if (!$isAllowed) {
    // get the number of students to add and compute the sum with the number of student already registered
    $addedLearnerNumber = count($data['users']);
    $totalLearnerCount = $learnerNumberCheck["learnerNumber"] + $addedLearnerNumber;

    // if the total exceed the limit max, return an error
    if ($totalLearnerCount > 50) {
        return ["isUsersAdded" => false, "currentLearnerCount" => $learnerNumberCheck["learnerNumber"], "addedLearnerNumber" => $addedLearnerNumber];
    }
}

if ($learnerNumberCheck['isPremium']) {
    // get the number of students to add and compute the sum with the number of student already registered
    $addedLearnerNumber = count($data['users']);
    $totalLearnerCount = $learnerNumberCheck["learnerNumber"] + $addedLearnerNumber;

    // if the total exceed the limit max, return an error
    if ($totalLearnerCount > 400) {
        return ["isUsersAdded" => false, "currentLearnerCount" => $learnerNumberCheck["learnerNumber"], "addedLearnerNumber" => $addedLearnerNumber];
    }
} */
// end remove the limitations for CABRI
/////////////////////////////////////////
/**
 * End of learner number limiting
 */
