<?php

namespace Drupal\mymodule\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for mymodule routes.
 */
class MymoduleController extends ControllerBase {

  /**
   * @var RendererInterface
   */
  private $renderer;
  /**
   * @var ClientFactory
   */
  private $httpClient;

  public function __construct(RendererInterface $renderer, ClientFactory $http_client) {
    $this->renderer = $renderer;
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('http_client_factory')
    );
  }


  /**
   * @param Request $request
   * @return array
   */
  public function teamList(Request $request) {

    $headerTemplate = [
      'display_name' => 'Team Name',
      'nickname' => 'Nickname',
      'conference' => 'National Football Conference',
      'division' => 'West',
    ];

    $client = $this->httpClient->fromOptions([
      'base_uri' => 'http://delivery.chalk247.com',
    ]);

    $response = $client->get('/team_list/NFL.JSON', [
      'query' => [
        'api_key' => '74db8efa2a6db279393b433d97c2bc843f8e32b0',
      ],
    ]);

    $result = Json::decode($response->getBody());

    $header = [];
    foreach ($headerTemplate as $colId => $colName) {
      $header[] = $colName;
    }

    $rows = [];
    foreach($result['results']['data']['team'] as $index => $team) {
      $detailData = array_intersect_key($team, $headerTemplate);
      $row = [];
      foreach($detailData as $key => $data) {
        $cell = [];

        $cell['data'] = $data;

        if ($key = 'display_name') {
          $cell['class'] = 'cell-primary';
        }
        $row[] = $cell;
      }
      $rows[] = $row;
    }

    $build['content'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

}
