<?php

namespace Drupal\ldbase_handlers\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\ldbase_handlers\Plugin\Archiver\Zip;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Node\NodeInterface;

/**
 * Download Project Controller.
 */
class DownloadProjectController extends ControllerBase {
  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('file_system'),
      $container->get('messenger'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(FileSystemInterface $file_system, Messenger $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Archive all file associated with project and stream it for download.
   *
   * @param \Drupal\Node\NodeInterface $node
   *   Project Node.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Downloads the file.
   */
  public function downloadProjectFiles(NodeInterface $node) {
    $zip_files_directory = DRUPAL_ROOT . '/sites/default/files/ldbase_zips';

    $escaped_project_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $node->getTitle());
    $file_path = $zip_files_directory . '/LDBASE-PROJECT-' . $escaped_project_name . '.zip';

    // get nodes in project hierarchy
    $nodes_in_tree = $this->getFileChildNodes($node);

    $redirect_on_error_to = \Drupal::url('entity.node.canonical', ['node' => $node->id()]);
    $files = [];
    $account = \Drupal::currentUser();
    $embargo_service = \Drupal::service('ldbase_embargoes.file_access');
    // construct zip archive, add files, stream
    foreach ($nodes_in_tree as $item) {
      $upload_node = $item['node'];
      // if the node has an upload the node is available
      if (!empty($upload_node)) {
        $type = $upload_node->bundle();
        switch ($type) {
          case 'dataset':
            $versions = [];
            $dataset_versions = $upload_node->field_dataset_version;
            foreach ($dataset_versions as $delta => $paragraph) {
              $p = $paragraph->entity;
              $versions[$delta] = $p->field_file_upload->entity;
            }
            $latest_version = end($versions);
            $file_entity = $latest_version;
            break;
          case 'code':
            $file_entity = $upload_node->field_code_file->entity;
            break;
          case 'document':
            $file_entity = $upload_node->field_document_file->entity;
            break;
        }
        if ($file_entity) {
          // check for embargoes
          $embargo = $embargo_service->isActivelyEmbargoed($file_entity, $account);
          if (!$embargo->isForbidden()) {
            $is_codebook = \Drupal::service('ldbase.object_service')->isLdbaseCodebook($upload_node->uuid());
            $node_type = $is_codebook ? 'Codebook' : ucfirst($type);
            $files[] = ['file' => $file_entity->getFileUri(), 'prefix' => $item['path'], 'type' => $node_type];
          }
        }
      }
    }

    $file_zip = NULL;
    $manifest = "FILES INCLUDED IN THIS ARCHIVE:\n\n";
    $cnt = 0;
    if ($this->fileSystem->prepareDirectory($zip_files_directory, FileSystemInterface::CREATE_DIRECTORY)) {
      foreach ($files as $item) {
        $file = $item['file'];
        $download_file = file_get_contents($file);
        $file_name_parts = explode('/', $file);
        $file_name = array_pop($file_name_parts);

        if (!$file_zip instanceof Zip) {
          $file_zip = new Zip($file_path);
        }
        $file_name_for_zip = $item['prefix'] . '/' . $file_name;
        $file_zip->addFromString($file_name_for_zip, $download_file);
        $cnt++;
        $manifest .= "{$cnt}. {$file_name_for_zip} ({$item['type']})\n";
      }

      $file_zip->addFromString('FILES_IN_THIS_ARCHIVE.txt', $manifest);

      if ($file_zip instanceof Zip) {
        $file_zip->close();
        return $this->streamZipFile($file_path);
      }
      else {
        $this->messenger->addMessage('There are no files to be downloaded for this project ', 'warning', TRUE);
        return new RedirectResponse($redirect_on_error_to);
      }
    }
    else {
      $this->messenger->addMessage('Zip file directory not found.', 'error', TRUE);
      return new RedirectResponse($redirect_on_error_to);
    }
  }

  /**
   * get children nids
   *
   * @param \Drupal\Node\NodeInterface $node
   *  Gets child nodes of given node.
   *
   * @return array
   *  array of nodes
   */
  private function getFileChildNodes(NodeInterface $node, $node_path = NULL) {
    $node_array = [];
    $escaped_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $node->getTitle());
    $node_path = empty($node_path) ? $escaped_name : $node_path . '/' . $escaped_name;

    $nid = $node->id();
    $node_storage = $this->entityTypeManager->getStorage('node');
    $children_ids = $node_storage->getQuery()
      ->condition('field_affiliated_parents', $nid)
      ->execute();

    foreach ($children_ids as $child_id) {
      $child_node = $node_storage->load($child_id);
      $child_node_bundle = $child_node->bundle();

      switch ($child_node_bundle) {
        case 'dataset':
          $upload_or_external = $child_node->get('field_dataset_upload_or_external')->value;
          break;
        case 'code':
          $upload_or_external = $child_node->get('field_code_upload_or_external')->value;
          break;
        case 'document':
          $upload_or_external = $child_node->get('field_doc_upload_or_external')->value;
          break;
        default:
          $upload_or_external = '';
      }
      // if the node has an upload, add the node
      $node_with_file = ($upload_or_external == 'upload') ? $child_node : NULL;

      $escaped_child_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $child_node->getTitle());
      array_push($node_array, ['path' => $node_path . '/' . $escaped_child_name, 'node' => $node_with_file]);
      $node_array = array_merge($node_array, $this->getFileChildNodes($child_node, $node_path));
    }
    return $node_array;
  }

  /**
   * Method to stream created zip file.
   *
   * @param string $file_path
   *   File physical path.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Downloads the file.
   */
  protected function streamZipFile($file_path) {

    $binary_file_response = new BinaryFileResponse($file_path);
    $binary_file_response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($file_path));
    // remove zip file when done
    $binary_file_response->deleteFileAfterSend(TRUE);

    return $binary_file_response;
  }


}
