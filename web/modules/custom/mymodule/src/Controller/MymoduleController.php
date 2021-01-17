<?php

namespace Drupal\mymodule\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for mymodule routes.
 */
class MymoduleController extends ControllerBase {

  /**
   * @var RendererInterface
   */
  protected $renderer;

  /**
   * @var ClientFactory
   */
  protected $httpClient;

  /**
   * @var Connection
   */
  protected $connection;

  /**
   * MymoduleController constructor.
   * @param RendererInterface $renderer
   * @param ClientFactory $http_client
   * @param Connection $connection
   */
  public function __construct(RendererInterface $renderer, ClientFactory $http_client, Connection $connection) {
    $this->renderer = $renderer;
    $this->httpClient = $http_client;
    $this->connection = $connection;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('http_client_factory'),
      $container->get('database')
    );
  }


  /**
   * @param Request $request
   * @return array
   */
  public function teamList(Request $request) {

    $client = $this->httpClient->fromOptions([
      'base_uri' => 'http://delivery.chalk247.com',
    ]);

    $response = $client->get('/team_list/NFL.JSON', [
      'query' => [
        'api_key' => '74db8efa2a6db279393b433d97c2bc843f8e32b0',
      ],
    ]);

    $result = Json::decode($response->getBody());

    if (empty($result)) {
      $form['content'] = [
        '#type' => 'markup',
        '#markup' => $this->t('There are no records using the current endpoint. Please try again later.'),
      ];

      return $form;
    }

    $teams = $result['results']['data']['team'];
    $cols = $result['results']['columns'];

    if ($request->request->get('op') == 'Import') {
      $this->importData($teams);
    }

    $header = [];
    $rows = [];

    foreach ($cols as $colId => $colName) {
      $header[] = $colName;
    }

    $header[] = 'Status';

    foreach($teams as $index => $team) {
      $row = [];
      foreach($team as $key => $data) {
        $cell = [];

        $cell['data'] = $data;

        if ($key == 'display_name') {
          $cell['class'] = 'cell-primary';
        }

       $row[] = $cell;
      }

      if (count($this->checkImportedRow($team)) <= 0) {
        $row[] = [
          'data' => 'No Imported',
          'class' => 'bg-danger',
        ];
      }
      else {
        $row[] = [
          'data' => 'Imported',
          'class' => 'bg-success',
        ];
      }

      $rows[] = $row;
    }

    $build['form'] = [
      '#type' => 'form',
    ];

    $build['form']['operations']['actions'] = [
      '#type' => 'actions',
    ];

    $build['form']['operations']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Import',
    ];

    $build['content'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  /**
   * @param $row
   * @return bool
   */
  function checkImportedRow(array $row) {

    $sql = "SELECT
        node.nid,
        node.title,
        itemid.field_id_value
      FROM node_field_data AS node
        LEFT JOIN node__field_id AS itemid
          ON node.nid = itemid.entity_id
      WHERE node.type = 'team'
      AND itemid.field_id_value = :id";

    $query = $this->connection->query($sql, ['id' => $row['id']]);

    return $query->fetchAll();
  }

  /**
   * @param array $data
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  function importData(array $data) {

    foreach ($data as $index => $item) {
      if ($nodeInfo = $this->checkImportedRow($item)) {
        /** @var NodeInterface $node */
        $node = $this->entityTypeManager()->getStorage('node')->load($nodeInfo->nid);
      }
      else {
        /** @var NodeInterface $node */
        $node = $this->entityTypeManager()->getStorage('node')->create([
          'type' => 'team'
        ]);
      }

      $node->set('title', $item['name']);
      $node->set('field_nickname', $item['nickname']);
      $node->set('field_display_name', $item['display_name']);
      $node->set('field_id', $item['id']);
      $node->set('field_conference', $this->checkTaxonomyByName($item['conference']));
      $node->set('field_division', $this->checkTaxonomyByName($item['division']));

      $node->save();
    }

  }

  function checkTaxonomyByName($name) {

    $sql = "SELECT taxo.tid
      FROM taxonomy_term_data AS taxo
        LEFT JOIN taxonomy_term_field_data AS tdata
          ON taxo.tid = tdata.tid
      WHERE name = :name";

    $query = $this->connection->query($sql, ['name' => $name]);

    return $query->fetchAll();

  }

}
