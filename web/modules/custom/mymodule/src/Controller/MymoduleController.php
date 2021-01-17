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
      $build['content'] = [
        '#type' => 'markup',
        '#markup' => $this->t('There are no records using the current endpoint. Please try again later.'),
      ];

      return $build;
    }

    $teams = $result['results']['data']['team'];
    $cols = $result['results']['columns'];

    $header = [];
    $rows = [];

    foreach ($cols as $colId => $colName) {
      $header[] = $colName;
    }

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

      $rows[] = $row;
    }

    $build = [];
    $this->buildFilter($build, $result['results']['data']['team']);

    $build['content'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $build;
  }

  function buildFilter(&$form, $data) {

    $division = array_column($data, 'division');
    $conference = array_column($data, 'conference');
    $name = array_column($data, 'name');

    $form['form'] = [
      '#type'  => 'form',
    ];

    $form['form']['filters'] = [
      '#type'  => 'fieldset',
      '#title' => $this->t('Filter'),
      '#open'  => true,
    ];

    $form['form']['filters']['name'] = [
      '#title'         => $this->t('Name'),
      '#type'          => 'select',
      '#empty_value'   => 'none',
      '#empty_option'  => '- None -',
      '#size'          => 0,
      '#options'       => array_merge(['none'], array_unique($name)),
      '#default_value' => 'none'
    ];

    $form['form']['filters']['division'] = [
      '#title'         => $this->t('Division'),
      '#type'          => 'select',
      '#empty_value'   => 'none',
      '#empty_option'  => '- None -',
      '#size'          => 0,
      '#options'       => array_merge(['none'], array_unique($division)),
      '#default_value' => 'none'
    ];

    $form['form']['filters']['actions'] = [
      '#type'       => 'actions'
    ];

    $form['form']['filters']['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Filter')
    ];
  }

}
