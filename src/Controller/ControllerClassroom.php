<?php

namespace Classroom\Controller;

use Dotenv\Dotenv;
use User\Entity\User;
use User\Entity\Regular;
use User\Entity\ClassroomUser;
use Classroom\Entity\Classroom;
use Classroom\Entity\ClassroomLinkUser;

/**
 * @ THOMAS MODIF line just below
 */

use DAO\RegularDAO;

class ControllerClassroom extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'get_by_user' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "classroomsNotRetrievedNotAuthenticated"];

                // sanitize data
                $userId = intval($_SESSION['id']);

                // get all classrooms where the user is the teacher (rights = 2)
                $classrooms = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array("user" => $userId, 'rights' => 2));

                //no classrooms found, return an empty array    
                if (!$classrooms) {
                    return $classrooms = [];
                }

                $demoStudent = htmlspecialchars(strip_tags(trim($this->envVariables['VS_DEMOSTUDENT'])));
                // some classrooms found, push them into $classrooms array
                $i = 0;
                foreach ($classrooms as $classroom) {
                    $students = $this->entityManager
                        ->getRepository('Classroom\Entity\ClassroomLinkUser')
                        ->getAllStudentsInClassroom($classroom->getClassroom()->getId(), 0, $demoStudent);
                    
                    $classrooms[$i] = array("classroom" => $classroom->getClassroom(), "students" => $students);
                    $i++;
                }

                return $classrooms;
            },
            'get_by_link' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // bind and sanitize incoming data
                $link = !empty($_POST['link'])
                    ? htmlspecialchars(strip_tags(trim($_POST['link'])))
                    : '';

                // no link received, return an error
                if (empty($link)) {
                    return ['exist' => false, 'errorLinkNotExists' => true];
                }
                $classExist = $this->entityManager->getRepository('Classroom\Entity\Classroom')->findOneBy(array("link" => $link));
                if ($classExist) {
                    $classRoom = ['exist' => true, 'isBlocked' => $classExist->getIsBlocked(), 'name' => $classExist->getName(), 'link' => $classExist->getLink()];
                } else {
                    $classRoom = ['exist' => false];
                }

                //no error, we can process the data and return the result
                return $classRoom;
            },
            'add' => function () {
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];
                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "classroomsAddNotAuthenticated"];

                // bind and sanitize incoming data, boolean "isBlocked" is received has a string "true"
                $currentUserId = $_SESSION["id"];
                $classroomName = !empty($_POST['name']) ? htmlspecialchars(strip_tags(trim($_POST['name']))) : '';
                $school = !empty($_POST['school']) ? htmlspecialchars(strip_tags(trim($_POST['school']))) : '';
                $isBlocked = !empty($_POST['isBlocked']) ? htmlspecialchars(strip_tags(trim($_POST['isBlocked']))) : false;          
                $demoStudent = !empty($this->envVariables['VS_DEMOSTUDENT'])
                    ? htmlspecialchars(strip_tags(trim(strtolower($this->envVariables['VS_DEMOSTUDENT']))))
                    : 'demostudent';

                // get user "roles"
                $isPremium = RegularDAO::getSharedInstance()->isTester($currentUserId);
                $isAdmin = RegularDAO::getSharedInstance()->isAdmin($currentUserId);

                // an error found, classroomName id required return the error
                if (empty($classroomName)) return array('errorType' => 'ClassroomNameInvalid');

                // get all classrooms where the user is teacher
                $classrooms = $this->entityManager
                    ->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array(
                        "user" => $currentUserId,
                        "rights" => 2

                    ));

                $nbClassroom = 0;
                foreach ($classrooms as $classroom) {
                    $nbClassroom++;
                }

                $learnerNumberCheck = [
                    "idUser" => $currentUserId,
                    "isPremium" => $isPremium,
                    "isAdmin" => $isAdmin,
                    "classroomNumber" => $nbClassroom
                ];

                // set the $isAllowed flag to true if the current user is admin or premium to allow them more possibilities
                $isAllowed = $learnerNumberCheck["isAdmin"] || $learnerNumberCheck["isPremium"];

                ///////////////////////////////////
                // remove the limitations for CABRI
                if (!$isAllowed) {
                    if ($nbClassroom + 1 > 1) {
                        // the current classroom number is reached, return an error
                        return [
                            "isClassroomAdded" => false,
                            "classroomNumberLimit" => $nbClassroom
                        ];
                    }
                }

                // check the classroom number for premium users 
                if ($learnerNumberCheck['isPremium']) {
                    if ($nbClassroom + 1 > 20) {

                        // the current classroom number is reached, return an error
                        return [
                            "isClassroomAdded" => false,
                            "classroomNumberLimit" => $nbClassroom
                        ];
                    }
                }
                // end remove the limitations for CABRI
                ///////////////////////////////////////
                /**
                 * End of learner number limiting
                 */

                $uniqueLink = $this->generateUniqueClassroomLink();

                $studyGroup = new Classroom();
                $studyGroup->setName($classroomName);
                $studyGroup->setSchool($school);
                $studyGroup->setIsBlocked($isBlocked);
                $studyGroup->setLink($uniqueLink);
                $this->entityManager->persist($studyGroup);

                //add the teacher to the classroom
                $user = $this->entityManager->getRepository('User\Entity\User')
                    ->findOneBy(array("id" => $currentUserId));
                $linkteacherToGroup = new ClassroomLinkUser($user, $studyGroup);
                $linkteacherToGroup->setRights(2);
                $this->entityManager->persist($linkteacherToGroup);

                //create demoStudent account and add it to the classroom
                $user = new User();
                $user->setFirstName("élève");
                $user->setSurname("modèl");
                $user->setPseudo($demoStudent);
                $password = passwordGenerator();
                $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $classroomUser = new ClassroomUser($user);
                $classroomUser->setGarId(null);
                $classroomUser->setSchoolId(null);
                $classroomUser->setIsTeacher(false);
                $classroomUser->setMailTeacher(NULL);
                $this->entityManager->persist($classroomUser);

                $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findBy(array("link" => $studyGroup->getLink()))[0];
                $linkteacherToGroup = new ClassroomLinkUser($user, $classroom);
                $linkteacherToGroup->setRights(0);
                $this->entityManager->persist($linkteacherToGroup);

                //save in database
                $this->entityManager->flush();
                return $studyGroup; //synchronized

            },
            'update' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "classroomUpdateNotAuthenticated"];

                // bind and sanitize incoming data, hint => isBlocked is received as a string type 
                $name = !empty($_POST['name']) ? htmlspecialchars(strip_tags(trim($_POST['name']))) : '';
                $school = !empty($_POST['school']) ? htmlspecialchars(strip_tags(trim($_POST['school']))) : '';
                $link = !empty($_POST['link']) ? htmlspecialchars(strip_tags(trim($_POST['link']))) : '';
                $isBlocked = !empty($_POST['isBlocked']) ? htmlspecialchars(strip_tags(trim($_POST['isBlocked']))) : '';

                // some errors found, return error
                if (empty($name)) return array('errorType' => 'ClassroomNameInvalid');

                // no errors found, we can proceed the data
                //retrieve the classroom by its link
                $classroom =  $this->entityManager
                    ->getRepository('Classroom\Entity\Classroom')
                    ->findOneBy(array("link" => $link));

                $classroom->setName($name);
                $classroom->setSchool($school);
                $classroom->setIsBlocked($isBlocked);

                // commented setLink to avoid link classroom link to change
                //$classroom->setLink();

                // save data in classrooms table
                $this->entityManager->persist($classroom);
                $this->entityManager->flush();
                return $classroom; //synchronized

            },
            'delete' => function () {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "classroomDeleteNotAuthenticated"];

                // sanitize data
                $userId = intval($_SESSION['id']);

                // bind and sanitize incoming data
                $classroomLink = isset($_POST['link'])
                    ? htmlspecialchars(strip_tags(trim($_POST['link'])))
                    : '';

                // no classroom link received, return an error
                if (empty($classroomLink)) return array('errorClassroomLinkEmpty' => true);

                // get the classroom 
                $classroomFound = $this->entityManager
                    ->getRepository('Classroom\Entity\Classroom')
                    ->findOneBy(array("link" => $classroomLink));

                // no classroom found, return an error
                if (!$classroomFound) return array('errorClassroomNotExists' => true);

                // check if the user is the teacher
                $teacherFound = $this->entityManager
                    ->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findOneBy(array(
                        'user' => $userId,
                        'rights' => 2
                    ));

                // the user is not the teacher of this classroom, return an error
                if (!$teacherFound) return array('errorTeacherNotExists' => true);

                // the current $classroom is not related to the GAR

                // start cleaning the db
                // get all students of the classroom
                $classroomStudentsData = $this->entityManager
                    ->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array(
                        'classroom' => $classroomFound->getId(),
                        'rights' => 0
                    ));

                // delete students from user_classroom_users 
                foreach ($classroomStudentsData as $studentData) {
                    // check if student exists
                    $classroomStudentExists = $this->entityManager
                        ->getRepository('User\Entity\User')
                        ->findOneBy(array(
                            'id' => $studentData->getUser()->getId()
                        ));


                    if ($classroomStudentExists) {
                        // delete the student
                        $this->entityManager->remove($classroomStudentExists);
                    }


                    // get all records from classroom_users_link_classrooms
                    $userActivitiesFound = $this->entityManager
                        ->getRepository('Classroom\Entity\ActivityLinkUser')
                        ->findBy(array(
                            'user' => $studentData->getUser()
                        ));

                    if ($userActivitiesFound) {
                        // delete each record found
                        foreach ($userActivitiesFound as $userActivity) {
                            $this->entityManager->remove($userActivity);
                        }
                    }
                }

                // set the data to return
                $name = $classroomFound->getName();
                $link = $classroomFound->getLink();
                $garCode = $classroomFound->getGarCode();

                // remove the classroom 
                $this->entityManager->remove($classroomFound);

                // delete all necessary records in each table and clear doctrine memory
                $this->entityManager->flush();
                $this->entityManager->clear();

                return [
                    'name' => $name,
                    'link' => $link,
                    'garCode' => $garCode
                ];  
            },
            'get_teacher_account' => function () {
                $_SESSION['id'] = $_SESSION['idProf'];
                unset($_SESSION['idProf']);
                return true;
            },
            'get_demo_student_account' => function ($data) {

                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "getDemoStudentAccountNotAuthenticated"];

                // bind and sanitize incoming data
                $link = !empty($_POST['link'])
                    ? htmlspecialchars(strip_tags(trim($_POST['link'])))
                    : '';

                $demoStudent = !empty($this->envVariables['VS_DEMOSTUDENT'])
                    ? htmlspecialchars(strip_tags(trim(strtolower($this->envVariables['VS_DEMOSTUDENT']))))
                    : 'demostudent';

                // no link provided, return an error
                if (empty($link)) return array('errorClassroomLinkInvalid' => true);

                // retrieve the classroom by its link
                $classroom = $this->entityManager
                    ->getRepository('Classroom\Entity\Classroom')
                    ->findOneBy(array('link' => $link));

                // get all users registered this classroom
                $userLinkClassroom = $this->entityManager
                    ->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array('classroom' => $classroom->getId()));

                /** 
                 * @UNCLEAR 
                 * we are looping through all users including the teacher but we are looking for a specific account => demoStudent account
                 * last check september 2021
                 */
                foreach ($userLinkClassroom as $u) {
                    if ($u->getUser()->getPseudo() == $demoStudent) {
                      
                        // set isFromGar to true based on $this->user received from Routing.php
                         if($this->user['isFromGar'] == true) $_SESSION['isFromGar'] = true;
                        $_SESSION['idProf'] = $_SESSION['id'];
                        $_SESSION['id'] = $u->getUser()->getId();
                        return $_SESSION['id'];
                    }
                }
            },
            'get_auth_link' => function () {
               
                // accept only POST request
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error" => "Method not Allowed"];

                // accept only connected user
                if (empty($_SESSION['id'])) return ["errorType" => "classroomNotAuthenticated"];

                $app = !(empty($_POST['app'])) ? htmlspecialchars(strip_tags(trim($_POST['app']))) : '';
                return $this->processEncrypt($app, $_SESSION['id']);
            },
            'get_file_names' => function () {
                $app_name = !empty($_POST['appName']) ? htmlspecialchars(strip_tags(trim($_POST['appName']))) : '';

                $extension = ['pdf'];
                $filesToReturn = [];
                $files = scandir(__DIR__."/../../../../../classroom/assets/plugins/media/$app_name/");

                foreach ($files as $key => $file) {
                    $path_parts = pathinfo($file);
                    if (in_array($path_parts['extension'], $extension)) {
                        $filesToReturn[] = $file;
                    }
                }

                return $filesToReturn;
            }
        );
    }

    private function processEncrypt($appName, $id) {
        $app_prefix = strtoupper(substr($appName, 0, 4));
        DEFINE('URL', $_ENV[$app_prefix . '_URL']);
        DEFINE('CRYPT_KEY', $_ENV[$app_prefix . '_CRYPT_KEY']);
        DEFINE('CRYPT_IV', $_ENV[$app_prefix . '_CRYPT_IV']);
        DEFINE('CRYPT_ALGO', $_ENV[$app_prefix . '_CRYPT_ALGO']);

        return $this->get_auth_url($id);
    }

    private function encrypt($account_id) {
        $data = $account_id.'-'.time()*1000;
        $encrypted = strtoupper(bin2hex(openssl_encrypt(
            $data,
            CRYPT_ALGO,
            CRYPT_KEY,
            OPENSSL_RAW_DATA,
            CRYPT_IV
        )));
        return $encrypted;
    }

    private function get_auth_url($account_id){
        return URL.'?token='.$this->encrypt($account_id);
    }

    private function generateUniqueClassroomLink(){
        $alphaNums = "abcdefghijklmnopqrstuvwxyz0123456789";
        do{
            $link = "";
            
            for ($i = 0; $i < 5; $i++) {
                $link .= substr($alphaNums, rand(0, 35), 1);
            }

            $classroomByLinkFound = $this->entityManager
                ->getRepository(Classroom::class)
                ->findOneByLink($link);
        }
        while($classroomByLinkFound);

        return $link;
        
    }
}

function passwordGenerator()
{
    $password = '';
    for ($i = 0; $i < 4; $i++) {
        $password .= rand(0, 9);
    }
    return $password;
}
