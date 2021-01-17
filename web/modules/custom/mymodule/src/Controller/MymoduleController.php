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

      $row[] = [
        'data' => 'No Imported',
        'class' => 'bg-danger',
      ];
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

}
