<?php

namespace Drupal\WhoDaBest\Plugin\ContextReaction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\islandora\ContextReaction\NormalizerAlterReaction;

/**
 * Replace the author with our own.
 *
 * @ContextReaction(
 *   id = "whodabest_replace_author_reaction",
 *   label = @Translation("Replace author with something")
 * )
 */
class ReplaceAuthor extends NormalizerAlterReaction {

  const AUTHOR_REPLACEMENT = 'whodabest_replace_author_text';

  const AUTHOR_PREDS = [
    'http://schema.org/author',
    'http://purl.org/dc/author',
    'http://purl.org/dc/elements/1.1/author'
  ];

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Replace author with text in the JSON-LD.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $config = $this->getConfiguration();
    $form[self::AUTHOR_REPLACEMENT] = [
      '#type' => 'textfield',
      '#title' => $this->t('New Author'),
      '#description' => $this->t("Enter the text to replace as the author of all your objects."),
      '#default_value' => isset($config[self::AUTHOR_REPLACEMENT]) ? $config[self::AUTHOR_REPLACEMENT] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([self::AUTHOR_REPLACEMENT => $form_state->getValue(self::AUTHOR_REPLACEMENT)]);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EntityInterface $entity = NULL, array &$normalized = NULL, array $context = NULL) {
    $config = $this->getConfiguration();
    $author_text = $config[self::AUTHOR_REPLACEMENT];
    if (isset($normalized['@graph'])) {
      $graph = &$normalized['@graph'];
      array_walk($graph, [$this, '_walk_array'], $author_text);
    }
  }

  /**
   * Recurse the array looking for author keys.
   *
   * @param $item
   *   The array element.
   * @param $key
   *   The array key.
   * @param $author_text
   *   The new author text.
   */
  private function _walk_array(&$item, $key, $author_text) {
    if (in_array(strtolower($key), self::AUTHOR_PREDS)) {
      $item = [
        [ '@value' => $author_text ],
      ];
    }
    elseif (is_array($item)) {
      array_walk($item, [$this, '_walk_array'], $author_text);
    }
  }

}