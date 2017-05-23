<?php

namespace Indholdskanalen\MainBundle\DataFixtures\ORM;

use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Indholdskanalen\MainBundle\Entity\Group;
use Indholdskanalen\MainBundle\Entity\GroupableEntity;
use Indholdskanalen\MainBundle\Entity\User;
use Symfony\Bridge\Doctrine\Tests\Fixtures\ContainerAwareFixture;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Yaml\Yaml;

class LoadData extends ContainerAwareFixture {
  /**
   * @var ObjectManager
   */
  private $manager;

  /**
   * @var ConsoleOutput
   */
  private $output;

  public function load(ObjectManager $manager) {
    $this->manager = $manager;
    $this->output = new ConsoleOutput();

    $filePaths = array_map('realpath', glob(__DIR__ . '/../fixtures/*.yml'));

    foreach ($filePaths as $path) {
      // Change directory to be able to load files relatively to the current fixture path.
      chdir(dirname($path));
      $this->write(null, PHP_EOL . "<options=bold>$path</>");

      $yaml = file_get_contents($path);
      $data = Yaml::parse($yaml);

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
      $metadata = $this->manager->getClassMetadata(get_class($entity));
      foreach ($values as $property => $value) {
        if ($metadata->hasAssociation($property)) {
          $targetClass = $metadata->getAssociationTargetClass($property);
          $value = $this->getEntity($targetClass, $value);
        }
        elseif ($entity instanceof GroupableEntity && $property === 'groups') {
          $value = $this->getGroups($value);
        }
        // Workaround for "user" not being a real association on some entities.
        elseif (is_array($value) && $metadata->getTypeOfField($property) === 'integer' && $property === 'user') {
          $value = $this->getEntity(User::class, $value)->getId();
        }
        if ($accessor->isWritable($entity, $property)) {
          $accessor->setValue($entity, $property, $value);
        }
        else {
          throw new \Exception('Unknown property: ' . $property . ' (' . get_class($entity) . ')');
        }
      }
      $this->persistEntity($entity);

      $this->info("\t" . get_class($entity) . '#' . $entity->getId());
    }
  }

  private function getGroups(array $items) {
    $groups = new ArrayCollection();
    foreach ($items as $criteria) {
      $groups->add($this->getEntity(Group::class, $criteria));
    }

    return $groups;
  }

  private function getEntity($class, $criteria) {
    $repository = $this->manager->getRepository($class);
    $entity = $repository->findOneBy($criteria);

    if ($entity === NULL) {
      throw new \Exception('No such entity of type ' . $class . ': ' . json_encode($criteria, JSON_PRETTY_PRINT));
    }

    return $entity;
  }

  private function info($messages, $newline = TRUE) {
    $this->write('info', $messages, $newline);
  }

  private function warning($messages, $newline = TRUE) {
    $this->write('warning', $messages, $newline);
  }

  private function write($type, $messages, $newline = TRUE) {
    if (!is_array($messages)) {
      $messages = [$messages];
    }
    if ($type) {
      $messages = array_map(function ($message) use ($type) {
        return "<$type>$message</$type>";
      }, $messages);
    }
    $this->output->write($messages, $newline);
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
    elseif ($entity instanceof Media) {
      $manager = $this->container->get('sonata.media.manager.media');
      $manager->save($entity);
    }
    else {
      $this->manager->persist($entity);
      $this->manager->flush();
    }
  }
}
