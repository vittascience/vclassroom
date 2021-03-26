<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;

class ClassroomLinkUserRepository extends EntityRepository
{
    public function getAllStudentsInClassroom($classroom, $rights)
    {
        $students = $this->_em->createQuery('SELECT u FROM Classroom\Entity\ClassroomLinkUser u WHERE u.classroom = ' . $classroom . 'AND u.rights=' . $rights)
            ->getResult();
        $arrayStudents = [];
        foreach ($students as $s) {
            $activities = $this->_em->createQuery('SELECT t FROM Classroom\Entity\ActivityLinkUser t WHERE t.user = ' . $s->getUser()->getId())
                ->getResult();
            $arrayStudents[] = array('user' => $s->getUser()->jsonSerialize(), 'activities' => $activities, 'pwd' => $s->getUser()->getPassword());
        }
        return $arrayStudents;
    }
}
