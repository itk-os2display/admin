<?php
/**
 * @file
 * Contains user controller.
 */

namespace Indholdskanalen\MainBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Util\Codes;
use Indholdskanalen\MainBundle\Entity\Group;
use Indholdskanalen\MainBundle\Entity\User;
use Indholdskanalen\MainBundle\Entity\UserGroup;
use Indholdskanalen\MainBundle\Exception\DuplicateEntityException;
use Indholdskanalen\MainBundle\Exception\HttpDataException;
use Indholdskanalen\MainBundle\Exception\ValidationException;
use Indholdskanalen\MainBundle\Security\Roles;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api/user")
 * @Rest\View(serializerGroups={"api"})
 */
class UserController extends ApiController {
  /**
   * Lists all user entities.
   *
   * @Rest\Get("", name="api_user_index")
   * @Rest\QueryParam(
   *   name="filter",
   *   description="Filter to apply",
   *   requirements="string",
   *   array=true,
   *   nullable=true
   * )
   * @ApiDoc(
   *   section="Users",
   *   description="Returns all users",
   *   resource=false,
   *   filters={
   *      {"name"="filter", "dataType"="string"}
   *   },
   *   statusCodes={
   *     200="Success"
   *   }
   * )
   *
   * @Security("is_granted('LIST', 'user')")
   *
   * @param \FOS\RestBundle\Request\ParamFetcherInterface $paramFetcher
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function indexAction() {
    $users = $this->findAll(User::class);

    foreach ($users as $user) {
      $user->buildRoleGroups();
    }

    return $this->setApiData($users);
  }

  /**
   * Creates a new user entity.
   *
   * @Rest\Post("", name="api_user_new")
   * @ApiDoc(
   *   section="Users",
   *   description="Create user",
   *   statusCodes={
   *     201="User created",
   *     400="Invalid user data",
   *     409="Duplicate user (specified email/username already used)"
   *   }
   * )
   *
   * @Security("has_role('ROLE_USER_ADMIN')")
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return User
   */
  public function newAction(Request $request) {
    // Get post content.
    $data = $this->getData($request);

    // Create user.
    try {
      $user = $this->get('os2display.user_manager')->createUser($data);
    }
    catch (ValidationException $e) {
      throw new HttpDataException(Codes::HTTP_BAD_REQUEST, $data, 'Invalid data', $e);
    }
    catch (DuplicateEntityException $e) {
      throw new HttpDataException(Codes::HTTP_CONFLICT, $data, 'Duplicate user', $e);
    }

    // Send response.
    return $this->createCreatedResponse($this->setApiData($user));
  }

  /**
   * @Rest\Get("/roles")
   * @ApiDoc(
   *   section="Users and groups",
   *   description="Get all available user roles"
   * )
   *
   * @Security("has_role('ROLE_USER_ADMIN')")
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return array
   */
  public function getRoles(Request $request) {
    $translator = $this->get('translator');
    $locale = $request->get('locale', $this->getParameter('locale'));

    $manager = $this->get('os2display.security_manager');
    $roles = $manager->getReachableRoles($this->getUser());
    $labels = array_map(function ($role) use ($translator, $locale) {
      return $translator->trans($role, [], 'IndholdskanalenMainBundle', $locale);
    }, $roles);
    $data = array_combine($roles, $labels);
    asort($data);

    return $data;
  }

  /**
   * Finds and displays a user entity.
   *
   * @Rest\Get("/{id}", name="api_user_show")
   *
   * Note: In the Security annotation "user" always refers to the current
   * user. Therefore we use parameter name different from "user".
   *
   * @Security("is_granted('READ', aUser)")
   *
   * @param \Indholdskanalen\MainBundle\Entity\User $aUser
   * @return \Indholdskanalen\MainBundle\Entity\User
   */
  public function showAction(User $aUser) {
    $aUser->buildRoleGroups();

    return $this->setApiData($aUser);
  }

  /**
   * @Rest\Put("/{id}", name="api_user_edit")
   *
   * @Security("is_granted('UPDATE', aUser)")
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Indholdskanalen\MainBundle\Entity\User $user
   * @return User
   */
  public function editAction(Request $request, User $aUser) {
    $data = $this->getData($request);

    try {
      $aUser = $this->get('os2display.user_manager')->updateUser($aUser, $data);
    }
    catch (ValidationException $e) {
      throw new HttpDataException(Codes::HTTP_BAD_REQUEST, $data, 'Invalid data', $e);
    }
    catch (DuplicateEntityException $e) {
      throw new HttpDataException(Codes::HTTP_CONFLICT, $data, 'Duplicate user', $e);
    }

    $aUser->buildRoleGroups();

    return $this->setApiData($aUser);
  }

