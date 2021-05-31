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
                $classrooms = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array("user" => $this->user));
                $i = 0;
                foreach ($classrooms as $c) {
                    $students = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                        ->getAllStudentsInClassroom($c->getClassroom()->getId(), 0);
                    $classrooms[$i] = array("classroom" => $c->getClassroom(), "students" => $students);
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

                if(!$isAllowed ){
                    if($nbClassroom+1>1){
                        return false;
                    }
                }
                /**
                 * End of learner number limiting
                 */


                $studyGroup = new Classroom();
                $studyGroup->setName($data['name']);
                $studyGroup->setSchool($data['school']);
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
                $studyGroup->setLink();
                $this->entityManager->persist($studyGroup);
                $this->entityManager->flush();
                return $studyGroup; //synchronized

            },
            'delete' => function ($data) {
                $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findOneBy(array('link' => $data['link']));
                $users = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array('classroom' => $classroom));
                foreach ($users as $u) {
                    $classroomUser = $this->entityManager->getRepository('User\Entity\ClassroomUser')
                        ->findOneBy(array('id' => $u->getUser()->getId()));
                    if ($classroomUser) {
                        $this->entityManager->remove($u->getUser());
                    }
                }
                $name = $classroom->getName();
                $link = $classroom->getLink();
                $this->entityManager->remove($classroom);
                $this->entityManager->flush();
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
