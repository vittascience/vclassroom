<?php

namespace Classroom\Repository;

use User\Entity\User;
use User\Entity\Regular;
use User\Entity\Teacher;
use Classroom\Entity\Groups;
use Doctrine\ORM\Query\Expr\Join;
use Classroom\Entity\Applications;
use Doctrine\ORM\EntityRepository;
use Classroom\Entity\UsersLinkGroups;
use Classroom\Entity\UsersRestrictions;
use Classroom\Entity\UsersLinkApplications;
use Classroom\Entity\GroupsLinkApplications;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Classroom\Entity\UsersLinkApplicationsFromGroups;

class UsersLinkGroupsRepository extends EntityRepository
{
    /**
     *  @param int $group_id, 
     *  @param int $page, 
     *  @param int $userpp, 
     *  @param int $sort
     *  @return Array of users
     */
    public function getAllMembersFromGroup(int $group_id, Int $page, Int $userspp, Int $sort)
    {

        $orderby = "u.surname";

        if ($sort == 0) {
            $orderby = "u.surname";
        } else if ($sort == 1) {
            $orderby = "u.firstname";
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        if ($group_id >= 1) {
            $result = $this->getEntityManager()
                ->createQueryBuilder()->select("u.id, u.surname, u.firstname, u.pseudo, g.rights AS rights, r.active as active")
                ->from(User::class, 'u')
                ->innerJoin(UsersLinkGroups::class, 'g')
                ->innerJoin(Regular::class, 'r', Join::WITH, 'r.user = u.id')
                ->where('g.group = :id AND u.id = g.user')
                ->orderBy('g.rights', 'DESC')
                ->addOrderBy($orderby)
                ->setParameter('id', $group_id)
                ->getQuery();
        } else if ($group_id == -1) {
            $id_members_in_groups = $this->getEntityManager()
                ->createQueryBuilder()->select("IDENTITY(g.user) as user, g.id")
                ->from(UsersLinkGroups::class, 'g')
                ->getQuery()
                ->getScalarResult();

            $users_id = [];
            foreach ($id_members_in_groups as $key => $value) {
                $users_id[] = $value['user'];
            }
            if (!empty($users_id)) {
                $result = $this->getEntityManager()
                    ->createQueryBuilder()
                    ->select("u.id, u.surname, u.firstname, u.pseudo, r.active as active")
                    ->from(User::class, 'u')
                    ->innerJoin(Regular::class, 'r', Join::WITH, 'r.user = u.id')
                    ->where($queryBuilder->expr()->notIn('u.id', ':ids'))
                    ->setParameter('ids', $users_id)
                    ->orderBy($orderby)
                    ->getQuery();
            } else {
                // in the case where $users_id is empty, we can't use the notIn statement
                $result = $this->getEntityManager()
                    ->createQueryBuilder()
                    ->select("u.id, u.surname, u.firstname, u.pseudo")
                    ->from(User::class, 'u')
                    ->innerJoin(Regular::class, 'r', Join::WITH, 'r.user = u.id')
                    ->where('r.active = 1')
                    ->orderBy($orderby)
                    ->getQuery();
            }
        } else if ($group_id == -2) {
            $result = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("u.id, u.surname, u.firstname, u.pseudo, IDENTITY(r.user) as isRegular, r.active")
                ->from(User::class, 'u')
                ->leftJoin(Regular::class, 'r', Join::WITH, 'r.user = u.id')
                ->where('r.active is NULL')
                ->orderBy($orderby)
                ->getQuery();
        }

        $paginator = new Paginator($result);

        // fetch applications of users
        $ApplicationsOfUsers = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("a.id AS application_id, a.image AS application_image, u.id AS user_id, ur.dateBegin as date_begin, ur.dateEnd as date_end")
            ->from(Applications::class, 'a')
            ->innerJoin(UsersLinkApplications::class, 'ula', Join::WITH, 'a.id = ula.application')
            ->innerJoin(User::class, 'u', Join::WITH, 'u.id = ula.user')
            ->innerJoin(UsersRestrictions::class, 'ur', Join::WITH, 'ur.user = u.id')
            ->getQuery()
            ->getScalarResult();

        $ApplicationsOfUsersFromGroup = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("  a.id AS application_id, 
                        a.name AS application_name,
                        a.image AS application_image, 
                        u.id AS user_id, 
                        g.dateBegin as date_begin, 
                        g.dateEnd as date_end,
                        g.maxStudentsPerTeachers as max_students_per_teachers,
                        g.maxStudents as max_students_per_groups,
                        g.maxTeachers as max_teachers_per_groups")
            ->from(Applications::class, 'a')
            ->innerJoin(UsersLinkApplicationsFromGroups::class, 'ulafg', Join::WITH, 'a.id = ulafg.application')
            ->innerJoin(User::class, 'u', Join::WITH, 'u.id = ulafg.user')
            ->innerJoin(UsersRestrictions::class, 'ur', Join::WITH, 'ur.user = u.id')
            ->innerJoin(Groups::class, 'g', Join::WITH, 'g.id = :id')
            ->setParameter('id', $group_id)
            ->getQuery()
            ->getScalarResult();


        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems / $userspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
            ->setFirstResult($userspp * ($currentPage - 1))
            ->setMaxResults($userspp)
            ->getScalarResult();


        // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($records as $key => $value) {
            foreach ($ApplicationsOfUsers as $key2 => $value2) {
                if ((int)$value['id'] == (int)$value2['user_id']) {
                    $records[$key]['applications'][] = [
                        'id' => $value2['application_id'],
                        'image' => $value2['application_image'],
                        'date_end' => $value2['date_end'],
                        'date_begin' => $value2['date_begin']
                    ];
                }
            }

            foreach ($ApplicationsOfUsersFromGroup as $AppOfUserFromGroup) {
                if ((int)$value['id'] == (int)$AppOfUserFromGroup['user_id']) {
                    $records[$key]['applicationsFromGroups'][] = [
                        'id' => $AppOfUserFromGroup['application_id'],
                        'name' => $AppOfUserFromGroup['application_name'],
                        'image' => $AppOfUserFromGroup['application_image'],
                        'dateBegin' => $AppOfUserFromGroup['date_begin'],
                        'dateEnd' => $AppOfUserFromGroup['date_end'],
                        'maxStudentsPerTeachers' => $AppOfUserFromGroup['max_students_per_teachers'],
                        'maxStudentsPerGroups' => $AppOfUserFromGroup['max_students_per_groups'],
                        'maxTeachersPerGroups' => $AppOfUserFromGroup['max_teachers_per_groups']
                    ];
                }
            }
        }

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    }

    public function getAdminFromGroup(int $group_id)
    {

        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();

        $queryBuilder->select("u")
            ->from(UsersLinkGroups::class, 'g')
            ->innerJoin(User::class, 'u')
            ->where('g.group = :id AND u.id = g.user AND g.rights = 1')
            ->setParameter('id', $group_id);
        $result = $queryBuilder->getQuery()->getResult();

        $Result_Users = [];
        foreach ($result as $key => $value) {
            $Result_Users[] = $value->jsonSerialize();
        }
        return $Result_Users;
    }


    /**
     * @Return User
     */
    public function getUsersWithHisGroups(Int $user_id)
    {

        $User = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u.id, u.firstname, u.surname, u.pseudo, r.email, IDENTITY(r.user) as isRegular, r.active as isActive, r.isAdmin, r.telephone, r.bio, IDENTITY(t.user) as isTeacher, t.grade, t.subject, t.school')
            ->from(User::class, 'u')
            ->leftJoin(Regular::class, 'r', 'WITH', 'r.user = u.id')
            ->leftJoin(Teacher::class, 't', 'WITH', 't.user = u.id')
            ->where('u.id = :id')
            ->setParameter('id', $user_id)
            ->getQuery()
            ->getResult();


        $LinkUserAndGroups = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('IDENTITY(ulg.user) as user, IDENTITY(ulg.group) as group, ulg.rights')
            ->from(UsersLinkGroups::class, 'ulg')
            ->where('ulg.user = :id')
            ->setParameter('id', $user_id)
            ->getQuery()
            ->getScalarResult();

        // Récupère les applications liées à l'utilisateur
        $ApplicationsOfUsers = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("a.id AS application_id, 
                        a.image AS application_image, 
                        u.id AS user_id, 
                        ula.dateBegin as date_begin, 
                        ula.dateEnd as date_end, 
                        ula.maxStudentsPerTeachers as max_students,
                        ula.maxActivitiesPerTeachers as max_activities")
            ->from(Applications::class, 'a')
            ->innerJoin(UsersLinkApplications::class, 'ula', Join::WITH, 'a.id = ula.application')
            ->innerJoin(User::class, 'u', Join::WITH, 'u.id = ula.user')
            ->where('ula.user = :id')
            ->setParameter('id', $user_id)
            ->getQuery()
            ->getScalarResult();


        $ApplicationsFromGroup = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('ulafg.id as id, 
                            IDENTITY(ulafg.user) as user, 
                            IDENTITY(ulafg.group) as group, 
                            IDENTITY(ulafg.application) as application')
            ->from(UsersLinkApplicationsFromGroups::class, 'ulafg')
            ->where('ulafg.user = :id')
            ->setParameter('id', $user_id)
            ->getQuery()
            ->getScalarResult();

        foreach ($LinkUserAndGroups as $key_2 => $value_2) {
            if ((int)$User[0]['id'] == (int)$value_2['user']) {
                $User[0]['groups'][] = ['id' => $value_2['group'], 'rights' => $value_2['rights']];
            }
        }

        // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($ApplicationsOfUsers as $key2 => $value2) {
            if ((int)$User[0]['id'] == (int)$value2['user_id']) {
                $User[0]['applications'][] = [
                    'id' => $value2['application_id'],
                    'image' => $value2['application_image'],
                    'date_end' => $value2['date_end'],
                    'date_begin' => $value2['date_begin'],
                    'max_students' => $value2['max_students'],
                    'max_activities' => $value2['max_activities']
                ];
            }
        }

        foreach ($ApplicationsFromGroup as $app) {
            if ((int)$User[0]['id'] == (int)$app['user']) {
                $User[0]['applications_from_groups'][] = [
                    'id' => $app['id'],
                    'user' => $app['user'],
                    'group' => $app['group'],
                    'application' => $app['application']
                ];
            }
        }

        return $User;
    }

    /**
     * @Return User
     */
    public function getUsersWithHisGroupsGA(Int $user_id)
    {

        $User = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u.id, u.firstname, u.surname, u.pseudo, r.email, r.telephone, r.bio, t.grade, t.subject, t.school')
            ->from(User::class, 'u')
            ->leftJoin(Regular::class, 'r', 'WITH', 'r.user = u.id')
            ->leftJoin(Teacher::class, 't', 'WITH', 't.user = u.id')
            ->where('u.id = :id')
            ->setParameter('id', $user_id)
            ->getQuery()
            ->getResult();

        $LinkUserAndGroups = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('IDENTITY(ulg.user) as user, IDENTITY(ulg.group) as group, ulg.rights')
            ->from(UsersLinkGroups::class, 'ulg')
            ->where('ulg.user = :id')
            ->setParameter('id', $user_id)
            ->getQuery()
            ->getScalarResult();

        $ApplicationsFromGroup = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('ulafg.id as id, 
                    IDENTITY(ulafg.user) as user, 
                    IDENTITY(ulafg.group) as group, 
                    IDENTITY(ulafg.application) as application')
            ->from(UsersLinkApplicationsFromGroups::class, 'ulafg')
            ->where('ulafg.user = :id')
            ->setParameter('id', $user_id)
            ->getQuery()
            ->getScalarResult();

        foreach ($LinkUserAndGroups as $key_2 => $linkUser) {
            if ((int)$User[0]['id'] == (int)$linkUser['user']) {
                $User[0]['groups'][] = ['id' => $linkUser['group'], 'rights' => $linkUser['rights']];
            }
        }

        foreach ($ApplicationsFromGroup as $app) {
            if ((int)$User[0]['id'] == (int)$app['user']) {
                $User[0]['applications_from_groups'][] = [
                    'id' => $app['id'],
                    'user' => $app['user'],
                    'group' => $app['group'],
                    'application' => $app['application']
                ];
            }
        }

        return $User;
    }

    /**
     * @Return array of User
     */
    public function globalSearchUser(String $string, Int $page, Int $userspp)
    {

        $Users = $this->getEntityManager()
            ->createQueryBuilder()->select("u.id, u.firstname, u.surname, u.pseudo, r.email, r.active")
            ->from(User::class, 'u')
            ->leftJoin(Regular::class, 'r', 'WITH', 'r.user = u.id')
            ->where('u.firstname LIKE :name OR u.surname LIKE :name')
            ->setParameter('name', '%' . $string . '%')
            ->groupBy('u.id')
            ->getQuery();

        // Initialise l'outil de pagination et les variables qui seront envoyées au javascript
        $paginator = new Paginator($Users);
        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems / $userspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
            ->setFirstResult($userspp * ($currentPage - 1))
            ->setMaxResults($userspp)
            ->getScalarResult();

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    }

    /**
     * @Return array of User
     */
    public function globalSearchUserGA(String $string, Int $page, Int $userspp, $AdmGrp)
    {


        $ids_groups = [];
        foreach ($AdmGrp as $key => $value) {
            $ids_groups[] = $value->getGroup();
        }
        // Get alls members with the property name somewhere where the user is related with a group where the requester is admin
        $Users = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("u.id, u.firstname, u.surname, u.pseudo, r.email, r.active")
            ->from(User::class, 'u')
            ->leftJoin(Regular::class, 'r', 'WITH', 'r.user = u.id')
            ->innerJoin(UsersLinkGroups::class, 'ulg')
            ->where('ulg.user = u.id AND ulg.group IN (:ids)')
            ->andwhere('u.firstname LIKE :name OR u.surname LIKE :name')
            ->setParameter('name', '%' . $string . '%')
            ->setParameter('ids', $ids_groups)
            ->groupBy('u.id')
            ->getQuery();



        // Initialise l'outil de pagination et les variables qui seront envoyées au javascript
        $paginator = new Paginator($Users);
        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems / $userspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
            ->setFirstResult($userspp * ($currentPage - 1))
            ->setMaxResults($userspp)
            ->getScalarResult();

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    }

    function groupWhereUserIsAdmin($admin_id)
    {
        $Groups = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("g.id, g.name, g.description")
            ->from(Groups::class, 'g')
            ->innerJoin(UsersLinkGroups::class, 'ulg')
            ->where('ulg.user = :id AND ulg.rights = 1 AND g.id = ulg.group')
            ->setParameter('id', $admin_id)
            ->getQuery()
            ->getScalarResult();

        // Récupère les applications liées à des groupes
        $ApplicationsOfGroups = $this->getEntityManager()
            ->createQueryBuilder()->select("a.id AS application_id, 
                                        a.name as application_name,
                                        a.image AS application_image, 
                                        g.id AS group_id, 
                                        gla.dateBegin as application_date_begin, 
                                        gla.dateEnd as application_date_end, 
                                        gla.maxStudentsPerTeachers as max_students_per_teachers,
                                        gla.maxStudentsPerGroups as max_students_per_groups,
                                        gla.maxTeachersPerGroups as max_teachers_per_groups")
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

    public function getIdFromGroupWhereUserAdmin($admin_id)
    {
        $Groups = $this->getEntityManager()
            ->createQueryBuilder()
            ->select("g.id")
            ->from(Groups::class, 'g')
            ->innerJoin(UsersLinkGroups::class, 'ulg')
            ->where('ulg.user = :id AND ulg.rights = 1 AND g.id = ulg.group')
            ->setParameter('id', $admin_id)
            ->getQuery()
            ->getScalarResult();

        return $Groups;
    }
}