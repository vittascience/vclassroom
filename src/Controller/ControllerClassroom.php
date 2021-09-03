<?php

namespace Classroom\Controller;

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
            'get_all' => function () {
                return $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findAll();
            },
            'get_by_user' => function () {

                // accept only POST request
                if($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error"=> "Method not Allowed"];

                // accept only connected user
                if(empty($_SESSION['id'])) return ["errorType"=> "classroomsNotRetrievedNotAuthenticated"];

                // sanitize data
                $userId = intval($_SESSION['id']);

                // get all classrooms where the user is the teacher (rights = 2)
                $classrooms = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array("user" => $userId, 'rights'=> 2));

                //no classrooms found, return an empty array    
                if(!$classrooms){
                    return $classrooms = [];
                }

                // some classrooms found, push them into $classrooms array
                $i = 0;
                foreach ($classrooms as $classroom) {
                    $students = $this->entityManager
                                        ->getRepository('Classroom\Entity\ClassroomLinkUser')
                                        ->getAllStudentsInClassroom($classroom->getClassroom()->getId(), 0);

                    $classrooms[$i] = array("classroom" => $classroom->getClassroom(), "students" => $students);
                    $i++;
                }
                return $classrooms;
            },
            'get_users_and_activities' => function ($data) {
                $students = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->getAllStudentsInClassroom($data['classroom'], 0);

                return $students;
            },
            'get_my_sandbox_projects' => function () {
                $arrayResults = [];
                $sharedProjects = $this->entityManager->getRepository('Interfaces\Entity\ProjectLinkUser')
                    ->findBy(array("user" => $this->user));
                foreach ($sharedProjects as $s) {
                    $arrayResults[] = $s->getProject();
                }
                return [
                    "mine" => $this->entityManager->getRepository('Interfaces\Entity\Project')
                        ->findBy(array("user" => $this->user['id'], "deleted" => false, "activitySolve" => false)),
                    "shared" => $arrayResults
                ];
            },
            'get_by_link' => function ($data) {
                return $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findBy(array("link" => $data['link']));
            },
            'add' => function ($data) {
                /**
                 * Limiting learner number @THOMAS MODIF
                 * Added the possibility for Admins to add more than 1 classroom @MODIF NASER
                 */
                $currentUserId = $this->user["id"];

                $isPremium = RegularDAO::getSharedInstance()->isTester($currentUserId);
                $isAdmin = RegularDAO::getSharedInstance()->isAdmin($currentUserId);

                $classrooms = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array("user" => $currentUserId));
                $nbClassroom = 0;
                foreach ($classrooms as $c) {
                    $nbClassroom++;
                }

                $learnerNumberCheck = [
                    "idUser"=>$currentUserId, 
                    "isPremium"=>$isPremium, 
                    "isAdmin"=> $isAdmin,
                    "classroomNumber"=>$nbClassroom
                ];

                // set the $isAllowed flag to true if the current user is admin or premium to allow them more possibilities
                $isAllowed = $learnerNumberCheck["isAdmin"] || $learnerNumberCheck["isPremium"];

                ///////////////////////////////////
                // remove the limitations for CABRI
                if(!$isAllowed ){
                    if($nbClassroom+1>1){
                        return false;
                    }
                }
                // end remove the limitations for CABRI
                ///////////////////////////////////////
                /**
                 * End of learner number limiting
                 */


                $studyGroup = new Classroom();
                $studyGroup->setName($data['name']);
                $studyGroup->setSchool($data['school']);
                $studyGroup->setIsBlocked($data['isBlocked'] ?? 0);
                $studyGroup->setLink();
                $this->entityManager->persist($studyGroup);
                //add the teacher to the classroom
                $user = $this->entityManager->getRepository('User\Entity\User')
                    ->findOneBy(array("id" => $this->user['id']));
                $linkteacherToGroup = new ClassroomLinkUser($user, $studyGroup);
                $linkteacherToGroup->setRights(2);
                $this->entityManager->persist($linkteacherToGroup);

                //create vittademo account and add it to the classroom
                $user = new User();
                $user->setFirstName("élève");
                $user->setSurname("modèl");
                $user->setPseudo('vittademo');
                $password = passwordGenerator();
                $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
                $lastQuestion = $this->entityManager->getRepository('User\Entity\User')->findOneBy([], ['id' => 'desc']);
                $user->setId($lastQuestion->getId() + 1);
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

            }, 'update' => function ($data) {
                $studyGroup =  $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findBy(array("link" => $data['link']))[0];
                $studyGroup->setName($data['name']);
                $studyGroup->setSchool($data['school']);
                $studyGroup->setIsBlocked($data['isBlocked']);

                // commented setLink to avoid link classroom link to change
                //$studyGroup->setLink();

                $this->entityManager->persist($studyGroup);
                $this->entityManager->flush();
                return $studyGroup; //synchronized

            },
            'delete' => function () {
               
                // accept only POST request
                if($_SERVER['REQUEST_METHOD'] !== 'POST') return ["error"=> "Method not Allowed"];

                // accept only connected user
                if(empty($_SESSION['id'])) return ["errorType"=> "classroomDeleteNotAuthenticated"];

                // sanitize data
                $userId = intval($_SESSION['id']);

                // bind and sanitize incoming data
                $classroomLink = isset($_POST['link']) 
                                    ? htmlspecialchars(strip_tags(trim($_POST['link'])))
                                    :'';
                
                // no classroom link received, return an error
                if(empty($classroomLink)) return array('errorClassroomLinkEmpty'=> true );

                // get the classroom 
                $classroomFound = $this->entityManager
                                    ->getRepository('Classroom\Entity\Classroom')
                                    ->findOneBy(array("link"=> $classroomLink ));

                // no classroom found, return an error
                if(!$classroomFound) return array('errorClassroomNotExists'=> true );

                // check if the user is the teacher
                $teacherFound = $this->entityManager
                                        ->getRepository('Classroom\Entity\ClassroomLinkUser')
                                        ->findOneBy(array(
                                            'user' => $userId,
                                            'rights'=> 2
                                        ));
                
                // the user is not the teacher of this classroom, return an error
                if(!$teacherFound) return array('errorTeacherNotExists'=> true );

                // the current $classroom is not related to the GAR
            
                // start cleaning the db
                // get all students of the classroom
                $classroomStudentsData = $this->entityManager
                                                ->getRepository('Classroom\Entity\ClassroomLinkUser')
                                                ->findBy(array(
                                                    'classroom' => $classroomFound->getId(),
                                                    'rights'=> 0
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
                    
                    if($userActivitiesFound){
                        // delete each record found
                        foreach($userActivitiesFound as $userActivity){
                            $this->entityManager->remove($userActivity);
                        }
                    }
                }

                // set the data to return
                $name = $classroomFound->getName();
                $link = $classroomFound->getLink();
                
                // remove the classroom 
                $this->entityManager->remove($classroomFound);   
                
                // delete all necessary records in each table and clear doctrine memory
                $this->entityManager->flush(); 
                $this->entityManager->clear();
                
                return [
                    'name' => $name,
                    'link' => $link
                ];    
            },
            'get_vittademo_account' => function ($data) {
                $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findOneBy(array('link' => $data['link']));
                $userLinkClassroom = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array('classroom' => $classroom->getId()));
                foreach ($userLinkClassroom as $u) {
                    if ($u->getUser()->getPseudo() == 'vittademo') {
                        $_SESSION['idProf'] = $_SESSION['id'];
                        $_SESSION['id'] = $u->getUser()->getId();
                        return $_SESSION['id'];
                    }
                }

                $user = new User();
                $user->setFirstName("élève");
                $user->setSurname("modèl");
                $user->setPseudo('vittademo');
                $password = passwordGenerator();
                $user->setPassword(password_hash($password, PASSWORD_DEFAULT));
                $lastQuestion = $this->entityManager->getRepository('User\Entity\User')->findOneBy([], ['id' => 'desc']);
                $user->setId($lastQuestion->getId() + 1);
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $classroomUser = new ClassroomUser($user);
                $classroomUser->setGarId(null);
                $classroomUser->setSchoolId(null);
                $classroomUser->setIsTeacher(false);
                $classroomUser->setMailTeacher(NULL);
                $this->entityManager->persist($classroomUser);
                $linkteacherToGroup = new ClassroomLinkUser($user, $classroom);
                $linkteacherToGroup->setRights(0);
                $this->entityManager->persist($linkteacherToGroup);
                $_SESSION['idProf'] = $_SESSION['id'];
                $_SESSION['id'] = $lastQuestion->getId() + 1;
                return $_SESSION['id'];
            },
            'get_teacher_account' => function () {
                $_SESSION['id'] = $_SESSION['idProf'];
                unset($_SESSION['idProf']);
                return true;
            }
        );
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
