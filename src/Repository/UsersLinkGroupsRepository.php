<?php

namespace Classroom\Repository;

use User\Entity\User;
use Classroom\Entity\Groups;
use Doctrine\ORM\Query\Expr\Join;
use Classroom\Entity\Applications;
use Classroom\Entity\GroupsLinkApplications;
use Doctrine\ORM\EntityRepository;
use Classroom\Entity\UsersLinkGroups;
use Classroom\Entity\UsersLinkApplications;
use Doctrine\ORM\Tools\Pagination\Paginator;
use User\Entity\Regular;
use User\Entity\Teacher;

class UsersLinkGroupsRepository extends EntityRepository
{
    /**
     *  @param int $group_id, 
     *  @param int $page, 
     *  @param int $userpp, 
     *  @param int $sort
     *  @return Array of users
     */
    public function getAllMembersFromGroup(int $group_id, Int $page, Int $userspp, Int $sort) {

        $orderby = "u.surname";
        
        if ($sort == 0)
            $orderby = "u.surname";
        else if ($sort == 1)
            $orderby = "u.firstname";

        $queryBuilder = $this->getEntityManager()
        ->createQueryBuilder();

        if ($group_id >= 1) {
            $result = $this->getEntityManager()
            ->createQueryBuilder()->select("u.id, u.surname, u.firstname, u.pseudo, g.rights AS rights")
                ->from(User::class,'u')
                ->innerJoin(UsersLinkGroups::class,'g')
                ->innerJoin(Regular::class,'r', Join::WITH, 'r.user = u.id')
                ->where('g.group = :id AND u.id = g.user AND r.active = 1')
                ->orderBy('g.rights', 'DESC')
                ->addOrderBy($orderby)
                ->setParameter('id',$group_id)
                ->getQuery();
        } else if ($group_id == -1){
            $id_members_in_groups = $this->getEntityManager()
            ->createQueryBuilder()->select("IDENTITY(g.user) as user, g.id")
                ->from(UsersLinkGroups::class,'g')
                ->getQuery()
                ->getScalarResult();

            $users_id = [];
            foreach ($id_members_in_groups as $key => $value) {
                $users_id[] = $value['user'];
            }
            $result = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("u.id, u.surname, u.firstname, u.pseudo")
                ->from(User::class,'u')
                ->innerJoin(Regular::class,'r', Join::WITH, 'r.user = u.id')
                ->where($queryBuilder->expr()->notIn('u.id', ':ids'))
                ->andWhere('r.active = 1')
                ->setParameter('ids', $users_id)
                ->orderBy($orderby)
                ->getQuery();
        } else if ($group_id == -2) {
            $result = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("u.id, u.surname, u.firstname, u.pseudo, IDENTITY(r.user) as isRegular, r.active")
                ->from(User::class,'u')
                ->leftJoin(Regular::class,'r', Join::WITH, 'r.user = u.id')
                ->where('r.active is NULL OR r.active = 0')
                ->orderBy($orderby)
                ->getQuery();
        }

        $paginator = new Paginator($result);

        // Récupère les applications liées à des utilisateurs
        $ApplicationsOfUsers = $this->getEntityManager()
                                ->createQueryBuilder()
                                ->select("a.id AS application_id, a.image AS application_image, u.id AS user_id, ula.dateBegin as date_begin, ula.dateEnd as date_end")
                                ->from(Applications::class,'a')
                                ->innerJoin(UsersLinkApplications::class,'ula', Join::WITH, 'a.id = ula.application')
                                ->innerJoin(User::class,'u', Join::WITH, 'u.id = ula.user')
                                ->getQuery()
                                ->getScalarResult();

        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems/$userspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
                            ->setFirstResult($userspp*($currentPage-1))
                            ->setMaxResults($userspp)
                            ->getScalarResult();


        // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($records as $key => $value) {
            foreach ($ApplicationsOfUsers as $key2 => $value2) {
                if ((int)$value['id'] == (int)$value2['user_id']) {
                    $records[$key]['applications'][] = ['id' => $value2['application_id'], 
                                                        'image' => $value2['application_image'], 
                                                        'date_end' => $value2['date_end'], 
                                                        'date_begin' => $value2['date_begin']];
                }
            }
        }

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    }

    public function getAdminFromGroup(int $group_id) {

        $queryBuilder = $this->getEntityManager()
        ->createQueryBuilder();

        $queryBuilder->select("u")
            ->from(UsersLinkGroups::class,'g')
            ->innerJoin(User::class,'u')
            ->where('g.group = :id AND u.id = g.user AND g.rights = 1')
            ->setParameter('id',$group_id);
        $result = $queryBuilder->getQuery()->getResult();

        $Result_Users=[];
        foreach ($result as $key => $value) {
            $Result_Users[] = $value->jsonSerialize();
        }
        return $Result_Users;
    }


    /**
     * @Return User
     */
    public function getUsersWithHisGroups(Int $user_id) {

        $User = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select('u.id, u.firstname, u.surname, u.pseudo, r.email, IDENTITY(r.user) as isRegular, r.active as isActive, r.isAdmin, r.telephone, r.bio, IDENTITY(t.user) as isTeacher, t.grade, t.subject, t.school')
                        ->from(User::class,'u')
                        ->leftJoin(Regular::class, 'r', 'WITH', 'r.user = u.id')
                        ->leftJoin(Teacher::class, 't', 'WITH', 't.user = u.id')
                        ->where('u.id = :id')
                        ->setParameter('id',$user_id)
                        ->getQuery()
                        ->getResult();
        

        $LinkUserAndGroups = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('IDENTITY(ulg.user) as user, IDENTITY(ulg.group) as group, ulg.rights')
                ->from(UsersLinkGroups::class,'ulg')
                ->where('ulg.user = :id')
                ->setParameter('id',$user_id)
                ->getQuery()
                ->getScalarResult();

        // Récupère les applications liées à l'utilisateur
        $ApplicationsOfUsers = $this->getEntityManager()
                ->createQueryBuilder()
                ->select("a.id AS application_id, a.image AS application_image, u.id AS user_id, ula.dateBegin as date_begin, ula.dateEnd as date_end")
                ->from(Applications::class,'a')
                ->innerJoin(UsersLinkApplications::class,'ula', Join::WITH, 'a.id = ula.application')
                ->innerJoin(User::class,'u', Join::WITH, 'u.id = ula.user')
                ->where('ula.user = :id')
                ->setParameter('id',$user_id)
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
                $User[0]['applications'][] = ['id' => $value2['application_id'], 
                                                'image' => $value2['application_image'], 
                                                'date_end' => $value2['date_end'], 
                                                'date_begin' => $value2['date_begin']];
            }
        }

        return $User;
    }

        /**
     * @Return User
     */
    public function getUsersWithHisGroupsGA(Int $user_id) {

        $User = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select('u.id, u.firstname, u.surname, u.pseudo, r.email, r.telephone, r.bio, t.grade, t.subject, t.school')
                        ->from(User::class,'u')
                        ->leftJoin(Regular::class, 'r', 'WITH', 'r.user = u.id')
                        ->leftJoin(Teacher::class, 't', 'WITH', 't.user = u.id')
                        ->where('u.id = :id')
                        ->setParameter('id',$user_id)
                        ->getQuery()
                        ->getResult();
        

        $LinkUserAndGroups = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('IDENTITY(ulg.user) as user, IDENTITY(ulg.group) as group, ulg.rights')
                ->from(UsersLinkGroups::class,'ulg')
                ->where('ulg.user = :id')
                ->setParameter('id',$user_id)
                ->getQuery()
                ->getScalarResult();


        foreach ($LinkUserAndGroups as $key_2 => $value_2) {
            if ((int)$User[0]['id'] == (int)$value_2['user']) {
                $User[0]['groups'][] = ['id' => $value_2['group'], 'rights' => $value_2['rights']];
            }
        }

        return $User;
    }

    /**
     * @Return array of User
     */
    public function searchUser(String $string, Int $page, Int $userspp, Int $group) {

        $queryBuilder = $this->getEntityManager()
        ->createQueryBuilder();

        // Récupère l'id des membres liés à un groupe si le groupe choisi est égal à -1 (ce qui est l'identificateur des utilisateurs sans groupe)
        $users_id_in_group = [];
        if ($group == -1) {
            $id_members_in_groups = $this->getEntityManager()
                ->createQueryBuilder()->select("IDENTITY(g.user) as user, g.id")
                    ->from(UsersLinkGroups::class,'g')
                    ->getQuery()
                    ->getScalarResult();
                    
            foreach ($id_members_in_groups as $key => $value) {
                $users_id_in_group[] = $value['user'];
            }
        }

        // Si l'identificateur de groupe correspond à celui des utilisateurs sans groupe et si il y au moins une personne lié a un groupe
        // On va exclure les id des utilisateurs liés à un groupe dans la query resultant à avoir tous les utilisateurs sans groupe
        if ($group == -1 && count($users_id_in_group) > 1) {
            $Users = $this->getEntityManager()
                    ->createQueryBuilder()
                    ->select("u.id, u.surname, u.firstname, u.pseudo")
                    ->from(User::class,'u')
                    ->where($queryBuilder->expr()->notIn('u.id', ':ids'))
                    ->andWhere('u.firstname LIKE :name OR u.surname LIKE :name OR u.pseudo LIKE :name')
                    ->setParameter('ids', $users_id_in_group)
                    ->setParameter('name','%' . $string . '%')
                    ->getQuery();
        } else {
            $Users = $this->getEntityManager()
                    ->createQueryBuilder()->select("u.id, u.firstname, u.surname, u.pseudo, g.rights AS rights")
                    ->from(User::class,'u')
                    ->innerJoin(UsersLinkGroups::class,'g')
                    ->where('u.firstname LIKE :name OR u.surname LIKE :name OR u.pseudo LIKE :name')
                    ->andWhere('g.group = :gid AND g.user = u.id')
                    ->setParameter('name','%' . $string . '%')
                    ->setParameter('gid', $group)
                    ->groupBy('u.id')
                    ->getQuery();
        }

        // Initialise l'outil de pagination et les variables qui seront envoyées au javascript
        $paginator = new Paginator($Users);
        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems/$userspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
                            ->setFirstResult($userspp*($currentPage-1))
                            ->setMaxResults($userspp)
                            ->getScalarResult();

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    } 

    /**
     * @Return array of User
     */
    public function globalSearchUser(String $string, Int $page, Int $userspp) {

        $Users = $this->getEntityManager()
        ->createQueryBuilder()->select("u.id, u.firstname, u.surname, u.pseudo, r.email")
        ->from(User::class,'u')
        ->leftJoin(Regular::class, 'r', 'WITH', 'r.user = u.id')
        ->where('u.firstname LIKE :name OR u.surname LIKE :name OR u.pseudo LIKE :name OR r.email LIKE :name')
        ->setParameter('name','%' . $string . '%')
        ->groupBy('u.id')
        ->getQuery();

        // Initialise l'outil de pagination et les variables qui seront envoyées au javascript
        $paginator = new Paginator($Users);
        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems/$userspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
                            ->setFirstResult($userspp*($currentPage-1))
                            ->setMaxResults($userspp)
                            ->getScalarResult();

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    }

    /**
     * @Return array of User
     */
    public function globalSearchUserGA(String $string, Int $page, Int $userspp, $AdmGrp) {

        
        $ids_groups = [];
        foreach ($AdmGrp as $key => $value) {
            $ids_groups[] = $value->getGroup();
        }
        // Get alls members with the property name somewhere where the user is related with a group where the requester is admin
        $Users = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select("u.id, u.firstname, u.surname, u.pseudo, r.email")
                        ->from(User::class,'u')
                        ->leftJoin(Regular::class, 'r', 'WITH', 'r.user = u.id')
                        ->innerJoin(UsersLinkGroups::class, 'ulg')
                        ->where('ulg.user = u.id AND ulg.group IN (:ids)')
                        ->andwhere('u.firstname LIKE :name OR u.surname LIKE :name OR u.pseudo LIKE :name OR r.email LIKE :name')
                        ->setParameter('name','%' . $string . '%')
                        ->setParameter('ids', $ids_groups)
                        ->groupBy('u.id')
                        ->getQuery();

                        

        // Initialise l'outil de pagination et les variables qui seront envoyées au javascript
        $paginator = new Paginator($Users);
        $paginator->setUseOutputWalkers(false);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems/$userspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
                            ->setFirstResult($userspp*($currentPage-1))
                            ->setMaxResults($userspp)
                            ->getScalarResult();

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    }

    function groupWhereUserIsAdmin($admin_id) {
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
        ->createQueryBuilder()->select("a.id AS application_id, a.image AS application_image, g.id AS group_id")
            ->from(Applications::class,'a')
            ->innerJoin(GroupsLinkApplications::class,'gla', Join::WITH, 'a.id = gla.application')
            ->innerJoin(Groups::class,'g', Join::WITH, 'g.id = gla.group')
            ->getQuery()
            ->getScalarResult();
    
    
            // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($Groups as $key => $value) {
            foreach ($ApplicationsOfGroups as $key2 => $value2) {
                if ((int)$value['id'] == (int)$value2['group_id']) {
                    $Groups[$key]['applications'][] = ['id' => $value2['application_id'], 'image' => $value2['application_image']];
                }
            }
        }
        
        return $Groups;
    }
}


        // 
        /* if ($group > -1) {
            $users_id = [];
            foreach ($records as $key => $value) {
                $users_id[] = $value['id'];
            }

            $LinkUsersAndGroups = $this->getEntityManager()
                    ->createQueryBuilder()
                    ->select('IDENTITY(ulg.user) as user, IDENTITY(ulg.group) as group, ulg.rights')
                    ->from(UsersLinkGroups::class,'ulg')
                    ->where('ulg.user IN (:ids)')
                    ->setParameter('ids', $users_id)
                    ->getQuery()
                    ->getScalarResult();

            foreach ($records as $key => $value) {
                foreach ($LinkUsersAndGroups as $key_2 => $value_2) {
                    if ((int)$value['id'] == (int)$value_2['user']) {
                        $records[$key]['groups'][] = ['id' => $value_2['group'], 'rights' => $value_2['rights']];
                    }
                }
            }
        } */

        /*     public function getAllAdmins() {

        $queryBuilder_1 = $this->getEntityManager()
        ->createQueryBuilder();
        $queryBuilder_2 = $this->getEntityManager()
        ->createQueryBuilder();

        // Récupère les groupes disposant d'un admin, obtient aussi certaine infos à son propos
        $AdminsOfGroups = $queryBuilder_1->select("u.id, u.firstname, u.surname, u.pseudo, g.id AS group_id, g.name AS group_name, ulg.rights")
            ->from(User::class,'u')
            ->innerJoin(UsersLinkGroups::class,'ulg', Join::WITH, 'u.id = ulg.user AND ulg.rights = 1')
            ->innerJoin(Groups::class,'g', Join::WITH, 'g.id = ulg.group')
            ->groupBy('u.id')
            ->getQuery()
            ->getScalarResult();

        // Récupère les applications liées à des groupes
        $ApplicationsOfGroups = $queryBuilder_2->select("a.id AS application_id, a.image AS application_image, g.id AS group_id")
            ->from(Applications::class,'a')
            ->innerJoin(GroupsLinkApplications::class,'gla', Join::WITH, 'a.id = gla.application')
            ->innerJoin(Groups::class,'g', Join::WITH, 'g.id = gla.group')
            ->getQuery()
            ->getScalarResult();

        // Set les applications aux groupes qui les possèdent dans le resultat initial
        foreach ($AdminsOfGroups as $key => $value) {
            foreach ($ApplicationsOfGroups as $key2 => $value2) {
                if ((int)$value['group_id'] == (int)$value2['group_id']) {
                    $AdminsOfGroups[$key]['applications'][] = ['id' => $value2['application_id'], 'image' => $value2['application_image']];
                }
            }
        }

        return $AdminsOfGroups;
    } */

