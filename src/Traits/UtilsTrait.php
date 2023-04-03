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
}