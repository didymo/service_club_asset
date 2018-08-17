<?php

namespace Drupal\service_club_asset\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Asset entity entity.
 *
 * @ingroup service_club_asset
 *
 * @ContentEntityType(
 *   id = "asset_entity",
 *   label = @Translation("Asset entity"),
 *   handlers = {
 *     "storage" = "Drupal\service_club_asset\AssetEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\service_club_asset\AssetEntityListBuilder",
 *     "views_data" = "Drupal\service_club_asset\Entity\AssetEntityViewsData",
 *     "translation" =
 *   "Drupal\service_club_asset\AssetEntityTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\service_club_asset\Form\AssetEntityForm",
 *       "add" = "Drupal\service_club_asset\Form\AssetEntityForm",
 *       "edit" = "Drupal\service_club_asset\Form\AssetEntityForm",
 *       "delete" = "Drupal\service_club_asset\Form\AssetEntityDeleteForm",
 *     },
 *     "access" = "Drupal\service_club_asset\AssetEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\service_club_asset\AssetEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "asset_entity",
 *   data_table = "asset_entity_field_data",
 *   revision_table = "asset_entity_revision",
 *   revision_data_table = "asset_entity_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer asset entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/asset_entity/{asset_entity}",
 *     "add-form" = "/admin/structure/asset_entity/add",
 *     "edit-form" = "/admin/structure/asset_entity/{asset_entity}/edit",
 *     "delete-form" = "/admin/structure/asset_entity/{asset_entity}/delete",
 *     "version-history" =
 *   "/admin/structure/asset_entity/{asset_entity}/revisions",
 *     "revision" =
 *   "/admin/structure/asset_entity/{asset_entity}/revisions/{asset_entity_revision}/view",
 *     "revision_revert" =
 *   "/admin/structure/asset_entity/{asset_entity}/revisions/{asset_entity_revision}/revert",
 *     "revision_delete" =
 *   "/admin/structure/asset_entity/{asset_entity}/revisions/{asset_entity_revision}/delete",
 *     "translation_revert" =
 *   "/admin/structure/asset_entity/{asset_entity}/revisions/{asset_entity_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/asset_entity",
 *     "clone-asset" = "/admin/structure/asset_entity/{asset_entity}/clone",
 *     "related-assets" = "/admin/structure/asset_entity/{asset_entity}/related_assets",
 *   },
 *   field_ui_base_route = "asset_entity.settings"
 * )
 */
class AssetEntity extends RevisionableContentEntityBase implements AssetEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the asset_entity
    // owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    return $this->get('price')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrice($price) {
    $this->set('price', $price);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpiryDate() {
    return $this->get('field_expiry_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExpiryDate($expiry_date) {
    $this->set('expiry_date', $expiry_date);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildRelationships() {
    return $this->get('child_related_assets')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setChildRelationships($relationship) {
    $this->set('child_related_assets', $relationship);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    return $this->get('parent_related_assets')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setParentId($parent) {
    $this->set('parent_related_assets', $parent);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the author of the Asset.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 7,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Asset.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Asset entity is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 5,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Asset Image'))
      ->setDescription(t('Please provide an image of the asset.'))
      ->setSettings([
        'file_directory' => 'image_folder',
        'alt_field_reindentquired' => FALSE,
        'file_extensions' => 'png jpg jpeg',
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'default',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Asset Description'))
      ->setDescription(t('Relevant information describing the asset.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 250,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['price'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Asset Price/Value'))
      ->setDescription(t("Please provide the asset\'s price"))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    /*
     * @Todo the current version of drupal doesn't allow implementations of
     * datetime to function the way it's required in this module. Expiry
     * date has been implemented using the configuration exports but should be
     * implemented programmatically if and when possible.
     *
    $fields['expiry_date'] = BaseFieldDefinition::create('datetime')
    ->setLabel(t('Expiry Date of the Asset'))
    ->setDescription(t('The date corresponding to an assets expiry date.'))
    ->setRevisionable(TRUE)
    ->setSettings([
    'datetime_type' => 'datetime',
    'offset' => TRUE,
    ])
    ->setDisplayOptions('view', [
    'label' => 'above',
    'weight' => -1,
    ])
    ->setDisplayOptions('form', [
    'weight' => -1,
    ])
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
     */

    // This creates a field allowing the asset to reference an array of assets.
    $fields['child_related_assets'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Child Related Assets'))
      ->setDescription(t('Related assets creates links to other assets.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'asset_entity')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 7,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // This creates a field allowing the asset to reference it's parent asset.
    // There can only be one parent asset associated with another asset.
    $fields['parent_related_assets'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent Asset'))
      ->setDescription(t('Parent asset creates a link to an asset it is dependent upon.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'asset_entity')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 6,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
