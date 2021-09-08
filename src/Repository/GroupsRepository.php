<?php

namespace Classroom\Repository;

use Classroom\Entity\Groups;
use Classroom\Entity\Applications;
use Classroom\Entity\GroupsLinkApplications;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Groups|null find($id, $lockMode = null, $lockVersion = null)
 * @method Groups|null findOneBy(array $criteria, array $orderBy = null)
 * @method Groups[]    findAll()
 * @method Groups[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupsRepository extends EntityRepository
{
    public function getPanelGroupInfos($sort, $page, $groupspp) {

        $orderby = "";
        if ($sort == 0)
            $orderby = "g.name";
        else 
            $orderby = "g.description";

        $Groups = $this->getEntityManager()
        ->createQueryBuilder()->select("g.id, g.name, g.link, g.description")
            ->from(Groups::class,'g')
            ->orderBy($orderby)
            ->getQuery();

        /**
         * Mise en place de la pagination
         */
        $paginator = new Paginator($Groups);
        // Pourquoi bug sans ça ? 
        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems/$groupspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()->setFirstResult($groupspp*($currentPage-1))->setMaxResults($groupspp)->getScalarResult();

        // Récupère les applications liées à des groupes
        $ApplicationsOfGroups = $this->getEntityManager()
        ->createQueryBuilder()->select("a.id AS application_id, a.image AS application_image, g.id AS group_id")
            ->from(Applications::class,'a')
            ->innerJoin(GroupsLinkApplications::class,'gla', Join::WITH, 'a.id = gla.application')
            ->innerJoin(Groups::class,'g', Join::WITH, 'g.id = gla.group')
            ->getQuery()
            ->getScalarResult();


         // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($records as $key => $value) {
            foreach ($ApplicationsOfGroups as $key2 => $value2) {
                if ((int)$value['id'] == (int)$value2['group_id']) {
                    $records[$key]['applications'][] = ['id' => $value2['application_id'], 'image' => $value2['application_image']];
                }
            }
        }

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    }

    public function getGroupInfo($group_id) {
        $Group = $this->getEntityManager()
                        ->createQueryBuilder()->select("g.id, g.name, g.description, g.link")
                        ->from(Groups::class,'g')
                        ->where('g.id = :id ')
                        ->setParameter('id',$group_id)
                        ->getQuery()
                        ->getResult();

        $GroupApplications = $this->getEntityManager()
                                    ->createQueryBuilder()->select("IDENTITY(gla.application) as application_id, gla.dateBegin as date_begin, gla.dateEnd as date_end")
                                    ->from(GroupsLinkApplications::class,'gla')
                                    ->where('gla.group = :id')
                                    ->setParameter('id',$group_id)
                                    ->getQuery()
                                    ->getScalarResult();

        // Récupère les applications liées au groupe, le [0] est ici car la variable $Group est un array de 1 element
        $Group[0]['applications'] = $GroupApplications;

        return $Group;
    }

    public function searchGroup(String $string, Int $page, Int $groupspp) {

        $Groups = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select("g.id, g.name,g.description")
                        ->from(Groups::class,'g')
                        ->where('g.name LIKE :name OR g.description LIKE :name')
                        ->setParameter('name','%' . $string . '%')
                        ->getQuery();

        $paginator = new Paginator($Groups);
        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems/$groupspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
                            ->setFirstResult($groupspp*($currentPage-1))
                            ->setMaxResults($groupspp)
                            ->getScalarResult();


        // Récupère les applications liées à des groupes
        $ApplicationsOfGroups = $this->getEntityManager()
                                ->createQueryBuilder()
                                ->select("a.id AS application_id, a.image AS application_image, g.id AS group_id")
                                ->from(Applications::class,'a')
                                ->innerJoin(GroupsLinkApplications::class,'gla', Join::WITH, 'a.id = gla.application')
                                ->innerJoin(Groups::class,'g', Join::WITH, 'g.id = gla.group')
                                ->getQuery()
                                ->getScalarResult();


         // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($records as $key => $value) {
            foreach ($ApplicationsOfGroups as $key2 => $value2) {
                if ((int)$value['id'] == (int)$value2['group_id']) {
                    $records[$key]['applications'][] = ['id' => $value2['application_id'], 'image' => $value2['application_image']];
                }
            }
        }

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    } 

    /**
     * 'StudentsPerTeachers' -> return false if the limit is reached
     * 'StudentsPerGroups' -> return false if the limit is reached
     * @var Integer $teacher_id
     * @return Array
     */
    public function isStudentsLimitReachedForTeacherInGroup(Int $teacher_id): ?array {
        $limitationStudentsPerTeachers = 0;
        $limitationStudentsPerGroups = 0;
        $totalStudentsTeacher = 0;
        $totalStudentsGroup = 0;

        $group = $this->getEntityManager()->getRepository(UsersLinkGroups::class)->findBy(['user' => $teacher_id]);
        if ($group) {
            // Get the limitation for the group and teacher
            $applications = $this->getEntityManager()->getRepository(GroupsLinkApplications::class)->findAll(['group' => $group[0]->getGroup()]);
            if ($applications) {
                foreach ($applications as $application) {
                    $app = $this->getEntityManager()->getRepository(Applications::class)->findOneBy(['id' => $application->getApplication()]);
                    // get the limitation for the group
                    if (!empty($app->getmaxStudentsPerGroups())) {
                        if ($app->getmaxStudentsPerGroups() > $limitationStudentsPerGroups) {
                            $limitationStudentsPerGroups = $app->getmaxStudentsPerGroups();
                        }
                    }
                    // get the limitation for the teacher
                    if (!empty($app->getmaxStudentsPerTeachers())) {
                        if ($app->getmaxStudentsPerTeachers() > $limitationStudentsPerTeachers) {
                            $limitationStudentsPerTeachers = $app->getmaxStudentsPerTeachers();
                        }
                    }  
                }
            }
            // Get the students, from the teachers in the group
            $usersFromGroup = $this->getEntityManager()->getRepository(UsersLinkGroups::class)->findBy(['group' => $group[0]->getGroup()]);
            foreach ($usersFromGroup as $teacher) {
                $teacherClassrooms = $this->getEntityManager()->
                                    ->getRepository('Classroom\Entity\ClassroomLinkUser')
                                    ->findBy(['user' => $teacher->getUser(), 'rights'=> 2]);
                foreach($teacherClassrooms as $classroomObject) {
                    // retrieve all student for the current classroom
                    $studentsInClassroom = $this->getEntityManager()->
                                                ->getRepository('Classroom\Entity\ClassroomLinkUser')
                                                ->findBy(['classroom' => $classroomObject->getClassroom()->getId(),'rights'=> 0]);
                    // add classroom students to the total
                    if ($teacher->getUser() == $teacher_id) {
                        $totalStudentsTeacher += count($studentsInClassroom);
                    }
                    $totalStudentsGroup += count($studentsInClassroom);
                }
            }
        }

        if ($limitationStudentsPerGroups != 0) {
            $groupLimit = ($totalStudentsGroup < $limitationStudentsPerGroups);
        } else {
            $groupLimit = true;
        }

        if ($limitationStudentsPerTeachers != 0) {
            $teacherLimit = ($totalStudentsTeacher < $limitationStudentsPerTeachers);
        } else {
            $teacherLimit = true;
        }

        return ['studentsPerTeachers' => $teacherLimit, 
                'studentsPerGroups' => $groupLimit, 
                'totalStudentsTeacher' => $totalStudentsTeacher, 
                'totalStudentsGroup' => $totalStudentsGroup,
                'limitStudentsTeacher' => $limitationStudentsPerTeachers,
                'limitStudentsGroup' => $limitationStudentsPerGroups];
    }
}
