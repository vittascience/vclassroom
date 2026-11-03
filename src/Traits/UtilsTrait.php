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

