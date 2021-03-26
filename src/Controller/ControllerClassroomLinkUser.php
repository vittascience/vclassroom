<?php

namespace Classroom\Controller;

use Classroom\Entity\ClassroomLinkUser;
use User\Entity\User;
use User\Entity\ClassroomUser;

class ControllerClassroomLinkUser extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);
        $this->actions = array(
            'add_users' => function ($data) {
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
                return $passwords; //synchronized

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
