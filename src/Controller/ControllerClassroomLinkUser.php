<?php

namespace Classroom\Controller;

use Classroom\Entity\ClassroomLinkUser;
use User\Entity\User;
/**
 * @ THOMAS MODIF 2 lines just below
 */
use DAO\RegularDAO;
use models\Regular;
use User\Entity\ClassroomUser;

class ControllerClassroomLinkUser extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'add_users' => function ($data) {
                /**
                 * Limiting learner number @THOMAS MODIF
                 * Added premium and Admin check @NASER MODIF
                 * @var $data[users] is an array of users submitted
                 * @var $data[classroom] is a string containing the classroom link 
                 */

                // get the currently logged user (professor, admin, premium,...) 
                $currentUserId = $this->user["id"];

                // get the statuses for the current user
                $isPremium = RegularDAO::getSharedInstance()->isTester($currentUserId);
                $isAdmin = RegularDAO::getSharedInstance()->isAdmin($currentUserId);

                // get all classrooms for the current user
                $classrooms = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array("user" => $currentUserId));
                    
                // initiate the $nbApprenants counter and loop through each classrooms
                $nbApprenants = 0;
                foreach ($classrooms as $c) {
                    $students = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                        ->getAllStudentsInClassroom($c->getClassroom()->getId(), 0);
                    
                    // add the current classroom users number and increase the total
                    $nbApprenants += count($students);
                }

                $learnerNumberCheck = [
                    "idUser"=>$currentUserId, 
                    "isPremium"=>$isPremium, 
                    "isAdmin"=> $isAdmin,
                    "learnerNumber"=>$nbApprenants
                ];
                
                // set the $isAllowed flag to true if the current user is admin or premium
                $isAllowed = $learnerNumberCheck["isAdmin"] || $learnerNumberCheck["isPremium"];

                // if not admin or premium
                ///////////////////////////////////
                // remove the limitations for CABRI
                if(!$isAllowed ){
                    // get the number of students to add and compute the sum with the number of student already registered
                    $addedLearnerNumber = count($data['users']);
                    $totalLearnerCount = $learnerNumberCheck["learnerNumber"] + $addedLearnerNumber;

                    // if the total exceed the limit max, return an error
                    if($totalLearnerCount > 50 ){
                        return ["isUsersAdded"=>false, "currentLearnerCount"=>$learnerNumberCheck["learnerNumber"], "addedLearnerNumber"=>$addedLearnerNumber];
                    }
                }
               
                // end remove the limitations for CABRI
                /////////////////////////////////////////
                /**
                 * End of learner number limiting
                 */
                /**
                 * check that teacher does not add a vittademo user @MODIF naser
                 */
                    if(in_array('vittademo',array_map('strtolower',$data['users']))){
                        return [
                            "isUsersAdded"=>false, 
                            "errorType"=> "reservedNickname",
                            "currentNickname"=> "vittademo"
                        ];
                }
               /**
                * 
                */


                $passwords = [];
                foreach ($data['users'] as $u) {
                    $user = new User();
                    $user->setSurname('surname');
                    $user->setFirstname('firstname');
                    $user->setPseudo($u);
                    $password = passwordGenerator();
                    $passwords[] = $password;
                    $user->setPassword($password);
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

                    $studyGroup = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                        ->findOneBy(array('link' => $data['classroom']));
                    $linkClassroomUserToGroup = new ClassroomLinkUser($user, $studyGroup);
                    $linkClassroomUserToGroup->setRights(0);
                    $this->entityManager->persist($linkClassroomUserToGroup);
                }
                if (isset($data['existingUsers']) && count($data['existingUsers']) > 0) {
                    foreach ($data['existingUsers'] as $eu) {
                        $existingUser = $this->entityManager->getRepository('User\Entity\User')->findOneBy(['id' => $eu['id']]);
                        $existingUser->setPseudo($eu['pseudo']);
                        $this->entityManager->persist($existingUser);
                    }
                }

                $this->entityManager->flush();

                return ["isUsersAdded"=>true, "passwords"=>$passwords];
            },
            'add_users_by_csv' => function ($data) {
                foreach ($data['users'] as $u) {
                    $user = new User();
                    $user->setSurname('surname');
                    $user->setFirstname('firstname');
                    $user->setPseudo($u['apprenant']);
                    if (isset($u['mot de passe'])) {
                        $password = $u['mot de passe'];
                    } else {
                        $password = passwordGenerator();
                    }
                    $user->setPassword($password);
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

                    $studyGroup = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                        ->findOneBy(array('link' => $data['classroom']));
                    $linkClassroomUserToGroup = new ClassroomLinkUser($user, $studyGroup);
                    $linkClassroomUserToGroup->setRights(0);
                    $this->entityManager->persist($linkClassroomUserToGroup);
                }
                $this->entityManager->flush();
                return true;
            },
            'get_teachers_by_classroom' => function ($data) {
                $studyGroup = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findOneBy(array('link' => $data['classroom']));
                return $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array("rights" => 2, "classroom" => $studyGroup->getId()));
            },
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
            },
            'remove_users' => function ($data) {
                foreach ($data['users'] as $user) {
                    $linkClassroomUserToGroup = $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                        ->findOneBy(array("user" => $user['id'], "studyGroup" => $data['studyGroupId']));

                    $linkClassroomUserToGroup = $this->entityManager->merge($linkClassroomUserToGroup);
                    $this->entityManager->remove($linkClassroomUserToGroup);
                }
                $this->entityManager->flush();
                return true; //synchronized

            }, 'get_by_classroom' => function ($data) {
                $classroom = $this->entityManager->getRepository('Classroom\Entity\Classroom')
                    ->findBy(array("link" => $data['classroom']));
                return $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findBy(array("classroom" => $classroom->getId(), "rights" => 0));
            }, 'get_by_user' => function ($data) {
                $user = $this->entityManager->getRepository('User\Entity\User')
                    ->findOneBy(array("id" => $data['user']));
                return $this->entityManager->getRepository('Classroom\Entity\ClassroomLinkUser')
                    ->findOneBy(array("user" => $user->getId()));
            }, 'get_student_activities_by_classroom' => function ($data) {
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
