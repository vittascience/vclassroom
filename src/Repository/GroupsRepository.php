<?php

namespace Classroom\Repository;

use Classroom\Entity\Groups;
use Doctrine\ORM\Query\Expr\Join;
use Classroom\Entity\Applications;
use Doctrine\ORM\EntityRepository;
use Classroom\Entity\UsersLinkGroups;
use Classroom\Entity\GroupsLinkApplications;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @method Groups|null find($id, $lockMode = null, $lockVersion = null)
 * @method Groups|null findOneBy(array $criteria, array $orderBy = null)
 * @method Groups[]    findAll()
 * @method Groups[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GroupsRepository extends EntityRepository
{
    public function getPanelGroupInfos(Int $sort, Int $page, Int $groupspp)
    {

        $orderby = "";
        if ($sort == 0)
            $orderby = "g.name";
        else
            $orderby = "g.description";

        $Groups = $this->getEntityManager()
            ->createQueryBuilder()->select("g.id, g.name, g.description, g.link, g.maxStudents, g.maxTeachers, g.maxStudentsPerTeachers, g.dateBegin, g.dateEnd")
            ->from(Groups::class, 'g')
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
        $totalPagesCount = ceil($totalItems / $groupspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()->setFirstResult($groupspp * ($currentPage - 1))->setMaxResults($groupspp)->getScalarResult();

        foreach ($records as $key => $Group) {
            // users link group from this group
            $userlinkgroup = $this->getEntityManager()->getRepository(UsersLinkGroups::class)->findBy(['group' => $Group['id']]);
            if (count($userlinkgroup) > 0) {
                $records[$key]['nbUsers'] = count($userlinkgroup);
            } else {
                $records[$key]['nbUsers'] = 0;
            }
        }

        // Récupère les applications liées à des groupes
        $ApplicationsOfGroups = $this->getEntityManager()
            ->createQueryBuilder()->select("a.id AS application_id, a.image AS application_image, a.name AS application_name, g.id AS group_id")
            ->from(Applications::class, 'a')
            ->innerJoin(GroupsLinkApplications::class, 'gla', Join::WITH, 'a.id = gla.application')
            ->innerJoin(Groups::class, 'g', Join::WITH, 'g.id = gla.group')
            ->getQuery()
            ->getScalarResult();


        // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($records as $key => $value) {
            foreach ($ApplicationsOfGroups as $key2 => $value2) {
                if ((int)$value['id'] == (int)$value2['group_id']) {
                    $records[$key]['applications'][] = [
                        'id' => $value2['application_id'],
                        'image' => $value2['application_image'],
                        'name' => $value2['application_name']
                    ];
                }
            }
        }

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    }

    public function getGroupInfo($group_id)
    {
        $Group = $this->getEntityManager()
            ->createQueryBuilder()->select("g.id, g.name, g.description, g.link, g.maxStudents, g.maxTeachers, g.maxClassroomsPerTeachers, g.maxStudentsPerTeachers, g.dateBegin, g.dateEnd")
            ->from(Groups::class, 'g')
            ->where('g.id = :id ')
            ->setParameter('id', $group_id)
            ->getQuery()
            ->getResult();

        $GroupApplications = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("IDENTITY(gla.application) as application_id, 
                                    gla.maxActivitiesPerGroups as max_activities_per_groups,
                                    gla.maxActivitiesPerTeachers as max_activities_per_teachers")
                                    
            ->from(GroupsLinkApplications::class, 'gla')
            ->where('gla.group = :id')
            ->setParameter('id', $group_id)
            ->getQuery()
            ->getScalarResult();

        // Récupère les applications liées au groupe, le [0] est ici car la variable $Group est un array de 1 element
        $Group[0]['applications'] = $GroupApplications;

        return $Group;
    }

    public function searchGroup(String $string, Int $page, Int $groupspp)
    {

        $Groups = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("g.id, g.name, g.description, g.link, g.maxStudents, g.maxTeachers, g.maxStudentsPerTeachers, g.dateBegin, g.dateEnd")
            ->from(Groups::class, 'g')
            ->where('g.name LIKE :name OR g.description LIKE :name')
            ->setParameter('name', '%' . $string . '%')
            ->getQuery();

        $paginator = new Paginator($Groups);
        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems / $groupspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
            ->setFirstResult($groupspp * ($currentPage - 1))
            ->setMaxResults($groupspp)
            ->getScalarResult();


        // Récupère les applications liées à des groupes
        $ApplicationsOfGroups = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("a.id AS application_id, a.image AS application_image, a.name AS application_name, g.id AS group_id")
            ->from(Applications::class, 'a')
            ->innerJoin(GroupsLinkApplications::class, 'gla', Join::WITH, 'a.id = gla.application')
            ->innerJoin(Groups::class, 'g', Join::WITH, 'g.id = gla.group')
            ->getQuery()
            ->getScalarResult();


        // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($records as $key => $value) {
            foreach ($ApplicationsOfGroups as $key2 => $value2) {
                if ((int)$value['id'] == (int)$value2['group_id']) {
                    $records[$key]['applications'][] = [
                        'id' => $value2['application_id'],
                        'image' => $value2['application_image'],
                        'name' => $value2['application_name']
                    ];
                }
            }
        }

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    }

    public function findAllWithApps()
    {
        $Groups = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("g.id, g.name, g.description, g.link, g.maxStudents, g.maxTeachers, g.maxStudentsPerTeachers, g.dateBegin, g.dateEnd")
            ->from(Groups::class, 'g')
            ->groupBy('g.id')
            ->getQuery()
            ->getScalarResult();

        // Récupère les applications liées à des groupes
        $ApplicationsOfGroups = $this->getEntityManager()
            ->createQueryBuilder()->select("a.id AS application_id, 
                                        a.name as application_name,
                                        a.image AS application_image, 
                                        g.id AS group_id, 
                                        g.dateBegin as application_date_begin, 
                                        g.dateEnd as application_date_end, 
                                        g.maxStudentsPerTeachers as max_students_per_teachers,
                                        g.maxStudents as max_students_per_groups,
                                        g.maxTeachers as max_teachers_per_groups")
            ->from(Applications::class, 'a')
            ->innerJoin(GroupsLinkApplications::class, 'gla', Join::WITH, 'a.id = gla.application')
            ->innerJoin(Groups::class, 'g', Join::WITH, 'g.id = gla.group')
            ->getQuery()
            ->getScalarResult();


        // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($Groups as $key => $value) {
            foreach ($ApplicationsOfGroups as $key2 => $value2) {
                if ((int)$value['id'] == (int)$value2['group_id']) {
                    $Groups[$key]['applications'][] = [
                        'id' => $value2['application_id'],
                        'name' => $value2['application_name'],
                        'image' => $value2['application_image'],
                        'dateBegin' => $value2['application_date_begin'],
                        'dateEnd' => $value2['application_date_end'],
                        'maxStudentsPerTeachers' => $value2['max_students_per_teachers'],
                        'maxStudentsPerGroups' => $value2['max_students_per_groups'],
                        'maxTeachersPerGroups' => $value2['max_teachers_per_groups']
                    ];
                }
            }
        }

        return $Groups;
    }
}
