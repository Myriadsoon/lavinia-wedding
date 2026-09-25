<?php

namespace Drupal\wedding_photos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides downloads of original wedding photographs.
 */
class PhotoDownloadController extends ControllerBase {

  /**
   * Download the original wedding photograph
   */
  public function download(NodeInterface $node): BinaryFileResponse {
    \Drupal::logger('wedding_photos')->notice(
      'Download controller reached. Node ID: @nid; bundle: @bundle',
      ['@nid' => $node->id(), '@bundle' => $node->bundle()]
    );

    if ($node->bundle() !== 'wedding_photos') {
      throw new NotFoundHttpException('Not a wedding_photos node.');
    }

    if (
      !$node->hasField('field_wedding_photo')
    ) {
      throw new NotFoundHttpException('field_wedding_photo is does not exist.');
    }

    if (
      $node->get('field_wedding_photo')->isEmpty()
    ) {
      throw new NotFoundHttpException('field_wedding_photo is empty.');
    }

    /** @var \Drupal\file\FileInterface\null $file */
    $file = $node->get('field_wedding_photo')->entity;

    if (!$file) {
      throw new NotFoundHttpException('Image field does not reference a file entity.');
    }

    $uri = $file->getFileUri();
    \Drupal::logger('wedding_photos')->notice(
      'Wedding photograph file found. URI: @uri; filename: @filename',
      ['@uri' => $uri, '@filename' => $file->getFilename()]
    );

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    $real_path = $file_system->realpath($uri);

    if (!$real_path) {
      throw new NotFoundHttpException('Could not resolve private file URI to a real path.');
    }

    if (!is_file($real_path)) {
      throw new NotFoundHttpException('Resolved path does not identify an existing file: ' . $real_path);
    }

    $response = new BinaryFileResponse($real_path);

    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $file->getFilename()
    );

    return $response;

  }
}
