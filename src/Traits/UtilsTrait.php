<?php
namespace Classroom\Traits;

use Classroom\Entity\Classroom;
use Classroom\Entity\ClassroomLinkUser;


trait UtilsTrait {

    public function manageDemoStudentPseudo() {
        $demoStudent = $this->envVariables['VS_DEMOSTUDENT'];

        if (empty($this->envVariables['VS_DEMOSTUDENT'])) {
            $demoStudent = 'demostudent';
        }
        
        if (str_contains($demoStudent, '"')) {
            $demoStudent = str_replace('"', '', $demoStudent);
        }

        //$this->FixForDemoStudentBug();

        $demoStudentToUpdate = $this->entityManager->getRepository(ClassroomLinkUser::class)->getDemoStudentWithWrongPseudo($demoStudent);
        if ($demoStudentToUpdate) {
            foreach ($demoStudentToUpdate as $value) {
                if (strlen($value->getPassword()) > 4) {
                    $value->setPseudo($demoStudent);
                    $this->entityManager->persist($value);
                    $this->entityManager->flush();
                }
            }
        }
        return $demoStudent;
    }

    // Returns the classroom only if the session user is its teacher
    public function findTaughtClassroom($link) {
        $classroom = $this->entityManager->getRepository(Classroom::class)->findOneBy(['link' => $link]);
        if (!$classroom) return null;

        $teacherLink = $this->entityManager->getRepository(ClassroomLinkUser::class)->findOneBy([
            'user' => intval($_SESSION['id']),
            'classroom' => $classroom->getId(),
            'rights' => 2
        ]);
        return $teacherLink ? $classroom : null;
    }

    public function findTaughtClassroomIds() {
        $teacherLinks = $this->entityManager->getRepository(ClassroomLinkUser::class)->findBy([
            'user' => intval($_SESSION['id']),
            'rights' => 2
        ]);
        return array_map(function ($link) {
            return $link->getClassroom()->getId();
        }, $teacherLinks);
    }

    public function findStudentIds(array $classroomIds) {
        if (empty($classroomIds)) return [];
        $studentLinks = $this->entityManager->getRepository(ClassroomLinkUser::class)->findBy([
            'classroom' => $classroomIds,
            'rights' => 0
        ]);
        return array_map(function ($link) {
            return $link->getUser()->getId();
        }, $studentLinks);
    }

    // The student or one of their teachers
    public function canAccessStudentActivity($activityLinkUser) {
        $studentId = $activityLinkUser->getUser()->getId();
        if ($studentId == intval($_SESSION['id'])) return true;
        return in_array($studentId, $this->findStudentIds($this->findTaughtClassroomIds()));
    }


/*     public function FixForDemoStudentBug() {
        $demoStudent = $this->envVariables['VS_DEMOSTUDENT'];

        if (empty($this->envVariables['VS_DEMOSTUDENT'])) {
            $demoStudent = 'demostudent';
        }
        
        if (str_contains($demoStudent, '"')) {
            $demoStudent = str_replace('"', '', $demoStudent);
        }

        $random = substr(str_shuffle(str_repeat($x='0123456789', ceil(4/strlen($x)) )),1,4);

        $demoStudentToUpdate = $this->entityManager->getRepository(ClassroomLinkUser::class)->getAllDemoStudent($demoStudent);
        if ($demoStudentToUpdate) {
            foreach ($demoStudentToUpdate as $value) {
                if (strlen($value->getPassword()) === 4) {
                    $value->setPseudo("Eleve-$random");
                    $value->setSurname("Eleve-$random");
                    $this->entityManager->persist($value);
                    $this->entityManager->flush();
                }
            }
        }
    } */
}

