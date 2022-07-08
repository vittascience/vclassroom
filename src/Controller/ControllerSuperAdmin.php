<?php

namespace Classroom\Controller;

use Utils\Mailer;
use User\Entity\User;
use User\Entity\Regular;
use User\Entity\Teacher;
use Aiken\i18next\i18next;
use Classroom\Entity\Groups;
use Classroom\Entity\LtiTool;
use Classroom\Entity\Applications;
use Classroom\Entity\Restrictions;
use Classroom\Entity\UsersLinkGroups;
use Classroom\Entity\UsersRestrictions;
use Classroom\Entity\UsersLinkApplications;
use Classroom\Entity\GroupsLinkApplications;
use Classroom\Entity\UsersLinkApplicationsFromGroups;

class ControllerSuperAdmin extends Controller
{
    public function __construct($entityManager, $user)
    {
        parent::__construct($entityManager, $user);

        // Vérifie si l'utilisateur actuel est admin, si il ne l'est pas alors lui refuser toutes actions
        $Autorisation = $this->entityManager->getRepository('User\Entity\Regular')->findOneBy(['user' => htmlspecialchars($_SESSION['id'])]);
        if (!$Autorisation || $Autorisation->getIsAdmin() == false || $_SERVER['REQUEST_METHOD'] != 'POST') {
            $this->actions = array(
                'is_user_admin' => function () {
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
                    return $this->entityManager->getRepository(Groups::class)->findAllWithApps();
                },
                'panel_groups_info' => function ($data) {
                    if (
                        isset($data['sort']) && $data['sort'] != null &&
                        isset($data['page']) && $data['page'] != null &&
                        isset($data['groupspp']) && $data['groupspp'] != null
                    ) {
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
                    if (isset($data['id']) && $data['id'] != null && isset($data['userspp']) && $data['userspp'] != null && isset($data['page']) && $data['page'] != null && isset($data['sort']) && $data['sort'] != null) {
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
                'get_all_applications' => function () {
                    $apps = $this->entityManager->getRepository(Applications::class)->findAll();

                    $Result_array = [];
                    foreach ($apps as $key => $value) {
                        $Result_array[] = $value->jsonSerialize();
                    }
                    return $Result_array;
                },
                'get_application_by_id' => function ($data) {
                    if (isset($data['application_id']) && $data['application_id'] != null) {
                        $application_id = htmlspecialchars($data['application_id']);

                        $app = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $application_id])->jsonSerialize();
                        $ltiData = $this->entityManager->getRepository(LtiTool::class)->findOneBy(['application' => $application_id]);
                        if ($ltiData) {
                            $app['lti'] = $ltiData->jsonSerialize();
                        }
                        return $app;
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'update_application' => function ($data) {
                    if (
                        isset($data['application_id']) && $data['application_id'] != null &&
                        isset($data['application_name']) && $data['application_name'] != null &&
                        isset($data['application_description']) && $data['application_description'] != null
                    ) {
                        $application_id = htmlspecialchars($data['application_id']);
                        $application_name = htmlspecialchars($data['application_name']);
                        $application_description = htmlspecialchars($data['application_description']);
                        $application_color = isset($data['application_color']) ? $data['application_color'] : null;
                        $restriction_max = isset($data['restriction_max']) ? htmlspecialchars($data['restriction_max']) : 0;


                        $application_sort_index = isset($data['application_sort_index']) ? htmlspecialchars($data['application_sort_index']) : 0;
                        $application_background_image = isset($data['application_background_image']) ? $data['application_background_image'] : null;



                        $application_image = isset($data['application_image']) ? htmlspecialchars($data['application_image']) : null;
                        $lti_data = isset($data['lti_data']) ? json_decode($data['lti_data'], true) : null;

                        $app = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $application_id]);
                        $app->setName($application_name);
                        $app->setDescription($application_description);
                        $app->setImage($application_image);
                        $app->setColor($application_color);
                        $app->setMaxPerTeachers($restriction_max);
                        $app->setSort($application_sort_index);
                        $app->setBackgroundImage($application_background_image);
                        
                        if ($lti_data['isLti']) {
                            $app->setIsLti($lti_data['isLti']);
                        } else {
                            $app->setIsLti(false);
                        }
                        $this->entityManager->persist($app);
                        $this->entityManager->flush();

                        // Only for lti apps
                        if ($lti_data['isLti']) {
                            $lti_data['clientId'] = isset($lti_data['clientId']) ? htmlspecialchars($lti_data['clientId']) : null;
                            $lti_data['deploymentId'] = isset($lti_data['deploymentId']) ? htmlspecialchars($lti_data['deploymentId']) : null;
                            $lti_data['toolUrl'] = isset($lti_data['toolUrl']) ? htmlspecialchars($lti_data['toolUrl']) : null;
                            $lti_data['publicKeySet'] = isset($lti_data['publicKeySet']) ? htmlspecialchars($lti_data['publicKeySet']) : null;
                            $lti_data['loginUrl'] = isset($lti_data['loginUrl']) ? htmlspecialchars($lti_data['loginUrl']) : null;
                            $lti_data['redirectionUrl'] = isset($lti_data['redirectionUrl']) ? htmlspecialchars($lti_data['redirectionUrl']) : null;
                            $lti_data['deepLinkUrl'] = isset($lti_data['deepLinkUrl']) ? htmlspecialchars($lti_data['deepLinkUrl']) : null;
                            $lti_data['privateKey'] = isset($lti_data['privateKey']) ? htmlspecialchars($lti_data['privateKey']) : null;

                            // Check if data are not null
                            if (
                                $lti_data['clientId'] == null ||
                                $lti_data['deploymentId'] == null ||
                                $lti_data['toolUrl'] == null ||
                                $lti_data['publicKeySet'] == null ||
                                $lti_data['loginUrl'] == null ||

                                $lti_data['redirectionUrl'] == null ||
                                $lti_data['deepLinkUrl'] == null ||
                                $lti_data['privateKey'] == null
                            ) {
                                return ['message' => 'missing data'];
                            }

                            $lti = $this->entityManager->getRepository(LtiTool::class)->findOneBy(['application' => $application_id]);
                            if (!$lti) {
                                $lti = new LtiTool();
                                $lti->setApplication($app);
                                $uid = "";
                                do {
                                    $uid = uniqid();
                                    $isUnique = $this->entityManager->getRepository(LtiTool::class)->findOneBy(['kid' => $uid]);
                                } while ($isUnique);
                                $lti->setKid($uid);
                            }

                            $lti->setClientId($lti_data['clientId']);
                            $lti->setDeploymentId($lti_data['deploymentId']);
                            $lti->setToolUrl($lti_data['toolUrl']);
                            $lti->setPublicKeySet($lti_data['publicKeySet']);
                            $lti->setLoginUrl($lti_data['loginUrl']);
                            $lti->setRedirectionUrl($lti_data['redirectionUrl']);
                            $lti->setDeepLinkUrl($lti_data['deepLinkUrl']);
                            $lti->setPrivateKey($lti_data['privateKey']);

                            $this->entityManager->persist($lti);
                            $this->entityManager->flush();
                        } else {
                            $ltiTool = $this->entityManager->getRepository(LtiTool::class)->findOneBy(['application' => $application_id]);
                            if ($ltiTool) {
                                $this->entityManager->remove($ltiTool);
                                $this->entityManager->flush();
                            }
                        }

                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'delete_application' => function ($data) {
                    if (isset($data['application_id']) && $data['application_id'] != null) {
                        $application_id = htmlspecialchars($data['application_id']);

                        $app = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $application_id]);

                        $groupLinkApp = $this->entityManager->getRepository(GroupsLinkApplications::class)->findBy(['application' => $application_id]);
                        $userLinkApp = $this->entityManager->getRepository(UsersLinkApplications::class)->findBy(['application' => $application_id]);
                        $userLinkApplicationFromGroup = $this->entityManager->getRepository(UsersLinkApplicationsFromGroups::class)->findBy(['application' => $application_id]);


                        foreach ($groupLinkApp as $groupApp) {
                            $this->entityManager->remove($groupApp);
                        }
                        foreach ($userLinkApp as $userApp) {
                            $this->entityManager->remove($userApp);
                        }
                        foreach ($userLinkApplicationFromGroup as $userAppFromGroup) {
                            $this->entityManager->remove($userAppFromGroup);
                        }

                        $this->entityManager->remove($app);
                        $this->entityManager->flush();

                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'create_application' => function ($data) {
                    if (
                        isset($data['application_name']) && $data['application_name'] != null &&
                        isset($data['application_description']) && $data['application_description'] != null
                    ) {
                        $application_name = htmlspecialchars($data['application_name']);
                        $application_description = htmlspecialchars($data['application_description']);
                        $application_image = isset($data['application_image']) ? htmlspecialchars($data['application_image']) : null;
                        $application_color = isset($data['application_color']) ? $data['application_color'] : null;
                        $restriction_max = isset($data['restriction_max']) ? htmlspecialchars($data['restriction_max']) : 0;

                        $lti_data = isset($data['lti_data']) ? json_decode($data['lti_data'], true) : null;

                        $application_sort_index = isset($data['application_sort_index']) ? htmlspecialchars($data['application_sort_index']) : 0;
                        $application_background_image = isset($data['application_background_image']) ? $data['application_background_image'] : null;

                        $app = new Applications();
                        $app->setName($application_name);
                        $app->setDescription($application_description);
                        $app->setImage($application_image);
                        $app->setColor($application_color);
                        $app->setMaxPerTeachers($restriction_max);
                        $app->setSort($application_sort_index);
                        $app->setBackgroundImage($application_background_image);

                        if ($lti_data['isLti']) {
                            $app->setIsLti($lti_data['isLti']);
                        } else {
                            $app->setIsLti(false);
                        }
                        $this->entityManager->persist($app);
                        $this->entityManager->flush();

                        // Only for lti apps

                        // Only for lti apps
                        if ($lti_data['isLti']) {
                            $lti_data['clientId'] = isset($lti_data['clientId']) ? htmlspecialchars($lti_data['clientId']) : null;
                            $lti_data['deploymentId'] = isset($lti_data['deploymentId']) ? htmlspecialchars($lti_data['deploymentId']) : null;
                            $lti_data['toolUrl'] = isset($lti_data['toolUrl']) ? htmlspecialchars($lti_data['toolUrl']) : null;
                            $lti_data['publicKeySet'] = isset($lti_data['publicKeySet']) ? htmlspecialchars($lti_data['publicKeySet']) : null;
                            $lti_data['loginUrl'] = isset($lti_data['loginUrl']) ? htmlspecialchars($lti_data['loginUrl']) : null;
                            $lti_data['redirectionUrl'] = isset($lti_data['redirectionUrl']) ? htmlspecialchars($lti_data['redirectionUrl']) : null;
                            $lti_data['deepLinkUrl'] = isset($lti_data['deepLinkUrl']) ? htmlspecialchars($lti_data['deepLinkUrl']) : null;
                            $lti_data['privateKey'] = isset($lti_data['privateKey']) ? htmlspecialchars($lti_data['privateKey']) : null;

                            // Check if data are not null
                            if (
                                $lti_data['clientId'] == null ||
                                $lti_data['deploymentId'] == null ||
                                $lti_data['toolUrl'] == null ||
                                $lti_data['publicKeySet'] == null ||
                                $lti_data['loginUrl'] == null ||

                                $lti_data['redirectionUrl'] == null ||
                                $lti_data['deepLinkUrl'] == null ||
                                $lti_data['privateKey'] == null
                            ) {
                                return ['message' => 'missing data'];
                            }

                            $ltiTool = new LtiTool();
                            $ltiTool->setApplication($app);
                            $ltiTool->setClientId($lti_data['clientId']);
                            $ltiTool->setDeploymentId($lti_data['deploymentId']);
                            $ltiTool->setToolUrl($lti_data['toolUrl']);
                            $ltiTool->setPublicKeySet($lti_data['publicKeySet']);
                            $ltiTool->setLoginUrl($lti_data['loginUrl']);
                            $ltiTool->setRedirectionUrl($lti_data['redirectionUrl']);
                            $ltiTool->setDeepLinkUrl($lti_data['deepLinkUrl']);
                            $ltiTool->setPrivateKey($lti_data['privateKey']);

                            $uid = "";
                            do {
                                $uid = uniqid();
                                $isUnique = $this->entityManager->getRepository(LtiTool::class)->findOneBy(['kid' => $uid]);
                            } while ($isUnique);
                            $ltiTool->setKid($uid);

                            $this->entityManager->persist($ltiTool);
                            $this->entityManager->flush();
                        }

                        return ['message' => 'success', 'application_id' => $app->getId()];
                    } else {
                        return ['message' => 'missing data'];
                    }
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
                'create_group' => function ($data) {
                    if (
                        isset($data['name']) && $data['name'] != null &&
                        isset($data['description']) && $data['description'] != null &&
                        isset($data['applications']) && $data['applications'] != null && 
                        isset($data['global_restriction']) && $data['global_restriction'] != null
                    ) {
                        $applications = json_decode($data['applications']);
                        $group_name = htmlspecialchars($data['name']);
                        $group_desc = htmlspecialchars($data['description']);
                        $global_restrictions = json_decode($data['global_restriction']);

                        // group restrictions 
                        $date_begin = $global_restrictions[0] != null ? \DateTime::createFromFormat('Y-m-d', $global_restrictions[0]) : null;
                        $date_end = $global_restrictions[1] != null ? \DateTime::createFromFormat('Y-m-d', $global_restrictions[1]) : null;
                        $max_students_per_teachers = $global_restrictions[2];
                        $max_students_per_groups = $global_restrictions[3];
                        $max_teachers_per_groups = $global_restrictions[4]; 


                        $group = new Groups;
                        $group->setName($group_name);
                        $group->setDescription($group_desc);
                        $group->setLink();

                        if ($date_begin != null && $date_end != null) {
                            $group->setDateBegin($date_begin);
                            $group->setDateEnd($date_end);
                        }              
                        $group->setmaxStudentsPerTeachers($max_students_per_teachers);
                        $group->setmaxStudents($max_students_per_groups);
                        $group->setmaxTeachers($max_teachers_per_groups);

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

                        $this->manageAppsForGroups($applications, $group_id, $group);
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


                        $ApplicationFromGroup = $this->entityManager->getRepository(UsersLinkApplicationsFromGroups::class)->findBy(['group' => $group_id]);
                        foreach ($ApplicationFromGroup as $application) {
                            $this->entityManager->remove($application);
                        }

                        $this->entityManager->flush();
                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'error'];
                    }
                },
                'update_group' => function ($data) {
                    if (
                        isset($data['id']) && $data['id'] != null &&
                        isset($data['name']) && $data['name'] != null &&
                        isset($data['description']) && $data['description'] != null &&
                        isset($data['applications']) && $data['applications'] != null &&
                        isset($data['global_restriction']) && $data['global_restriction'] != null
                    ) {
                        $applications = json_decode($data['applications']);
                        $global_restrictions = json_decode($data['global_restriction']);

                        $group_id = htmlspecialchars($data['id']);
                        $group_name = htmlspecialchars($data['name']);
                        $group_description = htmlspecialchars($data['description']);

                        $date_begin = $global_restrictions[0] != null ? \DateTime::createFromFormat('Y-m-d', $global_restrictions[0]) : null;
                        $date_end = $global_restrictions[1] != null ? \DateTime::createFromFormat('Y-m-d', $global_restrictions[1]) : null;

                        $group = $this->entityManager->getRepository(Groups::class)->findOneBy(['id' => $group_id]);
        
                        $max_students_per_teachers = $global_restrictions[2];
                        $max_students_per_groups = $global_restrictions[3];
                        $max_teachers_per_groups = $global_restrictions[4]; 

                        if ($group) {
                            if ($date_begin != null && $date_end != null) {
                                $group->setDateBegin($date_begin);
                                $group->setDateEnd($date_end);
                            }              
                            $group->setmaxStudentsPerTeachers($max_students_per_teachers);
                            $group->setmaxStudents($max_students_per_groups);
                            $group->setmaxTeachers($max_teachers_per_groups); 
                            $group->setDescription($group_description);
                            $group->setName($group_name);
                            $this->entityManager->persist($group);
                        }

                        $this->manageAppsForGroups($applications, $group_id, $group);
                        $this->entityManager->flush();
                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'get_user_info' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $user_id = htmlspecialchars($data['id']);
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);
                        return $user->jsonSerialize();
                    }
                },
                'get_all_users_in_a_group' => function () {
                    return $this->entityManager->getRepository(UsersLinkGroups::class)->getAllMembersInAGroup();
                },
                'get_all_users_with_their_groups' => function ($data) {
                    if (isset($data['sort']) && $data['sort'] != null && isset($data['page']) && $data['page'] != null && isset($data['userspp']) && $data['userspp'] != null) {
                        $sort = htmlspecialchars($data['sort']);
                        $page = htmlspecialchars($data['page']);
                        $userspp = htmlspecialchars($data['userspp']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->getAllUsersWithTheirGroups($sort, $page, $userspp);
                    }
                },
                'get_user_info_with_his_groups' => function ($data) {
                    if (isset($data['id']) && $data['id'] != null) {
                        $user_id = htmlspecialchars($data['id']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->getUsersWithHisGroups($user_id);
                    }
                },
                'create_user' => function ($data) {
                    if (
                        isset($data['firstname']) && $data['firstname'] != null &&
                        isset($data['surname']) && $data['surname'] != null &&
                        isset($data['groups']) && $data['groups'] != null &&
                        isset($data['mail']) && $data['mail'] != null &&
                        isset($data['admin']) && $data['admin'] != null &&
                        isset($data['teacher']) && $data['teacher'] != null
                    ) {
                        $apps =  json_decode($data['apps']);
                        $groups =  json_decode($data['groups']);
                        $surname = htmlspecialchars($data['surname']);
                        $firstname = htmlspecialchars($data['firstname']);
                        $mail = htmlspecialchars($data['mail']);
                        $admin = htmlspecialchars($data['admin'])  == "true" ? true : false;
                        $isTeacher = htmlspecialchars($data['teacher']) == "true" ? true : false;
                        $school = isset($data['school']) ? htmlspecialchars($data['school']) : null;
                        $grade = isset($data['grade']) ? (int)htmlspecialchars($data['grade']) : null;
                        $subject = isset($data['subject']) ? (int)htmlspecialchars($data['subject']) : null;

                        $checkExist = $this->entityManager->getRepository(Regular::class)->findOneBy(['email' => $mail]);

                        if (!$checkExist) {
                            // further information 
                            $pseudo = isset($data['pseudo']) ? htmlspecialchars($data['pseudo']) : null;
                            $phone = isset($data['phone']) ? htmlspecialchars($data['phone']) : null;
                            $bio = isset($data['bio']) ? htmlspecialchars($data['bio']) : null;

                            $user = new User();
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
                            $regular = new Regular($user, $mail, $bio, $phone, false, $admin, null, null, false);
                            $regular->setConfirmToken($confirmationToken);
                            $this->entityManager->persist($regular);


                            if ($isTeacher) {
                                $teacher = new Teacher($user, $subject, $school, $grade);
                                $this->entityManager->persist($teacher);
                            }

                            if (!empty($apps)) {
                                $date_begin = $apps[0] != null ? \DateTime::createFromFormat('Y-m-d', $apps[0]) : null;
                                $date_end = $apps[1] != null ? \DateTime::createFromFormat('Y-m-d', $apps[1]) : null;
                                $max_students = $apps[2] != null ? (int)$apps[2] : 0;

                                if ($date_begin != null && $date_end != null) {
                                    $UserRestrictions = new UsersRestrictions();
                                    $UserRestrictions->setUser($user);
                                    if ($date_begin != null && $date_end != null) {
                                        $UserRestrictions->setDateBegin($date_begin);
                                        $UserRestrictions->setDateEnd($date_end);
                                    }
                                    $UserRestrictions->setMaxStudents($max_students);
                                    $this->entityManager->persist($UserRestrictions);
                                }

                                $apps = $apps[3];
                                for ($i = 0; $i < count($apps); $i++) {
                                    if ($apps[$i][1] == true) {
                                        $app = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $apps[$i][0]]);
                                        $max = $apps[$i][2] != null ? (int)$apps[$i][2] : 0;
                                        $userLinkApp = new UsersLinkApplications();
                                        $userLinkApp->setUser($user);
                                        $userLinkApp->setApplication($app);
                                        $userLinkApp->setmaxActivitiesPerTeachers($max);
                                        $this->entityManager->persist($userLinkApp);
                                    }
                                }
                            }

                            $this->entityManager->flush();

                            $emailSent = $this->sendGenericMailWithToken(
                                $mail,
                                "_confirm_account",
                                $_ENV['VS_HOST'] . "/classroom/registration.php?token=$confirmationToken",
                                'manager.users.mail.finalizeAccount.subject',
                                'manager.users.mail.finalizeAccount.bodyTitle',
                                'manager.users.mail.finalizeAccount.textBeforeLink'
                            );

                            return ['message' => 'success', 'mail' => $emailSent, 'id' => $user->getId()];
                        } else {
                            return ['message' => 'mailAlreadyExist'];
                        }
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'update_user' => function ($data) {
                    if (
                        !empty($data['user_id']) &&
                        !empty($data['firstname']) &&
                        !empty($data['surname']) &&
                        !empty($data['groups']) &&
                        !empty($data['mail']) &&
                        !empty($data['admin']) &&
                        !empty($data['teacher']) &&
                        !empty($data['isactive'])
                    ) {

                        $user_id = htmlspecialchars($data['user_id']);
                        $groups =  json_decode($data['groups']);
                        $surname = htmlspecialchars($data['surname']);
                        $firstname = htmlspecialchars($data['firstname']);
                        $mail = htmlspecialchars($data['mail']);
                        $admin = htmlspecialchars($data['admin']) == "true" ? true : false;
                        $isTeacher = htmlspecialchars($data['teacher']) == "true" ? true : false;

                        $application = json_decode($data['application']);

                        // further information 
                        $pseudo = isset($data['pseudo']) ? htmlspecialchars($data['pseudo']) : null;
                        $phone = isset($data['phone']) ? htmlspecialchars($data['phone']) : null;
                        $bio = isset($data['bio']) ? htmlspecialchars($data['bio']) : null;
                        $school = isset($data['school']) ? htmlspecialchars($data['school']) : null;
                        $grade = isset($data['grade']) ? (int)htmlspecialchars($data['grade']) : null;
                        $subject = isset($data['subject']) ? (int)htmlspecialchars($data['subject']) : null;

                        $isactive = $data['isactive'] == "true" ? true : false;

                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);
                        $user->setFirstname($firstname);
                        $user->setSurname($surname);
                        $user->setPseudo($pseudo);
                        $user->setUpdateDate(new \DateTime());
                        $this->entityManager->persist($user);

                        $regular = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user_id]);
                        if ($regular) {
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

                        // If the user is already a teacher
                        $teacher = $this->entityManager->getRepository(Teacher::class)->findOneBy(['user' => $user_id]);
                        // Uodate the teacher informations
                        if ($isTeacher && $teacher) {
                            $teacher->setSubject($subject);
                            $teacher->setSchool($school);
                            $teacher->setGrade($grade);
                            $this->entityManager->persist($teacher);
                        }
                        // if he's not a teacher, we create the teacher entity 
                        else if ($isTeacher) {
                            $teacher = new Teacher($user, $subject, $school, $grade);
                            $this->entityManager->persist($teacher);
                        }
                        // delete teh teacher data
                        else if ($teacher && !$isTeacher) {
                            $this->entityManager->remove($teacher);
                        }
                        // get all groups from user
                        $AllGroupsFromUser = $this->entityManager->getRepository(UsersLinkGroups::class)->findBy(['user' => $user_id]);

                        $group = "";
                        if (!empty($groups)) {
                            if ($groups[1] != -1) {
                                $group = $this->entityManager->getRepository(Groups::class)->findOneBy(['id' => $groups[1]]);
                                $AlreadyLinked = $this->entityManager->getRepository(UsersLinkGroups::class)->findOneBy(['user' => $user_id, 'group' => $groups[1]]);
                                $rights = $groups[0] == true ? 1 : 0;
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
                                if ($value1->getGroup() == $groups[1]) {
                                    unset($AllGroupsFromUser[$key1]);
                                }
                            }
                        }
                        // delete the groups that are not in the new list
                        foreach ($AllGroupsFromUser as $key2 => $value2) {
                            $AlreadyLinked = $this->entityManager->getRepository(UsersLinkGroups::class)->findOneBy(['user' => $user_id, 'group' => $value2->getGroup()]);
                            $ApplicationFromGroup = $this->entityManager->getRepository(UsersLinkApplicationsFromGroups::class)->findOneBy(['user' => $user_id, 'group' => $value2->getGroup()]);
                            if ($AlreadyLinked) {
                                $this->entityManager->remove($AlreadyLinked);
                            }
                            if ($ApplicationFromGroup) {
                                $this->entityManager->remove($ApplicationFromGroup);
                            }
                        }

                        // Manage the group apps for user
                        $appsManager = $this->manageAppsFromGroupsUsers($user_id, $application, $groups, $user);
                        if ($appsManager != true) {
                            return $appsManager;
                        }

                        $this->entityManager->flush();
                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'missing data'];
                    }
                }, 'delete_user' => function ($data) {
                    if (isset($data['user_id']) && $data['user_id'] != null) {
                        $user_id = htmlspecialchars($data['user_id']);
                        $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);
                        $this->entityManager->remove($user);


                        $userR = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user_id]);
                        if ($userR) {
                            $this->entityManager->remove($userR);
                        }

                        $userT = $this->entityManager->getRepository(Teacher::class)->findOneBy(['user' => $user_id]);
                        if ($userT) {
                            $this->entityManager->remove($userT);
                        }

                        // Delete the link between the user and the group
                        $userlinkgroups = $this->entityManager->getRepository(UsersLinkGroups::class)->findBy(['user' => $user_id]);
                        foreach ($userlinkgroups as $key_ulg => $value_ulg) {
                            $this->entityManager->remove($userlinkgroups[$key_ulg]);
                        }

                        // Delete the link between the user and the application
                        $userlinkapplications = $this->entityManager->getRepository(UsersLinkApplications::class)->findBy(['user' => $user_id]);
                        foreach ($userlinkapplications as $key_ula => $value_ula) {
                            $this->entityManager->remove($userlinkapplications[$key_ula]);
                        }
                        $this->entityManager->flush();
                        return ['message' => 'success'];
                    } else {
                        return ['message' => 'missing data'];
                    }
                },
                'disable_user' => function ($data) {
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
                'global_search_user_by_name' => function ($data) {
                    if (
                        isset($data['name']) && $data['name'] != null &&
                        isset($data['userspp']) && $data['userspp'] != null &&
                        isset($data['page']) && $data['page'] != null
                    ) {
                        $page = htmlspecialchars($data['page']);
                        $userspp = htmlspecialchars($data['userspp']);
                        $name = htmlspecialchars($data['name']);
                        return $this->entityManager->getRepository(UsersLinkGroups::class)->globalSearchUser($name, $page, $userspp);
                    } else {
                        return ['response' => 'missing data'];
                    }
                },
                'search_group_by_name' => function ($data) {
                    if (
                        isset($data['name']) && $data['name'] != null &&
                        isset($data['groupspp']) && $data['groupspp'] != null &&
                        isset($data['page']) && $data['page'] != null
                    ) {
                        $name = htmlspecialchars($data['name']);
                        $page = htmlspecialchars($data['page']);
                        $groupspp = htmlspecialchars($data['groupspp']);
                        return $this->entityManager->getRepository(Groups::class)->searchGroup($name, $page, $groupspp);
                    } else {
                        return ['response' => 'missing data'];
                    }
                },
                'send_request_reset_user_password' => function ($data) {
                    if (isset($data['user_id']) && $data['user_id'] != null) {
                        $user_id = htmlspecialchars($data['user_id']);

                        $user = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user_id]);
                        // create token for this user
                        $token = bin2hex(random_bytes(16));
                        $user->setRecoveryToken($token);
                        $mail = $user->getEmail();
                        $this->entityManager->persist($user);

                        // create the confirmation account link and set the email template to be used      
                        $accountConfirmationLink = $_ENV['VS_HOST'] . "/classroom/password_manager.php?page=update&token=$token";
                        $emailSent = $this->sendGenericMailWithToken(
                            $mail,
                            "_confirm_account",
                            $accountConfirmationLink,
                            'manager.users.mail.resetPassword.subject',
                            'manager.users.mail.resetPassword.bodyTitle',
                            'manager.users.mail.resetPassword.textBeforeLink'
                        );

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
                'update_user_app' => function ($data) {
                    if (
                        isset($data['user_id']) && $data['user_id'] != null &&
                        isset($data['user_app']) && $data['user_app'] != null &&
                        isset($data['global_user_restriction']) && $data['global_user_restriction'] != null
                    ) {

                        $user_id = isset($data['user_id']) ? htmlspecialchars($data['user_id']) : null;
                        $user_app = json_decode($data['user_app']);
                        $global_user_restriction = json_decode($data['global_user_restriction']);

                        if ($user_id == null) {
                            return ['message' => 'missing data'];
                        } else {
                            $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user_id]);
                            if ($user) {

                                $UserRestrictions = $this->entityManager->getRepository(UsersRestrictions::class)->findOneBy(['user' => $user]);
                                $date_begin = $global_user_restriction[0] != null ? \DateTime::createFromFormat('Y-m-d', $global_user_restriction[0]) : null;
                                $date_end = $global_user_restriction[1] != null ? \DateTime::createFromFormat('Y-m-d', $global_user_restriction[1]) : null;

                                if (!$UserRestrictions) {
                                    $UserRestrictions = new UsersRestrictions();
                                }
                                $UserRestrictions->setUser($user);
                                if ($date_begin != null && $date_end != null) {
                                    $UserRestrictions->setDateBegin($date_begin);
                                    $UserRestrictions->setDateEnd($date_end);
                                }

                                $UserRestrictions->setmaxStudents($global_user_restriction[2]);
                                $this->entityManager->persist($UserRestrictions);

                                foreach ($user_app as $key => $value) {
                                    $AppExist = $this->entityManager->getRepository(UsersLinkApplications::class)->findOneBy(['user' => $user, 'application' => $value[0]]);
                                    $application = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $value[0]]);

                                    if ($value[1] == true) {
                                        if (!$AppExist) {
                                            $AppExist = new UsersLinkApplications();
                                        }
                                        $AppExist->setApplication($application);
                                        $AppExist->setUser($user);
                                        $AppExist->setmaxActivitiesPerTeachers($value[2]);
                                        $this->entityManager->persist($AppExist);
                                        
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
                'is_user_admin' => function () {
                    $user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => htmlspecialchars($_SESSION['id'])]);
                    $userR = $this->entityManager->getRepository(Regular::class)->findOneBy(['user' => $user]);
                    if ($userR->getIsAdmin()) {
                        return ['Admin' => true];
                    }
                    return ['Admin' => false];
                },
                'get_restriction_activity_applications' => function ($data) {
                    if (!empty($data['application_id'])) {
                        $application_id = htmlspecialchars($data['application_id']);
                        return $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $application_id]);
                    }
                },
                'get_default_restrictions' => function () {
                    $allRestrictions = $this->entityManager->getRepository(Restrictions::class)->findAll();
                    return $allRestrictions;
                },
                'get_default_users_restrictions' => function () {
                    $restrictions = $this->entityManager->getRepository(Restrictions::class)->findOneBy(['name' => 'userDefaultRestrictions']);
                    return $restrictions;
                },
                'get_default_groups_restrictions' => function () {
                    $restrictions = $this->entityManager->getRepository(Restrictions::class)->findOneBy(['name' => 'groupDefaultRestrictions']);
                    return $restrictions;
                },
                'update_default_users_restrictions' => function ($data) {
                    if (isset($data['maxStudents'])) {

                        $maxStudentsClear = htmlspecialchars($data['maxStudents']);

                        $restrictions = $this->entityManager->getRepository(Restrictions::class)->findOneBy(['name' => 'userDefaultRestrictions']);
                        $arrayRestriction = json_encode([
                            "maxStudents" => (int)$maxStudentsClear,
                        ]);
                        $restrictions->setRestrictions($arrayRestriction);
                        $this->entityManager->persist($restrictions);
                        $this->entityManager->flush();

                        return ['message' => "success"];
                    } else {
                        return ['message' => "missing data"];
                    }
                },
                'update_default_groups_restrictions' => function ($data) {
                    if (isset($data['maxStudents']) && isset($data['maxTeachers']) && isset($data['maxPerTeachers'])) {

                        $maxStudentsClear = htmlspecialchars($data['maxStudents']);
                        $maxTeachersClear = htmlspecialchars($data['maxTeachers']);
                        $maxPerTeachersClear = htmlspecialchars($data['maxPerTeachers']);

                        $restrictions = $this->entityManager->getRepository(Restrictions::class)->findOneBy(['name' => 'groupDefaultRestrictions']);
                        $arrayRestriction = json_encode([
                            "maxStudents" => (int)$maxStudentsClear,
                            "maxTeachers" => (int)$maxTeachersClear,
                            "maxStudentsPerTeacher" => (int)$maxPerTeachersClear,
                        ]);
                        $restrictions->setRestrictions($arrayRestriction);
                        $this->entityManager->persist($restrictions);
                        $this->entityManager->flush();

                        return ['message' => "success"];
                    } else {
                        return ['message' => "missing data"];
                    }
                },
            );
        }
    }

    /**
     * @param $mail string : the mail of the user
     * @param $emailTemplateBodysSring string : the i18n string of the email template
     * @param $confirmationLinkString string : the link in the mail
     * @param $emailSubjectString string : the i18n string of the email subject
     * @param $bodyTitleString string : the i18n string of the body title
     * @param $textBeforeLink string : the i18n string for the text before link
     * @return response from mailer class
     */
    private function sendGenericMailWithToken(
        string $mail,
        string $emailTemplateBodyString,
        string $confirmationLinkString,
        string $emailSubjectString,
        string $bodyTitleString,
        string $textBeforeLinkString
    ) {
        $userLang = isset($_COOKIE['lng']) ? htmlspecialchars(strip_tags(trim($_COOKIE['lng']))) : 'fr';
        $accountConfirmationLink = $confirmationLinkString;
        $emailTtemplateBody = $userLang . $emailTemplateBodyString;

        if (is_dir(__DIR__ . "/../../../../../openClassroom")) {
            i18next::init($userLang, __DIR__ . "/../../../../../openClassroom/classroom/assets/lang/__lng__/ns.json");
        } else {
            i18next::init($userLang, __DIR__ . "/../../../../../classroom/assets/lang/__lng__/ns.json");
        }

        $emailSubject = i18next::getTranslation($emailSubjectString);
        $bodyTitle = i18next::getTranslation($bodyTitleString);
        $textBeforeLink = i18next::getTranslation($textBeforeLinkString);
        $body = "
                            <a href='$accountConfirmationLink' style='text-decoration: none;padding: 10px;background: #27b88e;color: white;margin: 1rem auto;width: 50%;display: block;'>
                                $bodyTitle
                            </a>
                            <br>
                            <br>
                            <p>$textBeforeLink $accountConfirmationLink";
        $emailSent = Mailer::sendMail($mail, $emailSubject, $body, strip_tags($body), $emailTtemplateBody);
        return $emailSent;
    }

    private function manageAppsFromGroupsUsers(Int $user_id, array $application, ?array $groups, User $user)
    {
        $group = "";
        if (!empty($groups)) {
            $group = $this->entityManager->getRepository(Groups::class)->findOneBy(['id' => $groups[1]]);
        }
        $appFromGroupExist = $this->entityManager->getRepository(UsersLinkApplicationsFromGroups::class)->findBy(['user' => $user_id]);
        $isAppActive = false;
        foreach ($application as $app) {
            foreach ($appFromGroupExist as $appFromGroup) {
                if ($appFromGroup->getApplication()->getId() == $app[0] && $app[1] == false) {
                    $this->entityManager->remove($appFromGroup);
                } else if ($appFromGroup->getApplication()->getId() == $app[0]) {
                    $isAppActive = true;
                }
            }
            if (!$isAppActive && $app[1] == true && $group != "") {
                $apps = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $app[0]]);
                $newAppFromGroup = new UsersLinkApplicationsFromGroups();
                $newAppFromGroup->setApplication($apps);
                $newAppFromGroup->setGroup($group);
                $newAppFromGroup->setUser($user);
                $this->entityManager->persist($newAppFromGroup);
            }
            $isAppActive = false;
        }
        return true;
    }

    private function manageAppsForGroups($applications, $group_id, $group)
    {
        foreach ($applications as $key => $value) {
            $AppExist = $this->entityManager->getRepository(GroupsLinkApplications::class)->findOneBy(['group' => $group_id, 'application' => $value[0]]);
            // Récupère l'entité application liée à l'id de celle-ci (permet de la set ensuite en tant qu'entité dans le lien entre groupe et application)
            $application = $this->entityManager->getRepository(Applications::class)->findOneBy(['id' => $value[0]]);
            if ($value[1] == true) {

                $max_activities_per_groups = $value[2] != null ? $value[2] : 0;
                $max_activities_per_teachers = $value[3] != null ? $value[2] : 0;

                if (!$AppExist) {
                    $AppExist = new GroupsLinkApplications();
                }
                $AppExist->setApplication($application);
                $AppExist->setGroup($group);
                $AppExist->setmaxActivitiesPerGroups($max_activities_per_groups);
                $AppExist->setmaxActivitiesPerTeachers($max_activities_per_teachers);
                $this->entityManager->persist($AppExist);

            } else {
                if ($AppExist) {
                    $this->entityManager->remove($AppExist);
                    $appsGivenToTeachers = $this->entityManager->getRepository(UsersLinkApplicationsFromGroups::class)
                        ->findBy(['group' => $group_id, 'application' => $value[0]]);
                    foreach ($appsGivenToTeachers as $app) {
                        $this->entityManager->remove($app);
                    }
                }
            }
        }
    }
}
