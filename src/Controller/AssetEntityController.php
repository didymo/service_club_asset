<?php

namespace Drupal\service_club_asset\Controller;

use Drupal;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\service_club_asset\Entity\AssetEntityInterface;

/**
 * Class AssetEntityController.
 *
 *  Returns responses for Asset entity routes.
 */
class AssetEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Clones an asset.
   *
   * @param \Drupal\service_club_asset\Entity\AssetEntityInterface $asset_entity
   *   A Asset entity object.
   *
   * @return array
   *   Renderable array for the page.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cloneAsset(AssetEntityInterface $asset_entity) {
    $asset_clone = $asset_entity->createDuplicate();
    try {
      $asset_clone->save();
    }
    catch (EntityStorageException $e) {
      \Drupal::logger('service_club_asset')
        ->error('Failed to save the clone asset');
    }
    $display = [
      '#markup' => 'Edit cloned asset, ' . $asset_clone->getName() . ', <a href="/admin/structure/asset_entity/">here</a>.',
    ];
    return $display;
  }

  /**
   * Displays a Asset entity  revision.
   *
   * @param int $asset_entity_revision
   *   The Asset entity  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($asset_entity_revision) {
    $asset_entity = $this->entityManager()
      ->getStorage('asset_entity')
      ->loadRevision($asset_entity_revision);
    $view_builder = $this->entityManager()->getViewBuilder('asset_entity');

    return $view_builder->view($asset_entity);
  }

  /**
   * Page title callback for a Asset entity  revision.
   *
   * @param int $asset_entity_revision
   *   The Asset entity  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($asset_entity_revision) {
    $asset_entity = $this->entityManager()
      ->getStorage('asset_entity')
      ->loadRevision($asset_entity_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $asset_entity->label(),
      '%date' => format_date($asset_entity->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Asset entity .
   *
   * @param \Drupal\service_club_asset\Entity\AssetEntityInterface $asset_entity
   *   A Asset entity  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(AssetEntityInterface $asset_entity) {
    $account = $this->currentUser();
    $langcode = $asset_entity->language()->getId();
    $langname = $asset_entity->language()->getName();
    $languages = $asset_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $asset_entity_storage = $this->entityManager()->getStorage('asset_entity');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $asset_entity->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $asset_entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all asset entity revisions") || $account->hasPermission('administer asset entity entities')));
    $delete_permission = (($account->hasPermission("delete all asset entity revisions") || $account->hasPermission('administer asset entity entities')));

    $rows = [];

    $vids = $asset_entity_storage->revisionIds($asset_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\service_club_asset\AssetEntityInterface $revision */
      $revision = $asset_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)
          ->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = Drupal::service('date.formatter')
          ->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $asset_entity->getRevisionId()) {
          $link = $this->l($date, new Url('entity.asset_entity.revision', [
            'asset_entity' => $asset_entity->id(),
            'asset_entity_revision' => $vid,
          ]));
        }
        else {
          $link = $asset_entity->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => Drupal::service('renderer')
                ->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
                Url::fromRoute('entity.asset_entity.translation_revert', [
                  'asset_entity' => $asset_entity->id(),
                  'asset_entity_revision' => $vid,
                  'langcode' => $langcode,
                ]) :
                Url::fromRoute('entity.asset_entity.revision_revert', [
                  'asset_entity' => $asset_entity->id(),
                  'asset_entity_revision' => $vid,
                ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.asset_entity.revision_delete', [
                'asset_entity' => $asset_entity->id(),
                'asset_entity_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['asset_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
