<?php

namespace Classroom\Controller;

use Utils\Mailer;
use User\Entity\User;
use User\Entity\Regular;
use User\Entity\Teacher;
use Aiken\i18next\i18next;
use Classroom\Entity\Applications;
use Classroom\Entity\Groups;
use Classroom\Entity\UsersLinkGroups;
use Classroom\Entity\GroupsLinkApplications;
use Classroom\Entity\UsersLinkApplications;

class ControllerSuperAdmin extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);

        // Vérifie si l'utilisateur actuel est admin, si il ne l'est pas alors lui refuser toutes actions
        $Autorisation = $this->entityManager->getRepository('User\Entity\Regular')->findOneBy(['user' => htmlspecialchars($_SESSION['id'])]);
        if (!$Autorisation || $Autorisation->getIsAdmin() == false || $_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->actions = array(
                'is_user_admin' => function() {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => htmlspecialchars($_SESSION['id'])]);
                    $userR = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user]);
                    if ($userR->getIsAdmin()) {
                        return ['Admin' => true];
                    }
                    return ['Admin' => false];
                }
            );
            return false;
        } else if ($Autorisation->getIsAdmin() == true) {
            $this->actions = array(
                'get_all_groups' => function () {
                    return $this->entityManager->getRepository(Groups::class)->findAll();
                },
                'panel_groups_info' => function ($data) {
                    if (isset($data['sort']) && $data['sort'] != null && 
                    isset($data['page']) && $data['page'] != null && 
                    isset($data['groupspp']) && $data['groupspp'] != null) {
                        $sort = htmlspecialchars($data['sort']);
                        $page = htmlspecialchars($data['page']);
                        $groupspp = htmlspecialchars($data['groupspp']);
                        return $this->entityManager->getRepository(Groups::class)->getPanelGroupInfos($sort, $page, $groupspp);
                    }  
                },
                'get_group_info' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $group_id = htmlspecialchars($data['id']);
                        return $this->entityManager->getRepository(Groups::class)->getGroupInfo($group_id);
                    }
                },
                'get_all_admins' => function () {
                    return $this->entityManager->getRepository(UsersLinkGroups::class)->getAllAdmins();
                },
                'get_admin_from_group' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $group_id = htmlspecialchars($data['id']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->getAdminFromGroup($group_id);
                    }
                },
                'get_all_members_from_group' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null && isset($data['userspp']) && $data['userspp'] != null && isset($data['page']) && $data['page'] != null&& isset($data['sort']) && $data['sort'] != null ) {
                        $group_id = htmlspecialchars($data['id']);
                        $userspp = htmlspecialchars($data['userspp']);
                        $page = htmlspecialchars($data['page']);
                        $sort = htmlspecialchars($data['sort']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->getAllMembersFromGroup($group_id, $page, $userspp, $sort);
                    }
                },
                'get_all_groups_from_user' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $user_id = htmlspecialchars($data['id']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->findBy(['user' => $user_id]);
                    }
                },
                'get_all_applications' => function ($data) {
                    $apps = $this->entityManager->getRepository(Applications::class)->findAll();
    
                    $Result_array=[];
                    foreach ($apps as $key => $value) {
                        $Result_array[] = $value->jsonSerialize();
                    }
                    return $Result_array;
                },
                'get_all_applications_from_group' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $group_id = htmlspecialchars($data['id']);
                        return $this->entityManager->getRepository(GroupsLinkApplications::class)->getAllApplicationsFromGroup($group_id);
                    }
                },
                'get_all_groups_from_application' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $application_id = htmlspecialchars($data['id']);
                        return $this->entityManager->getRepository(GroupsLinkApplications::class)->findBy(['application' => $application_id]);
                    }
                },
                'get_all_applications_from_user' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $user_id = htmlspecialchars($data['id']);
                        return $this->entityManager->getRepository(UsersLinkApplications::class)->findBy(['user' => $user_id]);
                    }
                },
                'get_all_users_from_application' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $application_id = htmlspecialchars($data['id']);
                        return $this->entityManager->getRepository(UsersLinkApplications::class)->getAllMembersFromApplication($application_id);
                    }
                },
                'update_application_to_group' => function () {
                    if (isset($data['group_id']) && $data['group_id'] != null && 
                    isset($data['application_id']) && $data['application_id'] != null && 
                    isset($data['date_begin']) && $data['date_begin'] != null  && 
                    isset($data['date_end']) && $data['date_end'] != null) {
                        $group_id = htmlspecialchars($data['group_id']);
                        $application_id = htmlspecialchars($data['application_id']);
                        // TBD : Modifié le format au besoin
                        $date_begin = \DateTime::createFromFormat('j-M-Y', $data['date_begin']);
                        $date_end = \DateTime::createFromFormat('j-M-Y', $data['date_end']);
                        $GroupLinkApplications = $this->entityManager->getRepository(GroupsLinkApplication::class)->findBy(['application' => $application_id, 'group' => $group_id]);
                        $GroupLinkApplications->setDateBegin($date_begin);
                        $GroupLinkApplications->setDateEnd($date_end);
                        $this->entityManager->persist($GroupLinkApplications);
                        $this->entityManager->flush();
                    }
                },
                'create_group' => function ($data) {
                    if (isset($data['name']) && $data['name'] != null && 
                    isset($data['description']) && $data['description'] != null &&
                    isset($data['applications']) && $data['applications'] != null) {
    
                        $applications = json_decode($data['applications']);
                        $group_name = htmlspecialchars($data['name']);
                        $group_desc = htmlspecialchars($data['description']);
    
                        $group = new Groups;
                        $group->setName($group_name);
                        $group->setDescription($group_desc);
                        $group->setLink();
                        // Vérifie si il n'y a pas déjà de groupe avec ce lien, si il y en a un alors on re-set le link et on recommence
                        $linkExist = $this->entityManager->getRepository(Groups::class)->findOneBy(['link' => $group->getLink()]);
                        while ($linkExist) {
                            $group->setLink();
                            $linkExist = $this->entityManager->getRepository(Groups::class)->findOneBy(['link' => $group->getLink()]);
                        }
                        $this->entityManager->persist($group);
                        $this->entityManager->flush();
    
                        $lastgroup = $this->entityManager->getRepository(Groups::class)->findOneBy([], ['id' => 'desc']);
                        $group_id = $lastgroup->getId();
    
                        foreach ($applications as $key => $value) {
                            $AppExist = $this->entityManager->getRepository(GroupsLinkApplications::class)->findOneBy(['group' => $group_id, 'application' => $value[0]]);
                            // Récupère l'entité application liée à l'id de celle-ci (permet de la set ensuite en tant qu'entité dans le lien entre groupe et application)
                            $application = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $value[0]]);
                            if ($value[1] == true) {
                                $date_begin = \DateTime::createFromFormat('Y-m-d', $value[2]);
                                $date_end = \DateTime::createFromFormat('Y-m-d', $value[3]);
                                if ($AppExist) {
                                    $AppExist->setApplication($application);
                                    $AppExist->setGroup($group);
                                    $AppExist->setDateBegin($date_begin);
                                    $AppExist->setDateEnd($date_end);
                                    $this->entityManager->persist($AppExist);
                                } else {
                                    $Applications = new GroupsLinkApplications();
                                    $Applications->setApplication($application);
                                    $Applications->setGroup($group);
                                    $Applications->setDateBegin($date_begin);
                                    $Applications->setDateEnd($date_end);
                                    $this->entityManager->persist($Applications);
                                }
                            } else {
                                if ($AppExist) {
                                    $this->entityManager->remove($AppExist);
                                } 
                            }
                        }
                        $this->entityManager->flush();
    
                        return ['response' => 'success'];
                    } else {
                        return ['response' => 'missing data'];
                    }
                },
                'delete_group' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $group_id = htmlspecialchars($data['id']);
                        $group = $this->entityManager->getRepository(Groups::class)->findOneBy(['id' => $group_id]);
                        $this->entityManager->remove($group);
    
                        // Delete le lien entre le groupe et les utilisateurs
                        $userlinkgroups = $this->entityManager->getRepository(UsersLinkGroups::class)->findBy(['group' => $group_id]);
                        foreach ($userlinkgroups as $key_ulg => $value_ulg) {
                            $this->entityManager->remove($userlinkgroups[$key_ulg]);
                        }
    
                        // Delete le lien entre le groupe et les applications
                        $groupslinkapplications = $this->entityManager->getRepository(GroupsLinkApplications::class)->findBy(['group' => $group_id]);
                        foreach ($groupslinkapplications as $key_ula => $value_ula) {
                            $this->entityManager->remove($groupslinkapplications[$key_ula]);
                        }
    
                        $this->entityManager->flush();
                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'error'];
                    }
                },
                'update_group' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null && 
                    isset($data['name']) && $data['name'] != null && 
                    isset($data['description']) && $data['description'] != null &&
                    isset($data['applications']) && $data['applications'] != null) {
                        $applications = json_decode($data['applications']);
    
                        $group_id = htmlspecialchars($data['id']);
                        $group_name = htmlspecialchars($data['name']);
                        $group_description = htmlspecialchars($data['description']);

                        $group = $this->entityManager->getRepository(Groups::class)->findOneBy(['id' => $group_id]);
                        $group->setDescription($group_description);
                        $group->setName($group_name);
                        $this->entityManager->persist($group);
    
                        foreach ($applications as $key => $value) {
                            $AppExist = $this->entityManager->getRepository(GroupsLinkApplications::class)->findOneBy(['group' => $group_id, 'application' => $value[0]]);
                            // Récupère l'entité application liée à l'id de celle-ci (permet de la set ensuite en tant qu'entité dans le lien entre groupe et application)
                            $application = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $value[0]]);
                            if ($value[1] == true) {
                                $date_begin = \DateTime::createFromFormat('Y-m-d', $value[2]);
                                $date_end = \DateTime::createFromFormat('Y-m-d', $value[3]);
                                if ($AppExist) {
                                    $AppExist->setApplication($application);
                                    $AppExist->setGroup($group);
                                    $AppExist->setDateBegin($date_begin);
                                    $AppExist->setDateEnd($date_end);
                                    $this->entityManager->persist($AppExist);
                                } else {
                                    $Applications = new GroupsLinkApplications();
                                    $Applications->setApplication($application);
                                    $Applications->setGroup($group);
                                    $Applications->setDateBegin($date_begin);
                                    $Applications->setDateEnd($date_end);
                                    $this->entityManager->persist($Applications);
                                }
                            } else {
                                if ($AppExist) {
                                    $this->entityManager->remove($AppExist);
                                } 
                            }
                        }
                        $this->entityManager->flush();
                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'get_user_info' => function($data) {
                    if (isset($data['id']) && $data['id'] != null)               
                    {
                        $user_id = htmlspecialchars($data['id']);
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);
                        return $user->jsonSerialize();
                    }
                },
                'get_all_users_in_a_group' => function() {
                    return $this->entityManager->getRepository(UsersLinkGroups::class)->getAllMembersInAGroup();
                },  
                'get_all_users_with_their_groups' => function($data) {
                    if (isset($data['sort']) && $data['sort'] != null && isset($data['page']) && $data['page'] != null && isset($data['userspp']) && $data['userspp'] != null) {
                        $sort = htmlspecialchars($data['sort']);
                        $page = htmlspecialchars($data['page']);
                        $userspp = htmlspecialchars($data['userspp']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->getAllUsersWithTheirGroups($sort, $page, $userspp);
                    }
                },
                'get_user_info_with_his_groups' => function($data) {
                    if (isset($data['id']) && $data['id'] != null)
                    {
                        $user_id = htmlspecialchars($data['id']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->getUsersWithHisGroups($user_id);
                    }
                },
                'create_user' => function($data) {
                    if (isset($data['firstname']) && $data['firstname'] != null && 
                        isset($data['surname']) && $data['surname'] != null && 
                        isset($data['pseudo']) && $data['pseudo'] != null && 
                        isset($data['groups']) && $data['groups'] != null &&
                        isset($data['phone']) &&
                        isset($data['mail']) && $data['mail'] != null &&
                        isset($data['bio']) &&
                        isset($data['admin']) && $data['admin'] != null &&
                        isset($data['teacher']) && $data['teacher'] != null &&
                        isset($data['grade']) &&
                        isset($data['subject']) &&
                        isset($data['school']) &&
                        isset($data['isactive']) && $data['isactive'] != null)
                    {
    
                        $groups =  json_decode($data['groups']);
                        $surname = htmlspecialchars($data['surname']);
                        $firstname = htmlspecialchars($data['firstname']);
                        $pseudo = htmlspecialchars($data['pseudo']);
    
                        $phone = htmlspecialchars($data['phone']);
                        $bio = htmlspecialchars($data['bio']);
                        $mail = htmlspecialchars($data['mail']);
                        $admin = htmlspecialchars($data['admin'])  == "true" ? true : false;
                        $isTeacher = htmlspecialchars($data['teacher']) == "true" ? true : false;
                        $school = htmlspecialchars($data['school']);
                        $grade = (int)htmlspecialchars($data['grade']);
                        $subject = (int)htmlspecialchars($data['subject']);

                        $isactive = htmlspecialchars($data['isactive']) == "true" ? true : false;
    
                        
                        $user = new User;
                        $user->setFirstname($firstname);
                        $user->setSurname($surname);
                        $user->setPseudo($pseudo);
                        $objDateTime = new \DateTime('NOW');
                        $user->setInsertDate($objDateTime);

                        $password = "";
                        for ($i = 0; $i < 8; $i++) {
                            $password .= rand(0, 9);
                        }
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $user->setPassword($hash);
                        $this->entityManager->persist($user);
                        $this->entityManager->flush();

                        foreach ($groups as $key => $value) {
                            if ($value[1] != -1) {
                                $group = $this->entityManager->getRepository(Groups::class)->findOneBy(['id' => $value[1]]);
            
                                $rights = 0;
                                $UsersLinkGroups = new UsersLinkGroups();
                                $UsersLinkGroups->setGroup($group);
                                $UsersLinkGroups->setUser($user);
                                if ($value[0] == true) {
                                    $rights = 1;
                                }
                                $UsersLinkGroups->setRights($rights);
                                $this->entityManager->persist($UsersLinkGroups);
                            }
                        }
    
                        $confirmationToken = bin2hex(random_bytes(16));
                        $regular = new Regular($user, $mail, $bio, $phone, false, $admin, null , null, $isactive);
                        $regular->setConfirmToken($confirmationToken);
                        $this->entityManager->persist($regular);


                        if ($isTeacher) {
                            $teacher = new Teacher($user, $subject, $school, $grade);
                            $this->entityManager->persist($teacher);
                        }
    
                        $this->entityManager->flush();


                        $userLang = isset($_COOKIE['lng']) ? htmlspecialchars(strip_tags(trim($_COOKIE['lng']))) : 'fr'; 
                        $accountConfirmationLink = $_ENV['VS_HOST']."/classroom/registration.php?token=$confirmationToken";
                        $emailTtemplateBody = $userLang."_confirm_account";

                        if(is_dir(__DIR__."/../../../../../openClassroom")){
                            i18next::init($userLang,__DIR__."/../../../../../openClassroom/classroom/assets/lang/__lng__/ns.json");
                        }else {
                            i18next::init($userLang,__DIR__."/../../../../../classroom/assets/lang/__lng__/ns.json");
                        }

                        $emailSubject = i18next::getTranslation('superadmin.users.mail.finalizeAccount.subject');
                        $bodyTitle = i18next::getTranslation('superadmin.users.mail.finalizeAccount.bodyTitle');
                        $textBeforeLink = i18next::getTranslation('superadmin.users.mail.finalizeAccount.textBeforeLink');
                        
                        $body = "
                            <a href='$accountConfirmationLink' style='text-decoration: none;padding: 10px;background: #27b88e;color: white;margin: 1rem auto;width: 50%;display: block;'>
                                $bodyTitle
                            </a>
                            <br>
                            <br>
                            <p>$textBeforeLink $accountConfirmationLink
                        ";
                        
                        $emailSent = Mailer::sendMail($mail, $emailSubject, $body, strip_tags($body), $emailTtemplateBody, "remi.cointe@vittascience.com", "Rémi"); 
                        /////////////////////////////////////

                        return ['message' => 'success', 'mail' => $emailSent];
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'update_user' => function($data) {
                    if (isset($data['user_id']) && $data['user_id'] != null && isset($data['firstname']) && $data['firstname'] != null && 
                        isset($data['surname']) && $data['surname'] != null && 
                        isset($data['pseudo']) && $data['pseudo'] != null && 
                        isset($data['groups']) && $data['groups'] != null &&

                        isset($data['phone']) &&
                        isset($data['mail']) && $data['mail'] != null &&
                        isset($data['bio']) &&
                        isset($data['admin']) && $data['admin'] != null &&
                        isset($data['teacher']) && $data['teacher'] != null &&
                        isset($data['grade']) &&
                        isset($data['subject']) &&

                        isset($data['isactive']) && $data['isactive'] != null)
                    {
                        $user_id = htmlspecialchars($data['user_id']);
                        $groups =  json_decode($data['groups']);
                        $surname = htmlspecialchars($data['surname']);
                        $firstname = htmlspecialchars($data['firstname']);
                        $pseudo = htmlspecialchars($data['pseudo']);
    
                        $phone = htmlspecialchars($data['phone']);
                        $bio = htmlspecialchars($data['bio']);
                        $mail = htmlspecialchars($data['mail']);
                        $admin = htmlspecialchars($data['admin']) == "true" ? true : false;
                        $isTeacher = htmlspecialchars($data['teacher']) == "true" ? true : false;

                        $school = htmlspecialchars($data['school']);
                        $grade = (int)htmlspecialchars($data['grade']);
                        $subject = (int)htmlspecialchars($data['subject']);

                        $isactive = $data['isactive'] == "true" ? true : false;
    
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);
                        $user->setFirstname($firstname);
                        $user->setSurname($surname);
                        $user->setPseudo($pseudo);
                        $this->entityManager->persist($user);

                        $regular = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user_id]);
                        if($regular) {

                            $regular->setEmail($mail);
                            $regular->setBio($bio);
                            $regular->setTelephone($phone);
                            $regular->setIsAdmin($admin);
                            $regular->setActive($isactive);
                            $this->entityManager->persist($regular);
                        } else if (!$regular) {
                            $regular = new Regular($user, $mail, $bio, $phone, false, $admin, null, null, $isactive);
                            $this->entityManager->persist($regular);
                        }

                        // Si l'utilisateur est déjà référencé en tant que Teacher
                        $teacher = $this->entityManager->getRepository(Teacher::class)->findOneBy(['user' => $user_id]);
                        // Si l'utilisateur existe dans la bade de données en tant que teacher et que l'update le determine aussi en teacher alors on modifie les champs selon la requête
                        if ($isTeacher && $teacher) {
                            $teacher->setSubject($subject);
                            $teacher->setSchool($school);
                            $teacher->setGrade($grade);
                            $this->entityManager->persist($teacher);     
                        } 
                        // Si l'utilisateur n'existe pas en tant que teacher dans la BDD et que la requete le determine en tant que teacher alors on l'entre dans la BDD
                        else if ($isTeacher) {
                            $teacher = new Teacher($user, $subject, $school, $grade);
                            $this->entityManager->persist($teacher);
                        } 
                        // Si l'utilisateur existe en teacher dans la BDD et que la requete le determine en non teacher alors nous supprimons son status dans la BDD
                        else if ($teacher && !$isTeacher){
                            $this->entityManager->remove($teacher);
                        }
                        // Obtient la totalité des groupes de l'utilisateur
                        $AllGroupsFromUser = $this->entityManager->getRepository(UsersLinkGroups::class)->findBy(['user' => $user_id]);
                        foreach ($groups as $key => $value) {
                            if ($value[1] != -1) {
                                $group = $this->entityManager->getRepository(Groups::class)->findOneBy(['id' => $value[1]]);
                                $AlreadyLinked = $this->entityManager->getRepository(UsersLinkGroups::class)->findOneBy(['user' => $user_id, 'group' => $value[1]]);
                                $rights = $value[0] == true ? 1 : 0;
                                if (!$AlreadyLinked && $group) {
                                    $UsersLinkGroups = new UsersLinkGroups();
                                    $UsersLinkGroups->setGroup($group);
                                    $UsersLinkGroups->setUser($user);
                                    $UsersLinkGroups->setRights($rights);
                                    $this->entityManager->persist($UsersLinkGroups);
                                } else if ($group) {
                                    $AlreadyLinked->setRights($rights);
                                    $this->entityManager->persist($AlreadyLinked);
                                }
                            }
                            foreach ($AllGroupsFromUser as $key1 => $value1) {
                                if ($value1->getGroup() == $value[1]) {
                                    unset($AllGroupsFromUser[$key1]);
                                }
                            }
                        }
                        // Retire les groupes qui ne lui sont plus attribués
                        foreach ($AllGroupsFromUser as $key2 => $value2) {
                            $AlreadyLinked = $this->entityManager->getRepository(UsersLinkGroups::class)->findOneBy(['user' => $user_id, 'group' => $value2->getGroup()]);
                            if ($AlreadyLinked) {
                                $this->entityManager->remove($AlreadyLinked);
                            }
                        }
                        $this->entityManager->flush();
                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'missing data'];
                    }
                },'delete_user' => function($data) {
                    if (isset($data['user_id']) && $data['user_id'] != null) {
                        $user_id = htmlspecialchars($data['user_id']);
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);
                        $this->entityManager->remove($user);


                        $userR = $this->entityManager->getRepository(Regular::class)->findOneBy(['id' => $user_id]);
                        if ($userR) {
                            $this->entityManager->remove($userR);
                        }
    
                        $userT = $this->entityManager->getRepository(Teacher::class)->findOneBy(['id' => $user_id]);
                        if ($userT) {
                            $this->entityManager->remove($userT);
                        }

                        // Delete le lien entre l'utilisateur et le groupe
                        $userlinkgroups = $this->entityManager->getRepository(UsersLinkGroups::class)->findBy(['user' => $user_id]);
                        foreach ($userlinkgroups as $key_ulg => $value_ulg) {
                            $this->entityManager->remove($userlinkgroups[$key_ulg]);
                        }
    
                        // Delete le lien entre l'utilisateur et les applications
                        $userlinkapplications = $this->entityManager->getRepository(UsersLinkApplications::class)->findBy(['user' => $user_id]);
                        foreach ($userlinkapplications as $key_ula => $value_ula) {
                            $this->entityManager->remove($userlinkapplications[$key_ula]);
                        }
                        $this->entityManager->flush();
                        return ['response' => 'success'];
                    } else {
                        return ['response' => 'missing data'];
                    }
                },
                'disable_user' => function($data) {
                    if (isset($data['user_id']) && $data['user_id'] != null) {
                        $user_id = htmlspecialchars($data['user_id']);
                        $userR = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user_id]);
                        if ($userR) {
                            $userR->setActive(0);
                            $this->entityManager->persist($userR);
                        }
                        $this->entityManager->flush();

                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'search_user_by_name' => function($data) {
                    if (isset($data['name']) && $data['name'] != null &&
                    isset($data['userspp']) && $data['userspp'] != null &&
                    isset($data['page']) && $data['page'] != null &&
                    isset($data['group']) && $data['group'] != null) {
                        $page = htmlspecialchars($data['page']); 
                        $userspp = htmlspecialchars($data['userspp']);
                        $name = htmlspecialchars($data['name']);
                        $group = htmlspecialchars($data['group']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->searchUser($name, $page, $userspp, $group);
                    } else {
                        return ['response' => 'missing data'];
                    }
                },
                'global_search_user_by_name' => function($data) {
                    if (isset($data['name']) && $data['name'] != null &&
                    isset($data['userspp']) && $data['userspp'] != null &&
                    isset($data['page']) && $data['page'] != null) {
                        $page = htmlspecialchars($data['page']);
                        $userspp = htmlspecialchars($data['userspp']);
                        $name = htmlspecialchars($data['name']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->globalSearchUser($name, $page, $userspp);
                    } else {
                        return ['response' => 'missing data'];
                    }
                },
                'search_group_by_name' => function($data) {
                    if (isset($data['name']) && $data['name'] != null &&
                    isset($data['groupspp']) && $data['groupspp'] != null &&
                    isset($data['page']) && $data['page'] != null) {
                        $name = htmlspecialchars($data['name']);
                        $page = htmlspecialchars($data['page']);
                        $groupspp = htmlspecialchars($data['groupspp']);
                        return $this->entityManager->getRepository(Groups::class)->searchGroup($name, $page, $groupspp);
                    } else {
                        return ['response' => 'missing data'];
                    }
                },
                'send_request_reset_user_password' => function($data) {
                    if (isset($data['user_id']) && $data['user_id'] != null) {
                        $user_id = htmlspecialchars($data['user_id']);

                        $user = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user_id]);
                        // create token for this user
                        $token = bin2hex(random_bytes(16));
                        $user->setRecoveryToken($token);
                        $mail = $user->getEmail();
                        $this->entityManager->persist($user);
                        

                        $userLang = isset($_COOKIE['lng']) ? htmlspecialchars(strip_tags(trim($_COOKIE['lng']))) : 'fr';

                        // create the confirmation account link and set the email template to be used      
                        $accountConfirmationLink = $_ENV['VS_HOST']."/classroom/password_manager.php?page=update&token=$token";
                        $emailTtemplateBody = $userLang."_confirm_account";

                        // init i18next instance
                        if(is_dir(__DIR__."/../../../../../openClassroom")){
                            i18next::init($userLang,__DIR__."/../../../../../openClassroom/classroom/assets/lang/__lng__/ns.json");
                        }else {
                            i18next::init($userLang,__DIR__."/../../../../../classroom/assets/lang/__lng__/ns.json");
                        }

                        $emailSubject = i18next::getTranslation('superadmin.users.mail.resetPassword.subject');
                        $bodyTitle = i18next::getTranslation('superadmin.users.mail.resetPassword.bodyTitle');
                        $textBeforeLink = i18next::getTranslation('superadmin.users.mail.resetPassword.textBeforeLink');
                        $body = "
                            <a href='$accountConfirmationLink' style='text-decoration: none;padding: 10px;background: #27b88e;color: white;margin: 1rem auto;width: 50%;display: block;'>
                                $bodyTitle
                            </a>
                            <br>
                            <br>
                            <p>$textBeforeLink $accountConfirmationLink
                        ";

                        // send email
                        $emailSent = Mailer::sendMail($mail,  $emailSubject, $body, strip_tags($body),$emailTtemplateBody);

                        if ($emailSent) {
                            $this->entityManager->flush();
                        }
                        /////////////////////////////////////
                        return ['mail' => $mail, 'token' => $token, 'isSent' => $emailSent, 'link' => $accountConfirmationLink];
                        // send him a link to create a new password
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'update_user_app' => function($data) {
                    if (isset($data['user_id']) && $data['user_id'] != null &&
                    isset($data['user_app']) && $data['user_app'] != null ) {
                        $user_id = isset($data['user_id']) ? htmlspecialchars($data['user_id']) : null;
                        $user_app = json_decode($data['user_app']);

                        if ($user_id == null || $user_app == null) {
                            return ['message' => 'missing data'];
                        } else {
                            $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);
                            if ($user) {
                                foreach ($user_app as $key => $value) {
                                    $AppExist = $this->entityManager->getRepository(UsersLinkApplications::class)->findOneBy(['user' => $user, 'application' => $value[0]]);
                                    // Récupère l'entité application liée à l'id de celle-ci (permet de la set ensuite en tant qu'entité dans le lien entre groupe et application)
                                    $application = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $value[0]]);
                                    if ($value[1] == true) {
                                        $date_begin = \DateTime::createFromFormat('Y-m-d', $value[2]);
                                        $date_end = \DateTime::createFromFormat('Y-m-d', $value[3]);
                                        if ($AppExist) {
                                            $AppExist->setApplication($application);
                                            $AppExist->setUser($user);
                                            $AppExist->setDateBegin($date_begin);
                                            $AppExist->setDateEnd($date_end);
                                            $this->entityManager->persist($AppExist);
                                        } else {
                                            $Applications = new UsersLinkApplications();
                                            $Applications->setApplication($application);
                                            $Applications->setUser($user);
                                            $Applications->setDateBegin($date_begin);
                                            $Applications->setDateEnd($date_end);
                                            $this->entityManager->persist($Applications);
                                        }
                                    } else {
                                        if ($AppExist) {
                                            $this->entityManager->remove($AppExist);
                                        } 
                                    }
                                }
                                $this->entityManager->flush();
                                return ['message' => 'success'];
                            } else {
                                return ['message' => 'User not found'];
                            }
                        }
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'is_user_admin' => function() {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => htmlspecialchars($_SESSION['id'])]);
                    $userR = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user]);
                    if ($userR->getIsAdmin()) {
                        return ['Admin' => true];
                    }
                    return ['Admin' => false];
                }
            );
        }
    }
}