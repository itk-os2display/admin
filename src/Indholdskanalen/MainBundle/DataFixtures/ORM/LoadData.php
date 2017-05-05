<?php

namespace Indholdskanalen\MainBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Indholdskanalen\MainBundle\Entity\User;
use Symfony\Bridge\Doctrine\Tests\Fixtures\ContainerAwareFixture;
use Symfony\Component\Yaml\Yaml;

class LoadData extends ContainerAwareFixture {
  /**
   * @var ObjectManager
   */
  private $manager;

  public function load(ObjectManager $manager) {
    $this->manager = $manager;

    $filePaths = glob(__DIR__ . '/../fixtures/*.yml');

    foreach ($filePaths as $path) {
      echo $path, PHP_EOL;

      $data = Yaml::parse($path);

      if (isset($data['class'])) {
        $this->loadData($data);
      }
      else {
        foreach ($data as $item) {
          $this->loadData($item);
        }
      }
    }
  }

  private function loadData(array $data) {
    if (!isset($data['class'])) {
      throw new \Exception('Class not defined');
    }

    $class = $data['class'];
    if (!class_exists($class)) {
      throw new \Exception('Unknown class ' . $class);
    }

    /** @var \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    $accessor = $this->container->get('property_accessor');

    foreach ($data['entities'] as $values) {
      if (isset($data['defaults'])) {
        $values += $data['defaults'];
      }

      $entity = $this->createEntity($class, $values);
      foreach ($values as $property => $value) {
        if (preg_match('/^@(?<property>.+)/', $property, $matches)) {
          $property = $matches['property'];
          $metadata = $this->manager->getClassMetadata(get_class($entity));
          $targetClass = $metadata->getAssociationTargetClass($property);
          $value = $this->getEntity($targetClass, $value);
        }
        if ($accessor->isWritable($entity, $property)) {
          $accessor->setValue($entity, $property, $value);
        }
        else {
          echo 'Warning: Unknown property: ' . $property . ' (' . get_class($entity) . ')', PHP_EOL;
        }
      }
      $this->persistEntity($entity);

      echo get_class($entity) . '#' . $entity->getId(), PHP_EOL;
    }
  }

  private function getEntity($class, $criteria) {
    $repository = $this->manager->getRepository($class);
    $entity = $repository->findOneBy($criteria);

    if ($entity === NULL) {
      $this->warning($criteria . ': No such entity');
    }

    return $entity;
  }

  private function warning($message, $newline = TRUE) {
    fwrite(STDERR, $message);
    if ($newline) {
      fwrite(STDERR, PHP_EOL);
    }
  }

  private function createEntity($class, array &$values) {
    $entity = new $class();
    if ($entity instanceof User) {
      $manager = $this->container->get('fos_user.user_manager');
      $entity = $manager->createUser();
      $values += [
        'username' => $values['email'],
      ];
    }

    return $entity;
  }

  private function persistEntity($entity) {
    if ($entity instanceof User) {
      $manager = $this->container->get('fos_user.user_manager');
      $manager->updateUser($entity);
    }
    else {
      $this->manager->persist($entity);
      $this->manager->flush();
    }
  }
}