  /**
   * Deletes a user entity.
   *
   * @Rest\Delete("/{id}", name="api_user_delete")
   *
   * @Security("is_granted('DELETE', aUser)")
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Indholdskanalen\MainBundle\Entity\User $user
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function deleteAction(Request $request, User $aUser) {
    $em = $this->getDoctrine()->getManager();
    $em->remove($aUser);
    $em->flush();

    return $this->view(NULL, Codes::HTTP_NO_CONTENT);
  }

  /**
   * @Rest\Get("/{user}/group")
   */
  public function getUserGroups(User $user) {
    $groups = $user->buildRoleGroups()->getRoleGroups();

    return $this->setApiData($groups);
  }

  /**
   * @Rest\Get("/{user}/group/{group}", name="api_user_group_read")
   *
   * @ApiDoc(
   *   section="Users and groups"
   * )
   * @param \Indholdskanalen\MainBundle\Entity\User $user
   * @param \Indholdskanalen\MainBundle\Entity\Group $group
   * @return array
   */
  public function getUserGroupRoles(User $user, Group $group) {
    $items = $this->fetchUserGroupRoles($user, $group);

    $roles = array_map(function (UserGroup $userGroup) {
      return $userGroup->getRole();
    }, $items);

    $user->buildRoleGroups();

    return [
      'roles' => array_unique($roles),
      'group' => $group,
      'user' => $user,
    ];
  }

  /**
   * @Rest\Post("/{user}/group/{group}", name="api_user_group_create")
   *
   * @Rest\RequestParam(
   *   name="roles",
   *   description="Roles to give user in group.",
   *   requirements="string[]",
   *   nullable=true
   * )
   * @ApiDoc(
   *   section="Users and groups",
   *   description="Add user to group"
   * )
   *
   * @Security("is_granted('CREATE', group)")
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Indholdskanalen\MainBundle\Entity\User $user
   * @param \Indholdskanalen\MainBundle\Entity\Group $group
   * @param \FOS\RestBundle\Request\ParamFetcherInterface $paramFetcher
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function createUserGroup(Request $request, User $user, Group $group, ParamFetcherInterface $paramFetcher) {
    $items = $this->updateUserGroupRoles($request, $user, $group, $paramFetcher);

    return $this->createCreatedResponse($this->setApiData($items));
  }

  /**
   * @Rest\Put("/{user}/group/{group}", name="api_user_group_update")
   * @ApiDoc(
   *   section="Users and groups",
   *   description="Update user's roles in group"
   * )
   *
   * @Security("is_granted('UPDATE', group)")
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Indholdskanalen\MainBundle\Entity\User $user
   * @param \Indholdskanalen\MainBundle\Entity\Group $group
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function updateUserGroupRoles(Request $request, User $user, Group $group, ParamFetcherInterface $paramFetcher) {
    $em = $this->getDoctrine()->getManager();
    $roles = $this->getData($request, 'roles');

    $items = $this->fetchUserGroupRoles($user, $group);
    foreach ($items as $item) {
      $em->remove($item);
    }
    $em->flush();

    if (is_array($roles)) {
      foreach ($roles as $role) {
        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($group);
        $userGroup->setRole($role);
        $em->persist($userGroup);
      }
      $em->flush();
    }

    return $this->getUserGroupRoles($user, $group);
  }

  /**
   * @Rest\Delete("/{user}/group/{group}")
   * @ApiDoc(
   *   section="Users and groups",
   *   description="Remove user from group"
   * )
   *
   * @Security("is_granted('UPDATE', group)")
   *
   * @param \Indholdskanalen\MainBundle\Entity\User $user
   * @param \Indholdskanalen\MainBundle\Entity\Group $group
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function deleteUserGroupRoles(User $user, Group $group, ParamFetcherInterface $paramFetcher) {
    $em = $this->getDoctrine()->getManager();
    $items = $this->fetchUserGroupRoles($user, $group);
    foreach ($items as $item) {
      $em->remove($item);
    }
    $em->flush();

    return $this->view(NULL, Codes::HTTP_NO_CONTENT);
  }

  private function fetchUserGroupRoles(User $user, Group $group) {
    $items = $this->findBy(UserGroup::class, [
      'user' => $user,
      'group' => $group,
    ]);

    return $items;
  }

}
