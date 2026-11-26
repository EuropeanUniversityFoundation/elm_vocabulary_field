# ELM vocabulary field

Provides a field type for ELM controlled vocabularies.

## Installation

Include the repository in your project's `composer.json` file:

    "repositories": [
        ...
        {
            "type": "vcs",
            "url": "https://github.com/EuropeanUniversityFoundation/elm_vocabulary_field"
        }
    ],

Then you can require the package as usual:

    composer require euf/elm_vocabulary_field

Finally, install the module:

    drush en elm_vocabulary_field

## Usage

The **Controlled vocabulary** field type becomes available in the Field UI under the _Selection list_ category, so it can be added to any fieldable entity like any other field type.

### Field storage settings

Each field storage requires **selecting a controlled vocabulary**. Once the field has data, this cannot be changed.

### Field instance settings

Each field instance contains configuration to **allow selection** of only part of the controlled vocabulary. If left blank, all values from the vocabulary are allowed.

Narrowing the allowed selection never discards stored data. A value that is part of the vocabulary but no longer allowed:

  - remains in the select list of the entities that already hold it;
  - passes validation when those entities are saved again;
  - cannot be set on any other entity, or in place of the stored value.

A stored value that is not part of the vocabulary (for example, a term removed from the library) is not kept in the select list and fails validation, so it must be replaced when the entity is edited.

### Field widget settings

The default field widget is a selection list, sorted by label. Select options may be prefixed by their respective key.

### Field formatter settings

The default field formatter also allows items to be prefixed by their respective key. Stored values that are not found in the vocabulary are displayed as stored.

## Translations

Controlled vocabularies are published in all EU languages, and their translations are provided by the PHP library, not by Drupal's translation infrastructure. This module merges them with its own translations into one file per language, which Drupal imports like any other interface translation.

The merge requires [Drush](https://www.drush.org). After installing or updating the module or the library, run:

    drush elm_vocabulary_field:merge

or its alias:

    drush elm-merge

The command lists the files written to `public://translations` and the library languages it ignores. The ignored languages (`en`, `no` and `pt`) are generic copies of locales that the library already provides (`en_GB`, `nb` and `pt_PT`), so they are not merged separately.

Then import the compiled files:

    drush locale:update

The order matters: Drupal only imports a file that changed since the last import. Make sure the `locale` module is enabled and that the language exists on the site.

Language codes follow Drupal conventions (for example `pt-pt`, not `pt_PT`). The EU publications only use British English, Bokmål Norwegian and European Portuguese, so there are no translations for other variants such as `pt-br`.

## Data source

ELM controlled vocabularies are retrieved from this [PHP library](https://packagist.org/packages/euf/elm_vocabularies) which is required in `composer.json`.

## ROADMAP

  - Twig support