/*     public function getAllMembersInAGroup() {

        $queryBuilder = $this->getEntityManager()
        ->createQueryBuilder();

        $queryBuilder->select("u.id, u.firstname, u.surname, u.pseudo, g.rights AS rights")
            ->from(User::class,'u')
            ->innerJoin(UsersLinkGroups::class,'g')
            ->where('u.id = g.user')
            ->groupBy('u.id');
        $result = $queryBuilder->getQuery()->getScalarResult();

        return $result;
    } */

    /* public function getAllUsersWithTheirGroups(Int $sort, Int $page, Int $userspp) {

        $sort_by = "";
        if ($sort == 0)
            $sort_by="u.surname";
        else if ($sort == 1)
            $sort_by="u.firstname";
        else
            $sort_by="u.pseudo";

        $Users = $this->getEntityManager()
                        ->createQueryBuilder()
                        ->select('u.id, u.firstname, u.surname, u.pseudo')
                        ->from(User::class,'u')
                        ->orderBy($sort_by, 'ASC')
                        ->groupBy('u.id')
                        ->getQuery();
        
        $paginator = new Paginator($Users);
        $totalItems = count($paginator);
        $currentPage = $page;
        $totalPagesCount = ceil($totalItems/$userspp);
        $nextPage = (($currentPage < $totalPagesCount) ? $currentPage + 1 : $totalPagesCount);
        $previousPage = (($currentPage > 1) ? $currentPage - 1 : 1);

        $records = $paginator->getQuery()
                            ->setFirstResult($userspp*($currentPage-1))
                            ->setMaxResults($userspp)
                            ->getScalarResult();
        
        $users_id = [];
        foreach ($records as $key => $value) {
            $users_id[] = $value['id'];
        }

        $LinkUsersAndGroups = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('IDENTITY(ulg.user) as user, IDENTITY(ulg.group) as group, ulg.rights')
                ->from(UsersLinkGroups::class,'ulg')
                ->where('ulg.user IN (:ids)')
                ->setParameter('ids', $users_id)
                ->getQuery()
                ->getScalarResult();

        foreach ($records as $key => $value) {
            foreach ($LinkUsersAndGroups as $key_2 => $value_2) {
                if ((int)$value['id'] == (int)$value_2['user']) {
                    $records[$key]['groups'][] = ['id' => $value_2['group'], 'rights' => $value_2['rights']];
                }
            }
        }

        $records[] = ['totalItems' => $totalItems, 'currentPage' => (int)$currentPage, 'totalPagesCount' => $totalPagesCount, 'nextPage' => $nextPage, 'previousPage' => $previousPage];

        return $records;
    } */