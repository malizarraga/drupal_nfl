<?php

namespace Drupal\mymodule\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a mymodule form.
 */
class TeamForm extends FormBase {

  /**
   * @var RendererInterface
   */
  private $renderer;

  /**
   * @var ClientFactory
   */
  private $httpClient;

  /**
   * TeamForm constructor.
   * @param RendererInterface $renderer
   * @param ClientFactory $http_client
   */
  public function __construct(RendererInterface $renderer, ClientFactory $http_client) {
    $this->renderer = $renderer;
    $this->httpClient = $http_client;
  }

  /**
   * @param ContainerInterface $container
   * @return TeamForm|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('http_client_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'myform_team';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

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


    if ($form_state->get('op') == 'Filter') {
      $teams = $this->filterData($teams, $form_state);
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

    $division = $this->buildSelectOptions($data, 'division');
    $conference = $this->buildSelectOptions($data, 'conference');
    $name = $this->buildSelectOptions($data, 'name');

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
      '#name'          => 'name',
      '#type'          => 'select',
      '#empty_value'   => 'none',
      '#empty_option'  => '- None -',
      '#size'          => 0,
      '#options'       => array_merge(['none'], $name),
      '#default_value' => !empty($form_state->get('name')) ? $form_state->get('name') : 'none',
    ];

    $form['form']['filters']['division'] = [
      '#title'         => $this->t('Division'),
      '#name'          => 'division',
      '#type'          => 'select',
      '#empty_value'   => 'none',
      '#empty_option'  => '- None -',
      '#size'          => 0,
      '#options'       => array_merge(['none'], $division),
      '#default_value' => !empty($form_state->get('division')) ? $form_state->get('name') : 'none',
    ];

    $form['form']['filters']['actions'] = [
      '#type'       => 'actions'
    ];

    $form['form']['filters']['actions']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Filter')
    ];

    $build['content'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    return $form;
  }

  /**
   * @param array $data
   * @param $colKey
   * @return array
   */
  function buildSelectOptions(array $data, $colKey) {
    $column = array_column($data, $colKey);
    $options = [];

    foreach (array_unique($column) as $key => $value) {
      $options[strtolower($value)] = $value;
    }

    return $options;
  }

  /**
   * @param array $source
   * @param Request $request
   * @return array
   */
  function filterData(array $source, FormStateInterface $formState)
  {
    $data = $source;
    foreach($formState->getValues() as $key => $value) {
      if ($value != 'none' && $value != '0' && $value != 'Filter') {
        $data = array_filter($data, fn($x) => strtolower($x[$key]) == strtolower($value));
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
