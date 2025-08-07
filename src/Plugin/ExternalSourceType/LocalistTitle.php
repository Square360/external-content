<?php

declare(strict_types=1);

namespace Drupal\external_content\Plugin\ExternalSourceType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\external_content\Attribute\ExternalSourceType;
use Drupal\external_content\ExternalSourceTypePluginBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Plugin implementation of the external_source_type.
 */
#[ExternalSourceType(
  id: 'localist_title',
  label: new TranslatableMarkup('Localist by Title'),
  description: new TranslatableMarkup('Selects Localist events by title with date range filtering.'),
)]
final class LocalistTitle extends ExternalSourceTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function externalSourceConfigForm(array &$form_container, array &$plugin_configuration) {
    $start_date = $plugin_configuration['start_date'] ?? 'today';
    $end_date = $plugin_configuration['end_date'] ?? '+6 months';

    $form_container['start_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start Date'),
      '#default_value' => $start_date,
      '#description' => $this->t('Relative date string (e.g., "today", "-1 week", "+1 month"). Events starting from this date will be included.'),
      '#required' => TRUE,
    ];

    $form_container['end_date'] = [
      '#type' => 'textfield',
      '#title' => $this->t('End Date'),
      '#default_value' => $end_date,
      '#description' => $this->t('Relative date string (e.g., "today", "+6 months", "+1 year"). Events ending before this date will be included.'),
      '#required' => TRUE,
    ];

    // Add validation callback
    $form_container['#element_validate'][] = [$this, 'validateDateFields'];
  }

  /**
   * Validates the start_date and end_date fields.
   */
  public function validateDateFields($element, FormStateInterface $form_state, $form) {
    $values = $form_state->getValue($element['#parents']);

    if (!empty($values['start_date'])) {
      try {
        new DrupalDateTime($values['start_date']);
      }
      catch (\Exception $e) {
        $form_state->setError($element['start_date'], $this->t('Start Date: "@date" is not a valid relative date string. Use formats like "today", "+1 month", "-1 week".', [
          '@date' => $values['start_date'],
        ]));
      }
    }

    if (!empty($values['end_date'])) {
      try {
        new DrupalDateTime($values['end_date']);
      }
      catch (\Exception $e) {
        $form_state->setError($element['end_date'], $this->t('End Date: "@date" is not a valid relative date string. Use formats like "today", "+1 month", "-1 week".', [
          '@date' => $values['end_date'],
        ]));
      }
    }

    // Validate that start_date is before end_date
    if (!empty($values['start_date']) && !empty($values['end_date'])) {
      try {
        $start = new DrupalDateTime($values['start_date']);
        $end = new DrupalDateTime($values['end_date']);

        if ($start->getTimestamp() >= $end->getTimestamp()) {
          $form_state->setError($element['end_date'], $this->t('End Date must be after Start Date.'));
        }
      }
      catch (\Exception $e) {
        // Individual field validation will catch invalid dates
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleAutocomplete($source, string $input): array {
    $results = [];

    $endpoint = $source->getResource() . 'api/2/events/search';
    $query = $this->getLookupQuery($source, $input);
    $headers = [];
    $this->alterRequest($query, $headers, $source, 'handleAutocomplete');

    $json = $this->makeRequest($endpoint, $query, $headers);

    // Handle the response based on Localist API structure
    if ($json !== FALSE && !empty($json)) {
      $events = $json['events'] ?? [];
      foreach ($events as $event) {
        $title = $event['event']['title'] ?? 'Untitled Event';
        $date = $event['event']['first_date'];
        $id = $event['event']['id'] ?? '';
        if (!empty($title) && !empty($id)) {
          $results[] = [
            'value' => "$title ($id)",
            'label' => "$title - $date ($id)",
          ];
        }
      }
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getContent($source, $id, int $limit = 1) {
    $endpoint = $source->getResource() . "/api/2/events/{$id}";
    $headers = [];
    $query = [];
    $this->alterRequest($query, $headers, $source, 'getContent');
    return $this->makeRequest($endpoint, $query, $headers);
  }

  /**
   * Makes an HTTP request to the Localist API.
   *
   * @param string $endpoint
   *   The API endpoint URL.
   * @param array $query
   *   Query parameters.
   * @param array $headers
   *   HTTP headers.
   *
   * @return array|false
   *   Decoded JSON response or FALSE on failure.
   */
  protected function makeRequest(string $endpoint, array $query = [], array $headers = []) {
    $client = \Drupal::httpClient();

    $options = [
      'headers' => array_merge([
        'Accept' => 'application/json',
        'User-Agent' => 'Drupal External Content',
      ], $headers),
      'timeout' => 30,
    ];

    if (!empty($query)) {
      $options['query'] = $query;
    }

    try {
      $response = $client->request('GET', $endpoint, $options);

      if ($response->getStatusCode() === 200) {
        $body = $response->getBody()->getContents();
        $data = json_decode($body, TRUE);

        if (json_last_error() === JSON_ERROR_NONE) {
          return $data;
        } else {
          \Drupal::logger('external_content_localist')->error('Invalid JSON response from @endpoint: @error', [
            '@endpoint' => $endpoint,
            '@error' => json_last_error_msg(),
          ]);
        }
      } else {
        \Drupal::logger('external_content_localist')->warning('HTTP @status from @endpoint', [
          '@status' => $response->getStatusCode(),
          '@endpoint' => $endpoint,
        ]);
      }
    }
    catch (RequestException $e) {
      \Drupal::logger('external_content_localist')->error('Request failed for @endpoint: @message', [
        '@endpoint' => $endpoint,
        '@message' => $e->getMessage(),
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('external_content_localist')->error('Unexpected error for @endpoint: @message', [
        '@endpoint' => $endpoint,
        '@message' => $e->getMessage(),
      ]);
    }

    return FALSE;
  }

  /**
   * Gets lookup query for autocomplete.
   */
  public function getLookupQuery($source, string $input): array {
    $plugin_config = $source->getPluginConfiguration();

    $query = [
      'search' => $input,
    ];

    // Add date range parameters
    $query = array_merge($query, $this->getDateRangeQuery($plugin_config));

    return $query;
  }

  /**
   * Gets date range query parameters from plugin configuration.
   */
  protected function getDateRangeQuery(array $plugin_config): array {
    $query = [];

    try {
      // Get start date
      $start_date_string = $plugin_config['start_date'] ?? '';
      $start_date = new DrupalDateTime($start_date_string);
      $query['start'] = $start_date->format('Y-m-d');
    }
    catch (\Exception $e) {
      \Drupal::logger('external_content_localist')->error('Invalid start_date configuration "@date": @message', [
        '@date' => $plugin_config['start_date'] ?? '',
        '@message' => $e->getMessage(),
      ]);
    }

    try {
      // Get end date
      $end_date_string = $plugin_config['end_date'] ?? '';
      $end_date = new DrupalDateTime($end_date_string);
      $query['end'] = $end_date->format('Y-m-d');
    }
    catch (\Exception $e) {
      \Drupal::logger('external_content_localist')->error('Invalid end_date configuration "@date": @message', [
        '@date' => $plugin_config['end_date'] ?? '',
        '@message' => $e->getMessage(),
      ]);
    }

    return $query;
  }

}
