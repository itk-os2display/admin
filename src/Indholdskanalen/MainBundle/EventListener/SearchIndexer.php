<?php

namespace Indholdskanalen\MainBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

/*use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;*/

class SearchIndexer {
  protected $container;

  function __construct() {
    $this->container = $this->getContainer();
  }
  public function postPersist(LifecycleEventArgs $args) {
    $this->sendEvent($args, 'POST');
  }

  public function preUpdate(LifecycleEventArgs $args) {
    $this->sendEvent($args, 'PUT');
  }

  public function preRemove(LifecycleEventArgs $args) {
    $this->sendEvent($args, 'DELETE');
  }

  protected function sendEvent($args, $method) {
    // Get the current entity.
    $entity = $args->getEntity();
    $type = get_class($entity);

    // We will not send user data to ES.
    if ($type == 'Application\Sonata\UserBundle\Entity\User') {
      return FALSE;
    }

    if ($method != 'DELETE') {
      // Setup our serializer.

      $serializer = $this->container->get('jms_serializer');
      $jsonContent = $serializer->serialize($entity, 'json');

      $this->curl('http://localhost:9200/indholdskanalen/' . $type . '/' . $entity->getId(), $method, $jsonContent);
    }
    else {
      $this->curl('http://localhost:9200/indholdskanalen/' . $type . '/' . $entity->getId(), $method);
    }
  }

  /**
   * Communication function.
   *
   * Wrapper function for curl to send dato to ES
   *
   * @param $url
   *   URL to connect to.
   * @param string $method
   *   Method to send/get data "POST" or "PUT".
   * @param array $params
   *   The data to send.
   *
   * @return \stdClass|string
   */
  protected function curl($url, $method = 'POST', $params = array()) {
    // Build query.
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($method != 'DELETE') {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    }

    // Receive server response.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close connection.
    curl_close ($ch);
  }

  /**
   * Get the container.
   *
   * @todo: I am 100% sure that there must be a better way to get access to
   * doctrine in a helper class than use globals.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected function getContainer() {
    if (NULL === $this->container) {
      // This use of global is not the right way, but until DI makes sens... it works.
      $this->container = $GLOBALS['kernel']->getContainer();
    }

    return $this->container;
  }
}
