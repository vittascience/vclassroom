<?php
namespace Classroom\Traits;

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

        $demoStudentToUpdate = $this->entityManager->getRepository(ClassroomLinkUser::class)->getDemoStudentWithWrongPseudo($demoStudent);
        if ($demoStudentToUpdate) {
            foreach ($demoStudentToUpdate as $value) {
                $value->setPseudo($demoStudent);
                $this->entityManager->persist($value);
                $this->entityManager->flush();
            }
        }
        return $demoStudent;
    }


    public function FixForDemoStudentBug() {
        $demoStudent = $this->envVariables['VS_DEMOSTUDENT'];

        if (empty($this->envVariables['VS_DEMOSTUDENT'])) {
            $demoStudent = 'demostudent';
        }
        
        if (str_contains($demoStudent, '"')) {
            $demoStudent = str_replace('"', '', $demoStudent);
        }

        $random = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(4/strlen($x)) )),1,4);

        $demoStudentToUpdate = $this->entityManager->getRepository(ClassroomLinkUser::class)->getAllDemoStudent($demoStudent);
        if ($demoStudentToUpdate) {
            foreach ($demoStudentToUpdate as $value) {
                if (strlen($value->getPassword()) === 4) {
                    $value->setPseudo("Élève $random");
                    $value->setSurname("Élève$random");
                    $this->entityManager->persist($value);
                    $this->entityManager->flush();
                }
            }
        }
    }
}

