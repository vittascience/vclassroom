<?php

namespace Classroom\Repository;

use User\Entity\User;
use User\Entity\Regular;
use User\Entity\Teacher;
use Classroom\Entity\Groups;
use User\Entity\UserPremium;
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
    // Récupère la requête principale selon le type de groupe
    private function getMainQuery(int $group_id, string $orderByField)
    {
        $entityManager = $this->getEntityManager();

        if ($group_id > 0) {
            // Cas d'un groupe spécifique
            return $entityManager->createQueryBuilder()
                ->select("u.id, u.surname, u.firstname, u.pseudo, g.rights AS rights, r.active as active, r.newsletter, IDENTITY(p.user) as p_user, p.dateEnd as p_date_end")
                ->from(User::class, 'u')
                ->innerJoin(UsersLinkGroups::class, 'g')
                ->innerJoin(Regular::class, 'r', Join::WITH, 'r.user = u.id')
                ->leftJoin(UserPremium::class, 'p', Join::WITH, 'p.user = u.id')
                ->where('g.group = :id AND u.id = g.user')
                ->orderBy('g.rights', 'DESC')
                ->addOrderBy($orderByField)
                ->setParameter('id', $group_id)
                ->getQuery();

        } elseif ($group_id === -1) {
            // Cas des membres n'appartenant à aucun groupe
            $entityManager = $this->getEntityManager();
            $subQueryResult = $entityManager->createQueryBuilder()
                ->select("IDENTITY(g.user) as user")
                ->from(UsersLinkGroups::class, 'g')
                ->getQuery()
                ->getScalarResult();

            $userIdsInGroups = [];
            foreach ($subQueryResult as $row) {
                $userIdsInGroups[] = $row['user'];
            }

            if (!empty($userIdsInGroups)) {
                return $entityManager->createQueryBuilder()
                    ->select("u.id, u.surname, u.firstname, u.pseudo, r.active as active, r.newsletter, IDENTITY(p.user) as p_user, p.dateEnd as p_date_end")
                    ->from(User::class, 'u')
                    ->innerJoin(Regular::class, 'r', Join::WITH, 'r.user = u.id')
                    ->leftJoin(UserPremium::class, 'p', Join::WITH, 'p.user = u.id')
                    ->where($entityManager->createQueryBuilder()->expr()->notIn('u.id', ':ids'))
                    ->setParameter('ids', $userIdsInGroups)
                    ->orderBy($orderByField)
                    ->getQuery();
            } else {
                // Aucun utilisateur dans un groupe : renvoie les utilisateurs actifs
                return $entityManager->createQueryBuilder()
                    ->select("u.id, u.surname, u.firstname, u.pseudo")
                    ->from(User::class, 'u')
                    ->innerJoin(Regular::class, 'r', Join::WITH, 'r.user = u.id')
                    ->where('r.active = 1')
                    ->orderBy($orderByField)
                    ->getQuery();
            }
        } elseif ($group_id === -2) {
            // Cas des utilisateurs sans lien dans la table Regular
            return $entityManager->createQueryBuilder()
                ->select("u.id, u.surname, u.firstname, u.pseudo, IDENTITY(r.user) as isRegular, r.newsletter, r.active, IDENTITY(p.user) as p_user, p.dateEnd as p_date_end")
                ->from(User::class, 'u')
                ->leftJoin(Regular::class, 'r', Join::WITH, 'r.user = u.id')
                ->leftJoin(UserPremium::class, 'p', Join::WITH, 'p.user = u.id')
                ->where('r.active is NULL')
                ->orderBy($orderByField)
                ->getQuery();
        }

        return null;
    }

    // Récupère les applications globales liées aux utilisateurs
    private function getApplicationsOfUsers()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("a.id AS application_id, a.name AS application_name, a.image AS application_image, u.id AS user_id, ur.dateBegin as date_begin, ur.dateEnd as date_end")
            ->from(Applications::class, 'a')
            ->innerJoin(UsersLinkApplications::class, 'ula', Join::WITH, 'a.id = ula.application')
            ->innerJoin(User::class, 'u', Join::WITH, 'u.id = ula.user')
            ->innerJoin(UsersRestrictions::class, 'ur', Join::WITH, 'ur.user = u.id')
            ->getQuery()
            ->getScalarResult();
    }

    // Récupère les applications liées aux utilisateurs via les groupes
    private function getApplicationsOfUsersFromGroup(int $group_id)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select("a.id AS application_id, a.name AS application_name, a.image AS application_image, u.id AS user_id, g.dateBegin as date_begin, g.dateEnd as date_end, g.maxStudentsPerTeachers as max_students_per_teachers, g.maxStudents as max_students_per_groups, g.maxTeachers as max_teachers_per_groups")
            ->from(Applications::class, 'a')
            ->innerJoin(UsersLinkApplicationsFromGroups::class, 'ulafg', Join::WITH, 'a.id = ulafg.application')
            ->innerJoin(User::class, 'u', Join::WITH, 'u.id = ulafg.user')
            ->innerJoin(UsersRestrictions::class, 'ur', Join::WITH, 'ur.user = u.id')
            ->innerJoin(Groups::class, 'g', Join::WITH, 'g.id = :id')
            ->setParameter('id', $group_id)
            ->getQuery()
            ->getScalarResult();
    }

    // Gère la pagination d'une requête
    private function paginateQuery($query, int $page, int $usersPerPage)
    {
        $paginator = new Paginator($query);
        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $usersPerPage);
        $currentPage = $page;
        $nextPage = ($currentPage < $totalPages) ? $currentPage + 1 : $totalPages;
        $previousPage = ($currentPage > 1) ? $currentPage - 1 : 1;

        $records = $paginator->getQuery()
            ->setFirstResult($usersPerPage * ($currentPage - 1))
            ->setMaxResults($usersPerPage)
            ->getScalarResult();

        return [
            'records'         => $records,
            'totalItems'      => $totalItems,
            'totalPages'      => $totalPages,
            'currentPage'     => $currentPage,
            'nextPage'        => $nextPage,
            'previousPage'    => $previousPage
        ];
    }

    public function getAllMembersFromGroup(int $group_id, int $page, int $usersPerPage, int $sort)
    {
        // Choix du critère de tri
        $orderByField = ($sort === 0) ? "u.surname" : "u.firstname";

        // Vérifie que le groupe est valide
        if ($group_id === 0) {
            return false;
        }

        // Récupération de la requête principale selon le type de groupe
        $query = $this->getMainQuery($group_id, $orderByField);
        if ($query === null) {
            return false;
        }

        // Pagination
        $pagination = $this->paginateQuery($query, $page, $usersPerPage);
        $records = $pagination['records'];

        // Récupération des applications
        $applicationsGlobales = $this->getApplicationsOfUsers();
        $applicationsGroup = $this->getApplicationsOfUsersFromGroup($group_id);

        // Association des applications aux utilisateurs
        foreach ($records as $index => $user) {
            // Applications globales
            foreach ($applicationsGlobales as $app) {
                if ((int)$user['id'] === (int)$app['user_id']) {
                    $records[$index]['applications'][] = [
                        'id'         => $app['application_id'],
                        'name'       => $app['application_name'],
                        'image'      => $app['application_image'],
                        'date_begin' => $app['date_begin'] ?? null,
                        'date_end'   => $app['date_end'] ?? null,
                    ];
                }
            }
            // Applications issues des groupes
            foreach ($applicationsGroup as $appGroup) {
                if ((int)$user['id'] === (int)$appGroup['user_id']) {
                    $records[$index]['applicationsFromGroups'][] = [
                        'id'                     => $appGroup['application_id'],
                        'name'                   => $appGroup['application_name'],
                        'image'                  => $appGroup['application_image'],
                        'date_begin'             => $appGroup['date_begin'],
                        'date_end'               => $appGroup['date_end'],
                        'maxStudentsPerTeachers' => $appGroup['max_students_per_teachers'],
                        'maxStudentsPerGroups'   => $appGroup['max_students_per_groups'],
                        'maxTeachersPerGroups'   => $appGroup['max_teachers_per_groups']
                    ];
                }
            }
        }

        // Ajout des informations de pagination à la fin
        $records[] = [
            'totalItems'      => $pagination['totalItems'],
            'currentPage'     => $pagination['currentPage'],
            'totalPagesCount' => $pagination['totalPages'],
            'nextPage'        => $pagination['nextPage'],
            'previousPage'    => $pagination['previousPage']
        ];

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
            ->select('u.id, u.firstname, u.surname, u.pseudo, r.email, r.roles, IDENTITY(r.user) as isRegular, r.newsletter, r.active as isActive, r.isAdmin, r.telephone, r.bio, IDENTITY(t.user) as isTeacher, t.grade, t.subject, t.school')
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
                        ula.maxActivitiesPerTeachers as max_activities")
            ->from(Applications::class, 'a')
            ->innerJoin(UsersLinkApplications::class, 'ula', Join::WITH, 'a.id = ula.application')
            ->innerJoin(User::class, 'u', Join::WITH, 'u.id = ula.user')
            ->innerJoin(UsersRestrictions::class, 'ur', Join::WITH, 'ur.user = u.id')
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

        $UsersRestrictions = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('  ur.id as id, 
                        IDENTITY(ur.user) as user, 
                        ur.dateBegin as date_begin, 
                        ur.dateEnd as date_end, 
                        ur.maxStudents as max_students,
                        ur.maxClassrooms as max_classrooms')
            ->from(UsersRestrictions::class, 'ur')
            ->where('ur.user = :id')
            ->setParameter('id', $user_id)
            ->getQuery()
            ->getScalarResult();

        foreach ($LinkUserAndGroups as $key_2 => $value_2) {
            if ((int)$User[0]['id'] == (int)$value_2['user']) {
                $User[0]['groups'][] = ['id' => $value_2['group'], 'rights' => $value_2['rights']];
            }
        }

        $User[0]['restrictions'] = $UsersRestrictions;

        // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($ApplicationsOfUsers as $key2 => $value2) {
            if ((int)$User[0]['id'] == (int)$value2['user_id']) {
                $User[0]['applications'][] = [
                    'id' => $value2['application_id'],
                    'image' => $value2['application_image'],
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
            ->createQueryBuilder()->select("u.id, u.firstname, u.surname, u.pseudo, r.email, r.active, IDENTITY(p.user) as p_user, p.dateEnd as p_date_end")
            ->from(User::class, 'u')
            ->leftJoin(Regular::class, 'r', 'WITH', 'r.user = u.id')
            ->leftJoin(UserPremium::class, 'p', Join::WITH, 'p.user = u.id')
            ->where('u.firstname LIKE :name OR u.surname LIKE :name OR r.email LIKE :name')
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
